<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Identifica o tenant pelo usuário AUTENTICADO (painel do lojista).
 * Deliberadamente separado do TenantIdentificationMiddleware — misturar os
 * dois em um único middleware foi identificado na Fase 6 como o "atalho
 * clássico" que gera vazamento de dados cross-tenant meses após o lançamento.
 */
class TenantPanelScopeMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->tenant_id) {
            abort(403, 'Usuário sem loja associada.');
        }

        $tenant = $user->tenant;

        if (! $tenant || in_array($tenant->status, ['suspended', 'cancelled'])) {
            abort(403, 'Sua loja está suspensa. Entre em contato com o suporte.');
        }

        TenantContext::set($tenant->id);
        app()->instance('tenant', $tenant);

        return $next($request);
    }
}