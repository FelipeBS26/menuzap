# MenuZap

> Micro-SaaS de cardápio digital para pequenos estabelecimentos. O cliente monta o pedido numa vitrine com UX pensado em facilidade de navegação; ao finalizar, o pedido é organizado automaticamente e enviado direto para o WhatsApp do estabelecimento. Sem marketplace, sem taxa por pedido, sem app para instalar.

---

## Status atual

| Etapa | Status |
|---|---|
| Planejamento (Fases 1–10) | ✅ Concluído |
| Sprint 1 — Fundação, multi-tenant, auth | ✅ Concluído e testado (9 testes automatizados) |
| Sprint 2 — Painel do lojista | ✅ Concluído e testado |
| Sprint 3 — Vitrine pública | ✅ Concluído e testado |
| Sprint 4 — Carrinho e checkout |  🚧 Em andamento |
| Sprint 5 — Super admin e deploy | ⏳ Não iniciado |


---

## Stack

| Camada | Tecnologia |
|---|---|
| Backend | Laravel 11 (PHP 8.2+), Eloquent, Sanctum |
| Vitrine pública | Blade + Alpine.js |
| Painel do lojista/admin | Vue 3 + Inertia.js, Shadcn-Vue, Tailwind v4 |
| Banco de dados | PostgreSQL via Supabase |
| Cache / fila | Redis (produção) |
| Storage de imagens | Cloudflare R2 |
| Deploy | Coolify em VPS Hetzner |
| Testes | Pest |

Detalhamento técnico completo: [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md)

---

## Rodando o projeto localmente
Resumo rápido:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
# preencher credenciais do Supabase no .env
php artisan migrate
php artisan db:seed --class=PlanSeeder
php artisan storage:link
php artisan serve
```

Em outro terminal:

```bash
npm run dev
```

Acesse `http://127.0.0.1:8000/register` para criar a primeira loja de teste.

---

## Rodando os testes

```bash
php artisan test
```

Os testes rodam contra o Postgres real (Supabase), dentro de transações com rollback automático — nada fica gravado no banco após a suíte terminar. Veja `tests/Feature/TenantIsolationTest.php` para a suíte de isolamento multi-tenant.

---

## Estrutura do projeto

```
app/
├── Http/Controllers/     Controllers da vitrine, painel e auth
├── Http/Middleware/      Identificação de tenant (2 middlewares distintos — ver docs/ARCHITECTURE.md)
├── Models/                13 Models Eloquent, multi-tenant via Global Scope
├── Jobs/                  Processamento assíncrono (imagens, etc.)
└── Support/                TenantContext — o núcleo do isolamento multi-tenant

resources/
├── views/                 Blade (vitrine pública + template raiz do Inertia)
└── js/
    ├── Layouts/            Layout persistente do painel (sidebar, topbar, bottom nav)
    └── Pages/              Páginas Inertia (Dashboard, Categorias, Loja...)

routes/
├── web.php                 Autenticação pública (login/registro)
├── storefront.php          Vitrine — Blade, identificação de tenant por slug
└── tenant.php               Painel do lojista — Inertia, identificação por sessão

---

