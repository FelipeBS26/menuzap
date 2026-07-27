<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('category_id')->constrained('categories')->restrictOnDelete();
            // restrictOnDelete: CategoryObserver deve fazer soft delete dos produtos ANTES
            // de permitir a exclusão real da categoria — nunca via cascade silenciosa do banco.

            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->integer('base_price_cents'); // ignorado se has_sizes = true
            $table->string('image_url', 500)->nullable();
            $table->string('thumbnail_url', 500)->nullable();
            $table->string('badge', 20)->nullable(); // new | promo | highlight

            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true); // false = exibido como indisponível na vitrine
            $table->boolean('has_sizes')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'category_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};