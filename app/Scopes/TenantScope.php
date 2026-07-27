<?php

namespace App\Scopes;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Filtra automaticamente toda query por tenant_id quando há um tenant ativo
 * no contexto da requisição. Isso é o que torna Product::all() seguro —
 * ele NUNCA retorna produtos de outro tenant enquanto o contexto estiver setado.
 *
 * Se TenantContext estiver vazio (ex: rotas do super admin, ou um Job que
 * esqueceu de setar o contexto), o scope não filtra nada — por isso os
 * middlewares são obrigatórios em toda rota que expõe dados de tenant.
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if ($tenantId = TenantContext::id()) {
            $builder->where($model->getTable().'.tenant_id', $tenantId);
        }
    }
}
