<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_logs', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();

            // Sequencial por tenant — gerado via UPDATE tenants SET order_counter = order_counter + 1
            // WHERE id = ? RETURNING order_counter. Nunca calculado via MAX() (race condition).
            $table->integer('short_id');

            $table->string('customer_name', 150);
            $table->string('customer_phone', 20)->nullable();
            $table->string('order_type', 20); // delivery | pickup | dine_in
            $table->string('payment_method', 20); // pix | cash | debit | credit

            $table->integer('total_cents');
            $table->jsonb('items_snapshot'); // imutável — histórico nunca muda retroativamente
            $table->text('whatsapp_message'); // texto exato enviado — auditoria

            $table->ipAddress('ip_address')->nullable(); // rate limiting / detecção de abuso

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'short_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_logs');
    }
};