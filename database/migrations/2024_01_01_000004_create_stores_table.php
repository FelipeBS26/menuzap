<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('tenant_id')->unique()->constrained('tenants')->cascadeOnDelete(); // 1:1 com tenant

            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('logo_url', 500)->nullable();
            $table->string('banner_url', 500)->nullable();

            // Injetadas como CSS var em RGB espaçado (ex: "21 128 61") para suportar bg-primary/50 no Tailwind
            $table->string('primary_color', 20)->default(env('DEFAULT_PRIMARY_COLOR', '21 128 61'));
            $table->string('secondary_color', 20)->nullable();

            $table->string('whatsapp_number', 20); // número que recebe os pedidos
            $table->string('whatsapp_contact', 20)->nullable(); // botão de contato do hero, pode ser diferente
            $table->string('instagram_url', 255)->nullable();

            $table->integer('delivery_fee_cents')->default(0);
            $table->integer('min_order_cents')->default(0);
            $table->integer('estimated_time_min')->nullable();

            $table->boolean('accepts_delivery')->default(true);
            $table->boolean('accepts_pickup')->default(true);
            $table->boolean('accepts_dine_in')->default(false);
            $table->jsonb('payment_methods')->default('["pix","cash"]'); // controla o checkout

            $table->boolean('is_open')->default(true); // toggle manual — Observer invalida cache ao mudar
            $table->string('closed_message', 255)->nullable();
            $table->boolean('whatsapp_message_emoji')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};