<?php

namespace App\Http\Requests;

class UpdateContractRequest extends StoreContractRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            'items.*.id' => ['nullable', 'integer', 'min:1'],
            // Required for signed contracts; enforced in ContractController::update.
            'amendment_reason' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return parent::attributes() + ['amendment_reason' => 'reason for change'];
    }
}
