# TowerLine — Ministry Presentation Guide (English)

**Product:** Wasaaradda Isgaarsiinta iyo Technology — Tower Management & Monitoring System  
**Working name:** TowerLine  
**Prepared for:** Ministry of Communication and Information Technology (MoCIT), Somaliland  
**Audience:** Ministers, directors, and technical staff reviewing the demo / pilot  
**Status:** Phase 2 demo / pilot v2 (weeks 13–23 complete)

Use this document as a speaking script and slide outline when presenting the live system.

---

## 1. Opening (2 minutes)

**Say:**

> TowerLine is the Ministry’s official system for registering, mapping, inspecting, and licensing telecom and broadcast towers across Somaliland. It brings the map, the registry, field inspections, build-approval letters, frequency allocations, and printable reports into one place — in English and Somali.

**Key message:** One system for daily work, live briefings, and compliance follow-up.

---

## 2. The problem we solve (2 minutes)

Without a shared system, ministry staff often rely on spreadsheets, paper, and scattered photos. That makes it hard to answer:

- Where are the towers, and who operates them?
- Which sites need inspection or urgent attention?
- Which frequency allocations expire soon?
- Which inspector submissions are waiting for ministry approval?
- Who changed a record, and when?

**TowerLine answers those questions from one screen.**

---

## 3. What the Ministry gets today (3 minutes)

| Module | What leaders see |
|---|---|
| **Map (flagship)** | All towers on one national map — filter by region, district, operator, status, or health; colour pins by operator or status; print/snapshot for briefings |
| **Tower registry** | Structured list with official registration fields, districts, sub-districts, and multiple power sources |
| **Inspections** | Field form — most checks optional; comment always available; photos optional |
| **Approvals** | Inspector registrations, edits, and inspections wait here until admin or operations manager publishes them |
| **Build approval letters** | Ministry letter per tower, no expiry, print-ready |
| **Frequencies** | Band/range per operator, yearly renewal, letters and receipts, expiry on the dashboard |
| **Report centre** | Printable and Excel reports for towers, geography, inspections, letters, frequencies, and executive briefings |
| **Users & access** | Admin, operations manager, regional inspectors, and custom roles (task checklist) |
| **Audit log** | Admin-only history of who added, changed, or removed records |
| **Help** | Short in-app guide (`/help`) |

**Languages:** English (default) and Somali — toggle in the header.

**Operators in the demo:** Telesom, Somtel, Sogasho (telecom); Truecable, Astaan, Horncable (broadcast).

**Regions covered:** Awdal, Maroodi Jeex, Sahil, Togdheer, Sanaag, Sool.

---

## 4. Live demo walkthrough (8–10 minutes)

Open: `http://127.0.0.1:8001` (or the pilot URL when hosted).

### Step A — Ministry admin

1. On the login page, use **Quick demo login → Ministry admin**.
2. Open the **Dashboard** — show total towers, critical sites, overdue inspections, pending approvals, and frequency renewals.
3. Open the **Map** — colour pins by operator; filter by region; click a tower; use **Print / snapshot**.
4. Open **Tower registry** — show district, power sources, and search.
5. Open **Reports** — open Master tower register, print preview, mention Excel.
6. Open **Frequencies** — show active / expiring allocations.
7. Open **Users** and **Audit log** — show who can access what, and that changes are recorded.

### Step B — Regional inspector

1. Log out, then choose **Inspector · Maroodi Jeex**.
2. Show that the map and list only cover that region.
3. Open a tower → **Log inspection** — show optional fields and a comment-only visit.
4. Show **My submissions** — the visit is pending until ministry review.
5. Optionally log in as **Inspector · West** to show one inspector covering **three regions**.

### Step C — Operations manager

1. Log in as **Operations manager** (`ops@mocit.local`).
2. Open **Approvals**, approve the inspector visit, and confirm it is now live.
3. Show that **Users** and **Audit log** are not available to this role.

### Step D — Language

Toggle **EN / SO** in the header so the room sees Somali labels.

---

## 5. Roles and security (2 minutes)

| Role | Access |
|---|---|
| **Ministry admin** | Full system: towers, letters, frequencies, reports, users, audit log |
| **Operations manager** | Same operational work as admin except users, audit log, and settings |
| **Regional inspector** | Towers and inspections in assigned region(s); submissions wait for approval |
| **Regional analyst** | Seeded custom role: map and reports for assigned region |
| **Operator viewer** | Prepared for later; **switched off for this phase** |

- Staff do **not** self-register — admins create accounts.
- Every important change is written to the **audit log**.
- Map and lists never show towers outside a user’s scope.

---

## 6. What is *not* in this version (1 minute)

Be clear and confident:

- No live operator network feeds (SNMP/API) yet  
- No SMS or email renewal messages yet (dashboard alerts only)  
- No public citizen map yet  
- No native mobile app yet (mobile web works for field use)  
- Real national inventory import waits on MoCIT’s spreadsheet  
- Official complete district/sub-district list can replace the interim Law 23/2019 seed when MoCIT sends it  

These stay out of the current pilot unless the Ministry asks for them.

---

## 7. Suggested pilot (2 minutes)

**Start small:** one region — Maroodi Jeex.

1. Admin reviews dashboard, map, reports, frequencies, and users.
2. One inspector files a real inspection on a tablet; ops or admin approves it.
3. Leadership prints a map snapshot **and** one Report centre PDF.
4. Collect feedback for 1–2 weeks, then expand region by region.

**Hosting next step:** deploy on a government server (Apache/Nginx + PHP + MySQL). Exact server details to confirm with MoCIT IT.

---

## 8. Closing ask (1 minute)

**Say:**

> The Phase 2 demo is ready. We ask the Ministry to (1) approve a one-region pilot, (2) confirm government hosting (PHP 8.3, Laravel 12, MySQL), and (3) share the official tower inventory and district list when available so we can replace sample and interim data.

**Leave behind:**

- This English guide  
- The Somali guide (`ministry-presentation-so.md`)  
- Staff notes in `HANDOVER.md`

---

## 9. Quick Q&A answers

| Question | Answer |
|---|---|
| Is this online already? | Demo runs locally / on pilot hosting; production waits on MoCIT server. |
| Can inspectors work offline? | Forms are simple POST forms that work with poor connectivity; not a full offline app. |
| Can one inspector cover several regions? | Yes. |
| Can operators log in? | Operator viewer is built but disabled until the Ministry asks to turn it on. |
| Who owns the data? | MoCIT. The system is built for ministry servers. |

---

*Developed for MoCIT · TowerLine · Presentation guide (English)*
