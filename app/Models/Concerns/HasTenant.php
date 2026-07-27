<?php

namespace App\Models\Concerns;

use App\Models\Tenant;
use App\Scopes\TenantScope;
use App\Support\TenantContext;

/**
 * Aplica o TenantScope globalmente e preenche tenant_id automaticamente
 * ao criar um registro, se não informado explicitamente.
 *
 * IMPORTANTE: o Model User NÃO usa este trait — login precisa localizar
 * o usuário pelo e-mail atravessando todos os tenants, antes do contexto
 * de tenant sequer existir. Aplicar o scope ali quebraria a autenticação.
 */
trait HasTenant
{
    protected static function bootHasTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (empty($model->tenant_id) && TenantContext::has()) {
                $model->tenant_id = TenantContext::id();
            }
        });
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}