# Astro ↔ Vidula Public CRM API

Contract for the Cloudflare Astro landing page against Laravel (`https://vidula.up.railway.app`).
Shapes match Scramble / Spatie Data ReadModels (`snake_case` JSON) unless noted.

**Live docs:** `http://localhost/docs/api` (OpenAPI: `/docs/api.json`)  
Production Scramble is **403** unless a `viewApiDocs` gate is configured — use this file as the landing contract when Scramble is locked down.

---

## Scramble audit (landing public API)

Reviewed against route files + `Public*` controllers (July 2026). **Verdict: the landing surface is coherent** — split between open GETs (cacheable) and CRM-token POSTs (server-only).

| Route | Method | Auth | Scramble | Response shape |
|-------|--------|------|----------|----------------|
| `/api/company-data/public` | GET | `crm.token` | Documented via `CompanyPublicReadModel` | Flat object |
| `/api/blog-categories/public` | GET | None | `JsonResponse` + `BlogCategoryPublicReadModel[]` in `data` | `{ data: [...] }` |
| `/api/posts/public` | GET | None | `PaginatedDataCollection<PostPublicReadModel>` | Laravel pagination |
| `/api/posts/public/{slug}` | GET | None | `PostPublicReadModel` | Flat object |
| `/api/portfolios/public` | GET | None | `PaginatedDataCollection<PortfolioPublicReadModel>` | Pagination + `gallery[]` |
| `/api/services/public` | GET | None | `JsonResponse` (Eloquent allowlist) | `{ data: [...] }` |
| `/api/contact-supports/public/honeypot` | GET | `crm.token` | `JsonResponse` (honeypot descriptor) | Flat object |
| `/api/contact-supports/public` | POST | `crm.token` | `ContactSupportData` body | `{ message }` 201 |
| `/api/appointments/public/honeypot` | GET | `crm.token` | Same as contact | Flat object |
| `/api/appointments/public` | POST | `crm.token` | `BookAppointmentData` body | `{ message }` 201 |

**Scramble caveat:** `config/scramble.php` marks routes **public** when they lack `auth` / `auth:sanctum`. Routes behind `crm.token` still appear **without** API-key security in OpenAPI — trust **this doc** and the auth matrix, not only Scramble’s lock icon. Optional follow-up: register a custom `securitySchemes` entry for `CRM_API_TOKEN` in Scramble.

**Not part of the landing API (do not use from Astro):**

| Missing on purpose | Use instead |
|--------------------|-------------|
| `GET /api/blog-categories/public/{uuid}` | Categories list + `posts?category_uuid=` |
| Nested `posts[]` inside category JSON | Second request to `/api/posts/public` |
| `GET /api/portfolios/public/{uuid}` | List with `per_page`; use item from `data[]` (includes `gallery`) |
| Post image gallery | Only `cover_image_url` + HTML in `content`; **gallery is portfolio-only** |
| `GET /api/blog-categories` (Sanctum) | `/api/blog-categories/public` |

**Minor inconsistency (OK for now):** `services/public` returns an Eloquent allowlist inside `{ data }`, not a Spatie ReadModel. Fields still match the table below; only Scramble typing is looser than posts/portfolio.

**Sanctum routes** under `/api/posts`, `/api/portfolios`, etc. are for the CRM app — not the marketing site.

---

## Landing UX: pages & fetches

Best UX = **SSG/SSR with server-side token**, few requests, no secrets in the browser.

### Page map

| Astro page / section | Fetches (server at build or request) | Token |
|----------------------|--------------------------------------|-------|
| **Layout** (header/footer/SEO) | `GET /api/company-data/public` | Yes |
| **Home — hero** | Company (layout) | — |
| **Home — portfolio teaser** | `GET /api/portfolios/public?per_page=6` | No |
| **Home — blog teaser** | `GET /api/posts/public?per_page=3` | No |
| **Home — services** (optional) | `GET /api/services/public` | No |
| **`/blog`** | `GET /api/blog-categories/public` + `GET /api/posts/public?per_page=12` | No |
| **`/blog?category=`** | Same + `GET /api/posts/public?category_uuid={uuid}` | No |
| **`/blog/[slug]`** | `GET /api/posts/public/{slug}` | No |
| **`/work` or home grid** | `GET /api/portfolios/public?per_page=20` | No |
| **Contact / book** | Honeypot GET + POST via **Astro API route** (proxy to Laravel) | Yes |

### Blog data flow (correct mental model)

```text
blog-categories/public  →  tabs, filters, posts_count only (NO posts array)
posts/public            →  cards (content = null, cover_image_url)
posts/public/{slug}     →  article (full content); identifier is slug, NOT category uuid
```

### Portfolio data flow

```text
portfolios/public  →  each item includes cover_url, video_url, tech_stack, gallery[]
                     (portfolio_media allowlist: uuid, url, sort_order)
```

### Forms (security)

```text
Browser  →  POST /api/contact (Astro server)  →  Laravel + CRM_API_TOKEN + honeypot
```

Never call `POST /api/contact-supports/public` or `POST /api/appointments/public` from client JS with the token.

### Suggested Astro structure

```text
src/
  layouts/Base.astro          # company fetch once → props
  lib/crm.ts                  # paths, crmHeaders(), publicHeaders()
  pages/
    index.astro
    blog/index.astro
    blog/[slug].astro
    api/contact.ts            # server POST proxy
    api/appointment.ts        # server POST proxy
```

Parallelize public GETs at build time; cache at Cloudflare (5–15 min) for open endpoints.

---

## Env (Astro / Cloudflare)

```env
# Public — origin only (no trailing slash)
PUBLIC_CRM_API_URL=https://vidula.up.railway.app

# Server-only — NEVER PUBLIC_ / NEVER ship to the browser
CRM_API_TOKEN=your-long-random-token
```

```ts
const base = import.meta.env.PUBLIC_CRM_API_URL; // https://vidula.up.railway.app
const token = import.meta.env.CRM_API_TOKEN;     // server only
```

---

## Auth matrix

| Endpoint | `CRM_API_TOKEN` | Throttle |
|----------|-----------------|----------|
| `GET /api/company-data/public` | **Required** | 60/min |
| `GET /api/contact-supports/public/honeypot` | **Required** | 30/min |
| `POST /api/contact-supports/public` | **Required** | 5/min |
| `GET /api/appointments/public/honeypot` | **Required** | 30/min |
| `POST /api/appointments/public` | **Required** | 5/min |
| `GET /api/blog-categories/public` | No | 120/min per route + IP |
| `GET /api/posts/public` | No | 120/min per route + IP |
| `GET /api/posts/public/{slug}` | No | 120/min per route + IP |
| `GET /api/portfolios/public` | No | 120/min per route + IP |
| `GET /api/services/public` | No | 120/min per route + IP |

### Token header (either)

```http
Authorization: Bearer {CRM_API_TOKEN}
X-CRM-Api-Token: {CRM_API_TOKEN}
```

Missing / wrong token → `401` `{ "message": "Unauthorized." }`

### Helper

```ts
function crmHeaders(): HeadersInit {
  return {
    Accept: 'application/json',
    'Content-Type': 'application/json',
    Authorization: `Bearer ${import.meta.env.CRM_API_TOKEN}`,
  };
}

function publicHeaders(): HeadersInit {
  return { Accept: 'application/json' };
}
```

---

## Endpoint catalog (types ↔ JSON)

| # | Method | Path | Request body | Success response type |
|---|--------|------|--------------|-------------------------|
| 1 | GET | `/api/company-data/public` | — | `CompanyPublic` |
| 2 | GET | `/api/blog-categories/public` | — | `BlogCategoryPublicResponse` |
| 3 | GET | `/api/posts/public` | — | `Paginated<PostPublic>` |
| 4 | GET | `/api/posts/public/{slug}` | — | `PostPublic` |
| 5 | GET | `/api/portfolios/public` | — | `Paginated<PortfolioPublic>` |
| 6 | GET | `/api/services/public` | — | `ServicePublicResponse` |
| 7 | GET | `/api/contact-supports/public/honeypot` | — | `HoneypotDescriptor` |
| 8 | POST | `/api/contact-supports/public` | `ContactSupportBody` + honeypot keys | `MessageResponse` (201) |
| 9 | GET | `/api/appointments/public/honeypot` | — | `HoneypotDescriptor` |
| 10 | POST | `/api/appointments/public` | `BookAppointmentBody` + honeypot keys | `MessageResponse` (201) |

**Errors (shared):**

```json
// 401 — missing/invalid CRM token (token-gated routes only)
{ "message": "Unauthorized." }

// 422 — validation (Laravel standard)
{
  "message": "The first name field is required. (and 2 more errors)",
  "errors": {
    "first_name": ["The first name field is required."],
    "email": ["The email field is required."]
  }
}

// 404 — post slug not found or not published
{ "message": "No query results for model ..." }
```

---

## Shared TypeScript types (`src/types/crm-api.ts`)

Single module for Astro — mirrors `CompanyPublicReadModel`, `BlogCategoryPublicReadModel`, `PostPublicReadModel`, `PortfolioPublicReadModel`, `PortfolioGalleryImageReadModel`, `ContactSupportData`, `BookAppointmentData`.

```ts
/** Laravel / Spatie `PaginatedDataCollection` envelope (posts + portfolios). */
export type Paginated<T> = {
  data: T[];
  links: {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
  };
  meta: {
    current_page: number;
    from: number | null;
    last_page: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
    path: string;
    per_page: number;
    to: number | null;
    total: number;
  };
};

export type CompanyPublic = {
  name: string;
  description: string | null;
  website: string | null;
  logo_url: string;
  logo_white_url: string;
  mark_url: string;
  email: string | null;
  phone: string | null;
  address: string | null;
  address_2: string | null;
  zip_code: string | null;
  city: string | null;
  state: string | null;
  country: string | null;
  country_code: string | null;
  latitude: number | null;
  longitude: number | null;
  /** Keys from CRM (e.g. linkedin, instagram, facebook, tiktok, twitter). */
  socials: Record<string, string>;
};

export type BlogCategoryPublic = {
  uuid: string;
  name: string | null;
  description: string | null;
  image_url: string | null;
  posts_count: number;
};

export type BlogCategoryPublicResponse = {
  data: BlogCategoryPublic[];
};

export type PostPublic = {
  uuid: string;
  title: string;
  slug: string;
  excerpt: string | null;
  content: string | null;
  cover_image_url: string | null;
  meta_title: string | null;
  meta_description: string | null;
  meta_keywords: string | null;
  category_uuid: string | null;
  category_name: string | null;
  published_at: string | null;
};

export type PortfolioGalleryImage = {
  uuid: string;
  url: string | null;
  sort_order: number;
};

export type PortfolioPublic = {
  uuid: string;
  title: string;
  client_name: string;
  project_type: string;
  tech_stack: string[];
  live_url: string | null;
  published_at: string | null;
  description: string | null;
  cover_url: string | null;
  video_url: string | null;
  sort_order: number;
  gallery: PortfolioGalleryImage[];
};

export type ServicePublic = {
  uuid: string;
  name: string;
  slug: string;
  description: string | null;
  sort_order: number;
};

export type ServicePublicResponse = {
  data: ServicePublic[];
};

/** spatie/laravel-honeypot `Honeypot::toArray()` — camelCase keys on the wire. */
export type HoneypotDescriptor = {
  nameFieldName: string;
  validFromFieldName: string;
  encryptedValidFrom: string;
};

export type MessageResponse = {
  message: string;
};

export type ContactSupportBody = {
  first_name: string;
  last_name: string;
  email: string;
  phone: string;
  subject: string;
  message: string;
  sms_consent?: boolean;
};

export type ClientType = 'company' | 'individual';

export type BookAppointmentBody = {
  first_name: string;
  last_name: string;
  client_type: ClientType;
  company_name?: string | null;
  /** UUID from `GET /api/services/public` (`data[].uuid`); must be an active service. */
  service_uuid?: string | null;
  email: string;
  phone?: string | null;
  address?: string | null;
  address_2?: string | null;
  zip_code?: string | null;
  city?: string | null;
  state?: string | null;
  country?: string | null;
  country_code?: string | null;
  latitude?: number | null;
  longitude?: number | null;
  scheduled_at: string;
  sms_consent?: boolean;
  notes?: string | null;
};

/** Dynamic honeypot field names — spread onto POST body with empty trap + encrypted timestamp. */
export type ContactSupportSubmitBody = ContactSupportBody &
  Record<string, string | boolean | undefined>;

export type BookAppointmentSubmitBody = BookAppointmentBody &
  Record<string, string | boolean | undefined>;
```

### Helpers (same file or `src/lib/crm.ts`)

```ts
function crmHeaders(): HeadersInit {
  return {
    Accept: 'application/json',
    'Content-Type': 'application/json',
    Authorization: `Bearer ${import.meta.env.CRM_API_TOKEN}`,
  };
}

function publicHeaders(): HeadersInit {
  return { Accept: 'application/json' };
}
```

---

## 1. Company data

### `GET /api/company-data/public`

- Auth: CRM token  
- Response: **flat object** (no `data` wrapper) — type `CompanyPublic` (see consolidated types above).

```ts
const company = await fetch(`${base}/api/company-data/public`, {
  headers: crmHeaders(),
}).then((r) => r.json()) as CompanyPublic;
```

Example JSON:

```json
{
  "name": "Vidula",
  "description": "...",
  "website": "https://...",
  "logo_url": "https://...",
  "logo_white_url": "https://...",
  "mark_url": "https://...",
  "email": "info@argenis.dev",
  "phone": "+351 ...",
  "address": "...",
  "address_2": null,
  "zip_code": "6200-386",
  "city": "Covilhã",
  "state": "Castelo Branco",
  "country": "Portugal",
  "country_code": "PT",
  "latitude": 40.2806,
  "longitude": -7.5049,
  "socials": {
    "instagram": "https://www.instagram.com/argenis.dev/",
    "tiktok": "https://www.tiktok.com/@argenisdev692?lang=es-419",
    "facebook": "https://www.facebook.com/argenisdev692/",
    "linkedin": "https://www.linkedin.com/in/argenisdev692/"
  }
}
```

Never returned: bank IBAN, NIF/NIE, invoice notes, `user_id`, signature paths.

---

## 2. Blog categories (public)

### `GET /api/blog-categories/public`

- Auth: **none**  
- Response: `{ data: BlogCategoryPublic[] }` (not paginated)  
- **Does not include:** nested posts, post bodies, or galleries — only **`posts_count`** (published posts in that category).

Use this for navigation, filters, or badges (`Engineering (3)`). Load posts with `/api/posts/public?category_uuid=…`.

```ts
const { data: categories } = await fetch(`${base}/api/blog-categories/public`, {
  headers: publicHeaders(),
}).then((r) => r.json()) as BlogCategoryPublicResponse;
```

Example JSON:

```json
{
  "data": [
    {
      "uuid": "019c3a12-0000-7000-8000-000000000001",
      "name": "Engineering",
      "description": "Technical articles",
      "image_url": "https://cdn.example/categories/engineering.webp",
      "posts_count": 3
    }
  ]
}
```

---

## 3. Posts (public)

### `GET /api/posts/public`

Query:

| Param | Type | Default | Notes |
|-------|------|---------|--------|
| `page` | int | 1 | |
| `per_page` | int | 15 | capped 1–100 |
| `category_uuid` | uuid | — | filter by blog category |

- Auth: **none**  
- List: `content` is always `null` (bandwidth) — type `Paginated<PostPublic>`.

```ts
const posts = await fetch(
  `${base}/api/posts/public?per_page=12&category_uuid=${categoryUuid ?? ''}`,
  { headers: publicHeaders() },
).then((r) => r.json()) as Paginated<PostPublic>;
```

Example JSON (list):

```json
{
  "data": [
    {
      "uuid": "019c3a12-0000-7000-8000-000000000010",
      "title": "Full Article",
      "slug": "full-article",
      "excerpt": "Short teaser",
      "content": null,
      "cover_image_url": "https://cdn.example/posts/cover.webp",
      "meta_title": null,
      "meta_description": null,
      "meta_keywords": null,
      "category_uuid": "019c3a12-0000-7000-8000-000000000001",
      "category_name": "Engineering",
      "published_at": "2026-03-01T12:00:00+00:00"
    }
  ],
  "links": {
    "first": "https://vidula.up.railway.app/api/posts/public?page=1",
    "last": "https://vidula.up.railway.app/api/posts/public?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "links": [
      { "url": null, "label": "&laquo; Previous", "active": false },
      { "url": "https://vidula.up.railway.app/api/posts/public?page=1", "label": "1", "active": true },
      { "url": null, "label": "Next &raquo;", "active": false }
    ],
    "path": "https://vidula.up.railway.app/api/posts/public",
    "per_page": 15,
    "to": 1,
    "total": 1
  }
}
```

### `GET /api/posts/public/{slug}`

- Auth: **none**  
- Response: **flat** `PostPublic` (with `content` populated)  
- Identifier is **`slug`** (from the list), not category `uuid`.  
- Draft / missing → `404`  
- **No gallery:** images inside `content` HTML; list/detail expose only **`cover_image_url`**.

```ts
const post = await fetch(`${base}/api/posts/public/${slug}`, {
  headers: publicHeaders(),
}).then((r) => r.json()) as PostPublic;
```

Example JSON (detail — same keys as list, `content` filled):

```json
{
  "uuid": "019c3a12-0000-7000-8000-000000000010",
  "title": "Full Article",
  "slug": "full-article",
  "excerpt": "Short teaser",
  "content": "<p>The full body of the article.</p>",
  "cover_image_url": "https://cdn.example/posts/cover.webp",
  "meta_title": "Full Article | Vidula",
  "meta_description": "SEO description",
  "meta_keywords": "laravel, astro",
  "category_uuid": "019c3a12-0000-7000-8000-000000000001",
  "category_name": "Engineering",
  "published_at": "2026-03-01T12:00:00+00:00"
}
```

---

## 4. Portfolios + gallery media (public)

### `GET /api/portfolios/public`

Query: `page`, `per_page` (default 15, max 100)

- Auth: **none**  
- Only `is_public` portfolios  
- Includes **`gallery[]`** (`portfolio_media` relation allowlist)  
- **No public detail route** — for a case-study modal, use the row from this list (raise `per_page` at build time for small catalogs).

```ts
const portfolios = await fetch(`${base}/api/portfolios/public?per_page=20`, {
  headers: publicHeaders(),
}).then((r) => r.json()) as Paginated<PortfolioPublic>;
```

Example JSON (`data[0]` — pagination envelope same shape as posts):

```json
{
  "data": [
    {
      "uuid": "019c3a12-0000-7000-8000-000000000020",
      "title": "Public Project",
      "client_name": "Acme",
      "project_type": "web",
      "tech_stack": ["React", "Next.js", "PostgreSQL", "Stripe"],
      "live_url": "https://acme.example",
      "published_at": "2026-01-15T10:00:00+00:00",
      "description": "Case study summary",
      "cover_url": "https://r2.example/portfolios/cover.webp",
      "video_url": null,
      "sort_order": 0,
      "gallery": [
        {
          "uuid": "019c3a12-0000-7000-8000-000000000021",
          "url": "https://r2.example/portfolios/gallery/a.jpg",
          "sort_order": 0
        }
      ]
    }
  ],
  "links": { "first": "…", "last": "…", "prev": null, "next": null },
  "meta": { "current_page": 1, "per_page": 15, "total": 1, "last_page": 1, "from": 1, "to": 1, "path": "…", "links": [] }
}
```

Never returned: `id`, `user_id`, `path` (R2 key), author relation.

---

## 5. Services (public)

### `GET /api/services/public`

- Auth: **none**  
- Active services only (`is_active`); not paginated  
- Response: `{ data: ServicePublic[] }` — Eloquent allowlist (`uuid`, `name`, `slug`, `description`, `sort_order`); `id` hidden  
- Scramble may show extra model fields in schema; runtime JSON matches `ServicePublic` below.

```ts
const { data: services } = await fetch(`${base}/api/services/public`, {
  headers: publicHeaders(),
}).then((r) => r.json()) as ServicePublicResponse;
```

Example JSON:

```json
{
  "data": [
    {
      "uuid": "019c3a12-0000-7000-8000-000000000030",
      "name": "Web Development",
      "slug": "web-development",
      "description": "Custom sites and apps",
      "sort_order": 0
    }
  ]
}
```

Use for appointment / contact `<select>` options.

---

## 6. Contact support (CRM token)

### `GET /api/contact-supports/public/honeypot`

```ts
const honeypot = await fetch(`${base}/api/contact-supports/public/honeypot`, {
  headers: crmHeaders(),
}).then((r) => r.json()) as HoneypotDescriptor;
```

Example JSON (field names are **camelCase**; POST body uses **dynamic keys** from this object):

```json
{
  "nameFieldName": "my_name",
  "validFromFieldName": "valid_from",
  "encryptedValidFrom": "eyJpdiI6..."
}
```

On submit, add `[nameFieldName]: ""` and `[validFromFieldName]: encryptedValidFrom` to the JSON body (see tests — e.g. `my_name` / `valid_from`).

### `POST /api/contact-supports/public`

Request body (`ContactSupportBody` + honeypot keys) — **snake_case**:

```json
{
  "first_name": "Ada",
  "last_name": "Lovelace",
  "email": "ada@example.com",
  "phone": "+15551234567",
  "subject": "Billing question",
  "message": "I need help with my invoice.",
  "sms_consent": true,
  "my_name": "",
  "valid_from": "eyJpdiI6..."
}
```

Success **201** (`MessageResponse`):

```json
{
  "message": "Thanks! Your message has been received. We will get back to you shortly."
}
```

(Honeypot tripped → same **201** and message, row not stored.)

---

## 7. Appointment booking (CRM token)

### `GET /api/appointments/public/honeypot`

Same response as contact — `HoneypotDescriptor` (camelCase JSON).

### `POST /api/appointments/public`

Request body (`BookAppointmentBody` + honeypot) — **snake_case**:

```json
{
  "first_name": "Ada",
  "last_name": "Lovelace",
  "client_type": "individual",
  "company_name": null,
  "service_uuid": "019c3a12-0000-7000-8000-000000000030",
  "email": "ada@example.com",
  "phone": "+15551234567",
  "address": null,
  "address_2": null,
  "zip_code": null,
  "city": null,
  "state": null,
  "country": null,
  "country_code": null,
  "latitude": null,
  "longitude": null,
  "scheduled_at": "2026-12-11 10:00:00",
  "sms_consent": true,
  "notes": "Looking forward to it.",
  "my_name": "",
  "valid_from": "eyJpdiI6..."
}
```

| Field | Rules |
|-------|--------|
| `client_type` | `company` \| `individual` |
| `company_name` | Required when `client_type` is `company` |
| `service_uuid` | Optional UUID from `GET /api/services/public` (`data[].uuid`). Must reference an **active** service; backend stores `service_id` internally. |
| `country_code` | 2-letter uppercase when present |
| `scheduled_at` | Required; must be valid slot (not past / closed / taken) → else **422** on `scheduled_at` or `email` (duplicate active lead) |

Success **201** (`MessageResponse`):

```json
{
  "message": "Thanks! Your appointment request has been received."
}
```

```ts
const res = await fetch(`${base}/api/appointments/public`, {
  method: 'POST',
  headers: crmHeaders(),
  body: JSON.stringify({
    first_name: 'Ada',
    last_name: 'Lovelace',
    client_type: 'individual',
    service_uuid: services[0].uuid,
    email: 'ada@example.com',
    phone: '+15551234567',
    scheduled_at: '2026-12-11 10:00:00',
    sms_consent: true,
    [honeypot.nameFieldName]: '',
    [honeypot.validFromFieldName]: honeypot.encryptedValidFrom,
  }),
});
```

---

## Quick fetch map (copy into Astro)

```ts
const API = import.meta.env.PUBLIC_CRM_API_URL;

// Open GETs (SSG / SSR cache-friendly — no token)
export const paths = {
  blogCategories: `${API}/api/blog-categories/public`,
  posts: `${API}/api/posts/public`,
  post: (slug: string) => `${API}/api/posts/public/${slug}`,
  portfolios: `${API}/api/portfolios/public`,
  services: `${API}/api/services/public`,

  // CRM-token GETs / POSTs (server only)
  company: `${API}/api/company-data/public`,
  contactHoneypot: `${API}/api/contact-supports/public/honeypot`,
  contactSubmit: `${API}/api/contact-supports/public`,
  appointmentHoneypot: `${API}/api/appointments/public/honeypot`,
  appointmentBook: `${API}/api/appointments/public`,
} as const;
```

---

## Status codes cheat sheet

| Code | When |
|------|------|
| 200 | Successful GET |
| 201 | Contact / appointment accepted |
| 401 | Missing/invalid `CRM_API_TOKEN` |
| 404 | Post slug not found / not published |
| 422 | Validation (fields, schedule, duplicate appointment email) |
| 429 | Throttle |

---

## Source of truth in Laravel

| Concern | Location |
|---------|----------|
| Company | `CompanyPublicReadModel` · route `api.company-data.public` |
| Blog categories | `BlogCategoryPublicReadModel` · `api.blog-categories.public` |
| Posts | `PostPublicReadModel` · `api.posts.public` / `api.posts.public.show` |
| Portfolios + media | `PortfolioPublicReadModel` + `PortfolioGalleryImageReadModel` · `api.portfolios.public` |
| Services | `ListPublicServicesHandler` + Eloquent select allowlist · `api.services.public` |
| Contact body | `ContactSupportData` · `api.contact-supports.public` |
| Appointment body | `BookAppointmentData` · `api.appointments.public` |
| Token middleware | `EnsureCrmApiToken` · alias `crm.token` |

Scramble UI: `/docs/api` (local). OpenAPI JSON: `/docs/api.json`.

### Checklist before shipping Astro

- [ ] `CRM_API_TOKEN` only in Cloudflare **server** env (not `PUBLIC_*`)  
- [ ] Layout loads company once; blog uses **two-step** category + posts  
- [ ] Article URLs use post **`slug`**  
- [ ] Portfolio lightbox uses **`gallery`** from list payload  
- [ ] Contact/appointment POSTs go through Astro server routes  
- [ ] Compare responses to this doc if Scramble shows CRM routes as “unauthenticated”
