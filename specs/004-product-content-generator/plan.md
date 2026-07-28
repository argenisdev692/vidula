# Technical plan: Product Content Generator (Classroom & Video)

> Phase 4 · PLAN — Defines HOW it's built, verified against `research.md`.  
> Every technical decision here must be traceable to `spec.md` or `research.md`.

**Feature ID:** 004-product-content-generator  
**Based on:** spec.md, clarify.md, research.md  
**Date:** 2026-07-27  
**Status:** Approved — Tasks + Analyze complete; Implement in progress

## 1. Technical summary

Build a modular hexagonal **Products** bounded context in VIDULA that owns the billable catalog (`classroom` | `video_tutorial` | `video_pill`), a **unified** content tree (`product_sessions` → `product_session_topics` → `product_scripts` / `product_materials`), and an **ENTERPRISE** async **content-generation** pipeline. The operator pastes/uploads a markdown index; a queued job parses the outline, then per topic grounds content with **Tavily** (existing Shared adapter) + **Context7** (new Shared adapter), generates scripts/lesson notes via **`AIClientInterface` / `laravel/ai`**, consistency-checks against the seed, renders course MD + PDF, and finally builds a **ZIP** package for download.

Billing **reuses Invoices**: extend `invoice_items` with an optional `product_id` (API: `product_uuid`), parallel to today’s `service_id`. No separate products invoice module. Academic ops (enrollments/grades) are deferred.

Pattern mirror: **Campaigns** AI generation (handler → dispatcher → Horizon job → progress) + **Clients/Students** SIMPLE-CRUD for catalog screens.

## 2. Technology stack (verified with real-time research)

| Component | Choice | Verified version | Source / justification |
|---|---|---|---|
| Language / framework | PHP 8.5 + Laravel 13 | project + Laravel 13.x docs | research.md #3; Context7 `/websites/laravel_13_x` |
| Frontend | Vue 3 + Inertia v3 + PrimeVue Volt | project | existing VIDULA stack |
| AuthZ | Fortify session + Spatie permissions | project | OWASP baseline / Clients pattern |
| Queues | Redis + Horizon | Laravel 13 queues docs | research.md #3; Campaigns jobs |
| LLM | `laravel/ai` via `AIClientInterface` | project (`laravel/ai` v0) | research.md #5; repo |
| Web research | Tavily Search API via `TavilyClientInterface` | current Tavily Search API | research.md #1; docs.tavily.com |
| Library docs | Context7 REST via new `DocsVerificationPort` | Context7 / Upstash | research.md #2 |
| PDF | DomPDF (reuse invoice/export approach) | project | research.md gap note; Invoices `DomPdfInvoiceRenderer` |
| Markdown | Parsed seed + rendered course.md | — | thin parser; no new heavy CMS |
| Archive | PHP `ZipArchive` → `.zip` only | PHP built-in | research.md #4; clarify Q2 |
| Storage | Private disk / R2 via `StoragePort` + temporary URLs | Laravel filesystem | research.md #3, #6 |
| DB | PostgreSQL (Sail) | project | existing |

⚠️ PDF engine choice beyond DomPDF: `[UNVERIFIED alternative]` — stick to DomPDF unless implement-time proves insufficient for long course docs.

## 3. Architecture

### 3.1 Layers

```
Inertia/Vue (Products pages)
    ↓
Infrastructure/Http Controllers (+ FormRequests / Spatie Data)
    ↓
Application Commands/Queries (handlers)
    ↓
Domain Ports + Entities/Enums
    ↓
Infrastructure Persistence (Eloquent) | Queue | Ai | Research | Docs | Render | Zip | Storage
```

### 3.2 Generation pipeline (async)

```
POST generate-content (markdown)
  → Create ContentGeneration (pending)
  → Dispatch GenerateProductContentJob
  → 202 / redirect with generation uuid

Job stages:
  parsing     → SeedOutline from MD (classroom Shape A / video Shape B)
  generating  → for each topic:
                  Tavily.search(queries)
                  DocsVerificationPort.lookup(libs, topic)
                  AIClient.generateStructured(TopicScriptAgent | ClassroomLessonAgent)
                  persist script/content + sources_json
  verifying   → AI consistency pass vs seed titles; flag needs_review
  rendering   → CourseRendererPort → course.md + course.pdf → Storage + product_materials
  packaging   → ZipPackagePort → products/{uuid}/packages/{gen}.zip
  completed   → progress 100; broadcast/event
```

Graceful degradation: Tavily/Context7 empty → continue, mark topic `needs_review` (spec FR-15).

### 3.3 Shared adapters

| Port | Adapter | Notes |
|---|---|---|
| `AIClientInterface` | `LaravelAIAdapter` | existing |
| `TavilyClientInterface` | `TavilyResearchAdapter` | existing |
| `DocsVerificationPort` (new) | `Context7DocsAdapter` (new) | resolve library → query docs; breaker + empty fallback |
| `CourseRendererPort` (module) | `MarkdownCourseRenderer` + `DomPdfCourseRenderer` | MD template + PDF |
| `ZipPackagePort` (module) | `PhpZipPackageAdapter` | builds ZIP of MD/PDF/scripts tree |
| `StoragePort` | existing | private materials |

### 3.4 Invoice extension

- Migration: nullable `product_id` FK on `invoice_items` (mutually optional with `service_id`; at most one catalog link recommended in validation).
- DTO: add optional `productUuid` alongside `serviceUuid`.
- Snapshot `title`/`description`/`unit_price` from product at invoice time (same pattern as services).

## 4. Data model (physical schema)

Follow VIDULA convention: `id` bigint PK + `uuid` unique (as in draft MODULE-PRODUCTS migrations and Clients), soft deletes where listed, Spatie `LogsActivity` with explicit `logOnly`.

### products
- id, uuid, user_id, client_id nullable, type (`classroom|video_tutorial|video_pill`), title, slug unique, description, price, currency, status (`draft|published|archived`), thumbnail, level, language
- delivery: start_date, end_date, total_hours, total_sessions, modality, notes
- timestamps, deleted_at
- indexes: user_id, client_id, type, deleted_at

### classrooms (1:1 detail, type=classroom)
- id, uuid, product_id unique, max_students, meet_url, objectives, requirements, timestamps, deleted_at

### video_courses (1:1 detail, type=video_*)
- id, uuid, product_id unique, platform, playlist_url, total_videos, total_duration_minutes, target_audience, timestamps, deleted_at

### product_sessions
- id, uuid, product_id, session_number, title, session_date, start_time, end_time, hours, notes, timestamps, deleted_at
- index (product_id, session_number)

### product_session_topics
- id, uuid, product_session_id, title, description, hours, sort_order, sources_json nullable, timestamps, deleted_at
- index (product_session_id, sort_order)

### product_scripts
- id, uuid, product_session_topic_id unique
- intro, body, outro, notes (text nullable) — video types fill all; classroom may use body+notes primarily
- status (`draft|generated|verified|needs_review|recorded`)
- estimated_minutes, generated_by_model, sources_json
- timestamps, deleted_at

### product_materials
- id, uuid, product_id, product_session_topic_id nullable
- title, type (`pdf|markdown|link`), storage_disk, path, original_name, mime_type, size_bytes, url, content, is_downloadable, sort_order
- timestamps, deleted_at
- **No video binary type in v1**

### content_generations
- id, uuid, product_id, user_id
- status (`pending|parsing|generating|verifying|rendering|packaging|completed|failed`)
- mode (`replace` default)
- source_markdown, model, progress 0–100
- sessions_count, topics_count, scripts_count
- pdf_path, md_path, zip_path, error
- started_at, completed_at, timestamps
- partial unique: one non-terminal generation per product

### invoice_items (alter)
- add nullable product_id → products (nullOnDelete)

## 5. API / route contracts

Web (Inertia) is primary; JSON API mirrors where Clients/Invoices already expose API.

Permissions (illustrative): `VIEW_PRODUCTS`, `CREATE_PRODUCTS`, `UPDATE_PRODUCTS`, `DELETE_PRODUCTS`, `RESTORE_PRODUCTS`, `GENERATE_PRODUCT_CONTENT`, `DOWNLOAD_PRODUCT_PACKAGE` (+ existing invoice perms for billing).

### Product CRUD
| Method | Route | Story | Auth |
|---|---|---|---|
| GET | `/products` | US-9 | VIEW_PRODUCTS |
| GET | `/products/create` | US-1/9 | CREATE_PRODUCTS |
| POST | `/products` | US-1/9 | CREATE_PRODUCTS |
| GET | `/products/{uuid}` | US-8/9 | VIEW_PRODUCTS |
| GET | `/products/{uuid}/edit` | US-9 | UPDATE_PRODUCTS |
| PUT | `/products/{uuid}` | US-9 | UPDATE_PRODUCTS |
| DELETE | `/products/{uuid}` | US-9 | DELETE_PRODUCTS |
| POST | `/products/bulk-delete` | US-9 | DELETE_PRODUCTS |
| POST | `/products/bulk-restore` | US-9 | RESTORE_PRODUCTS |

### Generation
| Method | Route | Story | Notes |
|---|---|---|---|
| POST | `/products/{uuid}/generate-content` | US-2, US-3 | body: markdown or `.md` upload ≤ 1MB; mode=replace; 409 if in-flight |
| GET | `/products/{uuid}/generations/{generationUuid}` | US-8 | status, progress, counts, error |

### Scripts & materials
| Method | Route | Story | Notes |
|---|---|---|---|
| GET/PUT | `/products/{uuid}/topics/{topicUuid}/script` | US-6 | edit intro/body/outro/notes/status |
| GET | `/products/{uuid}/materials` | US-4 | list |
| GET | `/products/{uuid}/materials/{materialUuid}/download` | US-4 | signed/stream |
| POST | `/products/{uuid}/materials/{materialUuid}/replace` | US-4 | `.md`/`.pdf` only |

### Package
| Method | Route | Story | Notes |
|---|---|---|---|
| POST | `/products/{uuid}/package` | US-5 | queue ZIP if missing/stale |
| GET | `/products/{uuid}/package/download` | US-5 | download latest completed ZIP |

### Invoices (extend existing)
- Create/Update invoice item accepts optional `product_uuid` (US-7). Validation: product exists; snapshot title/price.

**Errors:** 403 unauthorized, 404 unknown uuid, 409 concurrent generation, 422 validation / wrong product type for generate, 429 throttle on generate.

## 6. Proposed folder structure

```
src/Modules/Products/
├── Domain/
│   ├── Enums/ (ProductType, ProductStatus, ScriptStatus, GenerationStatus, MaterialType, …)
│   ├── Ports/
│   │   ├── ProductRepositoryPort.php
│   │   ├── ContentGenerationRepositoryPort.php
│   │   ├── ProductScriptRepositoryPort.php
│   │   ├── ProductMaterialRepositoryPort.php
│   │   ├── CourseRendererPort.php
│   │   ├── ZipPackagePort.php
│   │   └── ContentGenerationDispatcherPort.php
│   └── Services/ (SeedOutlineParser, LibraryNameDetector, ConsistencyJudge input DTOs)
├── Application/
│   ├── Commands/ (Create/Update/Delete/Restore/Bulk*, StartContentGeneration, UpdateScript, ReplaceMaterial, RequestPackage)
│   ├── Queries/ (List/Get products, GetGeneration, ListMaterials)
│   └── DTOs/
├── Infrastructure/
│   ├── Persistence/Eloquent/Models + Repositories + Mappers
│   ├── Http/Controllers (+ Api/) + Requests + Routes
│   ├── Ai/ (TopicScriptAgent, ClassroomLessonAgent, ConsistencyAgent, LaravelAiProductGeneratorAdapter)
│   ├── Queue/ (GenerateProductContentJob, BuildProductZipJob, QueuedContentGenerationDispatcher)
│   ├── Rendering/ (MarkdownCourseRenderer, DomPdfCourseRenderer)
│   ├── Packaging/ (PhpZipPackageAdapter)
│   ├── Broadcasting/ (ProductContentGenerationProgress)
│   └── Export/ (optional list export)
├── Providers/ProductsServiceProvider.php
└── Tests/Feature/…

src/Shared/Domain/Ports/DocsVerificationPort.php
src/Shared/Infrastructure/Docs/
├── Context7DocsAdapter.php
└── Context7ClientInterface.php   # optional thin HTTP interface

# Invoices deltas
src/Modules/Invoices/… (DTO + model + migration product_id)
```

Frontend (ARCHITECTURE-VUE):
```
resources/js/modules/products/
resources/js/Pages/Products/…
```

## 7. Testing strategy

- **Feature (PHPUnit):** Product CRUD + permissions; generate rejects `service`/invalid MD/concurrent; generation job with faked AI/Tavily/Context7 produces sessions/topics/scripts/materials; consistency flags drift; material replace MIME allowlist; ZIP download authorized; invoice item with `product_uuid` snapshots correctly.
- **Unit:** SeedOutlineParser against fixture files from `docs/MODULE-PRODUCTS/CLASSROOM/indice-curso-copilot.md` and `VIDEOS/pildoras_video_claude_usuarios.md` (copied into `tests/Fixtures/products/`).
- **Fakes:** bind fake `AIClientInterface`, `TavilyClientInterface`, `DocsVerificationPort`, `StoragePort` in tests.
- Run targeted tests via Sail (developer executes); no bare `php`.

## 8. Security and compliance

| Requirement | Approach |
|---|---|
| AuthZ every route | `auth` + Spatie `permission:*` |
| UUID routes | `->whereUuid('uuid')` |
| Prompt injection / size | MD ≤ 1MB; sessions ≤ 200; topics ≤ 2000; strip/limit prompt context |
| LLM unbounded cost | throttle generate; one in-flight generation; circuit breakers on AI/Tavily/Context7 |
| Secrets | keys only in env/`config/services.php`; never in logs/activity |
| Downloads | private disk + permission + temporary URL / authorized download |
| Uploads | replace allowlist `.md`/`.pdf` + MIME + size; no video |
| Audit | `LogsActivity` on products/scripts/materials/generations with explicit `logOnly` |
| PII | do not inject client/student PII into generation prompts |

OWASP SKILL baseline applies to every change.

## 9. Risks and open decisions

| Risk | Mitigation |
|---|---|
| Long courses → high AI/Tavily/Context7 cost/time | Progress UI; per-topic continue-on-failure; caps |
| Context7 API shape drift | Config base URL; adapter integration test with Http::fake |
| DomPDF limits on huge MD | Paginate/split PDF or simplify CSS if needed |
| Invoice item both service+product set | Validation: at most one catalog FK |
| Operator force-replace wipes verified scripts | Confirm dialog + mode flag |

**Pending before implement (non-blocking for Tasks):** confirm env keys `TAVILY_API_KEY` already present; add `CONTEXT7_API_KEY` + `CONTEXT7_API_URL` to `.env.example`.

## 10. Traceability

| Requirement (spec.md) | Covered by |
|---|---|
| US-1 / FR-1 | §4 products.type; §5 CRUD; generate type guard |
| US-2 / FR-2 / FR-14 | §3 pipeline parsing; POST generate-content; singleton generation |
| US-3 / FR-3–5 / FR-13 / FR-15 | §3 generating+verifying; Tavily + Context7 + AI agents |
| US-4 / FR-7 / FR-12 | §4 materials; §5 download/replace; no video |
| US-5 / FR-8 | §3 packaging; ZipPackagePort; package routes |
| US-6 / FR-9 | script update routes; statuses |
| US-7 / FR-10 | §3.4 invoice extension |
| US-8 / FR-6 | content_generations + GET generation + broadcast |
| US-9 / FR-11 | CRUD + permissions |
| NFR Security | §8 |
| Clarify Q1–Q7 | §1, §3.4, ZIP-only, types, per-topic, classroom notes vs video scripts |
