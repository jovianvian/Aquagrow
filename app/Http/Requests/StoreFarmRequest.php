<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFarmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:120'], 'timezone' => ['required', 'string', 'max:64', function ($a, $v, $fail) {
            try {
                new \DateTimeZone($v);
            } catch (\Exception) {
                $fail('The selected timezone is invalid.');
            }
        }], 'currency' => ['required', Rule::in(['IDR'])]];
    }
}
