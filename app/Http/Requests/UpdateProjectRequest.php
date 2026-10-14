<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateProjectRequest extends StoreProjectRequest
{
    public function rules(): array
    {
        return array_replace(parent::rules(), [
            'project_code' => [
                'required', 'string', 'max:100',
                Rule::unique('projects', 'project_code')->ignore($this->route('project')),
            ],
        ]);
    }
}
