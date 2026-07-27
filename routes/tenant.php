<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StoreController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [DashboardController::class, 'index'])->name('tenant.dashboard');

Route::get('/store', [StoreController::class, 'edit'])->name('tenant.store.edit');
Route::put('/store', [StoreController::class, 'update'])->name('tenant.store.update');
Route::put('/store/toggle', [StoreController::class, 'toggle'])->name('tenant.store.toggle');

Route::get('/categories', [CategoryController::class, 'index'])->name('tenant.categories.index');
Route::post('/categories', [CategoryController::class, 'store'])->name('tenant.categories.store');
Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('tenant.categories.update');
Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('tenant.categories.destroy');
Route::put('/categories/{category}/move', [CategoryController::class, 'move'])->name('tenant.categories.move');