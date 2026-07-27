<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('name', 50);
            $table->string('slug', 30)->unique();
            $table->integer('price_cents');
            $table->integer('max_products')->nullable(); // NULL = ilimitado
            $table->integer('max_categories')->nullable(); // NULL = ilimitado
            $table->jsonb('features')->default('{}'); // {"custom_domain": true, "qr_code": true, ...}
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};