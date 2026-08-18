# Cursor Prompt — Wasaaradda Isgaarsiinta iyo Technology Tower Management System

Paste everything below into Cursor.

---

Build a full web application for **Wasaaradda Isgaarsiinta iyo Technology** (Ministry of Communication and Technology), Somaliland, to manage and monitor telecom towers across all regions. Bilingual interface (English/Somali).

## Stack
- **Backend:** Laravel (latest stable)
- **Database:** MySQL, using spatial data types (POINT columns + SPATIAL INDEX) for tower coordinates
- **Frontend:** Laravel Blade views + Tailwind CSS (server-rendered, with Alpine.js for light interactivity — avoid a heavy SPA framework; forms should work via standard POST/redirect as a baseline)
- **Auth:** Laravel Breeze
- **Map:** Leaflet.js + OpenStreetMap tiles (no Google Maps, no API key required)
- **Map plugins:** Leaflet.markercluster (clustering), custom canvas/SVG circle overlays for signal coverage radius (not a plugin — built from tower lat/lng + radius field)

## Branding
- Primary color: deep forest/emerald green (e.g. `#1B4D3E` or similar) for nav, headers, primary buttons
- Accent: light mint/sage green
- Status colors kept visually distinct from brand green: healthy = a different green tone (e.g. `#22C55E`), needs attention = yellow/amber, critical/expired = red, inactive = gray
- Header shows Ministry name in both languages with a language toggle: "Wasaaradda Isgaarsiinta iyo Technology" / "Ministry of Communication and Technology"
- Clean, official, government-appropriate aesthetic — no consumer-startup styling, no unnecessary animation

## Database Schema (Eloquent models + migrations)
- `regions` — id, name_en, name_so
- `operators` — id, name, logo, contact_info
- `towers` — id, name, latitude, longitude (as MySQL POINT), region_id, operator_id, type (guyed/monopole/rooftop), height_m, capacity, signal_radius_m, status (active/under_construction/decommissioned), commissioned_at
- `inspections` — id, tower_id, inspector_id, power_status, generator_condition, physical_condition, notes, photos (json/path), created_at
- `licenses` — id, tower_id, operator_id, license_type, issued_at, expires_at, status
- `users` — standard Laravel + role column (admin/inspector/operator_viewer) + region_id (nullable, for inspector scoping) + operator_id (nullable, for operator viewer scoping)
- `audit_logs` — id, user_id, action, model_type, model_id, changes (json), created_at

## Roles & Access (Laravel Policies/Gates)
- **Admin:** full access to everything
- **Regional inspector:** can only view/edit towers and submit inspections within their assigned region
- **Operator viewer:** read-only access, scoped to towers owned by their operator

## Screens to build

### 1. Auth
Login page, role-based redirect after login.

### 2. Main Dashboard
Summary cards (total towers, active alerts, towers needing inspection, licenses expiring soon) + embedded map preview linking to the full map view.

### 3. Mapping & Visualization (flagship feature — build this with the most care)
- Full-screen Leaflet map, OpenStreetMap tiles
- Left filter panel: region, operator, status, license state (all filter via query params, server-side filtered, map re-renders via AJAX)
- Toggle to show/hide **signal coverage overlay**: semi-transparent colored circles drawn around each tower using its `signal_radius_m`, layered beneath pin markers
- Color-coded pin markers by status, with a legend
- Marker clustering when zoomed out (Leaflet.markercluster), individual pins + coverage circles when zoomed in
- Click a pin → side panel or popup showing: tower name, operator, status, last inspection date, license expiry, signal radius, "view full details" link
- Fully responsive for tablet/mobile (field use)

### 4. Tower Registry
- Paginated, sortable, filterable table (Laravel pagination) — columns: name, region, operator, status, last inspection
- Add/edit tower form — all fields from the schema, including a map-click-to-set-coordinates picker and a signal radius input
- Tower detail page — full info + inspection history + license history + mini map

### 5. Inspection Module
- Mobile-friendly inspection submission form (power status, generator condition, physical condition, notes, photo upload)
- Design forms to degrade gracefully with poor connectivity (minimal JS dependency, works with standard form POST)
- Inspection history table per tower

### 6. Compliance & Licensing
- License list per tower/operator, sortable by expiry date
- Status badges (active/expiring soon/expired)
- Renewal reminder banner shown on dashboard for licenses expiring within 30 days

### 7. Roles & Admin
- User management (list, create, edit role/region/operator assignment)
- Audit log table (searchable/filterable by user, action, date)

## Build Order
1. Laravel project scaffold + Breeze auth + database migrations
2. Seed data (sample regions, operators, ~30 sample towers across Somaliland's 6 regions)
3. Tower Registry CRUD (backend + Blade views)
4. Mapping & Visualization screen (this is the priority — spend the most iteration here)
5. Inspection module
6. Compliance/Licensing module
7. Roles/permissions + audit log
8. Bilingual string coverage pass (Laravel localization, `en`/`so` language files)
9. Responsive/mobile pass, especially on the map and inspection form

## Notes
- Keep all forms bindable directly to Eloquent models — no complex nested/dynamic field structures
- Use Laravel's built-in localization (`resources/lang/en`, `resources/lang/so`) for bilingual support rather than hardcoding text
- Signal coverage circles are a custom-built overlay (radius drawn from `signal_radius_m`), not a third-party paid feature — keep this logic simple and reusable
