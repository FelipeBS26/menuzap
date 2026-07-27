<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Store;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function show(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'store_name' => ['required', 'string', 'max:150'],
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'whatsapp_number' => ['required', 'string', 'max:20'],
        ]);

        // Transação única — se qualquer criação falhar, as três fazem rollback.
        // Sem isso, um lojista pode acabar com tenant criado mas sem store (Fase 6).
        $tenant = DB::transaction(function () use ($validated) {
            $plan = Plan::where('slug', 'starter')->firstOrFail();

            $tenant = Tenant::create([
                'plan_id' => $plan->id,
                'slug' => $this->generateUniqueSlug($validated['store_name']),
                'status' => 'trial',
                'trial_ends_at' => now()->addDays(14),
            ]);

            User::create([
                'tenant_id' => $tenant->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'], // hash automático via cast 'hashed' no Model
                'role' => 'tenant_owner',
            ]);

            // Store padrão vazia — seed por nicho (categorias/produtos de exemplo)
            // só chega no Sprint 5 (onboarding mágico). Por ora, dados essenciais.
            Store::create([
                'tenant_id' => $tenant->id,
                'name' => $validated['store_name'],
                'whatsapp_number' => $validated['whatsapp_number'],
            ]);

            return $tenant;
        });

        Auth::login(User::where('tenant_id', $tenant->id)->firstOrFail());

        return redirect()->route('tenant.dashboard');
    }

    protected function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'loja';
        $slug = $base;
        $i = 1;

        while (Tenant::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}