---
trigger: always_on
---

# [ABSOLUTE] Non-negotiable constraints — ALWAYS apply

- **Language:** Respond in English at all times.
- **CLI:** Use `./vendor/bin/sail artisan` — NEVER bare `php`.
- **TypeScript:** Strict mode enforced on ALL `.tsx` / `.ts` files.
- **Context7 (MCP):** Always resolve live docs — never rely on cached training knowledge.
- **Investigate / Investigar:** Run Tavily search immediately before responding.

---

# [MUST] Before writing any code — read the relevant skill

| Task type             | Required reading                                  |
| --------------------- | ------------------------------------------------- |
| PHP / Laravel backend | `.agents/skills/ARCHITECTURE-INTERMEDIATE-PHP.md` |
| React / Inertia UI    | `.agents/skills/ARCHITECTURE-REACT-INERTIA.md`    |
| CSS / Styles / UI     | `.agents/skills/RULES-STYLES.md`                  |
| Full coding rules     | `.agents/skills/RULES-FULLSTACK.md`               |
| Business Logic / CRUD | `.agents/skills/HOW-TO-USE.md`                    |

> **Rule:** If a skill file covers the task, read it FIRST — no exceptions.

---

# [MUST] CSS / Styles

- Follow `.agents/skills/RULES-STYLES.md` strictly.
- NEVER hardcode hex, `bg-red-600`, or `bg-[#hex]`. Use `var(--token)` only.
- All tokens defined in `resources/css/app.css`.

---

# [MUST] React / TypeScript

- Follow `.agents/skills/ARCHITECTURE-REACT-INERTIA.md` and `.agents/skills/RULES-FULLSTACK.md`.
- No `any`. No `@ts-ignore`. No hardcoded colors in components.
- Every page wrapped in correct Layout. State always explicitly typed.

---

# [MUST] Laravel / PHP

- Follow `.agents/skills/RULES-FULLSTACK.md` and `.agents/skills/HOW-TO-USE.md`.
- No business logic in Controllers. No `php` bare CLI.
- Web routes = primary (Inertia + session). API routes = secondary (mobile/Sanctum only).

---

# [SHOULD] General quality

- Mobile-first on every UI component.
- `font-family: var(--font-sans)` everywhere.
- Prefer descriptive names over comments.
