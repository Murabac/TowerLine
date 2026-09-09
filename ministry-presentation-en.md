# TowerLine — Ministry Presentation Guide (English)

**Product:** Wasaaradda Isgaarsiinta iyo Technology — Tower Management & Monitoring System  
**Working name:** TowerLine  
**Prepared for:** Ministry of Communication and Information Technology (MoCIT), Somaliland  
**Audience:** Ministers, directors, and technical staff reviewing the demo / pilot  
**Status:** Phase 3 complete (weeks 13–29). Public site applications + HQ chain are in the demo.

Use this document as a speaking script and slide outline when presenting the live system.

---

## 1. Opening (2 minutes)

**Say:**

> TowerLine is the Ministry’s official system for registering, mapping, inspecting, and licensing telecom and broadcast towers across Somaliland. It brings the map, the registry, field inspections, public site applications, build-approval letters, frequency allocations, and printable reports into one place — in English and Somali.

**Key message:** One system for daily work, live briefings, and compliance follow-up.

---

## 2. The problem we solve (2 minutes)

Without a shared system, ministry staff often rely on spreadsheets, paper, and scattered photos. That makes it hard to answer:

- Where are the towers, and who operates them?
- Which sites need inspection or urgent attention?
- Which frequency allocations expire soon?
- Which inspector submissions are waiting for ministry approval?
- Who changed a record, and when?
- Has a new site application been assigned, visited, and granted?

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
| **Users & access** | Admin, operations manager, regional inspectors, HQ application roles, and custom roles (task checklist) |
| **Site applications** | Public `/guidelines` and `/apply` (no login). Section Head assigns; coordinator records a visit; Director Yes/No; DG grants a live tower and letter |
| **Audit log** | Admin-only history of who added, changed, or removed records |
| **Help** | Short in-app guide (`/help`) — HQ roles and the public apply path |

**Languages:** English (default) and Somali — toggle in the header. Public apply stays English.

**Operators in the demo:** Telesom, Somtel, Somcable (telecom); Truecable, Astaan, Horncable (broadcast).

**Regions covered:** Awdal, Maroodi Jeex, Sahil, Togdheer, Sanaag, Sool.

---

## 4. Live demo walkthrough (12–14 minutes)

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

### Step C — Operations manager

1. Log in as **Operations manager** (`ops@mocit.local`).
2. Open **Approvals**, approve the inspector visit, and confirm it is now live.
3. Show that **Users** and **Audit log** are not available to this role.

### Step D — Language

Toggle **EN / SO** in the header so the room sees Somali labels.

### Step E — Public site application (HQ chain)

Use the seeded Maroodi Jeex files (each stage already exists). Password for every demo account: `password`.

1. As a guest, open **Guidelines** (`/guidelines`) then **Apply** (`/apply`). No login. Mention that a tracking number appears after submit.
2. Log in as **Section Head**. Open **Applications**. Show **Demo Maroodi Jeex Site** (received) and **Demo assigned Maroodi site**.
3. Log in as **Coordinator Maroodi Jeex**. Open the assigned file. Site visit, remarks, and signature send it to the Director (or return it).
4. **Department Director:** **Demo director review Maroodi site** is waiting for Yes/No. **Demo refused Maroodi site** shows that **No closes the file**.
5. **Director General:** **Demo DG review Maroodi site** is waiting for Grant or Return. **Demo granted Maroodi site** is already a live tower with a letter. Print HQ and operator copies. Names and signatures appear on the file, the tower, and the letter.

---

## 5. Roles and security (2 minutes)

| Role | Access |
|---|---|
| **Ministry admin** | Full system: towers, letters, frequencies, reports, users, audit log, applications |
| **Operations manager** | Same operational work as admin except users, audit log, settings, and site applications |
| **Regional inspector** | Towers and inspections in assigned region(s); submissions wait for approval |
| **Section Head** | Public site applications. Assigns a received or returned file |
| **Regional coordinator** | Assigned Maroodi Jeex files. Site visit, then send to the Director or return |
| **Department Director** | Yes sends the file to the DG. No closes it |
| **Director General** | Grant (live tower + letter) or return to the Director |
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
3. Walk one public application through Section Head → coordinator → Director → DG (or use the seeded files).
4. Leadership prints a map snapshot, one Report centre PDF, and one grant letter.
5. Collect feedback for 1–2 weeks, then expand region by region.

**Hosting next step:** deploy on a government server (Apache/Nginx + PHP + MySQL). Exact server details to confirm with MoCIT IT.

---

## 8. Closing ask (1 minute)

**Say:**

> The Phase 3 demo is ready: registry, map, inspections, and the public apply → HQ grant path. We ask the Ministry to (1) approve a one-region pilot (Maroodi Jeex), (2) confirm government hosting (PHP 8.3, Laravel 12, MySQL), and (3) share the official tower inventory and district list when available so we can replace sample and interim data.

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
| How does a new site get registered? | Guest opens `/guidelines` then `/apply` (no login). Section Head assigns; coordinator visits; Director Yes/No; DG grants a live tower and letter. |
| Who owns the data? | MoCIT. The system is built for ministry servers. |

---

*Developed for MoCIT · TowerLine · Presentation guide (English)*
