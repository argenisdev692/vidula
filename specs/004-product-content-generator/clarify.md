# Clarify: Product Content Generator

> Phase 2 · CLARIFY — Ambiguities from `spec.md` and how they were resolved.
> **Feature ID:** 004-product-content-generator
> **Date:** 2026-07-27
> **Status:** Resolved (user: “ok continue” → defaults accepted)

## Open questions (audit trail)

### Q1 — Invoice strategy (High impact)
**Question:** Reuse/extend the existing `Invoices` module, or build a products-specific invoice module?  
**Impact:** Plan API/folder structure; whether `invoice_items` gains a product reference alongside today’s `service_id` / `service_uuid`.  
**Resolved:** **Reuse and extend** the existing Invoices module. Products become an optional line-item reference (parallel to Services). No second billing catalog.  
**Rationale:** One commerce pipeline; Clients already exist; draft MODULE-PRODUCTS migrations already foresaw `invoice_items.product_id`.

### Q2 — Archive format (High impact)
**Question:** ZIP, RAR, or both for the deliverable package?  
**Impact:** Packaging adapter and download UX.  
**Resolved:** **ZIP only.**  
**Rationale:** Open, universal, PHP `ZipArchive` / Laravel-friendly; RAR is proprietary and unnecessary for client delivery.

### Q3 — v1 scope boundary (High impact)
**Question:** Generator + catalog only, or also enrollments / attendance / grades in this SDD?  
**Impact:** Task volume and whether academic ops enter the same bounded context.  
**Resolved:** **v1 = products catalog + content generation + scripts + materials + MD/PDF + ZIP package + invoice product link.**  
Enrollments, attendance, and grades stay **out of scope** (future Phase 3 academic ops).

### Q4 — Product types in v1 (High impact)
**Question:** `classroom` + `video` only, or also `video_tutorial` / `video_pill` / `service`?  
**Impact:** Validation rules; which types may call generate.  
**Resolved:** v1 types = **`classroom` | `video_tutorial` | `video_pill`**.  
- Generation allowed for all three.  
- **`service`** deferred (catalog/billing later); generation MUST reject it if introduced early.  
- UI may label “video” generically; storage uses the two video subtypes for script/material shape.

### Q5 — Grounding granularity (Medium)
**Question:** Per-topic vs per-session Tavily + Context7 grounding?  
**Impact:** Job duration and API cost.  
**Resolved by default:** **Per-topic** grounding (higher quality).  
Mark topic `needs_review` if research/docs fail; do not abort the whole job.

### Q6 — Classroom script shape (Medium)
**Question:** Full video-style intro/body/outro guiones for classroom, or lesson notes?  
**Impact:** Script schema and prompts.  
**Resolved by default:** Classroom topics get **lesson content + presenter notes** (and supporting materials). Full intro/body/outro/notes scripts are **required for `video_tutorial` / `video_pill`**.

### Q7 — Re-generation mode (Medium)
**Question:** Append vs replace when re-running generation?  
**Impact:** Tree mutation rules.  
**Resolved by default:** **Replace after explicit confirmation** for draft/generated content; human-**verified** or **recorded** scripts are preserved unless the operator force-replaces.

### Q8 — PDF/MD storage disk (Low)
**Resolved by default:** Use existing private disk / R2 pattern via `StoragePort` (same as other private artifacts); downloads via temporary/signed URLs or authorized stream.

### Q9 — Context7 credentials (Low — env)
**Resolved by default:** Add `CONTEXT7_API_KEY` (and base URL) mirroring `TAVILY_API_KEY` in `config/services.php`. Adapter degrades to empty snippets when missing (same as Tavily).

## Residual non-blockers
- Exact ZIP folder layout naming (session/topic folders) — decided in Plan.
- Whether DomPDF vs existing invoice PDF path is reused for course PDF — Plan: reuse project PDF approach already used by Invoices/Exports where practical.
- Permission names (`VIEW_PRODUCTS`, `GENERATE_PRODUCT_CONTENT`, …) — Plan + seed tasks.
