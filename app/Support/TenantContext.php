<?php

namespace App\Support;

/**
 * Guarda o tenant ativo durante o ciclo de vida da requisição.
 *
 * Setado pelos middlewares (TenantIdentificationMiddleware na vitrine,
 * TenantPanelScopeMiddleware no painel). Jobs em fila NÃO herdam esse estado
 * automaticamente — todo Job que opera em dados de tenant deve receber o
 * tenant_id explícito no construtor e chamar TenantContext::set() manualmente
 * na primeira linha do handle(). Essa é a convenção obrigatória definida na Fase 4/5.
 */
class TenantContext
{
    protected static ?string $tenantId = null;

    public static function set(string $tenantId): void
    {
        static::$tenantId = $tenantId;
    }

    public static function id(): ?string
    {
        return static::$tenantId;
    }

    public static function clear(): void
    {
        static::$tenantId = null;
    }

    public static function has(): bool
    {
        return static::$tenantId !== null;
    }
}