<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessImageJob;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Products/Index', [
            'products' => Product::with(['category', 'sizes'])->orderBy('sort_order')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Products/Form', [
            'categories' => Category::orderBy('sort_order')->get(['id', 'name']),
            'product' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateProduct($request);

        $product = Product::create([
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'base_price_cents' => $validated['has_sizes'] ? 0 : $validated['base_price_cents'],
            'badge' => $validated['badge'] ?: null,
            'is_active' => $validated['is_active'],
            'has_sizes' => $validated['has_sizes'],
            'sort_order' => (Product::max('sort_order') ?? 0) + 1,
        ]);

        $this->syncSizes($product, $validated['sizes'] ?? []);
        $this->dispatchImageJob($request, $product);

        return redirect()->route('tenant.products.index');
    }

    public function edit(Product $product): Response
    {
        return Inertia::render('Products/Form', [
            'categories' => Category::orderBy('sort_order')->get(['id', 'name']),
            'product' => $product->load('sizes'),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $this->validateProduct($request);

        $product->update([
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'base_price_cents' => $validated['has_sizes'] ? 0 : $validated['base_price_cents'],
            'badge' => $validated['badge'] ?: null,
            'is_active' => $validated['is_active'],
            'has_sizes' => $validated['has_sizes'],
        ]);

        $this->syncSizes($product, $validated['sizes'] ?? []);
        $this->dispatchImageJob($request, $product);

        return redirect()->route('tenant.products.index');
    }

    public function destroy(Product $product): RedirectResponse
    {
        // Nota Sprint 3: quando o motor de adicionais existir, desvincular
        // da pivot product_option_groups aqui também (Fase 5).
        $product->delete();

        return back();
    }

    public function duplicate(Product $product): RedirectResponse
    {
        $copy = $product->replicate();
        $copy->name = $product->name.' (cópia)';
        $copy->sort_order = (Product::max('sort_order') ?? 0) + 1;
        $copy->save();

        foreach ($product->sizes as $size) {
            $copy->sizes()->create([
                'tenant_id' => $copy->tenant_id,
                'name' => $size->name,
                'price_cents' => $size->price_cents,
                'sort_order' => $size->sort_order,
            ]);
        }

        return back();
    }

    protected function validateProduct(Request $request): array
    {
        $tenantId = app('tenant')->id;

        return $request->validate([
            // Escopado explicitamente por tenant_id — a regra padrão exists:
            // consulta a tabela direto, ignorando o Global Scope (Fase 5/6).
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')->where('tenant_id', $tenantId),
            ],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'base_price_cents' => ['required_if:has_sizes,false', 'nullable', 'integer', 'min:0'],
            'badge' => ['nullable', 'in:new,promo,highlight'],
            'is_active' => ['boolean'],
            'has_sizes' => ['boolean'],
            'sizes' => ['array'],
            'sizes.*.name' => ['required_with:sizes', 'string', 'max:50'],
            'sizes.*.price_cents' => ['required_with:sizes', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'max:5120'],
        ]);
    }

    protected function syncSizes(Product $product, array $sizes): void
    {
        // Estratégia simples: apaga e recria a cada save. Para o volume de
        // tamanhos por produto (raramente mais que 3-4), o custo é irrelevante.
        $product->sizes()->delete();

        foreach ($sizes as $i => $size) {
            $product->sizes()->create([
                'tenant_id' => $product->tenant_id,
                'name' => $size['name'],
                'price_cents' => $size['price_cents'],
                'sort_order' => $i,
            ]);
        }
    }

    protected function dispatchImageJob(Request $request, Product $product): void
    {
        if ($request->hasFile('image')) {
            $tempPath = $request->file('image')->store('tmp');
            ProcessImageJob::dispatch(
                app('tenant')->id, Product::class, $product->id, 'image_url', $tempPath, 800, 800
            );
        }
    }
}