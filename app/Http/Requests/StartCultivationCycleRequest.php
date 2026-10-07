<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartCultivationCycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return ['stocked_at' => ['required', 'date'], 'initial_fish_count' => ['required', 'integer', 'gt:0'], 'initial_average_weight_g' => ['required', 'numeric', 'gt:0']];
    }
}
