# Products module

ENTERPRISE Hex + **CQRS** (opt-in).

**CQRS justification:** async content-generation pipeline (CommandBus accept + BullMQ process) must be separated from catalog CRUD commands/queries — same pattern as Campaigns.

## Wave B scope

- Product catalog CRUD (soft-delete / restore / bulk / export)
- Thin `classroom` / `video_course` detail rows by type
- `POST /products/:id/generate-content` + BullMQ skeleton
- Seed outline parser → sessions / topics / draft scripts
- REST poll: `GET /products/:id/generations/:generationId`

## Soft-delete note

Catalog column `status` is draft/published/archived. Soft-delete visibility uses
`withTrashed` / `onlyTrashed` only (no `statusFlagShape`). Response exposes
`lifecycleStatus: active | suspended` derived from `deletedAt`.

## Deferred (Wave B+)

- Per-topic Tavily + Context7 + AI grounding
- Materials MD/PDF, ZIP package, WebSocket progress
- Invoice `productId` link
