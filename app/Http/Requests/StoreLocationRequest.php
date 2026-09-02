<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:120'], 'address' => ['nullable', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:5000']];
    }
}
