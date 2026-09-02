<?php

namespace App\Http\Requests;

use App\Models\Role;
use App\Support\Permissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = $this->route('role');

        return $role
            ? $this->user()->can('update', $role)
            : $this->user()->can('create', Role::class);
    }

    public function rules(): array
    {
        $role = $this->route('role');

        return [
            'name' => [$role?->isSystem() ? 'nullable' : 'required', 'string', 'max:100'],
            'requires_regions' => ['sometimes', 'boolean'],
            'tasks' => ['nullable', 'array'],
            'tasks.*' => ['string', Rule::in(array_keys(Permissions::TASKS))],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'requires_regions' => $this->boolean('requires_regions'),
            'tasks' => array_values(array_filter((array) $this->input('tasks', []))),
        ]);
    }
}
