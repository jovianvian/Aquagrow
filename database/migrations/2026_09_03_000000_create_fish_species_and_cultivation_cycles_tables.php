<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fish_species', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('common_name', 120);
            $table->string('scientific_name', 180)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });
        DB::statement('CREATE UNIQUE INDEX ponds_id_farm_unique ON ponds (id, farm_id)');
        Schema::create('cultivation_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('pond_id');
            $table->foreignId('fish_species_id')->constrained('fish_species')->restrictOnDelete();
            $table->string('batch_code', 50);
            $table->string('status', 16);
            $table->date('planned_start_date')->nullable();
            $table->timestampTz('stocked_at')->nullable();
            $table->unsignedInteger('initial_fish_count')->nullable();
            $table->decimal('initial_average_weight_g', 10, 3)->nullable();
            $table->decimal('target_average_weight_g', 10, 3)->nullable();
            $table->date('expected_harvest_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestampsTz();
            $table->unique(['farm_id', 'batch_code']);
            $table->index(['farm_id', 'pond_id']);
        });
        DB::statement('ALTER TABLE cultivation_cycles ADD CONSTRAINT cultivation_cycles_pond_farm_fk FOREIGN KEY (pond_id, farm_id) REFERENCES ponds (id, farm_id) ON DELETE CASCADE');
        DB::statement("ALTER TABLE cultivation_cycles ADD CONSTRAINT cultivation_cycles_status_check CHECK (status IN ('PLANNED','ACTIVE','COMPLETED','CANCELLED'))");
        DB::statement('ALTER TABLE cultivation_cycles ADD CONSTRAINT cultivation_cycles_positive_check CHECK ((initial_fish_count IS NULL OR initial_fish_count > 0) AND (initial_average_weight_g IS NULL OR initial_average_weight_g > 0) AND (target_average_weight_g IS NULL OR target_average_weight_g > 0))');
        DB::statement("ALTER TABLE cultivation_cycles ADD CONSTRAINT cultivation_cycles_planned_stock_check CHECK (status <> 'PLANNED' OR (stocked_at IS NULL AND initial_fish_count IS NULL AND initial_average_weight_g IS NULL))");
        DB::statement("CREATE UNIQUE INDEX cultivation_cycles_one_open_per_pond ON cultivation_cycles (pond_id) WHERE status IN ('PLANNED','ACTIVE')");
    }

    public function down(): void
    {
        Schema::dropIfExists('cultivation_cycles');
        DB::statement('DROP INDEX IF EXISTS ponds_id_farm_unique');
        Schema::dropIfExists('fish_species');
    }
};
