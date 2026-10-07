<?php

namespace App\Http\Requests;

use App\Services\ActiveFarmContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCultivationCycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $farm = app(ActiveFarmContext::class)->current($this->user());

        return ['pond_id' => ['required', 'integer', Rule::exists('ponds', 'id')->where(fn ($q) => $q->where('farm_id', $farm?->id))], 'fish_species_id' => ['required', 'integer', Rule::exists('fish_species', 'id')->where(fn ($q) => $q->where('is_active', true))], 'batch_code' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9_-]+$/'], 'planned_start_date' => ['nullable', 'date'], 'target_average_weight_g' => ['nullable', 'numeric', 'gt:0'], 'expected_harvest_date' => ['nullable', 'date'], 'notes' => ['nullable', 'string', 'max:5000']];
    }
}
