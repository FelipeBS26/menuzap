<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\OrderLog;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $tenant = app('tenant');

        return Inertia::render('Dashboard', [
            'store' => $tenant->store,
            'planName' => $tenant->plan->name,
            'metrics' => [
                'ordersToday' => OrderLog::whereDate('created_at', today())->count(),
                'activeProducts' => Product::where('is_active', true)->count(),
                'categories' => Category::count(),
            ],
        ]);
    }
}