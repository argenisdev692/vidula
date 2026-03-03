<?php

declare(strict_types=1);

namespace Modules\Products\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'string', 'in:classroom,video'],
            'title' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'level' => ['sometimes', 'string', 'in:beginner,intermediate,advanced'],
            'language' => ['sometimes', 'string', 'size:2'],
            'thumbnail' => ['nullable', 'string', 'max:255'],
        ];
    }
}
