<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrderLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderLogController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:150'],
            'customer_phone' => ['nullable', 'string', 'max:20'],
            'order_type' => ['required', 'in:delivery,pickup,dine_in'],
            'payment_method' => ['required', 'in:pix,cash,debit,credit'],
            'total_cents' => ['required', 'integer', 'min:0'],
            'items_snapshot' => ['required', 'array'],
            'whatsapp_message' => ['required', 'string'],
        ]);

        $tenant = app('tenant');

        // UPDATE...RETURNING atômico (Fase 5) — evita a race condition de
        // dois pedidos simultâneos gerando o mesmo número sequencial.
        $shortId = $tenant->nextOrderNumber();

        OrderLog::create([
            ...$validated,
            'short_id' => $shortId,
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['short_id' => $shortId]);
    }
}