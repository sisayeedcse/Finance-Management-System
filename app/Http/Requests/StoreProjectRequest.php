<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:200',
            'description' => 'nullable|string',
            'type' => 'nullable|string|max:100',
            'capital' => 'required|numeric|min:0',
            'started_at' => 'required|date',
        ];
    }
}
