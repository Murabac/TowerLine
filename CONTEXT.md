# TowerLine — Project Context

**Working name:** TowerLine  
**Official product name:** Wasaaradda Isgaarsiinta iyo Technology — Tower Management & Monitoring System  
**Client:** Ministry of Communication and Information Technology (MoCIT), Somaliland  
**Languages:** English (default) + Somali toggle  
**Status:** Phase 3 complete — **Week 29 done** (Sep 2026). Phase 2 complete through Week 23.  
**Current week:** **Week 29 complete — Integration, tests, docs**  
**Last completed:** **Week 29**  
**Source of truth:** this file. Update it when a product decision changes. Phase 2 week notes: `MINISTRY-PHASE2-PLAN.md`. Phase 3 (public site applications): `MINISTRY-PHASE3-PLAN.md`.

---

## 1. What we are building

A government registry, map, monitoring, and compliance system for telecom **and broadcast (TV/cable)** towers across Somaliland.

Ministry staff need one place to:

- See every tower on a map, filtered by region, operator, status, and license state
- Keep a structured registry (location, operator, type, height, capacity, status)
- Record field inspections (power, generator/battery, physical condition, photos, notes)
- Track licenses (types A, B, C) and renewal dates on the dashboard
- Restrict access by role (ministry admin, regional inspector, operator viewer)

The **map dashboard is the flagship**. It must work for daily internal use, live briefings, and a printable/exportable snapshot.

This is **not** a consumer product. Official, bilingual, usable on tablet/phone in the field with poor connectivity.

---

## 2. Source documents

| File | Role |
|---|---|
| `tower-management-system-plan.md` | Original 12-week delivery plan |
| `Tower Mangement Design refrence.md` | Build spec (stack, branding, schema, screens) |
| `wasaarada logo.jpg` | Official Somaliland emblem — use in header / login |
| `CONTEXT.md` (this file) | Locked decisions + implementation guide |
| `MINISTRY-PHASE2-PLAN.md` | Phase 2 roadmap from MoCIT meeting (Aug 2026) — weeks 13–23 |
| `HANDOVER.md` | Staff training, pilot steps, local/server runbook |
| `MINISTRY-PHASE3-PLAN.md` | Phase 3 site-registration portal — weeks 24–29 |

Build from the design reference + this file. No separate Figma phase — the app **is** the design.

---

## 3. Locked decisions (answered 19 Aug 2026)

| Topic | Decision |
|---|---|
| Tower inventory | Real list comes later. Demo uses sample data only. |
| Operators | **Telecom:** Telesom, Somtel, Somcable. **Broadcast:** Truecable, Astaan, Horncable |
| Regions | Awdal, Maroodi Jeex, Sahil, Togdheer, Sanaag, Sool |
| Inspectors | Ministry members (MoCIT staff), assigned **one or more regions** |
| Hosting | Not decided; likely **government server**. Keep standard Laravel (Apache/Nginx + PHP + MySQL). No Redis required. |
| Map success | Daily use **and** live briefings **and** snapshot/print |
| Default language | **English** (`en`). Somali available via toggle. |
| Logo | `wasaarada logo.jpg` (Somaliland emblem) |
| Design | Implement the design-reference prompt directly — no Figma wait |
| Scale | Production will be **hundreds** of towers. Demo seed: **3–5 towers per region** |
| License types | **A, B, C** only |
| Renewal reminders | **Dashboard only** (no email/SMS in v1) |
| Inspector home | Map (flagship for field staff) |
| Operator home | Map (their towers only) |
| Admin home | Dashboard |
| Offline | Graceful POST forms, not a service-worker offline app |
| Coverage circles | Colored by **operator** |
| Auth registration | Disabled. Admins create users. |

---

## 3b. Phase 2 locked decisions (MoCIT, Aug–Sep 2026)

Full catalogue and week notes: `MINISTRY-PHASE2-PLAN.md`.

| Topic | Decision |
|---|---|
| Geography | Region → district → sub-district. Interim 102 districts from Law 23/2019 until MoCIT official list arrives |
| Power source | Towers store **multiple** sources (grid, generator, battery, solar, …) |
| Inspections | Most fields **optional**; comment always available; incomplete visits do not over-penalize health |
| Build approval letter | Per tower, ministry template, **no expiry**. Legacy A/B/C licences kept for reference |
| Frequency | Band/range per operator, annual renewal, letters and receipts, expiry on dashboard |
| Operations manager | Ministry-wide access **except** user admin, audit log, and settings. Can approve inspector work |
| Inspector writes | New towers, tower edits, and inspections **wait for approval** before they go live |
| Custom roles | Admins tick tasks. Demo includes **Regional analyst** (map + reports, Maroodi Jeex) |
| Regional operators | Operator can be national or limited to one or more regions |
| Map operator colors | Fixed color-blind-safe palette; pins can colour by operator or by status |
| Reports | Print/PDF + Excel. Admin and ops see the full centre. Inspectors see a region-scoped subset. Audit report **admin only** |
| Help in sidebar | Hidden for now; `/help` still works |

---

## 3c. Phase 3 locked decisions (MoCIT, Sep 2026)

Full week notes: `MINISTRY-PHASE3-PLAN.md`.

| Topic | Decision |
|---|---|
| Site application | Public form, **no login**. Ministry staff process the file. |
| Inspector queue | Unchanged — still for field edits/inspections on **live** towers |
| After DG grant | Create live tower **and** issue build-approval letter |
| Guidelines | Public `/guidelines` window + official PDF `resources/docs/Xeer-Nidaamiyaha Goobaha Isgaadhsiinta.pdf` (Xeer-Nidaamiye Lr.02/2019) |
| Public apply | `/apply` — no login; five compulsory uploads; tracking number on screen (`MoCIT/APP/{year}/0001`) |
| HQ roles | Section Head, Assigned Officer, Department Director, Director General — seeded demo users |
| Regional coordinator | Region-scoped assigned officer. Section Head assigns a file to the coordinator who covers that region. |
| Staff inbox | `/applications` — Section Head assigns; coordinator/officer records a site visit then approves to the Department Director or returns the file. Director Yes/No. DG grants a live tower + letter or returns to the Director. Director No closes the file. Officer, Director, and DG each draw or reuse a saved signature; the trail stays on the file, tower, and printed letter. |

---

## 4. Goals (demo / v1)

1. Full-screen Leaflet map: color-coded, filterable, clustered, coverage circles, print/snapshot
2. Tower registry CRUD with search/filter/pagination
3. Inspection forms as standard HTML POST
4. License list + 30-day expiry **banner on dashboard**
5. Three roles with Laravel policies
6. Bilingual UI via `lang/en` and `lang/so`
7. Audit log
8. Sample seed: 6 regions, 6 operators, 3–5 towers each, mixed health/licenses

### Out of v1

- Live SNMP / operator API feeds
- SMS / email reminders
- Public-facing coverage map
- Native mobile app
- React/Vue SPA
- Import of the real tower spreadsheet (later)

---

## 5. Users and roles

Inspectors are **ministry employees**, not contractors.

| Role | Key | Access |
|---|---|---|
| Ministry admin | `admin` | Everything |
| Operations manager | `operations_manager` | Ministry-wide registry, letters, frequencies, reports, and approvals. **No** users, audit log, or settings |
| Regional inspector | `inspector` | View/edit towers and submit inspections **in one or more assigned regions**. Changes wait for approval. Map scoped. No user admin |
| Regional analyst | `regional_analyst` (custom) | Seeded example: map + reports for assigned region(s) |
| Operator viewer | `operator_viewer` | **Disabled for now** (login blocked). Policies remain for a later phase |

---

## 6. Tech stack (locked)

| Layer | Choice |
|---|---|
| Backend | Laravel (latest stable) |
| Frontend | Blade + Tailwind CSS + Alpine.js |
| Auth | Laravel Breeze (Blade) |
| DB | MySQL 8+ (XAMPP locally). `latitude`/`longitude` decimals + `POINT` `location` with spatial index |
| Map | Leaflet.js + OpenStreetMap |
| Clustering | Leaflet.markercluster |
| Coverage | Custom Leaflet circles from `signal_radius_m` |
| Photos | `storage/app/public` |
| Audit | Custom `audit_logs` table |

**Do not introduce:** React/Vue, Google Maps, paid map tiles, GraphQL, Redis as a hard requirement.

Local demo DB: `towerline` on XAMPP MySQL (`127.0.0.1`, user `root`, empty password unless `.env` says otherwise).

---

## 7. Design system

### Brand

- **Primary:** deep forest / emerald `#1B4D3E` — nav, headers, primary buttons
- **Accent:** mint/sage `#A8C5B5`
- **Surface:** `#F7F8F6`
- **Text:** `#1A1A1A` / secondary `#4B5563`
- **Emblem gold (logo bg, sparingly):** `#F5C518` — do not flood the UI with yellow
- **Header:** emblem + ministry name in both languages + language toggle  
  - SO: `Wasaaradda Isgaarsiinta iyo Technology`  
  - EN: `Ministry of Communication and Technology`

Tone: official government. No startup gradients, no decorative animation.

Logo file lives at `public/images/mocit-logo.jpg` (copy of `wasaarada logo.jpg`).

### Status colors (never use brand green for status)

| Meaning | Hex |
|---|---|
| Healthy | `#22C55E` |
| Needs attention | `#F59E0B` |
| Critical / expired | `#DC2626` |
| Inactive / decommissioned | `#6B7280` |
| Under construction | `#2563EB` |

### Operator colors (coverage circles + legend)

| Operator | Color |
|---|---|
| Telesom | `#009E73` |
| Somtel | `#0072B2` |
| Somcable | `#CC79A7` |
| Truecable | `#D55E00` |
| Astaan | `#56B4E9` |
| Horncable | `#E69F00` |

### Layout

- App shell: top bar (emblem, bilingual title, language, user) + left nav desktop; drawer on mobile
- Map: full viewport minus chrome; left filter panel (collapsible); **Print / snapshot** button for briefings
- Tables: Laravel pagination, sortable, filters
- Forms: `__()` / `@lang`, POST/redirect/GET
- Default locale `en`, session toggle to `so`

---

## 8. Geography and operators (seed)

| `name_en` | `name_so` | Anchor (approx.) |
|---|---|---|
| Awdal | Awdal | Borama 9.936, 43.182 |
| Maroodi Jeex | Maroodi Jeex | Hargeisa 9.562, 44.077 |
| Sahil | Saaxil | Berbera 10.434, 45.014 |
| Togdheer | Togdheer | Burao 9.522, 45.534 |
| Sanaag | Sanaag | Erigavo 10.616, 47.368 |
| Sool | Sool | Las Anod 8.477, 47.360 |

Map default: center ~ `9.56, 44.06`, zoom 7, Somaliland bounds.

| Operator | `category` |
|---|---|
| Telesom | telecom |
| Somtel | telecom |
| Somcable | telecom |
| Truecable | broadcast |
| Astaan | broadcast |
| Horncable | broadcast |

**Broadcast coverage (sample data):** Truecable, Astaan, and Horncable operate in **Hargeisa** only so far, and are **starting work in Borama**. No TV/cable sample towers in other towns.

Seed **4 towers per region** (24 total), mixed operators, mixed status/health/licenses so the map and dashboard look real.

---

## 9. Data model

```
regions 1──* towers *──1 operators
users.region_id  → regions     (inspectors)
users.operator_id → operators  (operator viewers)
towers 1──* inspections
towers 1──* licenses
users  1──* inspections (inspector_id)
users  1──* audit_logs
```

### `regions` — id, name_en, name_so, timestamps

### `operators` — id, name, category (`telecom`/`broadcast`), logo nullable, contact_info nullable, color (hex), timestamps

### `towers`

- name, latitude `decimal(10,7)`, longitude `decimal(10,7)`, location `POINT` SRID 4326 + spatial index
- region_id, operator_id
- type: `guyed` / `monopole` / `rooftop`
- height_m, capacity (string), signal_radius_m
- status: `active` / `under_construction` / `decommissioned`
- health_status cached: `good` / `needs_attention` / `critical` / `unknown` (recalc on inspection save)
- commissioned_at nullable
- no soft deletes

Keep lat/lng and `location` in sync on `saving`.

### `inspections`

- tower_id, inspector_id
- power_status: `on_grid` / `generator` / `battery` / `down`
- generator_condition: `good` / `fair` / `poor` / `n_a`
- physical_condition: `good` / `fair` / `poor`
- notes, photos (json paths)
- inspected_at (default now)

Health rules:

- Critical: power `down` OR physical `poor`
- Needs attention: generator `poor` OR physical `fair`
- Good: otherwise
- Unknown: no inspections

Overdue: no inspection in **90 days**.

### `licenses`

- tower_id, operator_id
- license_type: `A` / `B` / `C`
- issued_at, expires_at
- documents: JSON list of scanned files (PDF / photo / Word), stored on the public disk
- Display status **computed from dates** (not stored): expired / expiring_soon (≤30 days) / active

### `users` — Breeze + role (`admin` / `operations_manager` / `inspector` / `operator_viewer` or a custom `role_id`) + assigned regions + operator_id

### `audit_logs` — user_id, action, model_type, model_id, changes json, created_at

---

## 10. Screens

1. **Auth** — branded login, no register. Forgot password hidden unless mail is configured.
2. **Dashboard** — cards + mini map + pending-approval and frequency-renewal banners
3. **Map (priority)** — filters including district; colour pins by operator or status; print/snapshot
4. **Registry** — paginated table, official registration fields, power sources, geography
5. **Inspections** — optional fields + comment; wait for approval when filed by inspector
6. **Approvals** — queue for inspector submissions
7. **Build approval letters** — generate/print from tower (no expiry)
8. **Frequencies** — dashboard, registry, letters, receipts, renewals
9. **Reports** — hub, print/PDF, Excel
10. **Admin** — users, custom roles, districts, audit log, ministry settings
11. **Help** — `/help` (sidebar hidden)
12. **Guidelines** — public `/guidelines` + PDF (no login)
13. **Apply** — public `/apply` (English; tracking number after submit)
14. **Applications** — HQ inbox: assign → site visit → Director Yes/No → DG grant

---

## 11. Routes (authenticated)

- `GET /` dashboard
- `GET /map` + `GET /map/towers` JSON
- `resource towers` + nested inspections store
- `GET /approvals` (queue / my submissions)
- `resource licenses` (legacy A/B/C, not in sidebar)
- build-approval letters from tower detail
- frequencies dashboard + allocations, letters, receipts
- `GET /reports` + show / excel / print
- `resource users` (admin); custom roles
- `resource operators` / `resource regions` / districts (admin, ops)
- `GET /audit-logs` (admin)
- `GET /help` (sidebar hidden)
- `GET /guidelines` + `GET /guidelines.pdf` (public)
- `GET /apply` + `POST /apply` + `GET /apply/received` (public, English, rate-limited)
- `GET /applications` + show / assign / review / concur / grant (HQ chain)
- `POST /locale`

Policies on every write. Map JSON never includes out-of-scope towers.

---

## 12. Demo accounts (local only)

Password for all: `password`

| Email | Role | Scope |
|---|---|---|
| `admin@mocit.local` | admin | all |
| `ops@mocit.local` | operations_manager | all except users, audit, settings |
| `inspector.maroodi@mocit.local` | inspector | Maroodi Jeex |
| `section.head@mocit.local` | section_head | all site applications; can assign |
| `coordinator.maroodi@mocit.local` | regional_coordinator | Maroodi Jeex files assigned to them; site visit + decision |
| `director@mocit.local` | department_director | all site applications; Yes/No concurrence |
| `dg@mocit.local` | director_general | all site applications; grant permit or return to Director |

---

## 13. How we work (weekly)

Do **not** build the whole product in one pass. Each session finishes **one week’s deliverable**, then we stop until you say to start the next week.

| Week | Focus | Status |
|---|---|---|
| 1 | Discovery & requirements | **Done** — answers locked in this file |
| 2 | Design | **Done as context** — no Figma; design reference + this file |
| 3 | Data model & architecture | **Done** |
| 4 | Tower registry backend + sample seed + registry screens | **Done** |
| 5 | Tower registry UI (list, form, detail, search/filter, bilingual, locale toggle) | **Done** |
| 6 | Map v1 | **Done** |
| 7 | Map polish (clustering, layer toggles, mobile) | **Done** |
| 8 | Inspections | **Done** |
| 9 | Health rollup & alerts | **Done** |
| 10 | Licenses (A/B/C), dashboard banner, documents | **Done** |
| 11 | Roles polish & audit log UI | **Done** |
| 12 | Test, pilot, handover | **Done** — automated tests + Help screen + `HANDOVER.md`. Live government-server cutover and in-person MoCIT training wait on hosting. |
| 13 | Districts & sub-districts | **Done** |
| 14 | Power sources + registration form | **Done** |
| 15 | Lenient inspections | **Done** |
| 16 | Build approval letters | **Done** |
| 17 | Frequency allocations | **Done** |
| 18 | Operations manager + regional operators | **Done** |
| 19 | Inspector approval queue | **Done** |
| 20 | Custom roles & tasks | **Done** |
| 21 | Operator colors on map | **Done** |
| 22 | Report centre | **Done** |
| 23 | Integration, tests, docs, pilot v2 | **Done** |
| 24 | Site registration guidelines (public window + PDF) | **Done** |
| 25 | Public application form | **Done** |
| 26 | HQ roles and assignment inbox | **Done** |
| 27 | Officer review and site visit | **Done** |
| 28 | Director chain, permit, archive | **Done** |
| 29 | Integration, tests, docs (pilot-ready portal) | **Done** |

Auth (Breeze) is installed. User-admin, audit-log, and Help screens are live for ministry staff.

- User-facing strings go through lang files (`en` default)
- Writes ministry staff care about → `audit_logs`
- Map JSON is role-scoped
- Boring Laravel: controllers, Blade, Form Requests, Policies, seeders
- When the real tower list arrives, add an importer; do not hand-edit production coordinates in seeders
- Public self-registration is off. Admins create users. Staff cannot delete their own account from Profile.

---

## 14. Week 3 deliverable — ER + route spec

```mermaid
erDiagram
    regions ||--o{ towers : has
    operators ||--o{ towers : operates
    regions ||--o{ users : scopes
    operators ||--o{ users : scopes
    towers ||--o{ inspections : has
    towers ||--o{ licenses : has
    users ||--o{ inspections : files
    users ||--o{ audit_logs : writes
    operators ||--o{ licenses : holds

    regions {
        bigint id PK
        string name_en
        string name_so
    }
    operators {
        bigint id PK
        string name
        string category
        string color
    }
    users {
        bigint id PK
        string role
        bigint region_id FK
        bigint operator_id FK
    }
    towers {
        bigint id PK
        point location
        decimal latitude
        decimal longitude
        string status
        string health_status
    }
    inspections {
        bigint id PK
        bigint tower_id FK
        bigint inspector_id FK
        string power_status
    }
    licenses {
        bigint id PK
        bigint tower_id FK
        string license_type
        date expires_at
    }
    audit_logs {
        bigint id PK
        bigint user_id FK
        string action
        json changes
    }
```

**Routes we will add in later weeks (spec only this week):**

| Method | Path | Week |
|---|---|---|
| GET/POST | `/login` `/logout` | 3 (Breeze) |
| GET | `/dashboard` | 3 stub → 9 cards |
| GET | `/map` `/map/towers` | 6–7 (done) |
| resource | `/towers` | 4–5 |
| POST | `/towers/{tower}/inspections` | 8 (done) |
| resource | `/licenses` | 10 (done) |
| resource | `/users` | 11 (done) |
| GET | `/audit-logs` | 11 (done) |
| GET | `/help` | 12 (done) |
| POST | `/locale` | 5 (done) |

## 15. Still open (not blocking demo)

- Exact government-server PHP/MySQL versions
- Production domain / intranet vs public
- Official Somali spellings if MoCIT differs
- Real inventory import format (spreadsheet columns)
- SMTP later if they want email reminders in a later phase
