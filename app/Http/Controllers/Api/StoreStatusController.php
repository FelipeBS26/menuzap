<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Preflight chamado pelo Alpine no instante de clicar em "Enviar pedido" —
 * garante que a loja não fechou enquanto o cliente preenchia o checkout
 * (Fase 7). Nunca bloqueia a venda se falhar: o front trata timeout/erro
 * aqui como "segue mesmo assim", só usa a resposta quando ela chega a tempo.
 */
class StoreStatusController extends Controller
{
    public function show(): JsonResponse
    {
        $store = app('tenant')->store;

        return response()->json([
            'is_open' => $store->is_open,
            'message' => $store->is_open ? null : ($store->closed_message ?: 'Loja fechada no momento.'),
        ]);
    }
}