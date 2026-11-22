<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'pelajar';
    }

    public function rules(): array
    {
        return ['notes' => 'sometimes|nullable|string|max:2000'];
    }
}
