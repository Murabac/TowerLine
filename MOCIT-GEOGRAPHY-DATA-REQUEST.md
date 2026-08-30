# MoCIT Data Request — Official Geography for TowerLine

**To:** Ministry of Communications & Technology (MoCIT) — Tower Registry Programme  
**From:** TowerLine project team  
**Date:** 30 August 2026  
**Subject:** Official district and sub-district list for the national tower registry  

---

## Purpose

TowerLine (the MoCIT tower registration and monitoring system) records every telecom and broadcast tower with a **region → district → sub-district** location. We need MoCIT’s **authoritative geography data** to replace interim seed data and to align the system with ministry and local-government practice.

---

## What we have today (interim)

| Item | Status |
|------|--------|
| **6 regions** | Aligned with Somaliland Law No. 23/2019 |
| **102 districts** | Seeded from Law 23/2019, Article 9–10 (`database/data/somaliland-geography-interim.json`) |
| **Sub-districts** | **Hargeisa (9)** and **Burao (4)** from public municipal sources; **all other districts** use a single placeholder (“Central / Dhexe”) until MoCIT provides real names |

This is sufficient for development and demos but **not** for production reporting or inspector workflows at full granularity.

---

## What we need from MoCIT

Please provide **one master list** (Excel or CSV preferred) covering all regions:

| Column | Required | Notes |
|--------|----------|-------|
| `region_en` | Yes | e.g. Maroodi Jeex |
| `region_so` | Yes | Somali name |
| `district_en` | Yes | Official degmo name (English or transliteration) |
| `district_so` | Yes | Official Somali name |
| `district_grade` | If available | A / B / C / D per Law 23/2019 |
| `sub_district_en` | Yes | Official name below degmo (xaafad, ward, tuulo, or ministry term) |
| `sub_district_so` | Yes | Somali name |
| `sub_district_type` | If available | e.g. municipal ward, xaafad, village cluster |
| `parent_district_code` | Optional | Stable ID if ministry uses one |
| `sub_district_code` | Optional | Stable ID for imports and future GIS |
| `latitude` / `longitude` | Optional | Centroid or admin point for map zoom |
| `boundary` | Optional | GeoJSON or shapefile for map boundaries |
| `status` | Optional | active / merged / renamed / pending |
| `effective_date` | Optional | When name or boundary took effect |
| `notes` | Optional | Spelling variants, disputed areas, etc. |

**Minimum viable delivery:** columns through `sub_district_so` for every district where inspectors are expected to register towers.

---

## Clarifications requested

1. **Official terminology** — What is the correct Somali term for the level below degmo in TowerLine (xaafad, degmo-hoosaad, tuulo, or other)?
2. **Urban vs rural** — For Grade A cities (Hargeisa, Burao, Berbera, Borama, Erigavo, Las Anod), should sub-districts match **municipal wards** or a different ministry list?
3. **Rural districts** — For Grade D degmo, are sub-units named **tuulo** only, or is there a standard sub-degmo list?
4. **Changes since 2019** — Any district renames, splits, or boundary changes not reflected in Law 23/2019?
5. **Single source of truth** — Which directorate or document is authoritative (MoCIT, Interior, National Planning, or local councils)?

---

## How we will use the data

- Replace interim JSON seed files in TowerLine  
- Populate map filters, tower forms, and Phase 2 reports (towers by district / sub-district)  
- No change to region or district counts without ministry written confirmation  

---

## Delivery

| Format | Preference |
|--------|------------|
| **Preferred** | Excel (.xlsx) or CSV, UTF-8, one row per sub-district |
| **Also accepted** | Signed PDF list + spreadsheet, or GIS package with attribute table |
| **Contact** | *[MoCIT focal point name / email / phone]* |
| **Target date** | *[Agreed milestone — e.g. before Week 14 go-live or production cutover]* |

---

## References (interim sources used)

- Somaliland Regions and Districts Self-management Law No. 23/2019 — [somalilandlaw.com](http://www.somalilandlaw.com/Xeerka__wkb__Gobolada_iyo_Degmooyinka_2019SLLaw.pdf)  
- Hargeisa Municipality — [hargeisamunicipality.com/districts-info](https://hargeisamunicipality.com/districts-info/)  
- Burao administrative wards — Wikipedia / Interpeace baseline report (2016)  

*This request supports the TowerLine Phase 2 geography module (Week 13+). Interim data files: `database/data/somaliland-geography-interim.json`, `database/data/somaliland-sub-districts-interim.json`.*
