# Specification: Product Content Generator (Classroom & Video)

> Phase 1 · SPECIFY — Defines WHAT is built and WHY. No technical stack here.

**Feature ID:** 004-product-content-generator
**Date:** 2026-07-27
**Status:** Approved for Plan — Clarify resolved 2026-07-27 (defaults accepted)

**Related existing work:**
- Source notes: `docs/MODULE-PRODUCTS/` (`phases.md`, `PHASES.txt`, `PHASE-2-ANALYSIS.md`, `tablas-nuevas.md`, sample CLASSROOM / VIDEOS materials). Note: `docs/GUIDE/MODULE-PRODUCTS` was not found; analysis used `docs/MODULE-PRODUCTS`.
- Already shipped in VIDULA: **Clients**, **Students**, **Invoices** modules (Phase 1 CRM/billing foundation).
- Draft migrations / analysis for products, sessions, topics, materials, classrooms, video courses, and invoice line items that can reference products.

## 1. Summary

Build a **Product Content Generator** that turns a **markdown course/video index** into a complete, billable educational product package: sessions, topics/chapters, grounded scripts (guiones), supporting materials, and downloadable Markdown + PDF artifacts — for **classroom** or **video** products depending on the selected product type. The operator can review, refine, and download everything as a single archive for delivery to the client.

## 2. Motivation / Business context

Today, course and video packages for clients (e.g. Imagina Formación) are assembled by hand: parse an index, write guiones per topic, gather materials, export MD/PDF, and zip the deliverable. That is slow, inconsistent, and easy to drift from the agreed index. Without this module, each new classroom or video product remains a manual content factory. With it, the owner seeds one index, gets a full tree of sessions/topics/scripts/materials that stay consistent with the index, and ships a downloadable package — while still billing the client through the existing commerce path.

## 3. Actors

- **Content operator (primary user):** creates products, uploads/pastes an index, launches generation, reviews scripts/materials, edits drafts, and downloads the package.
- **Instructor / presenter:** consumes guiones and materials to teach a classroom or record videos (may be the same person as the operator in v1).
- **Client (billing party):** organization that pays for the product (e.g. training company); receives invoices, not necessarily in-app access in v1.
- **Student (consumer):** learner enrolled in classrooms later; out of scope for generation itself but already exists as master data.
- **System job runner:** advances long-running content generation and notifies when packages are ready.

## 4. User stories

### US-1: Create and select a product by type (Priority: High)
**As a** content operator, **I want** to create a product as **classroom** or **video** (and optionally related video variants), **so that** generation produces the right kind of scripts and materials for that delivery format.

**Acceptance criteria:**
- [ ] Given I create a product with type classroom, when I open generation, then the system expects a classroom-style index (sessions → topics) and classroom-oriented materials.
- [ ] Given I create a product with type video (tutorial or pill set), when I open generation, then the system expects a video-style index (blocks → numbered videos/topics) and produces per-topic recording scripts (intro/body/outro/notes).
- [ ] Given a product type that has no content tree (e.g. pure services, if enabled), when I start generation, then the system blocks the run with a clear message.

### US-2: Seed generation from a markdown index (Priority: High)
**As a** content operator, **I want** to paste or upload a markdown index for the product, **so that** the system extracts only the outline (sessions/blocks and topic titles) as the seed for everything else.

**Acceptance criteria:**
- [ ] Given a valid classroom index (session headings + topic bullets), when I submit, then the system stores the seed outline and starts an async generation job.
- [ ] Given a valid video/pills index (blocks + video table/detail sections), when I submit, then the system stores the seed outline and starts an async generation job.
- [ ] Given empty or unparseable markdown, when I submit, then validation fails with a clear error and no job is created.
- [ ] Given generation is already in progress for that product, when I submit again, then the system rejects the duplicate run.

### US-3: Generate the full content tree with grounded scripts (Priority: High)
**As a** content operator, **I want** the system to generate sessions, topics, and full scripts for every topic/chapter, **so that** I do not write guiones by hand while still matching the index.

**Acceptance criteria:**
- [ ] Given a successful generation run for a video product, when complete, then every topic has a script with intro, body, outro, and presenter notes (or an explicit needs-review status).
- [ ] Given a successful generation run for a classroom product, when complete, then every topic has teaching content/material suitable for classroom delivery (and optional script-like notes if applicable).
- [ ] Given generation, when producing a topic, then content is grounded with **current web research** and **current library/documentation lookup** for libraries named in the topic, and cited sources are retained for traceability (“que todo coincida”).
- [ ] Given external research/docs lookup fails for a topic, when generation continues, then that topic is marked needs-review rather than aborting the whole job.
- [ ] Given generation finishes, when I inspect the tree, then session/topic titles match the seed index (consistency check); drifted items are flagged for review.

### US-4: Persist materials as Markdown and PDF (Priority: High)
**As a** content operator, **I want** generated course packages stored as Markdown and PDF materials, **so that** I can download, edit offline, and replace the stored versions.

**Acceptance criteria:**
- [ ] Given a completed generation, when I open materials, then I can access at least a course-level Markdown and a course-level PDF (plus per-topic materials/scripts when generated).
- [ ] Given a material, when I download it, then I receive the current file via a secure, time-limited access path.
- [ ] Given I edited a Markdown or PDF locally, when I replace the material, then only Markdown/PDF replacements are accepted and the previous version is superseded.
- [ ] Given generation, when materials are created, then no video binary upload is required or accepted as input.

### US-5: Download the full deliverable as one archive (Priority: High)
**As a** content operator, **I want** to download all generated scripts and materials for a product as a single archive, **so that** I can deliver the package to the client or use it offline for recording/teaching.

**Acceptance criteria:**
- [ ] Given a product with completed generation, when I request a package download, then I receive one archive containing the organized MD/PDF (and scripts) tree.
- [ ] Given generation is incomplete or failed, when I request a package download, then the system blocks or clearly labels partial content.
- [ ] Given a large package, when packaging runs, then the user sees progress or is notified when the archive is ready. [NEEDS CLARIFICATION: ZIP vs RAR — see §9]

### US-6: Review and edit scripts before delivery (Priority: High)
**As a** content operator, **I want** to review and edit guiones/materials after AI generation, **so that** the final package is human-approved.

**Acceptance criteria:**
- [ ] Given generated scripts, when I open a topic, then I can edit intro/body/outro/notes and change status (draft / generated / verified / needs_review / recorded as applicable).
- [ ] Given I mark a script verified, when I later regenerate, then replace-vs-append behavior is explicit and confirmed. [NEEDS CLARIFICATION: default mode append vs replace]

### US-7: Bill the product to a client (Priority: Medium)
**As a** content operator, **I want** to associate a product with a client and invoice it, **so that** classroom/video work is billed through the commerce flow without a parallel billing inventario.

**Acceptance criteria:**
- [ ] Given a client and a product, when I create an invoice line, then the line can reference that product and snapshot its description/price.
- [ ] Given the existing Invoices module, when products become billable, then either the same invoice flow is extended **or** a products-specific invoice path is chosen — not both silently. [NEEDS CLARIFICATION: reuse vs special — see §9]
- [ ] Given a product without a client, when I still generate content, then generation works; billing remains optional until invoicing.

### US-8: Track generation progress (Priority: Medium)
**As a** content operator, **I want** to see generation status and progress, **so that** I know when the package is ready.

**Acceptance criteria:**
- [ ] Given I started generation, when I poll/open the product, then I see status (queued → parsing → generating → verifying → rendering → completed/failed) and progress.
- [ ] Given failure, when I open the generation record, then I see a clear error summary without leaking secrets or internal paths to unauthorized users.

### US-9: Manage product catalog CRUD (Priority: Medium)
**As a** content operator, **I want** basic product CRUD (list, create, update, soft-delete, restore, bulk actions) with permissions, **so that** products are first-class catalog entities beside clients and students.

**Acceptance criteria:**
- [ ] Given permission to manage products, when I list products, then I can filter by type, status, client, and search title.
- [ ] Given insufficient permission, when I attempt mutations, then the system denies the action.

## 5. Functional requirements

- **FR-1**: The system MUST support product types that drive generation behavior at least for **classroom** and **video** deliverables.
- **FR-2**: The system MUST accept a markdown index as the only required seed for classroom/video content generation (no video file upload).
- **FR-3**: The system MUST create a structured tree of sessions (or blocks) and topics/chapters from the seed.
- **FR-4**: The system MUST generate scripts and/or teaching materials for **all** topics in the seed, adapted to the selected product type.
- **FR-5**: The system MUST ground generated content with current web research and current library/documentation verification, and retain source citations per topic/script.
- **FR-6**: The system MUST run generation asynchronously and expose progress/status to the operator.
- **FR-7**: The system MUST render and store course artifacts as Markdown and PDF materials suitable for download and replace.
- **FR-8**: The system MUST allow downloading a single archive of the product’s generated deliverables.
- **FR-9**: The system MUST allow human review/edit of generated scripts and materials before considering them delivery-ready.
- **FR-10**: The system MUST allow associating products with clients and invoicing products as line items (exact invoice module strategy TBD — §9).
- **FR-11**: The system MUST authorize every product/generation/material/invoice action with authentication and permission checks.
- **FR-12**: The system MUST NOT accept or require video binary uploads as generation input.
- **FR-13**: The system MUST NOT invent library APIs or course structure that contradict the seed index without flagging needs-review.
- **FR-14**: The system MUST enforce one in-flight generation per product (reject concurrent runs).
- **FR-15**: The system MUST degrade gracefully when research or docs lookup fails (mark topic needs-review; do not wipe completed topics).

## 6. Non-functional requirements

- **Performance**: Generation is long-running (minutes for multi-topic courses); the interactive start request MUST return quickly (accepted job). Package download of a finished archive SHOULD complete within a reasonable window for typical course sizes (&lt; few hundred MB of docs).
- **Security**: Secrets for AI/research/docs providers never exposed to the client; materials accessed via authorized/signed paths; uploads limited to allowed document types/sizes; generation inputs size-capped to prevent abuse.
- **Availability**: External research/docs/AI outages MUST not corrupt the product catalog; failed jobs are recorded and retryable.
- **Scalability**: Expect tens of products and generations per month initially; design for concurrent jobs without blocking the app UI.
- **Compliance**: Client/student PII already in CRM must not be injected into generation prompts unnecessarily; audit meaningful content/generation actions without logging secrets or full prompt dumps with PII.
- **Traceability**: Every generated topic/script SHOULD retain cited sources so the operator can verify alignment with the index and current docs.

## 7. Data entities (conceptual, not a physical schema)

- **Product**: billable catalog item; type (classroom / video / …); title; status; optional client; delivery metadata (dates, hours, modality when relevant).
- **Classroom detail**: thin classroom-specific attributes for classroom products (capacity, meet link, objectives…).
- **Video course detail**: thin video-specific attributes (platform, playlist, total videos/duration…).
- **Product session / block**: ordered unit under a product (classroom session or video block).
- **Product session topic / chapter**: ordered topic under a session; seed title + generated content.
- **Product script (guion)**: per-topic recording/teaching script (intro/body/outro/notes), status, estimated duration, source citations.
- **Product material**: Markdown, PDF, or link attached to product and/or topic; downloadable; replaceable.
- **Content generation**: one generation run for a product (seed markdown, status, progress, counts, artifact paths, errors).
- **Client** (existing): billing party linked to products/invoices.
- **Student** (existing): learner master data (used later for enrollments; not required for generation).
- **Invoice / Invoice item** (existing module): commercial document; items may reference products. [NEEDS CLARIFICATION: extend vs fork]

## 8. Out of scope

- Uploading or hosting mp4/video binaries as course content.
- Live video recording, editing, or publishing to YouTube/Vimeo from this module.
- Classroom enrollments, attendance, and grades (Phase 3 academic operations) — deferred unless explicitly pulled into this feature.
- Automatic emailing of packages to clients.
- Building a separate “products-only” CRM for clients/students (reuse existing modules).
- Full public LMS learner portal.
- Auto-sending invoices or payment gateway capture beyond what Invoices already supports.
- Dev-service product type workflows (website/landing/etc.) unless confirmed in Clarify — generation does not apply to them.

## 9. Assumptions and open decisions

- Assumption: Phase 1 CRM entities (**Clients**, **Students**) and the **Invoices** module already exist and remain the source of truth for people and billing.
- Assumption: Content model follows the **unified** sessions → topics → materials/scripts tree (not the older split classroom_* / video_* trees in `phases.md`), per `PHASE-2-ANALYSIS.md`.
- Assumption: Operator workflow is **AI draft → human edit → download package**; AI output is never final without human ownership.
- **Resolved (Clarify Q1):** Reuse/extend existing **Invoices** — optional product reference on invoice line items (alongside services). No special products invoice module.
- **Resolved (Clarify Q2):** Deliverable archive is **ZIP only** (no RAR).
- **Resolved (Clarify Q3):** v1 scope = products catalog + content generation + scripts + materials + MD/PDF + ZIP + invoice product link. Enrollments/attendance/grades deferred.
- **Resolved (Clarify Q4):** Product types in v1 = `classroom` | `video_tutorial` | `video_pill`. Generation allowed for all three. `service` deferred.
- **Resolved (Clarify Q5):** Grounding is **per-topic** (web research + library docs).
- **Resolved (Clarify Q6):** Video types use full intro/body/outro/notes scripts; classroom uses lesson content + presenter notes (+ materials).
- **Resolved (Clarify Q7):** Re-generation is **replace after confirmation**; preserve verified/recorded scripts unless force-replace.

## 10. Success criteria (measurable)

- From a valid markdown index, an operator can produce a complete topic tree with scripts/materials for a sample classroom course and a sample video/pills course (using the sample files under `docs/MODULE-PRODUCTS/CLASSROOM` and `VIDEOS` as acceptance fixtures).
- ≥ 90% of generated topics retain source citations when research/docs providers succeed.
- Consistency check flags any topic whose title drifts from the seed index.
- Operator can download one archive containing MD + PDF (+ scripts) for a completed product.
- No generation path requires video file upload.
- All product/generation/material endpoints are permission-gated; unauthorized users cannot download packages.
- Invoice path decision is resolved and products are billable without a duplicate billing catalog.
