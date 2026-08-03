<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\TenantIdentificationMiddleware;
use App\Http\Middleware\TenantPanelScopeMiddleware;
use App\Http\Middleware\VitrineCacheMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Painel do lojista — Inertia, identificação de tenant por sessão.
            // Registrado ANTES da vitrine de propósito: routes/storefront.php
            // tem uma rota curinga GET /{slug} que aceita qualquer caminho de
            // um segmento só. Se ela viesse primeiro, "engoliria" /dashboard
            // (e qualquer outra rota de um segmento) antes de chegar aqui.
            //
            // IMPORTANTE: 'web' precisa vir explícito aqui. Só o arquivo
            // passado no parâmetro web: deste withRouting() ganha o grupo
            // 'web' (sessão, cookies, CSRF) automaticamente — qualquer rota
            // registrada manualmente dentro deste bloco then() fica sem
            // sessão se não declararmos 'web' na lista de middlewares.
            // HandleInertiaRequests entra aqui, não na vitrine — a vitrine é
            // Blade puro (Fase 4), Inertia é exclusivo dos painéis.
            Route::middleware(['web', 'auth', 'tenant.panel', HandleInertiaRequests::class])
                ->group(base_path('routes/tenant.php'));

            // TODO Parte 4/Sprint 2: descomentar quando routes/admin.php e routes/api.php existirem.
            // Ambos entram ANTES da vitrine, pelo mesmo motivo acima.
            // Route::middleware(['web', 'auth', 'role:super_admin'])->prefix('admin')
            //     ->group(base_path('routes/admin.php'));
            // Route::middleware(['tenant.identify'])->prefix('api')
            //     ->group(base_path('routes/api.php'));

            // Vitrine pública — Blade, identificação de tenant por host/slug.
            // Fica por ÚLTIMO sempre: a rota curinga /{slug} deve ser a
            // última tentativa de match do router, nunca a primeira.
            //
            // 'vitrine.cache' vem ANTES de 'tenant.identify' de propósito
            // (Sprint 3 Parte 3): a chave do cache usa o slug direto da URL,
            // sem precisar identificar o tenant — em cache HIT, a requisição
            // nunca chega a tocar o banco de dados.
            Route::middleware(['web', 'vitrine.cache', 'tenant.identify'])
                ->group(base_path('routes/storefront.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'tenant.identify' => TenantIdentificationMiddleware::class,
            'tenant.panel' => TenantPanelScopeMiddleware::class,
            'vitrine.cache' => VitrineCacheMiddleware::class,
        ]);

        // Declarados explicitamente para evitar ambiguidade: o padrão do
        // Laravel 11 pressupõe uma rota chamada "dashboard", mas a nossa se
        // chama "tenant.dashboard" (de propósito, para não colidir com o
        // futuro painel do super admin). Sem isso, o middleware 'guest'
        // tentava resolver um destino inconsistente e causava o loop de
        // redirecionamento entre /login e /dashboard.
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('tenant.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();