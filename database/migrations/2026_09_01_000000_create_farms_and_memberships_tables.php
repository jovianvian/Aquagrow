<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farms', function (Blueprint $t) {
            $t->id();
            $t->string('name', 120);
            $t->string('slug', 160)->unique();
            $t->string('timezone', 64)->default('Asia/Jakarta');
            $t->char('currency', 3)->default('IDR');
            $t->timestamps();
        });
        Schema::create('farm_memberships', function (Blueprint $t) {
            $t->id();
            $t->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('role', 32);
            $t->timestamps();
            $t->unique(['farm_id', 'user_id']);
            $t->index(['user_id', 'farm_id']);
            $t->index(['farm_id', 'role']);
        });
        DB::statement("ALTER TABLE farm_memberships ADD CONSTRAINT farm_memberships_role_check CHECK (role IN ('FARM_OWNER', 'POND_OPERATOR'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('farm_memberships');
        Schema::dropIfExists('farms');
    }
};
