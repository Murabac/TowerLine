# Deploy TowerLine to Bluehost (ZIP + SQL)

**Easiest path for shared hosting:** zip the app on your PC, upload/extract in cPanel, import a `.sql` file in phpMyAdmin. **No SSH required.**

---

## Overview

| Step | Where | What |
|---|---|---|
| A | Your PC | Build assets, zip the project, export MySQL → `.sql` |
| B | Bluehost cPanel | Create empty MySQL database |
| C | Bluehost File Manager | Upload zip → extract → point domain at `public/` |
| D | Bluehost File Manager | Create `.env` (production) |
| E | Bluehost phpMyAdmin | Import the `.sql` file |
| F | Browser | Open the site, log in, change passwords |

---

## A. On your PC — prepare ZIP + SQL

### A1. Build front-end assets

In the TowerLine folder:

```bash
npm run build
```

Confirm `public/build/` exists (CSS/JS). This folder is **not** in git — it must be inside the zip.

### A2. Create the ZIP

Zip the **whole project**, including:

- `vendor/` (already installed locally)
- `public/build/`
- `public/images/`
- `storage/` folders (empty logs are fine)

**Leave out of the zip:**

| Exclude | Why |
|---|---|
| `.env` | Has your local DB password — create a new one on Bluehost |
| `node_modules/` | Huge and not needed on the server |
| `.git/` | Optional; not needed to run the app |
| `storage/logs/*.log` | Optional cleanup |

**Suggested zip name:** `towerline-deploy.zip`

**Windows tip:** Select project folders/files → Send to → Compressed folder. Or use 7-Zip.  
Do **not** zip only the `public` folder — Bluehost needs the full Laravel app.

### A3. Export the database to a `.sql` file

Your local DB is usually `towerline` on XAMPP.

**Option 1 — phpMyAdmin (XAMPP)**

1. Start XAMPP MySQL.
2. Open http://127.0.0.1/phpmyadmin
3. Click database **`towerline`**
4. Tab **Export** → method **Quick** → format **SQL** → **Go**
5. Save as `towerline.sql`

**Option 2 — Command line**

```bash
C:\xampp\mysql\bin\mysqldump.exe -u root towerline > towerline.sql
```

(If your local MySQL has a password: add `-p`.)

You should now have two files ready:

- `towerline-deploy.zip`
- `towerline.sql`

---

## B. Bluehost — create an empty database

1. Log into **cPanel**.
2. Open **MySQL® Databases**.
3. Create database, e.g. `username_towerline`.
4. Create user + strong password.
5. Add user to database → **ALL PRIVILEGES**.
6. Write down:

```text
DB_HOST=localhost
DB_DATABASE=username_towerline
DB_USERNAME=username_toweruser
DB_PASSWORD=********
```

Leave the database **empty** — you will fill it by importing `towerline.sql`.

---

## C. Bluehost — upload ZIP and extract

### C1. Folder layout (important)

Put the app **outside** the old web root, then point the domain at `public/`:

```text
/home/USERNAME/
  towerline/              ← extract zip contents HERE
    app/
    public/               ← this becomes the website root
    vendor/
    .env                  ← you create this next
    ...
```

### C2. Upload + extract

1. cPanel → **File Manager**.
2. Go to `/home/USERNAME/` (one level above `public_html`).
3. Create folder `towerline` if it does not exist.
4. Open `towerline` → **Upload** → upload `towerline-deploy.zip`.
5. Right-click the zip → **Extract**.
6. If extract created `towerline/towerline/...`, move inner files up one level so you see `towerline/public` and `towerline/vendor` directly.
7. Delete the zip after extract (saves space).

### C3. Point the domain / subdomain at `public`

cPanel → **Domains** (or Subdomains):

| Setting | Value |
|---|---|
| Domain / subdomain | e.g. `towers.yourdomain.com` |
| Document root | `/home/USERNAME/towerline/public` |

Not `/home/USERNAME/towerline` — that will break Laravel.

### C4. PHP version

cPanel → **MultiPHP Manager** → select that domain → **PHP 8.3** (TowerLine uses Laravel 12; PHP 8.3 is required and sufficient).

---

## D. Bluehost — create `.env`

1. File Manager → `/home/USERNAME/towerline/`
2. If `.env.example` exists, copy it and rename the copy to `.env`  
   (or **+ File** → name it `.env`)
3. Edit `.env` (Edit button) and set:

```env
APP_NAME="TowerLine"
APP_ENV=production
APP_KEY=base64:GENERATE_A_NEW_KEY
APP_DEBUG=false
APP_URL=https://towers.yourdomain.com

APP_LOCALE=en
APP_FALLBACK_LOCALE=en

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=username_towerline
DB_USERNAME=username_toweruser
DB_PASSWORD=your_strong_password

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

FILESYSTEM_DISK=public
QUEUE_CONNECTION=database
CACHE_STORE=database

MAIL_MAILER=log
```

### How to get `APP_KEY` without SSH

On your PC (in the project folder):

```bash
php artisan key:generate --show
```

Copy the `base64:...` line into Bluehost `.env`.  
**Do not** reuse a key that was pasted in chat or committed to git.

Save the file.

---

## E. Bluehost — import the SQL file

1. cPanel → **phpMyAdmin**.
2. Click your empty database `username_towerline` in the left sidebar.
3. Tab **Import**.
4. Choose file → select `towerline.sql`.
5. Click **Go** / **Import**.
6. Wait until you see success. You should see tables like `users`, `towers`, `licenses`, `sessions`, etc.

**If import fails because the file is too large:**

- cPanel → phpMyAdmin → Import → try again, or  
- Split is rarely needed for TowerLine demo size; if needed, use **Compress** the sql to `.sql.zip` and import that, or raise limits / use Bluehost “Upload a dump” tools.

**Note:** Local dump may contain `CREATE DATABASE` / `USE towerline`. If phpMyAdmin errors on that:

- Edit `towerline.sql` in Notepad and **delete** lines like:
  - `CREATE DATABASE ...`
  - `USE towerline;`
- Then import again into `username_towerline` (select that DB first).

Spatial/MySQL `POINT` data from XAMPP usually imports fine on Bluehost MySQL/MariaDB. If a tower location-related statement fails, tell M2B — we can strip `location` updates and rely on lat/lng.

---

## F. Storage link (photos) without SSH

Inspection photos and license files need `public/storage` → `storage/app/public`.

**Without SSH**, in File Manager:

1. Go to `/home/USERNAME/towerline/public/`
2. If a folder `storage` already exists as a normal folder, delete it only if empty/unused.
3. Create a **symbolic link** if your File Manager supports it:  
   - Link name: `storage`  
   - Target: `/home/USERNAME/towerline/storage/app/public`  
4. If File Manager cannot create symlinks, open a Bluehost support ticket:  
   > Please create symlink: `public/storage` → `../storage/app/public` for account USERNAME path towerline.

Also ensure folders are writable: `storage` and `bootstrap/cache` (permissions **755** or **775**).

---

## G. Open the site

1. Visit `https://towers.yourdomain.com` (enable AutoSSL / Force HTTPS in cPanel).
2. Login (demo accounts from local seed, if you imported that data).
3. **Change all passwords** immediately.
4. Check: Dashboard, Map, Towers, one inspection upload, Licenses.

Quick demo login buttons stay **hidden** when `APP_ENV=production`.

---

## Checklist (ZIP + SQL method)

- [ ] `npm run build` done; `public/build` inside zip  
- [ ] `vendor/` inside zip  
- [ ] `.env` **not** in zip; created on server with Bluehost DB + new `APP_KEY`  
- [ ] Document root = `towerline/public`  
- [ ] PHP 8.3+  
- [ ] `towerline.sql` imported into Bluehost DB  
- [ ] Storage link / photo uploads work  
- [ ] HTTPS on  
- [ ] Demo passwords changed  

---

## Common problems (ZIP + SQL)

| Problem | Fix |
|---|---|
| Site shows Bluehost default page | Document root must be `.../towerline/public` |
| White screen / 500 | PHP 8.3+; check `storage/logs/laravel.log`; fix `.env` |
| “No application encryption key” | Set `APP_KEY` in `.env` |
| CSS looks broken | Zip was missing `public/build` — rebuild and re-upload that folder |
| DB connection error | Use full cPanel names (`username_towerline`), host `localhost` |
| Import error on `USE towerline` | Remove `CREATE DATABASE` / `USE` lines from `.sql` |
| Photos 404 | Create `public/storage` symlink (section F) |

---

## Optional: SSH method (later)

If SSH works, you can use Artisan instead of SQL import (`migrate --seed`). See the older notes below only if you prefer that.

```bash
cd ~/towerline
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache
```

For MoCIT pilot on shared Bluehost, **ZIP + SQL is enough**.

---

*TowerLine · Bluehost · ZIP upload + SQL import*
