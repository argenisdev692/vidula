<?php

declare(strict_types=1);

namespace Modules\Students\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateStudentRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('birthDate') && ! $this->has('birth_date')) {
            $this->merge([
                'birth_date' => $this->input('birthDate'),
            ]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:students,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'dni' => ['nullable', 'string', 'max:50', 'unique:students,dni'],
            'birth_date' => ['nullable', 'date'],
            'address' => ['nullable', 'string'],
            'avatar' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'status' => ['sometimes', 'string', 'in:DRAFT,ACTIVE,INACTIVE,GRADUATED,SUSPENDED'],
            'active' => ['boolean'],
        ];
    }
}
