<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class StorefrontController extends Controller
{
    public function index(): View
    {
        $tenant = app('tenant');
        $store = $tenant->store;

        $categories = $tenant->categories()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->with(['products' => function ($query) {
                $query->where('is_active', true)->orderBy('sort_order')->with('sizes');
            }])
            ->get()
            // Categoria sem nenhum produto ativo não aparece — evita seção
            // vazia confusa na vitrine (ex: categoria criada mas ainda sem
            // produtos cadastrados).
            ->filter(fn ($category) => $category->products->isNotEmpty())
            ->values();

        return view('storefront.index', [
            'tenant' => $tenant,
            'store' => $store,
            'categories' => $categories,
        ]);
    }
}