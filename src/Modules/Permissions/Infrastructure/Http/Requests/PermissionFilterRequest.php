<?php

declare(strict_types=1);

namespace Modules\Permissions\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class PermissionFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'guard_name' => ['nullable', 'string', 'max:255'],
            'sort_by' => ['nullable', 'string', 'in:name,created_at,updated_at'],
            'sort_dir' => ['nullable', 'string', 'in:asc,desc'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
