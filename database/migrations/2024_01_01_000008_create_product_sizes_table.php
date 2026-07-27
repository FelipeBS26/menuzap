<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_sizes', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            // tenant_id denormalizado — permite reativar o Global Scope em Jobs sem precisar
            // fazer join até products a cada verificação.
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $table->string('name', 50); // ex: "Pequena", "Grande"
            $table->integer('price_cents');
            $table->integer('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_sizes');
    }
};