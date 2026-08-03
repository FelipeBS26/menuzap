<?php

namespace App\Http\Controllers;

use App\Models\OptionGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OptionGroupController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('OptionGroups/Index', [
            'groups' => OptionGroup::with(['items' => fn ($q) => $q->orderBy('sort_order')])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateGroup($request);

        $group = OptionGroup::create(['name' => $validated['name']]);

        $this->syncItems($group, $validated['items'] ?? []);

        return back();
    }

    public function update(Request $request, OptionGroup $group): RedirectResponse
    {
        $validated = $this->validateGroup($request, withActive: true);

        $group->update([
            'name' => $validated['name'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $this->syncItems($group, $validated['items'] ?? []);

        return back();
    }

    public function destroy(OptionGroup $group): RedirectResponse
    {
        // O grupo inteiro está sumindo — o vínculo com produtos (pivot) não
        // faz mais sentido existir, então aqui SIM desfazemos com detach().
        // Diferente do fluxo normal de "desvincular 1 produto", que mantém
        // o grupo intacto para outros produtos (Fase 5).
        $group->products()->detach();
        $group->items()->delete();
        $group->delete();

        return back();
    }

    protected function validateGroup(Request $request, bool $withActive = false): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'items' => ['array'],
            'items.*.id' => ['nullable', 'string'],
            'items.*.name' => ['required_with:items', 'string', 'max:100'],
            'items.*.price_cents' => ['required_with:items', 'integer', 'min:0'],
        ];

        if ($withActive) {
            $rules['is_active'] = ['boolean'];
        }

        return $request->validate($rules);
    }

    protected function syncItems(OptionGroup $group, array $items): void
    {
        $incomingIds = collect($items)->pluck('id')->filter()->all();
        $group->items()->whereNotIn('id', $incomingIds)->delete();

        foreach ($items as $i => $item) {
            if (! empty($item['id'])) {
                $group->items()->where('id', $item['id'])->update([
                    'name' => $item['name'],
                    'price_cents' => $item['price_cents'],
                    'sort_order' => $i,
                ]);
            } else {
                $group->items()->create([
                    'tenant_id' => $group->tenant_id,
                    'name' => $item['name'],
                    'price_cents' => $item['price_cents'],
                    'sort_order' => $i,
                ]);
            }
        }
    }
}