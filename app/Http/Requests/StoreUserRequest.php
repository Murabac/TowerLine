<?php

namespace App\Http\Requests;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user
            ? $this->user()->can('update', $user)
            : $this->user()->can('create', User::class);
    }

    public function rules(): array
    {
        $managed = $this->route('user');
        $creating = ! $managed;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($managed?->id)],
            'password' => [$creating ? 'required' : 'nullable', 'string', 'min:8'],
            'role' => ['required', 'string', Rule::exists('roles', 'key')->where(fn ($query) => $query->where('key', '!=', Role::KEY_OPERATOR_VIEWER))],
            'region_ids' => ['nullable', 'array'],
            'region_ids.*' => ['integer', 'exists:regions,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $role = Role::query()->where('key', $this->input('role'))->first();

            if ($role?->requires_regions && empty($this->input('region_ids'))) {
                $validator->errors()->add('region_ids', __('app.users.regions_required'));
            }
        });
    }

    protected function passedValidation(): void
    {
        $role = Role::query()->where('key', $this->input('role'))->first();

        if (! $role?->requires_regions) {
            $this->merge(['region_ids' => []]);
        }
    }
}
