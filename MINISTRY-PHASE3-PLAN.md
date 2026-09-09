# TowerLine — Phase 3 plan (site registration portal)

**Source:** MoCIT site-registration guidelines and approval workflow (Sep 2026)  
**Status:** **Week 29 done** — Phase 3 complete (Sep 2026)  
**Builds on:** Phase 2 (`MINISTRY-PHASE2-PLAN.md`, `CONTEXT.md`)  
**How we work:** Finish **one week’s deliverable** per session, then stop for review.

---

## Locked decisions

| Topic | Decision |
|---|---|
| Applicant | **Public form** (no login). Ministry staff process the file. |
| vs inspector queue | **New pipeline.** Inspector Approvals stay for edits/inspections on live towers. |
| After DG grant | Create the **live tower** and issue the **build-approval letter**. |
| HQ roles | Section Head, Assigned Officer, Department Director, Director General |
| Regional coordinator | **Assigned officer for that region** (Section Head assigns). Correct if MoCIT says otherwise. |
| Guidelines PDF | Official file `resources/docs/Xeer-Nidaamiyaha Goobaha Isgaadhsiinta.pdf` (Xeer-Nidaamiye Lr.02/2019) |
| Director of Telecom | Same person as **Department Director** in the system |

```text
Customer (no login) → Guidelines + PDF → application (Week 25)
    → Section Head assigns
    → Officer / regional coordinator reviews + site visit
    → Department Director Yes/No + remarks + signature
    → Director General permit or return
    → Live tower + letter + archive/print
```

---

## Week 24 — Site registration guidelines

**Goal:** Dedicated public **Guidelines** window and independent PDF.

- [x] Public `/guidelines` window labeled Guidelines (EN/SO)
- [x] Downloadable PDF of the required documentation
- [x] Link from the login page (and Help for staff)
- [x] All five required documents listed; radio apparatus sub-points included
- [x] Tests: guest page, PDF, locale, login/help links

**Done when:** A customer can open Guidelines without signing in and download the PDF.

---

## Week 25 — Public application form

**Goal:** No-login application with compulsory uploads.

- [x] Contact + operator + licence class/no. + site name, region, district, sub-district, city, lat/lng
- [x] Five required files: request letter, GPS map, site plan, radio apparatus, ICNIRP declaration
- [x] Tracking number on screen (no email)
- [x] Rate-limit the public endpoint
- [x] Link from Guidelines

**Done when:** A guest can submit a complete application and receive a reference number.

---

## Week 26 — HQ roles and assignment

**Goal:** Section Head receives applications and assigns an officer/coordinator.

- [x] System roles: section head, assigned officer, department director, director general, regional coordinator (region-scoped)
- [x] Section Head inbox: receive → assign
- [x] Demo users seeded
- [x] Admin still sees everything

**Done when:** Section Head can assign a Maroodi Jeex application to that region’s coordinator.

---

## Week 27 — Officer review and site visit

**Goal:** View-only review plus on-site verification.

- [x] Officer sees application + documents (no edit of applicant data)
- [x] Site visit tied to the application
- [x] Approve or reject with remarks
- [x] Coordinator only sees assigned region(s)

**Done when:** Officer decision moves the file to the Department Director (or back on reject).

---

## Week 28 — Director chain, permit, archive

**Goal:** Department Director concurrence and DG final permit.

- [x] Director: Yes / No + remarks + name/signature
- [x] No closes the file (`refused`); it does not return to the Section Head
- [x] DG grants permit or returns to Director
- [x] Grant creates live tower + build-approval letter
- [x] Printable HQ copy and customer copy

**Done when:** A granted application appears as a tower on the map with a letter.

---

## Week 29 — Integration, tests, docs

**Goal:** Pilot-ready portal.

- [x] Full suite green
- [x] Help, `HANDOVER.md`, presentations
- [x] Seed: sample applications at each stage

**Done when:** MoCIT can demo the public apply → HQ chain path.

---

## Next step

**Phase 3 is complete.** Public apply is `/apply` (no login). Staff process files on `/applications`. Do not start a later week unless asked.
