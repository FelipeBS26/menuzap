<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * A peça central da performance da vitrine (Fase 4/6). Roda ANTES do
 * TenantIdentificationMiddleware de propósito — a chave do cache vem
 * direto do slug na URL, sem precisar consultar o banco. Em cache HIT,
 * a requisição inteira nunca toca o Postgres.
 *
 * TTL de 60s é rede de segurança, não o mecanismo principal — a
 * invalidação de verdade é por evento, via VitrineCacheInvalidationObserver.
 */
class VitrineCacheMiddleware
{
    protected int $ttl = 60;

    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->cacheKey($request);

        if ($cached = Cache::store('redis')->get($key)) {
            return response($cached)->header('X-Vitrine-Cache', 'HIT');
        }

        $response = $next($request);

        // Nunca cacheia erro — só resposta de sucesso (loja aberta,
        // slug existente, etc.). Um 404 cacheado por 60s seria um bug sério.
        if ($response->getStatusCode() === 200) {
            Cache::store('redis')->put($key, $response->getContent(), $this->ttl);
        }

        $response->headers->set('X-Vitrine-Cache', 'MISS');

        return $response;
    }

    protected function cacheKey(Request $request): string
    {
        $slug = $request->route('slug') ?? $request->path();

        return "vitrine:{$slug}";
    }
}