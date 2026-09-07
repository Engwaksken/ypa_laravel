<?php

namespace App\Http\Requests;

use App\Models\ContractItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreHarvestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contract_id' => ['required', 'integer', 'exists:contracts,id'],
            'contract_item_id' => ['nullable', 'integer', 'exists:contract_items,id'],
            'harvest_type' => ['required', Rule::in(['Cash', 'Bags', 'Goats', 'Monthly Payout', 'Profit'])],
            'harvest_date' => ['required', 'date'],
            'amount_harvested' => ['nullable', 'numeric', 'min:0'],
            'quantity_harvested' => ['nullable', 'numeric', 'min:0'],
            'number_of_goats_harvested' => ['nullable', 'integer', 'min:0'],
            'periods_due' => ['nullable', 'integer', 'min:1'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Early UX feedback for the amount cap. The authoritative check runs in
     * HarvestController::createHarvest() under a row lock on the item.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $data = $validator->getData();

            $itemId = isset($data['contract_item_id']) ? (int) $data['contract_item_id'] : 0;
            $amount = isset($data['amount_harvested']) ? (float) $data['amount_harvested'] : 0.0;

            if ($itemId > 0 && $amount > 0) {
                $item = ContractItem::query()->find($itemId);
                if ($item) {
                    $balance = (float) ($item->balance_amount ?? $item->projected_harvest_balance ?? 0);
                    if ($amount > $balance) {
                        $validator->errors()->add(
                            'amount_harvested',
                            'Harvest amount cannot exceed the item\'s remaining balance (' . number_format($balance, 2) . ').'
                        );
                    }
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $data = array_map(static function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $this->all());

        $contractItem = $this->route('contractItem');
        if ($contractItem) {
            $data['contract_id'] = $contractItem->contract_id;
            $data['contract_item_id'] = $contractItem->id;
        }

        $this->merge($data);
    }
}
