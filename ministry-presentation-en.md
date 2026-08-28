# TowerLine — Ministry Presentation Guide (English)

**Product:** Wasaaradda Isgaarsiinta iyo Technology — Tower Management & Monitoring System  
**Working name:** TowerLine  
**Prepared for:** Ministry of Communication and Information Technology (MoCIT), Somaliland  
**Audience:** Ministers, directors, and technical staff reviewing the demo / pilot  
**Status:** Demo and pilot-ready (12-week build complete)

Use this document as a speaking script and slide outline when presenting the live system.

---

## 1. Opening (2 minutes)

**Say:**

> TowerLine is the Ministry’s official system for registering, mapping, inspecting, and licensing telecom and broadcast towers across Somaliland. It brings the map, the registry, field inspections, and license renewals into one place — in English and Somali.

**Key message:** One system for daily work, live briefings, and compliance follow-up.

---

## 2. The problem we solve (2 minutes)

Without a shared system, ministry staff often rely on spreadsheets, paper, and scattered photos. That makes it hard to answer:

- Where are the towers, and who operates them?
- Which sites need inspection or urgent attention?
- Which licenses expire in the next 30 days?
- Who changed a record, and when?

**TowerLine answers those questions from one screen.**

---

## 3. What the Ministry gets today (3 minutes)

| Module | What leaders see |
|---|---|
| **Map (flagship)** | All towers on one national map — filter by region, operator, status, health, or license; print/snapshot for briefings |
| **Tower registry** | Structured list: location, operator, type, height, capacity, status |
| **Inspections** | Field form for power, generator/battery, physical condition, notes, and photos |
| **Licenses** | Types A, B, C with active / expiring soon / expired status and a dashboard renewal banner |
| **Users & access** | Ministry admin and regional inspectors (one or more regions per inspector) |
| **Audit log** | Clear history of who added, changed, or removed records |
| **Help** | Short in-app guide for staff |

**Languages:** English (default) and Somali — toggle in the header.

**Operators in the demo:** Telesom, Somtel, Sogasho (telecom); Truecable, Astaan, Horncable (broadcast).

**Regions covered:** Awdal, Maroodi Jeex, Sahil, Togdheer, Sanaag, Sool.

---

## 4. Live demo walkthrough (8–10 minutes)

Open: `http://127.0.0.1:8001` (or the pilot URL when hosted).

### Step A — Ministry admin

1. On the login page, use **Quick demo login → Ministry admin**.
2. Open the **Dashboard** — show total towers, critical sites, overdue inspections, and licenses expiring soon.
3. Open the **Map** — filter by region or operator; click a tower; use **Print / snapshot**.
4. Open **Tower registry** — show search and filters.
5. Open **Licenses** — show A/B/C and expiry states.
6. Open **Users** and **Audit log** — show who can access what, and that changes are recorded.

### Step B — Regional inspector

1. Log out, then choose **Inspector · Maroodi Jeex**.
2. Show that the map and list only cover that region.
3. Open a tower → **Log inspection** — power, condition, optional photos.
4. Optionally log in as **Inspector · West** to show one inspector covering **three regions**.

### Step C — Language

Toggle **EN / SO** in the header so the room sees Somali labels.

---

## 5. Roles and security (2 minutes)

| Role | Access |
|---|---|
| **Ministry admin** | Full system: towers, licenses, users, audit log |
| **Regional inspector** | Towers and inspections in assigned region(s) only |
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

These are **Phase 2** candidates after the pilot.

---

## 7. Suggested pilot (2 minutes)

**Start small:** one region — Maroodi Jeex.

1. Admin reviews dashboard, map, licenses, and users.
2. One inspector files a real inspection on a tablet.
3. Leadership prints a map snapshot for a short briefing.
4. Collect feedback for 1–2 weeks, then expand region by region.

**Hosting next step:** deploy on a government server (Apache/Nginx + PHP + MySQL). Exact server details to confirm with MoCIT IT.

---

## 8. Closing ask (1 minute)

**Say:**

> The demo is ready. We ask the Ministry to (1) approve a one-region pilot, (2) confirm government hosting, and (3) share the official tower inventory format when available so we can replace sample data with real sites.

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
