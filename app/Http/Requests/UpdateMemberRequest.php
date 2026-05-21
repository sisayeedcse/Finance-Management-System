<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:100',
            'email' => 'sometimes|required|email',
            'phone' => 'sometimes|nullable|string|max:30',
            'title' => 'sometimes|nullable|string|max:100',
            'role' => 'sometimes|required|in:admin,finance,secretary,member',
            'monthly_due' => 'sometimes|required|numeric|min:0',
            'photo' => 'sometimes|nullable|string',
        ];
    }
}
