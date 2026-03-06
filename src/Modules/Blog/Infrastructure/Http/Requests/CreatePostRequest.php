<?php

declare(strict_types=1);

namespace Modules\Blog\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'post_title' => ['required', 'string', 'max:255'],
            'post_title_slug' => ['nullable', 'string', 'max:255'],
            'post_content' => ['required', 'string'],
            'post_excerpt' => ['nullable', 'string', 'max:500'],
            'post_cover_image' => ['nullable', 'string', 'max:500'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords' => ['nullable', 'string', 'max:500'],
            'category_uuid' => ['nullable', 'string', 'uuid'],
            'post_status' => ['required', 'string', 'in:draft,published,scheduled,archived'],
            'published_at' => ['nullable', 'date'],
            'scheduled_at' => ['nullable', 'date'],
        ];
    }
}
