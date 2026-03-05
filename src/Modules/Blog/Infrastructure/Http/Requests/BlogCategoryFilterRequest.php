<?php

declare(strict_types=1);

namespace Modules\Blog\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * BlogCategoryFilterRequest — Validates filter/pagination query params.
 */
final class BlogCategoryFilterRequest extends FormRequest
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
            'sort_by' => 'nullable|string|in:blog_category_name,created_at,updated_at',
            'sort_dir' => 'nullable|string|in:asc,desc',
        ];
    }
}
