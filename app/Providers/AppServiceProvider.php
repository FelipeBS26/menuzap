<?php

namespace App\Providers;

use App\Models\BusinessHour;
use App\Models\Category;
use App\Models\OptionGroup;
use App\Models\OptionItem;
use App\Models\Product;
use App\Models\Store;
use App\Observers\VitrineCacheInvalidationObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Qualquer Model que afeta o que aparece na vitrine invalida o
        // cache Redis do tenant correspondente ao ser salvo/excluído
        // (Fase 4/6/Sprint 3 Parte 3).
        Store::observe(VitrineCacheInvalidationObserver::class);
        Product::observe(VitrineCacheInvalidationObserver::class);
        Category::observe(VitrineCacheInvalidationObserver::class);
        BusinessHour::observe(VitrineCacheInvalidationObserver::class);
        OptionGroup::observe(VitrineCacheInvalidationObserver::class);
        OptionItem::observe(VitrineCacheInvalidationObserver::class);
    }
}