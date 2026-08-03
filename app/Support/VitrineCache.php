<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Ponto único de invalidação — a mesma lógica de montar a chave que o
 * VitrineCacheMiddleware usa para ler, usada aqui para apagar. Se um dia
 * a estratégia de chave mudar (ex: incluir idioma, ou domínio próprio na
 * V3), só precisa mudar em dois lugares, não espalhado pelo projeto.
 */
class VitrineCache
{
    public static function forget(string $slug): void
    {
        Cache::store('redis')->forget("vitrine:{$slug}");
    }
}