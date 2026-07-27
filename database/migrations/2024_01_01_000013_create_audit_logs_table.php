<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete(); // NULL = ação do sistema
            $table->foreignUuid('tenant_id')->nullable()->constrained('tenants')->nullOnDelete(); // NULL = ação global de super admin

            $table->string('action', 80); // ex: tenant.suspended · plan.changed · impersonation.started
            $table->string('target_type', 50)->nullable(); // ex: Tenant · User · Product
            $table->uuid('target_id')->nullable();
            $table->jsonb('payload')->nullable(); // before/after dos campos alterados
            $table->ipAddress('ip_address')->nullable();

            $table->timestamps(); // log é imutável — sem soft delete, sem updated_at útil na prática

            $table->index(['tenant_id', 'created_at']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};