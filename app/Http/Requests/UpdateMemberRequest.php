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
            'name' => 'required|string|max:100',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:30',
            'title' => 'nullable|string|max:100',
            'role' => 'required|in:admin,finance,secretary,member',
            'monthly_due' => 'required|numeric|min:0',
        ];
    }
}
