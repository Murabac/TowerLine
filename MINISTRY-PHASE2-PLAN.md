# TowerLine — Phase 2 plan (MoCIT meeting)

**Source:** Ministry officials meeting (Aug 2026)  
**Status:** Planning — **Week 16 done** (Aug 2026)  
**Builds on:** Week 12 demo (`CONTEXT.md`)  
**How we work:** Same as Phase 1 — finish **one week’s deliverable** per session, then stop for review.

This file is the source of truth for Phase 2 until decisions are locked in `CONTEXT.md`.

---

## Summary of what changes

| # | Ministry requirement | Current app | Phase 2 direction |
|---|---|---|---|
| 1 | Operator unique colors on map | Coverage circles colored by operator; pin colors may not be distinct enough | Every operator gets a fixed map color (marker + legend + filters) |
| 2 | Districts & sub-districts | Region only | Add geography hierarchy; filter map/registry/CRUD by district and sub-district |
| 3 | Power source: solar + multi-select | No power-source field on towers; inspections use single `power_status` | Tower registry stores **multiple** power sources (grid, generator, battery, **solar**, etc.) |
| 4 | Lenient inspections | Inspection fields mostly required; health rollup is strict | Most inspection fields **optional**; free-text **comment** always available; softer compliance rules |
| 5 | Letter of approval (not tower licence) | `licenses` with types A/B/C and expiry | **Build approval letter** per tower — ministry-provided template, **no expiry**; rename/replace licence UX where needed |
| 6 | Frequency management | Not in app | New module: assign frequency band/range per operator, **annual renewal**, expiry tracking |
| 7 | Operational manager role | `admin`, `inspector`, `operator_viewer` only | New role with **less control than admin** (TBD permission list) |
| 8 | Inspector submit/edit needs approval | Inspectors write directly | Inspectors **propose** changes; admin/ops manager **approves** before data goes live |
| 9 | Custom roles & task permissions | Fixed roles in code | Admins create roles and tick **tasks** from a permission list |
| 10 | Regional operators | Operators are national (`operators` table) | Operators can be scoped to **one or more regions** |
| 11 | Detailed printable reports | Dashboard stats only; map print | Report centre: many report types, **date filters**, **print-friendly** layouts |
| 12 | Ministry paper tower form | Custom registry fields | Replace/extend tower form fields to match **official registration form** (awaiting copy) |

---

## Dependencies (read before scheduling)

```text
Week 13 (geography) ──► Week 14 (tower form + power)
         │
         ├──► Week 18 (regional operators)
         └──► Week 22 (reports — need district/region filters)

Week 16 (approval letters) ──► Week 22 (approval letter reports)

Week 18 (RBAC foundation) ──► Week 19 (inspector approval queue)
         │
         └──► Week 20 (custom roles)

Week 15 (lenient inspections) ──► Week 22 (inspection reports)

Week 17 (frequency) can run after Week 18; ties into operators + reports
```

---

## Locked decisions (MoCIT follow-up)

| Topic | Decision |
|---|---|
| **Reports scope** | Full report centre — as many detailed reports as practical, centred on **towers, operators, regions, districts, sub-districts** |
| **Export formats** | **Print / PDF** and **Excel (.xlsx)** — **no CSV** |
| **Report access** | **Admin + ops manager:** all reports. **Inspectors:** scoped reports (their assigned region(s) only) |
| **Report sorting** | User picks sort column + direction; date range filters on every applicable report |
| **Report branding** | Ministry logo + header/footer on print/PDF (default yes) |
| **Build approval letter** | Ministry will share PDF template **later** — Week 16 blocked on that file |
| **Districts / sub-districts** | **Interim seed:** 102 districts from Somaliland Law No. 23/2019 (`database/data/somaliland-geography-interim.json`). MoCIT will provide **official complete list** to replace interim data |
| **Tower registration form** | Ministry will share **PDF later** — Week 14 form mapping blocked on that file |
| **Inspector approval** | Applies to **all three:** new tower registration, tower edits, and inspection submissions |
| **Who approves** | **Admin and ops manager** can approve/reject |
| **Ops manager limits** | Same broad access as admin **except audit log** — ops manager **cannot** view audit log (for now) |
| **Pending approvals UX** | Show pending count on **dashboard** + link to review queue |

---

## Still waiting on ministry

| Item | Needed for |
|---|---|
| Build approval letter PDF sample | ~~Week 16 letter template~~ — received at `resources/docs/tower_registration_approval.pdf` |
| Official district + sub-district list | Replace interim geography seed |
| Tower registration paper form PDF | ~~Week 14 field mapping~~ — received at `resources/docs/reg form.pdf` |
| Frequency allocation field spec (MHz, band name, etc.) | Week 17 form design |
| Confirm: retire licence types A/B/C entirely, or keep for another purpose? | Week 16 migration |

---

## Interim geography data (Week 13 starter)

**File:** `database/data/somaliland-geography-interim.json`  
**Source:** [Somaliland Regions and Districts Law 23/2019](http://www.somalilandlaw.com/Xeerka__wkb__Gobolada_iyo_Degmooyinka_2019SLLaw.pdf), Article 10 — 102 districts across 6 regions (grades A–D).

| Region | Districts |
|---|---|
| Maroodi Jeex | 19 |
| Togdheer | 22 |
| Sanaag | 19 |
| Awdal | 12 |
| Sool | 21 |
| Sahil | 8 |

**Sub-districts (interim):**
- **Researched:** Hargeisa (9 municipal wards) and Burao (4 wards) in `database/data/somaliland-sub-districts-interim.json` — sources: Hargeisa Municipality website, Wikipedia / Interpeace Burao report.
- **Placeholder:** All other districts use one entry (`{District} — Central / Dhexe`) until MoCIT supplies the official list.
- **MoCIT request:** One-page data request drafted in `MOCIT-GEOGRAPHY-DATA-REQUEST.md` (send to ministry focal point).

**Replace workflow:** Import ministry spreadsheet → update seed JSON or admin bulk-import → re-link existing towers (`TowerGeographyBackfillSeeder` or manual).

---

## Report centre — full catalogue (Week 22)

Every report supports: **date range**, **sortable columns**, **on-screen view**, **Print/PDF**, **Excel export**. Shared filters where relevant: region, district, sub-district, operator, tower status, health, power source.

### Towers

| Report | Details |
|---|---|
| Master tower register | Full list: ID, name, coords, region → district → sub-district, operator, type, height, capacity, radius, status, health, power sources, commissioned date, last inspection |
| Towers by region | Count + list per region; subtotals by status/health |
| Towers by district | Same, grouped by district within region |
| Towers by sub-district | Same, finest geography grain |
| Towers by operator | All operators; split national vs regional operators |
| Towers by operator + region | Cross-tab: which operators in which regions |
| Towers by type | Guyed / monopole / rooftop counts and lists |
| Towers by status | Active / under construction / decommissioned |
| Towers by health | Good / needs attention / critical / unknown |
| Towers by power source | Filter towers using grid, generator, battery, solar (multi-match) |
| Towers missing district | Data-quality report for incomplete records |
| Towers on map (tabular) | Same data as map JSON export for briefings |
| New towers in period | Registered/approved in date range |
| Decommissioned towers | Historical removals in date range |

### Operators

| Report | Details |
|---|---|
| Operator summary | Per operator: tower count, regions served, categories (telecom/broadcast) |
| Operator tower list | Detailed tower list per operator |
| Operator regional footprint | Matrix: operator × region × district counts |
| Operator compliance overview | Inspection coverage %, pending approvals, letters issued |
| Operator frequency holdings | Linked frequency allocations (see Frequencies) |

### Geography

| Report | Details |
|---|---|
| Regional summary | Towers, operators, inspections, letters per region |
| District summary | Per district: tower count, operators present, last inspection date |
| Sub-district summary | Finest-level counts |
| Empty districts | Districts with zero registered towers |
| Inspector coverage by district | Which districts have / lack recent inspection activity |

### Inspections

| Report | Details |
|---|---|
| Inspection log | All inspections in date range with inspector, tower, geography, optional fields filled |
| Inspections by inspector | Activity per inspector (scoped for inspectors: self only) |
| Inspections by region / district | Geographic breakdown |
| Partial inspections | Visits with mostly blank fields + comment only (lenient workflow) |
| Overdue / stale towers | No inspection in configurable window |
| Pending inspection approvals | Awaiting admin/ops sign-off |
| Approved vs rejected | Approval outcomes in date range |

### Build approval letters

| Report | Details |
|---|---|
| Letters issued | All letters in date range (no expiry column) |
| Letters by operator | Grouped list |
| Letters by region / district | Geographic grouping |
| Towers without letter | Registered towers missing an approval letter |

### Frequencies

| Report | Details |
|---|---|
| Active allocations | Current valid frequencies per operator |
| Expiring within 30 / 60 / 90 days | Renewal planning |
| Expired frequencies | Past expiry, not renewed |
| Frequency history | All issuances and renewals in date range |
| Operator frequency summary | One row per operator with band details |

### Governance & workflow

| Report | Details |
|---|---|
| Pending approvals (all types) | Tower create, tower edit, inspection — queue snapshot |
| Approval turnaround | Time from submit to approve/reject |
| User activity summary | Actions per user (excludes raw audit log for ops manager) |
| **Audit log extract** | **Admin only** — full audit trail in date range |

### Executive / briefing

| Report | Details |
|---|---|
| Executive snapshot | One printable page: total towers, by region, by operator, health alerts, pending approvals, expiring frequencies |
| Monthly ministry brief | Pre-set month picker; comparison to prior month |
| Custom filter export | User picks columns + filters → Excel (power users) |

### Inspector-scoped subset

Inspectors see only reports filtered to **their assigned region(s)**:

- Master tower register (scoped)
- Towers by district / sub-district (scoped)
- Their inspection log + their pending submissions
- Overdue towers in their regions
- Districts lacking coverage in their regions

---

## Open questions (need ministry input)

_Remaining items only — most decisions are locked above._

## Week 13 — Geography: districts & sub-districts

**Goal:** Region → district → sub-district hierarchy everywhere data is filtered or entered.

### Data model

- [x] `districts` — `id`, `region_id`, `name_en`, `name_so`, timestamps
- [x] `sub_districts` — `id`, `district_id`, `name_en`, `name_so`, timestamps
- [x] `towers.district_id`, `towers.sub_district_id` (nullable FKs; validate sub-district belongs to district)
- [x] Seed from `database/data/somaliland-geography-interim.json` (102 districts); placeholder sub-districts until MoCIT list arrives

### App behaviour

- [x] Map filters: region → district → sub-district (cascading selects)
- [x] Tower list filters match map
- [x] Tower create/edit: cascading district/sub-district
- [x] Inspector scope: still by **region** (districts within assigned regions only)
- [x] Map JSON + policies include district fields
- [x] Bilingual labels (`lang/en`, `lang/so`)
- [x] Admin CRUD for districts/sub-districts (or seed-only if ministry maintains list centrally)

### Tests

- [x] Filter by district/sub-district
- [x] Inspector cannot pick district outside assigned region
- [x] Validation when sub-district does not match district

**Done when:** Map and tower CRUD filter and save district/sub-district correctly.

---

## Week 14 — Tower registry: power sources & ministry form fields

**Goal:** Align tower registration with field reality and ministry paper form.

### Power source (item 3)

- [x] Replace single power field with **multi-select** on tower: `grid`, `generator`, `battery`, `solar` (extensible list)
- [x] Store as JSON array or pivot table (`tower_power_sources`)
- [x] Show on tower detail, list filters, and map side panel
- [x] Migrate/remove old single-value power fields if any remain on inspections only

### Ministry registration form (item 12)

- [x] Map every field from official form → tower columns or JSON `registration_meta`
- [x] Update create/edit Blade form layout to match paper form sections
- [x] Update validation, seeders, and tests
- [x] Bilingual labels for new fields

**Source:** `resources/docs/reg form.pdf` (Faahfaahinta Goob Isgaadhsiineed).

**Done when:** Demo tower can be registered with multiple power sources and all form fields from the official template.

---

## Week 15 — Lenient inspections

**Goal:** Inspections reflect low compliance in the field; comments capture context.

### Form changes (item 4)

- [x] Make `power_status`, `generator_condition`, `physical_condition`, photos **optional**
- [x] Add prominent **inspector comment** field (optional but encouraged)
- [x] Allow “partial” or “not inspected” states without blocking submit
- [x] Update inspection create UI copy (EN/SO) to explain optional fields

### Health & dashboard rules

- [x] Relax health rollup: missing inspection data → `unknown` / `needs_attention`, not hard `critical`
- [x] Revisit **90-day overdue** rule — extended to **180 days** (warning-style stale flag)
- [x] Dashboard cards reflect new lenient semantics

### Tests

- [x] Submit inspection with only a comment
- [x] Submit with all fields empty (if allowed) or minimal set
- [x] Health status does not over-penalize incomplete visits

**Done when:** Inspector can file a visit with mostly blanks + comment; dashboard still usable.

---

## Week 16 — Build approval letters

**Goal:** Replace “tower licence with expiry” with ministry **letter of approval to build**.

### Concept change (item 5)

- [x] New model `build_approval_letters`
- [x] Fields: tower, operator, letter number, issued_at, issued_by, template version
- [x] **No `expires_at`** on build approval
- [x] Tower detail prioritises approval letter; legacy licences kept with note
- [x] Keep audit log on generate

### Letter generation

- [x] Blade/HTML template matching ministry design (`resources/docs/tower_registration_approval.pdf`)
- [x] “Generate letter” action on tower detail → preview → print/PDF
- [x] Bilingual letter on same template (signatures left blank for paper)
- [x] Store generated snapshot metadata (who, when, ref number)

### Legacy licences

- [x] Keep existing demo `licenses` rows during transition
- [ ] Update Help text and presentations

**Blocked until:** ~~ministry shares build approval letter PDF.~~ Template at `resources/docs/tower_registration_approval.pdf`.

**Done when:** Admin can generate a printable approval letter for a tower with no expiry date.

---

## Week 17 — Frequency management

**Goal:** Track operator frequency allocations with **annual renewal** (item 6).

### Data model

- [ ] `frequency_allocations` — operator, band/range label, channel details (TBD), `issued_at`, `expires_at` (+1 year default), status, notes, document optional
- [ ] Link to operator (and optionally region if allocations are regional)

### UI

- [ ] List with filters: operator, active/expired, expiring within 30/60 days
- [ ] Create/edit/renew (renew = new row or extend — pick one pattern)
- [ ] Dashboard banner: frequencies expiring soon (similar to old licence banner)
- [ ] Audit log on changes

### Permissions

- [ ] Admin + ops manager can manage (exact split in Week 18)

**Open:** exact fields the ministry records today (MHz range, band name, letter reference, etc.).

**Done when:** Each operator has trackable frequency records with yearly expiry and renewal workflow.

---

## Week 18 — Roles: ops manager, regional operators, permission foundation

**Goal:** New roles and operator geography without full custom-role UI yet.

### Operational manager (item 7)

- [ ] Add role `operations_manager` (or `ops_manager`)
- [ ] Default permission set — **same as admin except:**
  - **No audit log** access (locked decision)
  - **No** custom role management (Week 20 — admin only)
  - **No** user admin (admin only unless ministry changes this later)
- [ ] Can: approve inspector submissions, manage towers/inspections/letters/frequencies, **all reports**, district data if needed
- [ ] Login/home route, nav visibility, policies

### Regional operators (item 10)

- [ ] `operator_region` pivot (operator ↔ region many-to-many)
- [ ] Tower form: when region selected, filter operator dropdown to operators serving that region (national operators = all regions)
- [ ] Map/registry operator filter respects regional scope in labels
- [ ] Seed: mark which operators are regional vs national

### Permission foundation for Week 19

- [ ] Introduce `permissions` + `role_permission` tables (or JSON on roles) with **fixed task list** in code first
- [ ] Middleware/policy helper: `user->canTask('towers.approve')`

**Done when:** Ops manager account works; operators can be regional; task checks exist in code.

---

## Week 19 — Inspector approval workflow

**Goal:** Inspectors never publish directly; admins/ops managers approve (item 8).

### Workflow

- [ ] New `approval_requests` table: type (`tower_create`, `tower_update`, `inspection`), payload JSON, submitted_by, status, reviewed_by, reviewed_at, reviewer_comment
- [ ] Inspector actions on **all three types** create **pending** records instead of live writes
- [ ] Review queue UI: list, diff/preview, approve, reject with comment
- [ ] Dashboard pending count + link (locked decision)
- [ ] Audit log links pending → approved record (admin-visible only)

### Policy matrix

- [ ] Inspector: submit only (scoped to assigned regions)
- [ ] **Ops manager + admin:** approve and reject
- [ ] Approved record applies to live tower/inspection tables in transaction

### Tests

- [ ] Inspector edit does not change live tower until approved
- [ ] Rejection keeps live data unchanged
- [ ] Approval writes audit entry

**Done when:** End-to-end submit → approve → visible on map/registry.

---

## Week 20 — Custom roles & task permissions

**Goal:** Admins define roles from a task checklist (item 9).

### Admin UI

- [ ] Roles CRUD (system roles `admin`, `inspector`, `ops_manager` protected from delete)
- [ ] Task checklist grouped by area: towers, inspections, approvals, letters, frequencies, reports, users, geography, audit
- [ ] Assign role to user on user form
- [ ] Replace hard-coded `isAdmin()` checks gradually with `canTask()`

### Migration

- [ ] Map existing users to new role records
- [ ] Seed default roles matching current behaviour

**Done when:** Admin can create e.g. “Regional read-only analyst” with only report + map view tasks.

---

## Week 21 — Operator colors on map

**Goal:** Item 1 — visually distinct operators on the map.

**Note:** Can be pulled forward to Week 13 if quick win is wanted before geography work.

- [ ] Ensure every operator has unique `color` in DB (fix clashes in seeder)
- [ ] Map markers use operator color (not only health/status)
- [ ] Legend: operator name + color swatch
- [ ] Coverage circles keep operator color; optional toggle marker vs circle coloring
- [ ] Print/snapshot includes legend
- [ ] Color-blind-safe palette review (distinct hues)

**Done when:** Any two operators on the same map are distinguishable by color without opening the side panel.

---

## Week 22 — Report centre

**Goal:** Full report system per locked decisions and catalogue above.

### Infrastructure

- [ ] `GET /reports` hub — grouped cards: Towers, Operators, Geography, Inspections, Letters, Frequencies, Governance, Executive
- [ ] Shared filter bar: date from/to, region, district, sub-district, operator, status, health (per report)
- [ ] Server-side sortable columns on all tabular reports
- [ ] **Print / PDF:** `@media print` layout + ministry header/footer + logo
- [ ] **Excel export:** `.xlsx` download via Laravel Excel (or equivalent) — **no CSV**
- [ ] Role scoping: inspectors auto-filtered to assigned regions; audit log report **admin only**

### Implementation order

1. Shared report base (filters, export, print CSS)
2. Tower + geography reports (highest priority)
3. Operator + inspection reports
4. Letters + frequencies (after Weeks 16–17)
5. Governance + executive snapshot
6. Inspector-scoped subset

### Permissions

- [ ] `reports.view`, `reports.export_excel`, `reports.print`
- [ ] `reports.audit` — **admin only**

**Done when:** All catalogue reports work with date sort, print/PDF, Excel, and correct role scoping.

---

## Week 23 — Integration, tests, docs, pilot update

**Goal:** Phase 2 shippable as demo/pilot v2.

- [ ] Full test suite green (feature tests per week)
- [ ] Update `CONTEXT.md` locked decisions
- [ ] Update `HANDOVER.md` and Help screen
- [ ] Update `ministry-presentation-en.md` / `ministry-presentation-so.md`
- [ ] Seed data covers districts, regional operators, sample letters, frequencies, pending approvals
- [ ] Deploy package notes if Bluehost constraints unchanged (PHP 8.3, Laravel 12)

**Done when:** MoCIT can pilot Phase 2 on staging with updated training doc.

---

## Suggested session order (if ministry priorities differ)

| Priority | Week | Why first |
|---|---|---|
| A | 21 (operator colors) | Small, visible map win |
| A | 13 (districts) | Unblocks form, reports, regional operators |
| B | 14 (power + form) | Daily registry accuracy |
| B | 15 (lenient inspections) | Field staff friction |
| C | 18 + 19 (roles + approvals) | Governance before go-live |
| C | 16 + 17 (letters + frequency) | New compliance modules |
| D | 20 (custom roles) | After fixed roles proven |
| D | 22 (reports) | Needs stable data model from above |

---

## What we are **not** doing in Phase 2 (unless ministry asks)

- Email/SMS reminders for frequency or inspections
- Public citizen-facing map
- Live RF spectrum hardware integration
- Mobile native app
- Automatic OCR of paper forms

---

## Next step

1. Ministry sends **letter PDF**, **registration form PDF**, and **official district/sub-district list** when ready.
2. Say **“Start Week 14”** (or another week) to begin implementation.
