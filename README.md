# MenuZap

> Micro-SaaS de cardápio digital para pequenos estabelecimentos. O cliente monta o pedido numa vitrine com UX de nível iFood; ao finalizar, o pedido é organizado automaticamente e enviado direto para o WhatsApp do estabelecimento. Sem marketplace, sem taxa por pedido, sem app para instalar.

---

## Status atual

| Etapa | Status |
|---|---|
| Planejamento (Fases 1–10) | ✅ Concluído |
| Sprint 1 — Fundação, multi-tenant, auth | ✅ Concluído e testado (9 testes automatizados) |
| Sprint 2 — Painel do lojista | 🚧 Em andamento (Partes 1 e 2 de 4 entregues) |
| Sprint 3 — Vitrine pública | ⏳ Não iniciado |
| Sprint 4 — Carrinho e checkout | ⏳ Não iniciado |
| Sprint 5 — Super admin e deploy | ⏳ Não iniciado |

Histórico completo de decisões, fase a fase: [`docs/CHECKPOINT.md`](docs/CHECKPOINT.md)

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

Guia passo a passo completo, incluindo troubleshooting de ambiente Windows: [`docs/SETUP.md`](docs/SETUP.md)

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

docs/
├── ARCHITECTURE.md          Arquitetura técnica completa
├── CHECKPOINT.md            Histórico de decisões, fase a fase
├── SETUP.md                  Guia de setup do ambiente
└── GIT_WORKFLOW.md            Estratégia de branches e versionamento
```

---

## Fluxo de contribuição

Uma branch por Sprint, um commit por Parte concluída e testada. Nunca commitar código que não passou pelo menos por teste manual. Detalhes completos, incluindo como reverter uma entrega quebrada: [`docs/GIT_WORKFLOW.md`](docs/GIT_WORKFLOW.md)

```bash
git checkout -b sprint-N
# ... trabalho ...
git add .
git commit -m "feat: Sprint N Parte X — descrição"
git push
```

---

## Documentação completa

- [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) — schema do banco, rotas, middlewares, estratégia de cache, segurança
- [`docs/CHECKPOINT.md`](docs/CHECKPOINT.md) — toda decisão tomada, fase por fase, incluindo o porquê de cada uma
- [`docs/SETUP.md`](docs/SETUP.md) — setup do ambiente de desenvolvimento (VS Code, extensões, Supabase)
- [`docs/GIT_WORKFLOW.md`](docs/GIT_WORKFLOW.md) — branches, commits, rollback