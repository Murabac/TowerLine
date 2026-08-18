# Somaliland MoCIT Tower Management & Monitoring System
## Weekly Build Plan

**Client:** Ministry of Communication and Information Technology (MoCIT), Somaliland
**Goal:** A registry, monitoring, and compliance system for tracking telecom towers across all regions.

---

## System Overview

| Module | Purpose |
|---|---|
| Mapping & Visualization | **Flagship feature.** Regional map dashboard, filterable by operator/status — the primary screen the Ministry uses day to day |
| Tower Registry | Core database: location, operator, type, height, capacity, status |
| Monitoring | Field-inspection-based status tracking (power, condition, issues); optional automated feed later |
| Compliance & Licensing | License status, renewal alerts, inspection history |
| Roles & Access | Ministry admin, regional inspector, telco operator viewer |

**Suggested stack:** Laravel (backend + Blade or API for a React/TS frontend), MySQL with spatial data types (POINT columns + spatial indexes for geo queries), Leaflet, bilingual (English/Somali), offline-first field forms.

**Design tool:** Figma, for a full wireframe → high-fidelity mockup phase before any code is written. Since Mapping & Visualization is the priority feature, it gets the deepest design treatment — map states, filters, drill-downs, and status color systems all get mocked up and approved before build.

---

## Week 1 — Discovery & Requirements
- Meet with MoCIT stakeholders to confirm scope, regions covered, and existing tower data sources
- Confirm which telecom operators are in scope (Telesom, Somtel, others) and whether any will share API/SNMP access
- Define regulatory/compliance fields required (licensing categories, renewal cycles, inspection standards)
- Decide on hosting (local vs cloud/Bluehost) and connectivity assumptions for field inspectors
- **Nail down exactly what the map dashboard needs to show at a glance** (status, operator, region, alerts) — this drives the Figma phase
- Deliverable: signed-off requirements doc + list of data fields per module

## Week 2 — Figma Design Phase
- Wireframe all core screens: registry list/detail, inspection form, compliance tracker, role-based views
- **Deep design pass on Mapping & Visualization**: map dashboard layout, status color legend, filter panel (region/operator/status), tower detail popup/drill-down, mobile/tablet view for field use
- High-fidelity mockups in Figma, MoCIT branded visual identity, bilingual (English/Somali) labels in the designs themselves
- Review cycle with Ministry stakeholders — get sign-off on the map dashboard specifically before moving on
- Deliverable: full Figma file (wireframes + hi-fi), approved by MoCIT

## Week 3 — Data Model & Architecture
- Design MySQL schema: towers, operators, regions, inspections, licenses, users/roles
- Use MySQL spatial types (POINT + SPATIAL INDEX) for tower coordinates and region-based geo queries
- Set up Laravel project structure: migrations, Eloquent models, policies for role-based access
- Define API structure (Laravel API resources/routes for registry, monitoring, compliance)
- Set up repos, environments (dev/staging), basic CI
- Deliverable: ER diagram + migrations scaffolded + API spec draft

## Week 4 — Tower Registry: Backend
- Build Eloquent models + migrations for towers, operators, regions
- Build core tower CRUD via Laravel controllers/API resources
- Seed with any existing tower data from MoCIT (if available) or sample data via Laravel seeders
- Deliverable: working registry API

## Week 5 — Tower Registry: Frontend
- Build registry UI from approved Figma designs: list view, add/edit tower form, detail view
- Bilingual labels (English/Somali)
- Basic search and filter (by region, operator, status)
- Deliverable: usable registry admin screens

## Week 6 — Mapping & Visualization: Core Build
- Integrate Leaflet map matching the approved Figma design exactly
- Plot all towers, color-coded by status/operator per the design's legend
- Region-based filtering and zoom, filter panel per mockups
- Deliverable: functional map dashboard (v1)

## Week 7 — Mapping & Visualization: Depth & Polish
- Tower detail drill-down/popup from map clicks (status, operator, last inspection, license status inline)
- Layer toggles (e.g. show only flagged towers, show only pending-license towers)
- Performance pass for large numbers of map markers (clustering if needed)
- Mobile/tablet responsive pass for field use
- Deliverable: polished, production-ready map dashboard — this is the module MoCIT will judge the system by, so budget real review time here

## Week 8 — Monitoring Module (Field Inspections)
- Build inspection report form (power status, generator/battery condition, physical condition, issues, photos)
- Offline-first form handling for field inspectors with poor connectivity
- Link inspections to towers, feed inspection status into the map's status colors
- Deliverable: inspectors can log and submit tower status reports, visible on the map

## Week 9 — Monitoring: Alerts & Status Rollup
- Auto-calculate tower "health status" from latest inspection (good/needs attention/critical)
- Alert system for overdue inspections or flagged issues, surfaced on the map dashboard
- Optional: scope out SNMP/API integration for any operator willing to share live data (phase 2 candidate)
- Deliverable: monitoring dashboard fully wired into the map

## Week 10 — Compliance & Licensing Module
- License records per tower/operator (type, issue date, expiry date, status)
- Automated renewal reminders (email/SMS/dashboard alert)
- License status reflected as a map filter/layer
- Deliverable: compliance tracking screens + alerting, visible from the map

## Week 11 — Roles, Auth & Permissions
- Implement auth using Laravel Breeze/Sanctum (Ministry admin, regional inspector, operator viewer)
- Role-based data scoping via Laravel policies/gates (inspectors see only their region, operators see only their towers, including on the map)
- Audit log (e.g. Laravel activity log package) for who changed/approved what
- Deliverable: secure multi-role system

## Week 12 — Testing, Pilot & Handover
- End-to-end testing across all modules, with extra QA time on the map dashboard across roles/devices
- Run a pilot with a small set of real towers/inspectors from one region
- Collect feedback, fix priority issues
- Deployment to production environment
- Training session + documentation handover for Ministry staff
- Deliverable: live pilot system + handover docs

---

## Phase 2 Candidates (not in initial 12 weeks)
- Automated SNMP/API monitoring feeds from cooperating operators
- SMS-based inspection reporting for very low-connectivity areas
- Public-facing coverage map (if Ministry wants transparency reporting)
- Mobile app for inspectors (vs. mobile web form)

---

## Open Questions to Confirm With MoCIT
1. Do we have an existing tower inventory (spreadsheet, paper records) to migrate, or starting from zero?
2. Which operators are willing to share data or grant field access to their towers?
3. Who are the actual field inspectors — Ministry staff, regional government, or contracted?
4. What's the hosting preference — government servers, Bluehost/cloud, or local data center?
5. What does the Ministry consider "success" for the map dashboard specifically — e.g. does it need to work in a live briefing/presentation context, exportable snapshots, or just internal daily use?
