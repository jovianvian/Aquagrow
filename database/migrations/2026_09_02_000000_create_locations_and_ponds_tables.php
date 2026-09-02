<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('slug', 160);
            $table->string('address', 255)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['id', 'farm_id']);
            $table->unique(['farm_id', 'slug']);
            $table->index('farm_id');
        });

        Schema::create('ponds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id');
            $table->string('name', 120);
            $table->string('code', 50);
            $table->string('type', 20);
            $table->string('operational_status', 20);
            $table->decimal('length_m', 8, 2)->nullable();
            $table->decimal('width_m', 8, 2)->nullable();
            $table->decimal('depth_m', 8, 2)->nullable();
            $table->decimal('water_volume_l', 12, 2)->nullable();
            $table->unsignedInteger('capacity_fish')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['farm_id', 'code']);
            $table->index(['farm_id', 'location_id']);
        });

        DB::statement('ALTER TABLE ponds ADD CONSTRAINT ponds_location_farm_fk FOREIGN KEY (location_id, farm_id) REFERENCES locations (id, farm_id) ON DELETE RESTRICT');
        DB::statement("ALTER TABLE ponds ADD CONSTRAINT ponds_type_check CHECK (type IN ('EARTHEN', 'CONCRETE', 'TARPAULIN', 'FIBERGLASS', 'CAGE', 'OTHER'))");
        DB::statement("ALTER TABLE ponds ADD CONSTRAINT ponds_operational_status_check CHECK (operational_status IN ('AVAILABLE', 'MAINTENANCE', 'INACTIVE'))");
        DB::statement('ALTER TABLE ponds ADD CONSTRAINT ponds_positive_measurements_check CHECK ((length_m IS NULL OR length_m > 0) AND (width_m IS NULL OR width_m > 0) AND (depth_m IS NULL OR depth_m > 0) AND (water_volume_l IS NULL OR water_volume_l > 0) AND (capacity_fish IS NULL OR capacity_fish > 0))');
    }

    public function down(): void
    {
        Schema::dropIfExists('ponds');
        Schema::dropIfExists('locations');
    }
};
