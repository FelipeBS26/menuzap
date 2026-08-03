<?php

namespace App\Observers;

use App\Support\VitrineCache;

/**
 * Um único Observer reutilizado por todos os Models que afetam o que
 * aparece na vitrine (Store, Product, Category, BusinessHour, OptionGroup,
 * OptionItem) — evita repetir a mesma lógica de invalidação 6 vezes.
 *
 * Invalidação é sempre por tenant inteiro, não por produto/categoria
 * específico — a vitrine é uma página única, e o custo de regenerar o
 * cache inteiro é irrelevante (TTL de 60s já limitava isso de qualquer
 * forma). Simplicidade aqui vale mais que uma otimização marginal.
 */
class VitrineCacheInvalidationObserver
{
    public function saved($model): void
    {
        $this->invalidate($model);
    }

    public function deleted($model): void
    {
        $this->invalidate($model);
    }

    protected function invalidate($model): void
    {
        // $model->tenant funciona em qualquer Model que use o trait
        // HasTenant (Fase 5) — todos os que registramos como Observer têm.
        if ($tenant = $model->tenant) {
            VitrineCache::forget($tenant->slug);
        }
    }
}