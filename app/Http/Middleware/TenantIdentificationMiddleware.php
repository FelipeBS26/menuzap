<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Identifica o tenant pelo host (vitrine pública + API XHR do Alpine).
 * Usado em rotas SEM sessão — qualquer visitante anônimo passa por aqui.
 *
 * MVP: menuzap.com/{slug}. V3: custom_domain via CNAME.
 */
class TenantIdentificationMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $slug = $request->route('slug');

        $tenant = Tenant::query()
            ->when($slug, fn ($q) => $q->where('slug', $slug))
            ->when(! $slug, fn ($q) => $q->where('custom_domain', $host))
            ->first();

        if (! $tenant) {
            abort(404, 'Loja não encontrada.');
        }

        if ($tenant->status === 'suspended') {
            return response()->view('storefront.suspended', [], 403);
        }

        if ($tenant->status === 'cancelled') {
            abort(404, 'Loja não encontrada.');
        }

        TenantContext::set($tenant->id);
        app()->instance('tenant', $tenant);

        return $next($request);
    }
}