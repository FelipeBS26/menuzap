<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessImageJob;
use App\Models\Category;
use App\Models\OptionGroup;
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
            'optionGroups' => OptionGroup::where('is_active', true)->orderBy('name')->get(['id', 'name']),
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
        $this->syncOptionGroups($product, $validated['option_groups'] ?? []);
        $this->dispatchImageJob($request, $product);

        return redirect()->route('tenant.products.index');
    }

    public function edit(Product $product): Response
    {
        return Inertia::render('Products/Form', [
            'categories' => Category::orderBy('sort_order')->get(['id', 'name']),
            'optionGroups' => OptionGroup::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'product' => $product->load(['sizes', 'optionGroups']),
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
        $this->syncOptionGroups($product, $validated['option_groups'] ?? []);
        $this->dispatchImageJob($request, $product);

        return redirect()->route('tenant.products.index');
    }

    public function destroy(Product $product): RedirectResponse
    {
        // detach() aqui é seguro e correto: o PRODUTO está sumindo, então o
        // vínculo dele com os grupos não faz mais sentido — os grupos em si
        // continuam existindo intactos para os outros produtos (Fase 5).
        $product->optionGroups()->detach();
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

        // Duplica também os vínculos de adicionais, com as mesmas regras
        // de min/max — é o comportamento que o lojista espera de "Duplicar".
        $syncData = [];
        foreach ($product->optionGroups as $group) {
            $syncData[$group->id] = [
                'tenant_id' => $copy->tenant_id,
                'min_selections' => $group->pivot->min_selections,
                'max_selections' => $group->pivot->max_selections,
                'sort_order' => $group->pivot->sort_order,
            ];
        }
        $copy->optionGroups()->sync($syncData);

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

            // Motor de adicionais (Fase 5/9) — só os grupos marcados como
            // "linked" chegam aqui como candidatos reais de vínculo.
            'option_groups' => ['array'],
            'option_groups.*.id' => [
                'required_with:option_groups',
                Rule::exists('option_groups', 'id')->where('tenant_id', $tenantId),
            ],
            'option_groups.*.min_selections' => ['required_with:option_groups', 'integer', 'min:0'],
            'option_groups.*.max_selections' => ['required_with:option_groups', 'integer', 'min:1', 'gte:option_groups.*.min_selections'],
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

    protected function syncOptionGroups(Product $product, array $optionGroups): void
    {
        $syncData = [];

        foreach ($optionGroups as $i => $group) {
            // tenant_id incluído explicitamente: sync() faz INSERT/UPDATE
            // direto na tabela pivot, sem passar pelos hooks do Model
            // ProductOptionGroup — não podemos depender do trait HasTenant
            // preenchendo isso sozinho aqui.
            $syncData[$group['id']] = [
                'tenant_id' => $product->tenant_id,
                'min_selections' => $group['min_selections'],
                'max_selections' => $group['max_selections'],
                'sort_order' => $i,
            ];
        }

        $product->optionGroups()->sync($syncData);
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