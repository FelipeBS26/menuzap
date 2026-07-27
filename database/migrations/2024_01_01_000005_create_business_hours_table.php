<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_hours', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $table->smallInteger('day_of_week'); // 0=Dom ... 6=Sáb
            $table->time('opens_at');
            $table->time('closes_at');

            // Resolve o "paradoxo do turno da madrugada" (ex: 19h-02h) — setado automaticamente
            // pelo BusinessHourObserver quando closes_at < opens_at.
            $table->boolean('is_overnight')->default(false);
            $table->boolean('is_24h')->default(false);

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_hours');
    }
};