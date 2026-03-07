<?php

declare(strict_types=1);

namespace Modules\Products\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'string', 'uuid', 'exists:users,uuid'],
            'type' => ['required', 'string', 'in:classroom,video'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:products,slug'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'level' => ['required', 'string', 'in:beginner,intermediate,advanced'],
            'language' => ['required', 'string', 'size:2'],
            'thumbnail' => ['nullable', 'string', 'max:255'],
        ];
    }
}
