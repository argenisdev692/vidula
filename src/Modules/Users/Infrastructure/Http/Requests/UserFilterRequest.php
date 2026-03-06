<?php

declare(strict_types=1);

namespace Modules\Users\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * UserFilterRequest — Validates filter/pagination query params.
 */
final class UserFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:active,suspended,banned,deleted,pending_setup',
            'date_from' => 'nullable|date|before_or_equal:date_to',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'sort_by' => 'nullable|string|in:name,email,created_at,updated_at',
            'sort_dir' => 'nullable|string|in:asc,desc',
        ];
    }
}
