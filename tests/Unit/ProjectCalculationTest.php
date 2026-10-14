<?php

namespace Tests\Unit;

use App\Models\Contract;
use App\Models\Project;
use App\Services\ArithmeticExpression;
use App\Services\ProjectCalculationService;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProjectCalculationTest extends TestCase
{
    public static function arithmetic(): array
    {
        return [['2 + 3 * 4', 14], ['(2 + 3) * 4', 20], ['-2 * -3', 6], ['10 / 2 - 3', 2],
            ['round(10 / 3, 2)', 3.33], ['min(2, max(3, 4))', 2], ['.5 + +.5', 1], ['quantity * 5', 15]];
    }

    #[DataProvider('arithmetic')]
    public function test_safe_arithmetic(string $expression, float $expected): void
    {
        $this->assertSame($expected, (new ArithmeticExpression)->evaluate($expression, ['quantity' => 3]));
    }

    public static function unsafeArithmetic(): array
    {
        return array_map(fn ($value) => [$value], ['system("whoami")', 'phpinfo()', '1;2', '1 ** 2', 'unknown + 1',
            '1 / 0', '1e999', '999999999999 * 100', 'round(1, 8)', 'round(1, 0.5)', 'min()', '(1 + 2',
            str_repeat('(', 40) . '1' . str_repeat(')', 40), '1 < 2']);
    }

    #[DataProvider('unsafeArithmetic')]
    public function test_unsafe_or_invalid_arithmetic_is_rejected(string $expression): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new ArithmeticExpression)->evaluate($expression, []);
    }

    private function schema(): array
    {
        return ['inputs' => [
            ['key' => 'quantity', 'label' => 'Quantity', 'type' => 'number', 'required' => true, 'min' => 1, 'max' => 100],
            ['key' => 'unit_cost', 'label' => 'Unit Cost', 'type' => 'number', 'required' => false, 'default' => 500],
            ['key' => 'location', 'label' => 'Location', 'type' => 'text', 'required' => false],
        ], 'formulas' => [
            ['key' => 'purchase', 'label' => 'Purchase', 'expression' => 'quantity * unit_cost'],
            ['key' => 'payable', 'label' => 'Payable', 'expression' => 'purchase + registration_fee + administrative_fee', 'target' => 'total_payable'],
            ['key' => 'monthly', 'label' => 'Monthly', 'expression' => 'round(purchase / duration, 2)', 'target' => 'monthly_payout_amount'],
        ]];
    }

    private function project(): Project
    {
        $project = new Project(['calculation_schema' => $this->schema(), 'registration_fee' => 100, 'administrative_fee' => 50]);
        $project->id = 4;
        return $project;
    }

    public function test_new_project_calculates_dynamic_inputs_chained_formulas_and_targets(): void
    {
        $result = (new ProjectCalculationService)->calculate($this->project(), ['quantity' => 5, 'location' => 'Kampala'], 12);
        $this->assertSame(2650.0, $result['targets']['total_payable']);
        $this->assertSame(208.33, $result['targets']['monthly_payout_amount']);
        $this->assertSame('Kampala', $result['inputs']['location']);
        $this->assertSame($this->schema(), $result['schema']);
    }

    public function test_harvest_uses_configurable_weight_and_rate_without_rounding_to_two_hundred_thousand(): void
    {
        $project = new Project(['calculation_schema' => ['inputs' => [
            ['key' => 'bags', 'label' => 'Bags', 'type' => 'number', 'required' => true],
            ['key' => 'kg_per_bag', 'label' => 'Kg per bag', 'type' => 'number', 'required' => true],
            ['key' => 'cost_per_kg', 'label' => 'Rate per kg', 'type' => 'number', 'required' => true],
        ], 'formulas' => [
            ['key' => 'bag_rate', 'label' => 'Rate per bag', 'expression' => 'kg_per_bag * cost_per_kg'],
            ['key' => 'harvest', 'label' => 'Harvest total', 'expression' => 'bags * bag_rate', 'target' => 'projected_harvest_amount'],
        ]]]);
        $result = (new ProjectCalculationService)->calculate($project, ['bags' => 108, 'kg_per_bag' => 150, 'cost_per_kg' => 1333], 12);
        $this->assertSame(199950.0, $result['results']['bag_rate']);
        $this->assertSame(21594600.0, $result['targets']['projected_harvest_amount']);
    }

    public function test_schema_changes_and_fee_changes_do_not_change_existing_agreements(): void
    {
        $service = new ProjectCalculationService;
        $project = $this->project();
        $saved = $service->calculate($project, ['quantity' => 5], 12);
        $contract = new Contract(['project_id' => 4, 'project_calculation' => $saved]);
        $project->calculation_schema = ['inputs' => [], 'formulas' => [['key' => 'new_total', 'label' => 'New Total', 'expression' => '9999', 'target' => 'total_payable']]];
        $project->registration_fee = 900;
        $this->assertSame(2650.0, $service->calculate($project, ['quantity' => 5], 12, $contract)['targets']['total_payable']);
        $this->assertSame(9999.0, $service->calculate($project, [], 12)['targets']['total_payable']);
    }

    public function test_legacy_contracts_do_not_automatically_adopt_new_formulas(): void
    {
        $this->assertNull((new ProjectCalculationService)->calculate($this->project(), [], 12, new Contract(['project_id' => 4])));
    }

    public function test_switching_project_uses_the_new_projects_definition(): void
    {
        $project = $this->project();
        $contract = new Contract(['project_id' => 99, 'project_calculation' => ['schema' => []]]);
        $this->assertSame(650.0, (new ProjectCalculationService)->calculate($project, ['quantity' => 1], 12, $contract)['targets']['total_payable']);
    }

    public function test_missing_required_inputs_are_rejected(): void
    {
        $this->expectException(ValidationException::class);
        (new ProjectCalculationService)->calculate($this->project(), [], 12);
    }

    public function test_unknown_inputs_cannot_override_context_or_results(): void
    {
        $this->expectException(ValidationException::class);
        (new ProjectCalculationService)->calculate($this->project(), ['quantity' => 1, 'registration_fee' => 0], 12);
    }

    public function test_input_bounds_are_enforced(): void
    {
        $this->expectException(ValidationException::class);
        (new ProjectCalculationService)->calculate($this->project(), ['quantity' => 101], 12);
    }

    public function test_text_variables_are_not_accepted_in_arithmetic(): void
    {
        $schema = $this->schema(); $schema['formulas'][0]['expression'] = 'location * 5';
        $this->expectException(InvalidArgumentException::class);
        (new ProjectCalculationService)->validateSchema($schema);
    }

    public function test_duplicate_variable_keys_are_rejected(): void
    {
        $schema = $this->schema(); $schema['formulas'][0]['key'] = 'quantity';
        $this->expectException(InvalidArgumentException::class);
        (new ProjectCalculationService)->validateSchema($schema);
    }

    public function test_later_and_cyclic_dependencies_are_rejected(): void
    {
        $schema = $this->schema(); $schema['formulas'][0]['expression'] = 'payable * 2';
        $this->expectException(InvalidArgumentException::class);
        (new ProjectCalculationService)->validateSchema($schema);
    }

    public function test_runtime_division_by_zero_is_a_validation_error_not_server_failure(): void
    {
        $schema = $this->schema(); $schema['formulas'][0]['expression'] = '1 / (quantity - 5)';
        $project = $this->project(); $project->calculation_schema = $schema;
        $this->expectException(ValidationException::class);
        (new ProjectCalculationService)->calculate($project, ['quantity' => 5], 12);
    }

    public function test_negative_financial_results_are_rejected(): void
    {
        $schema = $this->schema(); $schema['formulas'][1]['expression'] = '-100';
        $project = $this->project(); $project->calculation_schema = $schema;
        $this->expectException(ValidationException::class);
        (new ProjectCalculationService)->calculate($project, ['quantity' => 5], 12);
    }

    public function test_financial_targets_cannot_override_paid_or_other_protected_columns(): void
    {
        $schema = $this->schema(); $schema['formulas'][0]['target'] = 'total_paid';
        $this->expectException(ValidationException::class);
        (new ProjectCalculationService)->validateSchema($schema);
    }
}
