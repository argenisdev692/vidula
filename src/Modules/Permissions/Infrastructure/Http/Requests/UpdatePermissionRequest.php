<?php

declare(strict_types=1);

namespace Modules\Permissions\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdatePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'guard_name' => $this->input('guard_name', 'web'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('permissions', 'name')
                    ->where(fn ($query) => $query->where('guard_name', $this->input('guard_name', 'web')))
                    ->ignore($this->route('uuid'), 'uuid'),
            ],
            'guard_name' => ['sometimes', 'string', 'max:255'],
            'roles' => ['nullable', 'array'],
            'roles.*' => [
                'required',
                'string',
                Rule::exists('roles', 'name')->where(fn ($query) => $query->where('guard_name', $this->input('guard_name', 'web'))),
            ],
        ];
    }
}
