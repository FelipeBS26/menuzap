<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Props compartilhados automaticamente em TODA página Inertia do painel.
     * 'tenant' e 'store' são o que permitem a topbar do AppLayout.vue (nome
     * da loja, toggle aberto/fechado, link "Ver minha loja") funcionar em
     * QUALQUER página — sem isso, telas como Categorias (que não passam
     * 'store' explicitamente) ficariam com a topbar quebrada.
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'tenant' => fn () => app()->bound('tenant')
                ? ['slug' => app('tenant')->slug]
                : null,
            'store' => fn () => app()->bound('tenant')
                ? app('tenant')->store
                : null,
        ];
    }
}