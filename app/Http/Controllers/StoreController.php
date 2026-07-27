<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessImageJob;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StoreController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Store/Edit', [
            'store' => app('tenant')->store,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $tenant = app('tenant');
        $store = $tenant->store;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'whatsapp_number' => ['required', 'string', 'max:20'],
            'whatsapp_contact' => ['nullable', 'string', 'max:20'],
            'instagram_url' => ['nullable', 'string', 'max:255'],
            'delivery_fee_cents' => ['required', 'integer', 'min:0'],
            'min_order_cents' => ['required', 'integer', 'min:0'],
            'estimated_time_min' => ['nullable', 'integer', 'min:0'],
            'accepts_delivery' => ['boolean'],
            'accepts_pickup' => ['boolean'],
            'accepts_dine_in' => ['boolean'],
            'logo' => ['nullable', 'image', 'max:5120'],
            'banner' => ['nullable', 'image', 'max:5120'],
        ]);

        $store->update(collect($validated)->except(['logo', 'banner'])->toArray());

        // QUEUE_CONNECTION=sync no ambiente local (Fase 4) — o Job roda
        // imediatamente dentro do request. Em produção, com Redis, isso
        // vira assíncrono de verdade sem mudar nenhuma linha aqui.
        if ($request->hasFile('logo')) {
            $tempPath = $request->file('logo')->store('tmp');
            ProcessImageJob::dispatch($tenant->id, Store::class, $store->id, 'logo_url', $tempPath, 400, 400);
        }

        if ($request->hasFile('banner')) {
            $tempPath = $request->file('banner')->store('tmp');
            ProcessImageJob::dispatch($tenant->id, Store::class, $store->id, 'banner_url', $tempPath, 1200, 400);
        }

        return back();
    }

    /**
     * A ação mais frequente do lojista (Fase 7/9) — 1 clique, sem sair do
     * topbar. Invalidação de cache da vitrine chega junto do
     * VitrineCacheMiddleware no Sprint 3; por ora só atualiza o estado.
     */
    public function toggle(): RedirectResponse
    {
        $store = app('tenant')->store;
        $store->update(['is_open' => ! $store->is_open]);

        return back();
    }
}