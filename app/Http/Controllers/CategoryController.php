<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Categories/Index', [
            'categories' => Category::orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:100']]);

        $maxOrder = Category::max('sort_order') ?? 0;

        Category::create([...$validated, 'sort_order' => $maxOrder + 1]);

        return back();
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ]);

        $category->update($validated);

        return back();
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        return back();
    }

    public function move(Request $request, Category $category): RedirectResponse
    {
        $direction = $request->validate(['direction' => ['required', 'in:up,down']])['direction'];

        $swap = $direction === 'up'
            ? Category::where('sort_order', '<', $category->sort_order)->orderByDesc('sort_order')->first()
            : Category::where('sort_order', '>', $category->sort_order)->orderBy('sort_order')->first();

        if ($swap) {
            [$category->sort_order, $swap->sort_order] = [$swap->sort_order, $category->sort_order];
            $category->save();
            $swap->save();
        }

        return back();
    }
}