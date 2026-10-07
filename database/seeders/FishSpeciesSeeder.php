<?php

namespace Database\Seeders;

use App\Models\FishSpecies;
use Illuminate\Database\Seeder;

class FishSpeciesSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        FishSpecies::upsert([
            ['code' => 'NILA', 'common_name' => 'Ikan nila', 'scientific_name' => null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'LELE', 'common_name' => 'Ikan lele', 'scientific_name' => null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'GURAME', 'common_name' => 'Ikan gurame', 'scientific_name' => null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ], ['code'], ['common_name', 'scientific_name', 'is_active', 'updated_at']);
    }
}
