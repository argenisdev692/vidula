# Analyze: Product Content Generator

> Phase 6 · ANALYZE — Cross-check spec ↔ plan ↔ tasks before implementation.  
**Feature ID:** 004-product-content-generator  
**Date:** 2026-07-27

## 1. Spec → Plan → Tasks coverage

| Spec item | Plan section | Task(s) | Status |
|---|---|---|---|
| US-1 Product type selection | §4 types, §5 CRUD | T008–T012 | OK |
| US-2 Markdown seed | §3 parsing, POST generate | T013–T016 | OK |
| US-3 Grounded scripts all topics | §3 generating/verifying, Tavily+Context7+AI | T017–T020 | OK |
| US-4 MD/PDF materials | §3 rendering, materials routes | T021–T023 | OK |
| US-5 ZIP download | §3 packaging, ZipPort | T024–T026 | OK |
| US-6 Review/edit scripts | script routes | T027–T028 | OK |
| US-7 Invoice product link | §3.4 invoice extension | T029, T031 | OK |
| US-8 Generation progress | content_generations + broadcast | T014, T019, T030 | OK |
| US-9 Product CRUD + perms | CRUD + seeder | T003, T008–T012, T032 | OK |
| FR-1…FR-15 | §5–§8 | Phases C–J | OK |
| Clarify Q1 reuse invoices | §3.4 | T004, T029 | OK |
| Clarify Q2 ZIP only | ZipPackagePort | T024–T026 | OK |
| Clarify Q3 no enrollments | Out of scope | No task (correct) | OK |
| Clarify Q4 types | ProductType enum | T005, T009 | OK |
| Clarify Q5 per-topic grounding | Job loop | T019 | OK |
| Clarify Q6 classroom vs video scripts | Two AI agents | T018 | OK |
| Clarify Q7 replace/preserve verified | StartContentGeneration mode + tests | T014, T028 | OK |
| NFR Security | §8 | T003, T015, T022, T033, T034 | OK |

## 2. Orphan / gap check

| Check | Result |
|---|---|
| Orphan requirements (spec without task) | **None** |
| Orphan tasks (task without spec/plan) | **None** — T001–T002 foundations only |
| Plan vs Clarify contradictions | **None** — invoice reuse, ZIP, scope, types match |
| Missing Shared Context7 in tasks | Covered by T017 |
| Frontend pages | Covered by T011 + T032 |
| Acceptance fixtures (classroom/video MD) | T013 |

## 3. Gaps found and fixes applied

| Gap | Fix |
|---|---|
| `DOWNLOAD_PRODUCTS` vs EXPORT confusion | T003: use MODULES export actions for list export; package download gated by `VIEW_PRODUCTS` or dedicated `GENERATE_PRODUCTS` — implement as VIEW for download of own materials + GENERATE for start; document in T025 |
| Classroom thin detail creation | Explicit in T009 |
| Invoice mutual exclusion service/product | Explicit in T029 |

## 4. Verdict

**No blocking gaps.** Safe to proceed to Phase 7 — Implement in task order (A → K).

Parallelizable next: T002 ∥ T003 ∥ T005 after T001 scaffolding; T017 can start once Shared provider access is clear.
