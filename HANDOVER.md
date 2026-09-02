# TowerLine handover

Staff guide for the MoCIT tower registry. Product decisions live in `CONTEXT.md`. Phase 2 week notes live in `MINISTRY-PHASE2-PLAN.md`.

## What this is

TowerLine is the bilingual (English / Somali) registry, map, inspection, approval-letter, frequency, and report system for telecom and broadcast towers in Somaliland. It is meant for daily ministry use, live briefings, printable map snapshots, and Excel/PDF reports.

## Demo login (local / pilot only)

Password for every demo account: `password`

| Email | Role | Sees |
|---|---|---|
| `admin@mocit.local` | Ministry admin | Everything, including the audit log and user admin |
| `ops@mocit.local` | Operations manager | Registry, letters, frequencies, reports, and approvals. No users, audit log, or settings |
| `inspector.maroodi@mocit.local` | Inspector | Maroodi Jeex only. Submissions wait for approval |
| `inspector.sahil@mocit.local` | Inspector | Sahil only |
| `inspector.west@mocit.local` | Inspector | Awdal, Maroodi Jeex, and Sahil |
| `analyst.maroodi@mocit.local` | Regional analyst | Maroodi Jeex map and reports (custom role example) |

Change these passwords before any shared or government server is used.

## Suggested first pilot

Start with **one region** (Maroodi Jeex):

1. Admin logs in and walks the dashboard, map (colour pins by operator), registry, **Reports**, frequencies, users, and audit log.
2. Inspector logs in on a tablet, opens the map, and files one inspection. Show that it sits in **My submissions** until ops or admin approves it.
3. Operations manager opens the **approval queue**, approves the visit, then prints one report from **Report centre**.
4. Multi-region inspector (`inspector.west`) confirms they see Awdal, Maroodi Jeex, and Sahil — not the east.

Language: EN / SO toggle in the header. In-app guide: **Help** (`/help`).

## Daily work

- **Admin:** dashboard for pending submissions and frequency renewals; users and custom roles; audit log; ministry settings for letter signatory.
- **Operations manager:** approve inspector work; manage towers, letters, frequencies, districts, and reports.
- **Inspector:** map first, then tower detail → log inspection (most fields optional). New towers and edits wait for ministry review.
- **Anyone with reports access:** Report centre → choose a category → print/PDF or Excel.

Build approval letters are issued from the tower page. They do not expire. Legacy A/B/C licences remain for reference.

## Local run (XAMPP)

1. MySQL database `towerline` (user `root`, empty password unless `.env` says otherwise).
2. Copy `.env.example` to `.env`, set `APP_URL`, generate a key: `php artisan key:generate`
3. `composer install` then `npm install` and `npm run build`
4. `php artisan migrate --seed`
5. `php artisan storage:link`
6. Serve on **http://127.0.0.1:8001** (`php artisan serve --host=127.0.0.1 --port=8001`)

Seed includes: 6 regions, interim districts/sub-districts, national and regional operators, sample towers, inspections, build approval letters, frequency allocations (with letters/receipts), pending approvals, and demo staff accounts.

## Government server (when hosting is ready)

Standard Laravel **12** on Apache or Nginx + **PHP 8.3+** + MySQL 8:

- Point the document root at `public/`
- `APP_ENV=production`, `APP_DEBUG=false`, a real `APP_KEY`, HTTPS `APP_URL`
- `php artisan migrate --force` then seed only if this is a fresh demo
- `php artisan storage:link` and `npm run build` (or deploy already-built `public/build`)
- Backup MySQL and `storage/app` (inspection photos, letter/receipt files)

### Bluehost (cPanel shared) — ZIP + SQL (no SSH)

Preferred for shared hosting: **[DEPLOY-BLUEHOST.md](DEPLOY-BLUEHOST.md)**

1. On PC: `npm run build`, zip the project (include `vendor/` + `public/build/`, exclude `.env`)
2. Export local DB → `towerline.sql` (phpMyAdmin or mysqldump)
3. Bluehost: create MySQL DB, upload/extract zip, point domain at `towerline/public`
4. Create production `.env`, import `.sql` in phpMyAdmin, link `public/storage`
5. Open HTTPS URL and change demo passwords

PHP **8.3+** and Laravel **12** — unchanged for this pilot.

Live production cutover, in-person training, and the real tower import wait on MoCIT’s server and inventory file. Official district list and remaining paper templates can replace interim data when the ministry sends them.

## Tests

```bash
php artisan test
```

GitHub Actions runs the same suite on `main`.
