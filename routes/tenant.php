<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OptionGroupController;
use App\Http\Controllers\ProductController;
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

Route::get('/products', [ProductController::class, 'index'])->name('tenant.products.index');
Route::get('/products/create', [ProductController::class, 'create'])->name('tenant.products.create');
Route::post('/products', [ProductController::class, 'store'])->name('tenant.products.store');
Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('tenant.products.edit');
Route::put('/products/{product}', [ProductController::class, 'update'])->name('tenant.products.update');
Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('tenant.products.destroy');
Route::post('/products/{product}/duplicate', [ProductController::class, 'duplicate'])->name('tenant.products.duplicate');

Route::get('/option-groups', [OptionGroupController::class, 'index'])->name('tenant.option-groups.index');
Route::post('/option-groups', [OptionGroupController::class, 'store'])->name('tenant.option-groups.store');
Route::put('/option-groups/{group}', [OptionGroupController::class, 'update'])->name('tenant.option-groups.update');
Route::delete('/option-groups/{group}', [OptionGroupController::class, 'destroy'])->name('tenant.option-groups.destroy');