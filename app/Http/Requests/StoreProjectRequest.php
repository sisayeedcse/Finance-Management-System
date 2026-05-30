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
            // Accept capital in several possible fields the SPA may send
            'capital' => 'nullable|numeric|min:0',
            'capitalDeployed' => 'nullable|numeric|min:0',
            'amount' => 'nullable|numeric|min:0',
            // Accept either started_at or date from the SPA; both optional
            'started_at' => 'nullable|date',
            'date' => 'nullable|date',
        ];
    }
}
