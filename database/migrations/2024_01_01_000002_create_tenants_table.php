<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('plan_id')->constrained('plans')->restrictOnDelete();
            $table->string('slug', 80)->unique(); // menuzap.com/{slug}
            $table->string('custom_domain', 255)->nullable(); // NULL no MVP, V3
            $table->string('status', 20)->default('trial'); // active | suspended | cancelled | trial
            $table->timestampTz('trial_ends_at')->nullable();
            $table->timestampTz('plan_expires_at')->nullable(); // cobrança manual: super admin atualiza
            $table->bigInteger('storage_used_bytes')->default(0);
            $table->integer('order_counter')->default(0); // base do short_id — incrementado via UPDATE...RETURNING atômico
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};