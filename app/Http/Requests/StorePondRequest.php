<?php

namespace App\Http\Requests;

use App\Enums\PondOperationalStatus;
use App\Enums\PondType;
use App\Services\ActiveFarmContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePondRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
        }
    }

    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $farm = app(ActiveFarmContext::class)->current($this->user());

        return ['location_id' => ['required', 'integer', Rule::exists('locations', 'id')->where(fn ($query) => $query->where('farm_id', $farm !== null ? $farm->id : 0))], 'name' => ['required', 'string', 'max:120'], 'code' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9_-]+$/'], 'type' => ['required', Rule::enum(PondType::class)], 'operational_status' => ['required', Rule::enum(PondOperationalStatus::class)], 'length_m' => ['nullable', 'numeric', 'gt:0', 'max:999999.99'], 'width_m' => ['nullable', 'numeric', 'gt:0', 'max:999999.99'], 'depth_m' => ['nullable', 'numeric', 'gt:0', 'max:999999.99'], 'water_volume_l' => ['nullable', 'numeric', 'gt:0', 'max:9999999999.99'], 'capacity_fish' => ['nullable', 'integer', 'gt:0'], 'notes' => ['nullable', 'string', 'max:5000']];
    }
}
