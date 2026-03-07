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
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'level' => ['required', 'string', 'in:beginner,intermediate,advanced'],
            'language' => ['required', 'string', 'size:2'],
            'thumbnail' => ['nullable', 'string', 'max:255'],
        ];
    }
}
