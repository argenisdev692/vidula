<?php

declare(strict_types=1);

namespace Modules\Blog\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * UpdateBlogCategoryRequest — Validates incoming blog category update data.
 */
final class UpdateBlogCategoryRequest extends FormRequest
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
            'blog_category_name' => 'sometimes|string|max:255',
            'blog_category_description' => 'nullable|string|max:1000',
            'blog_category_image' => 'nullable|string|max:500',
        ];
    }
}
