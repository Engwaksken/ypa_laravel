<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Project;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class ProjectCalculationService
{
    public const TARGETS = ['total_payable', 'projected_harvest_amount', 'monthly_payout_amount', 'expected_quantity'];
    public const CONTEXT = ['registration_fee', 'administrative_fee', 'duration'];

    public function validateSchema(array $schema): void
    {
        Validator::make($schema, [
            'inputs' => ['present', 'array', 'list', 'max:30'],
            'inputs.*' => ['array:key,label,type,required,default,min,max'],
            'inputs.*.key' => ['required', 'string', 'regex:/^[a-z][a-z0-9_]{0,39}$/'],
            'inputs.*.label' => ['required', 'string', 'max:100'],
            'inputs.*.type' => ['required', Rule::in(['number', 'text'])],
            'inputs.*.required' => ['required', 'boolean'],
            'inputs.*.default' => ['nullable'],
            'inputs.*.min' => ['nullable', 'numeric', 'between:-1000000000000,1000000000000'],
            'inputs.*.max' => ['nullable', 'numeric', 'between:-1000000000000,1000000000000'],
            'formulas' => ['present', 'array', 'list', 'max:30'],
            'formulas.*' => ['array:key,label,expression,target'],
            'formulas.*.key' => ['required', 'string', 'regex:/^[a-z][a-z0-9_]{0,39}$/'],
            'formulas.*.label' => ['required', 'string', 'max:100'],
            'formulas.*.expression' => ['required', 'string', 'max:500'],
            'formulas.*.target' => ['nullable', Rule::in(self::TARGETS)],
        ])->validate();
        $known = array_fill_keys(array_merge(self::CONTEXT, ['min', 'max', 'round']), true);
        $numeric = array_fill_keys(self::CONTEXT, 1);
        foreach ($schema['inputs'] as $field) {
            $this->registerKey($field['key'], $known);
            if (isset($field['min'], $field['max']) && $field['min'] > $field['max']) throw new InvalidArgumentException('Minimum exceeds maximum for ' . $field['key'] . '.');
            $default = $field['default'] ?? null;
            if ($default !== null && $default !== '') {
                $this->validateInput($field, $default, 'calculation_schema');
            }
            if ($field['type'] === 'number') $numeric[$field['key']] = 1;
        }
        $targets = [];
        foreach ($schema['formulas'] as $formula) {
            $this->registerKey($formula['key'], $known);
            (new ArithmeticExpression)->evaluate($formula['expression'], $numeric, true);
            $numeric[$formula['key']] = 1;
            $target = $formula['target'] ?? '';
            if ($target !== '') {
                if (isset($targets[$target])) throw new InvalidArgumentException('Only one formula may set ' . $target . '.');
                $targets[$target] = true;
            }
        }
    }

    private function registerKey(string $key, array &$known): void
    {
        if (isset($known[$key])) throw new InvalidArgumentException('Duplicate or reserved key: ' . $key);
        $known[$key] = true;
    }

    private function validateInput(array $field, mixed $value, string $prefix): mixed
    {
        $rules = $field['type'] === 'number'
            ? ['numeric', 'between:-1000000000000,1000000000000'] : ['string', 'max:500'];
        if ($field['type'] === 'number') {
            if (isset($field['min'])) $rules[] = 'min:' . $field['min'];
            if (isset($field['max'])) $rules[] = 'max:' . $field['max'];
        }
        Validator::make([$prefix => [$field['key'] => $value]], [$prefix . '.' . $field['key'] => $rules], [],
            [$prefix . '.' . $field['key'] => $field['label']])->validate();
        return $field['type'] === 'number' ? (float) $value : $value;
    }

    public function calculate(Project $project, array $inputs, int $duration, ?Contract $contract = null): ?array
    {
        // Retain both the formula definition and fee context for existing contracts.
        $sameProject = $contract && (int) $contract->project_id === (int) $project->id;
        $saved = $sameProject ? $contract->project_calculation : null;
        // A pre-configuration agreement must not silently adopt newly added formulas.
        if ($sameProject && $saved === null) return null;
        $schema = $saved['schema'] ?? $project->calculation_schema;
        if (! $schema || (empty($schema['inputs']) && empty($schema['formulas']))) return null;
        $this->validateSchema($schema);
        $context = $saved['context'] ?? [
            'registration_fee' => (float) $project->registration_fee,
            'administrative_fee' => (float) $project->administrative_fee,
        ];
        $context['duration'] = $duration;
        $variables = $context;
        $values = [];
        $allowed = array_column($schema['inputs'], 'key');
        if (array_diff(array_keys($inputs), $allowed)) throw ValidationException::withMessages(['project_inputs' => 'Unknown project input fields.']);
        foreach ($schema['inputs'] as $field) {
            $value = $inputs[$field['key']] ?? $field['default'] ?? null;
            if ($value === null || $value === '') {
                if ($field['required']) throw ValidationException::withMessages(['project_inputs.' . $field['key'] => $field['label'] . ' is required.']);
                $value = $field['type'] === 'number' ? 0 : '';
            }
            $values[$field['key']] = $this->validateInput($field, $value, 'project_inputs');
            if ($field['type'] === 'number') $variables[$field['key']] = $values[$field['key']];
        }
        $results = $targets = [];
        foreach ($schema['formulas'] as $formula) {
            try {
                $value = (new ArithmeticExpression)->evaluate($formula['expression'], $variables);
                $variables[$formula['key']] = $value;
                $results[$formula['key']] = round($value, 6);
                if (! empty($formula['target'])) {
                    if ($value < 0) throw new InvalidArgumentException('Financial and quantity results cannot be negative.');
                    $targets[$formula['target']] = round($value, 2);
                }
            } catch (InvalidArgumentException $e) {
                throw ValidationException::withMessages(['project_inputs' => $formula['label'] . ': ' . $e->getMessage()]);
            }
        }
        return ['version' => 1, 'schema' => $schema, 'context' => $context, 'inputs' => $values, 'results' => $results, 'targets' => $targets];
    }
}
