# Astro ↔ Vidula Public CRM API

Contract for the Cloudflare Astro landing page against Laravel (`https://vidula.up.railway.app`).
Shapes match Scramble / Spatie Data ReadModels (`snake_case` JSON).

Live docs (local only by default): `http://localhost/docs/api`  
Production Scramble is **403** unless a `viewApiDocs` gate is configured.

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
| `GET /api/contact-supports/honeypot` | **Required** | 30/min |
| `POST /api/contact-supports` | **Required** | 5/min |
| `GET /api/appointments/honeypot` | **Required** | 30/min |
| `POST /api/appointments` | **Required** | 5/min |
| `GET /api/blog-categories/public` | No | 60/min |
| `GET /api/posts/public` | No | 60/min |
| `GET /api/posts/public/{slug}` | No | 60/min |
| `GET /api/portfolios/public` | No | 60/min |
| `GET /api/services/public` | No | 60/min |

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

## Shared TypeScript types

```ts
/** Laravel / Spatie paginated envelope (posts + portfolios lists). */
export type Paginated<T> = {
  data: T[];
  links?: {
    first?: string | null;
    last?: string | null;
    prev?: string | null;
    next?: string | null;
  };
  meta?: {
    current_page: number;
    from: number | null;
    last_page: number;
    path: string;
    per_page: number;
    to: number | null;
    total: number;
  };
};

export type HoneypotDescriptor = {
  nameFieldName: string;
  validFromFieldName: string;
  encryptedValidFrom: string;
};

export type MessageResponse = {
  message: string;
};
```

---

## 1. Company data

### `GET /api/company-data/public`

- Auth: CRM token  
- Response: **flat object** (no `data` wrapper)

```ts
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
  socials: Partial<{
    linkedin: string;
    twitter: string;
    instagram: string;
    facebook: string;
    tiktok: string;
  }>;
};
```

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

```ts
export type BlogCategoryPublic = {
  uuid: string;
  name: string | null;
  description: string | null;
  image_url: string | null;
  posts_count: number; // published posts only
};

export type BlogCategoryPublicResponse = {
  data: BlogCategoryPublic[];
};
```

```ts
const { data: categories } = await fetch(`${base}/api/blog-categories/public`, {
  headers: publicHeaders(),
}).then((r) => r.json()) as BlogCategoryPublicResponse;
```

Example:

```json
{
  "data": [
    {
      "uuid": "019...",
      "name": "Engineering",
      "description": "...",
      "image_url": "https://...",
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
- List: `content` is always `null` (bandwidth)

```ts
export type PostPublic = {
  uuid: string;
  title: string;
  slug: string;
  excerpt: string | null;
  content: string | null; // null on list; full HTML/Markdown on detail
  cover_image_url: string | null;
  meta_title: string | null;
  meta_description: string | null;
  meta_keywords: string | null;
  category_uuid: string | null;
  category_name: string | null;
  published_at: string | null; // ISO-8601
};
```

```ts
const posts = await fetch(
  `${base}/api/posts/public?per_page=12&category_uuid=${categoryUuid ?? ''}`,
  { headers: publicHeaders() },
).then((r) => r.json()) as Paginated<PostPublic>;
```

### `GET /api/posts/public/{slug}`

- Auth: **none**  
- Response: **flat** `PostPublic` (with `content` populated)  
- Draft / missing → `404`

```ts
const post = await fetch(`${base}/api/posts/public/${slug}`, {
  headers: publicHeaders(),
}).then((r) => r.json()) as PostPublic;
```

---

## 4. Portfolios + gallery media (public)

### `GET /api/portfolios/public`

Query: `page`, `per_page` (default 15, max 100)

- Auth: **none**  
- Only `is_public` portfolios  
- Includes `gallery` (`portfolio_media` relation allowlist)

```ts
export type PortfolioGalleryImage = {
  uuid: string;
  url: string | null;   // public R2 URL — never raw `path`
  sort_order: number;
};

export type PortfolioPublic = {
  uuid: string;
  title: string;
  client_name: string;
  project_type: string;
  tech_stack: string[];
  live_url: string | null;
  published_at: string | null; // ISO-8601
  description: string | null;
  cover_url: string | null;
  video_url: string | null;
  sort_order: number;
  gallery: PortfolioGalleryImage[];
};
```

```ts
const portfolios = await fetch(`${base}/api/portfolios/public?per_page=20`, {
  headers: publicHeaders(),
}).then((r) => r.json()) as Paginated<PortfolioPublic>;
```

Example item:

```json
{
  "uuid": "019...",
  "title": "Public Project",
  "client_name": "Acme",
  "project_type": "web",
  "tech_stack": ["React", "Next.js", "PostgreSQL"],
  "live_url": "https://...",
  "published_at": "2026-01-15T10:00:00+00:00",
  "description": "...",
  "cover_url": "https://r2.../cover.webp",
  "video_url": null,
  "sort_order": 0,
  "gallery": [
    {
      "uuid": "019...",
      "url": "https://r2.../gallery/a.jpg",
      "sort_order": 0
    }
  ]
}
```

Never returned: `id`, `user_id`, `path` (R2 key), author relation.

---

## 5. Services (public)

### `GET /api/services/public`

- Auth: **none**  
- Active services only; not paginated  
- Response: `{ data: ServicePublic[] }`  
- `id` is hidden

```ts
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
```

```ts
const { data: services } = await fetch(`${base}/api/services/public`, {
  headers: publicHeaders(),
}).then((r) => r.json()) as ServicePublicResponse;
```

Use for appointment / contact `<select>` options.

---

## 6. Contact support (CRM token)

### `GET /api/contact-supports/honeypot`

```ts
const honeypot = await fetch(`${base}/api/contact-supports/honeypot`, {
  headers: crmHeaders(),
}).then((r) => r.json()) as HoneypotDescriptor;
```

### `POST /api/contact-supports`

Body (snake_case):

```ts
export type ContactSupportBody = {
  first_name: string;   // required, max 255
  last_name: string;    // required, max 255
  email: string;        // required, email
  phone: string;        // required, max 20
  subject: string;      // required, max 150
  message: string;      // required, max 5000
  sms_consent?: boolean;
  // Optional honeypot echo (field names from honeypot descriptor):
  // [nameFieldName]: ''  (must stay empty)
  // [validFromFieldName]: encryptedValidFrom
};
```

```ts
const res = await fetch(`${base}/api/contact-supports`, {
  method: 'POST',
  headers: crmHeaders(),
  body: JSON.stringify({
    first_name: 'Ada',
    last_name: 'Lovelace',
    email: 'ada@example.com',
    phone: '+15551234567',
    subject: 'Billing question',
    message: 'I need help with my invoice.',
    sms_consent: true,
    [honeypot.nameFieldName]: '',
    [honeypot.validFromFieldName]: honeypot.encryptedValidFrom,
  }),
});
// 201 → { message: string }
// Tripped honeypot → still 201, nothing stored
// Validation → 422
```

---

## 7. Appointment booking (CRM token)

### `GET /api/appointments/honeypot`

Same shape as contact honeypot.

### `POST /api/appointments`

```ts
export type ClientType = 'company' | 'individual';
export type ProjectType =
  | 'new_website'
  | 'redesign'
  | 'ecommerce'
  | 'landing_page'
  | 'maintenance'
  | 'other';

export type BookAppointmentBody = {
  first_name: string;                 // required
  last_name: string;                  // required
  client_type: ClientType;            // required
  company_name?: string | null;       // required_if client_type=company
  project_type?: ProjectType | null;
  email: string;                      // required (normalized to lowercase)
  phone?: string | null;
  address?: string | null;
  address_2?: string | null;
  zip_code?: string | null;
  city?: string | null;
  state?: string | null;
  country?: string | null;
  country_code?: string | null;       // exactly 2 uppercase letters
  latitude?: number | null;           // -90..90
  longitude?: number | null;          // -180..180
  scheduled_at: string;               // required, parseable date (e.g. "2026-12-11 10:00:00")
  sms_consent?: boolean;
  notes?: string | null;              // max 5000
  // + optional honeypot fields (same pattern as contact)
};
```

```ts
const res = await fetch(`${base}/api/appointments`, {
  method: 'POST',
  headers: crmHeaders(),
  body: JSON.stringify({
    first_name: 'Ada',
    last_name: 'Lovelace',
    client_type: 'individual',
    company_name: null,
    project_type: 'new_website',
    email: 'ada@example.com',
    phone: '+15551234567',
    scheduled_at: '2026-12-11 10:00:00',
    sms_consent: true,
    notes: 'Looking forward to it.',
    [honeypot.nameFieldName]: '',
    [honeypot.validFromFieldName]: honeypot.encryptedValidFrom,
  }),
});
// 201 → { message: string }
// Past / closed / conflict / duplicate email → 422
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
  contactHoneypot: `${API}/api/contact-supports/honeypot`,
  contactSubmit: `${API}/api/contact-supports`,
  appointmentHoneypot: `${API}/api/appointments/honeypot`,
  appointmentBook: `${API}/api/appointments`,
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
| Company | `CompanyPublicReadModel` |
| Blog categories | `BlogCategoryPublicReadModel` |
| Posts | `PostPublicReadModel` |
| Portfolios + media | `PortfolioPublicReadModel` + `PortfolioGalleryImageReadModel` |
| Contact body | `ContactSupportData` |
| Appointment body | `BookAppointmentData` |
| Token middleware | `EnsureCrmApiToken` (`crm.token`) |

Scramble UI: `/docs/api` (local). OpenAPI JSON: `/docs/api.json`.
