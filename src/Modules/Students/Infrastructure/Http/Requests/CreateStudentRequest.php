<?php

declare(strict_types=1);

namespace Modules\Students\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:students,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'dni' => ['nullable', 'string', 'max:50', 'unique:students,dni'],
            'birth_date' => ['nullable', 'date'],
            'address' => ['nullable', 'string'],
            'avatar' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'active' => ['boolean'],
        ];
    }
}
