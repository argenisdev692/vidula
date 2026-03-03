<?php

declare(strict_types=1);

namespace Modules\Students\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255', 'unique:students,email,' . $this->route('uuid') . ',uuid'],
            'phone' => ['nullable', 'string', 'max:50'],
            'dni' => ['nullable', 'string', 'max:50', 'unique:students,dni,' . $this->route('uuid') . ',uuid'],
            'birth_date' => ['nullable', 'date'],
            'address' => ['nullable', 'string'],
            'avatar' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'status' => ['sometimes', 'string', 'in:DRAFT,ACTIVE,INACTIVE,GRADUATED,SUSPENDED'],
            'active' => ['boolean'],
        ];
    }
}
