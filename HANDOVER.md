# TowerLine handover

Staff guide for the MoCIT tower registry. Product decisions live in `CONTEXT.md`.

## What this is

TowerLine is the bilingual (English / Somali) registry, map, inspection, and license system for telecom and broadcast towers in Somaliland. It is meant for daily ministry use, live briefings, and a printable map snapshot.

## Demo login (local / pilot only)

Password for every demo account: `password`

| Email | Role | Sees |
|---|---|---|
| `admin@mocit.local` | Ministry admin | Everything |
| `inspector.maroodi@mocit.local` | Inspector | Maroodi Jeex only |
| `inspector.sahil@mocit.local` | Inspector | Sahil only |
| `inspector.west@mocit.local` | Inspector | Awdal, Maroodi Jeex, and Sahil |

Change these passwords before any shared or government server is used.

## Suggested first pilot

Start with **one region** (Maroodi Jeex) and the existing inspector account:

1. Admin logs in and walks the dashboard, map, registry, licenses, users, and audit log.
2. Inspector logs in on a tablet, opens the map, and files one inspection on a Maroodi Jeex tower (photos optional).
3. Multi-region inspector (`inspector.west`) confirms they see Awdal, Maroodi Jeex, and Sahil — not the east.

Language: EN / SO toggle in the header. In-app guide: **Help**.

## Daily work

- **Admin:** dashboard for license warnings, users for new staff, audit log for who changed what.
- **Inspector:** map first, then tower detail → log inspection (power, generator/battery, condition, photos). An inspector may be assigned several regions.

Language: EN / SO toggle in the header. In-app guide: **Help**.

## Local run (XAMPP)

1. MySQL database `towerline` (user `root`, empty password unless `.env` says otherwise).
2. Copy `.env.example` to `.env`, set `APP_URL`, generate a key: `php artisan key:generate`
3. `composer install` then `npm install` and `npm run build`
4. `php artisan migrate --seed`
5. `php artisan storage:link`
6. Serve on **http://127.0.0.1:8001** (`php artisan serve --host=127.0.0.1 --port=8001`)

## Government server (when hosting is ready)

Standard Laravel on Apache or Nginx + PHP 8.3+ + MySQL 8:

- Point the document root at `public/`
- `APP_ENV=production`, `APP_DEBUG=false`, a real `APP_KEY`, HTTPS `APP_URL`
- `php artisan migrate --force` then seed only if this is a fresh demo
- `php artisan storage:link` and `npm run build` (or deploy already-built `public/build`)
- Backup MySQL and `storage/app` (inspection photos, license scans)

Live production cutover, in-person training, and the real tower import wait on MoCIT’s server and inventory file.

## Tests

```bash
php artisan test
```

GitHub Actions runs the same suite on `main`.
