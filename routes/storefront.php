<?php

use App\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;

Route::get('/{slug}', [StorefrontController::class, 'index'])->name('storefront.index');