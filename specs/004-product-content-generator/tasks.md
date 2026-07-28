# Tasks: Product Content Generator

> Phase 5 · BREAK DOWN TASKS  
**Feature ID:** 004-product-content-generator  
**Based on:** plan.md, clarify.md, spec.md

## Phase A — Foundations
- [x] T001 Create `src/Modules/Products/` tree + `ProductsServiceProvider` + register in `bootstrap/providers.php`
- [x] T002 [P] Add `config/products.php` (generation caps, default model, package TTL) + `.env.example` keys: `CONTEXT7_API_KEY`, `CONTEXT7_API_URL` (Tavily already present)
- [x] T003 [P] Add `PRODUCTS` to `RolePermissionSeeder` MODULES + ADMIN_MODULES; add `GENERATE_PRODUCTS` (+ `DOWNLOAD_PRODUCTS` if not covered by VIEW/EXPORT) actions

## Phase B — Data model
- [x] T004 Migrations: `products`, `classrooms`, `video_courses`, `product_sessions`, `product_session_topics`, `product_scripts`, `product_materials`, `content_generations` (+ alter `invoice_items.product_id`)
- [x] T005 [P] Enums: ProductType, ProductStatus, ScriptStatus, GenerationStatus, MaterialType, ProductModality, VideoPlatform
- [x] T006 Eloquent models + factories + LogsActivity (`logOnly` explicit; no secrets/markdown dumps of full source in activity if oversized — log hashes/counts)
- [x] T007 Domain ports: ProductRepositoryPort, ContentGenerationRepositoryPort, ProductScriptRepositoryPort, ProductMaterialRepositoryPort, CourseRendererPort, ZipPackagePort, ContentGenerationDispatcherPort

## Phase C — US-1 / US-9 Product catalog CRUD
- [x] T008 DTOs: ProductData, ProductFilterData, BulkProductUuidsData
- [x] T009 Commands: Create/Update/Delete/Restore/BulkDelete/BulkRestore Product handlers (+ create thin classroom/video_course detail row by type)
- [x] T010 Queries: ListProductsHandler, GetProductHandler
- [x] T011 Controllers + web/api routes (`whereUuid`) + Inertia Pages (Index/Create/Edit/Show) mirroring Clients
- [x] T012 Feature tests: Product CRUD + permissions + soft-delete/restore/bulk

## Phase D — US-2 Seed + start generation
- [x] T013 SeedOutlineParser (classroom Shape A + video Shape B) + unit tests with fixtures from `docs/MODULE-PRODUCTS`
- [x] T014 StartContentGenerationHandler: validate type/size, singleton in-flight (409), persist ContentGeneration, dispatch job
- [x] T015 POST `/products/{uuid}/generate-content` + throttle + GENERATE permission
- [x] T016 Feature tests: invalid MD, wrong type, concurrent generation

## Phase E — US-3 Grounded generation pipeline
- [x] T017 Shared `DocsVerificationPort` + `Context7DocsAdapter` (circuit breaker, empty-on-failure) + bind in SharedServiceProvider
- [x] T018 AI agents: TopicScriptAgent (video), ClassroomLessonAgent, ConsistencyAgent via AIClientInterface
- [x] T019 `GenerateProductContentJob`: parse → persist sessions/topics → per-topic Tavily+Context7+AI → verify → update progress/broadcast
- [x] T020 Feature/unit tests with Http::fake / fake AI ports: sources_json stored; needs_review on research failure; titles match seed

## Phase F — US-4 Materials MD/PDF
- [x] T021 CourseRendererPort implementation (Markdown + DomPDF)
- [x] T022 Persist product_materials rows + paths on content_generations; download + replace (`.md`/`.pdf` only)
- [x] T023 Feature tests: download auth, replace MIME rejection, no video upload

## Phase G — US-5 ZIP package
- [x] T024 PhpZipPackageAdapter + BuildProductZipJob / package step in pipeline
- [x] T025 POST package + GET package/download routes
- [x] T026 Feature tests: ZIP contains md/pdf/scripts tree; blocked when generation incomplete

## Phase H — US-6 Script review/edit
- [x] T027 UpdateScriptHandler + GET/PUT topic script routes + status transitions
- [x] T028 Feature tests: edit + verify status; force-replace vs preserve verified on re-gen

## Phase I — US-7 / US-8 Invoice link + progress
- [x] T029 Extend Invoices: InvoiceItemData `productUuid`, model FK, validation (at most one of service/product)
- [x] T030 GET generation status endpoint + progress broadcast (Campaigns pattern)
- [x] T031 Feature tests: invoice with product_uuid snapshot; generation progress payload

## Phase J — Cross-cutting
- [x] T032 Nav entry + permissions on frontend (`useNavGroups` / products module)
- [x] T033 Activity audit events for generation start/complete/fail (no API keys)
- [x] T034 Rate limit + caps enforced (1MB MD, sessions/topics limits from config)

## Phase K — Closeout
- [ ] T035 Pint dirty (user runs Sail) + `artisan test --filter=Product`
- [ ] T036 Traceability review vs spec FR/US
- [ ] T037 Write `SSD-SUMMARY.md` (Phase 8) after all tasks checked

---
**Suggested commit convention:** `feat(products): T0XX short description`
