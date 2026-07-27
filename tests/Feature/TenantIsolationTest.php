<?php

use App\Models\Category;
use App\Models\Plan;
use App\Models\Store;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * O critério de aceite definido na auditoria da Fase 10: transformar
 * "confiamos que o multi-tenant funciona" em prova programática. Cada teste
 * roda dentro de uma transação com rollback automático — nada aqui fica
 * gravado no banco depois que a suíte termina.
 */
uses(DatabaseTransactions::class);

beforeEach(function () {
    TenantContext::clear();

    $plan = Plan::query()->first();

    $this->tenantA = Tenant::create([
        'plan_id' => $plan->id,
        'slug' => 'teste-isolamento-a-'.uniqid(),
        'status' => 'trial',
    ]);

    $this->tenantB = Tenant::create([
        'plan_id' => $plan->id,
        'slug' => 'teste-isolamento-b-'.uniqid(),
        'status' => 'trial',
    ]);
});

afterEach(function () {
    TenantContext::clear();
});

// ---------- Nível 1: o TenantScope isoladamente ----------

it('nunca retorna registros de outro tenant ao listar com o escopo ativo', function () {
    TenantContext::set($this->tenantA->id);
    $categoryA = Category::create(['tenant_id' => $this->tenantA->id, 'name' => 'Categoria A']);

    TenantContext::set($this->tenantB->id);
    $categoryB = Category::create(['tenant_id' => $this->tenantB->id, 'name' => 'Categoria B']);

    TenantContext::set($this->tenantA->id);
    $categories = Category::all();

    expect($categories)->toHaveCount(1)
        ->and($categories->first()->id)->toBe($categoryA->id)
        ->and($categories->pluck('id'))->not->toContain($categoryB->id);
});

it('bloqueia find() direto por ID de um registro pertencente a outro tenant', function () {
    TenantContext::set($this->tenantB->id);
    $categoryB = Category::create(['tenant_id' => $this->tenantB->id, 'name' => 'Categoria B']);

    // Mesmo sabendo o ID exato, o tenant A não pode "adivinhar" e acessar
    // um registro de outro tenant via find() direto.
    TenantContext::set($this->tenantA->id);
    $found = Category::find($categoryB->id);

    expect($found)->toBeNull();
});

it('preenche tenant_id automaticamente ao criar sem informar explicitamente', function () {
    TenantContext::set($this->tenantA->id);

    $category = Category::create(['name' => 'Sem tenant_id explícito no create()']);

    expect($category->tenant_id)->toBe($this->tenantA->id);
});

it('não filtra nada quando não há tenant no contexto — e é por isso que o middleware é obrigatório', function () {
    TenantContext::clear();

    Category::create(['tenant_id' => $this->tenantA->id, 'name' => 'A']);
    Category::create(['tenant_id' => $this->tenantB->id, 'name' => 'B']);

    // Comportamento esperado e documentado: sem contexto (ex: rotas do super
    // admin), o Scope é um no-op. A proteção real está nos middlewares
    // garantindo que TODA rota de tenant passe por eles antes de chegar aqui.
    expect(Category::count())->toBeGreaterThanOrEqual(2);
});

// ---------- Nível 2: middlewares via requisição HTTP real ----------

it('a vitrine resolve a loja certa pelo slug e exibe os dados dela', function () {
    Store::create([
        'tenant_id' => $this->tenantA->id,
        'name' => 'Loja de Teste A',
        'whatsapp_number' => '5551999990000',
    ]);

    $response = $this->get("/{$this->tenantA->slug}");

    $response->assertOk();
    $response->assertSee('Loja de Teste A');
});

it('retorna 404 para um slug que não existe', function () {
    $response = $this->get('/slug-inexistente-'.uniqid());

    $response->assertNotFound();
});

it('bloqueia acesso ao painel sem autenticação, redirecionando para o login', function () {
    $response = $this->get('/dashboard');

    $response->assertRedirect(route('login'));
});

it('o painel autentica e escopa corretamente o dono do tenant', function () {
    $user = User::create([
        'tenant_id' => $this->tenantA->id,
        'name' => 'Dono da Loja A',
        'email' => 'dono-teste-'.uniqid().'@menuzap.test',
        'password' => 'senha-de-teste-123',
        'role' => 'tenant_owner',
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk();
    $response->assertSee($this->tenantA->slug);
});

it('bloqueia um usuário sem tenant_id de acessar o painel', function () {
    $superAdmin = User::create([
        'tenant_id' => null,
        'name' => 'Super Admin',
        'email' => 'admin-teste-'.uniqid().'@menuzap.test',
        'password' => 'senha-de-teste-123',
        'role' => 'super_admin',
    ]);

    $response = $this->actingAs($superAdmin)->get('/dashboard');

    $response->assertForbidden();
});