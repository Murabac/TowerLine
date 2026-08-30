<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'role' => ['required', Rule::in(['admin', 'operations_manager', 'inspector'])],
            'region_ids' => ['nullable', 'array', 'required_if:role,inspector'],
            'region_ids.*' => ['integer', 'exists:regions,id'],
        ];
    }

    protected function passedValidation(): void
    {
        if ($this->input('role') !== 'inspector') {
            $this->merge(['region_ids' => []]);
        }
    }
}
