<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pivot crítica: aqui vive a flexibilidade central do motor de adicionais.
        // O mesmo option_group pode ser obrigatório num produto e opcional noutro —
        // por isso min/max ficam AQUI, não em option_groups.
        // Sem soft delete de propósito: desvincular = deletar a linha (ver ProductObserver
        // e OptionGroupObserver na Fase 5/Model layer).
        Schema::create('product_option_groups', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('option_group_id')->constrained('option_groups')->cascadeOnDelete();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete(); // denormalizado

            $table->smallInteger('min_selections')->default(0); // 0 = opcional · 1+ = obrigatório
            $table->smallInteger('max_selections')->default(1); // igual ao min = "escolha exatamente X"
            $table->integer('sort_order')->default(0);

            $table->timestamps();

            $table->unique(['product_id', 'option_group_id']); // não permite vincular o mesmo grupo 2x
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_option_groups');
    }
};