<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * Placeholder — a vitrine de verdade (Blade + Alpine, hero, cardápio, carrinho)
 * é construída no Sprint 3. Isto só prova que o TenantIdentificationMiddleware
 * está resolvendo a loja corretamente pelo slug.
 */
class StorefrontController extends Controller
{
    public function index(): View
    {
        $tenant = app('tenant');

        return view('storefront.placeholder', [
            'tenant' => $tenant,
            'store' => $tenant->store,
        ]);
    }
}