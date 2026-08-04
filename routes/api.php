<?php

use App\Http\Controllers\Api\OrderLogController;
use App\Http\Controllers\Api\StoreStatusController;
use Illuminate\Support\Facades\Route;

// Registrado sob o prefixo api/{slug} no bootstrap/app.php — o {slug} é o
// que faz o TenantIdentificationMiddleware funcionar aqui.

Route::get('/store/status', [StoreStatusController::class, 'show'])->name('api.store.status');

// Rate limit de 5/min por IP (Fase 6) — protege contra abuso sem
// atrapalhar um cliente real fazendo um pedido.
Route::post('/orders/log', [OrderLogController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('api.orders.log');