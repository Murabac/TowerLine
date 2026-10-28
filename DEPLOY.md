# TowerLine — Coolify deploy

Same pattern as Nova: **Dockerfile + FrankenPHP on port 80**, MySQL linked in Coolify, persistent `storage`.

## Coolify General screen

| Field | Set to |
|---|---|
| Build Pack | **Dockerfile** (not Nixpacks) |
| Ports Exposes | **80** (not 3000 / 8080) |
| Base Directory | `/` |
| Publish Directory | empty |
| Watch Paths | empty (deploy on every push) |
| Install / Build / Start | leave empty — the Dockerfile handles them |
| Pre-deploy command | empty (entrypoint already migrates) |
| Post-deploy command | empty |

Also:

1. **Deployment server** — pick the same Coolify server you used for Nova.
2. **Git** — this repo (`Murabac/TowerLine` or your Coolify source), branch `main`.
3. **MySQL** — add a **MySQL 8** database in Coolify, then **link it** to this app so `DB_HOST` / `DB_PORT` / `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` are injected. Do not reuse Nova’s database.
4. **Persistent storage** — mount a volume to `/app/storage` (complaint photos, letters, logs survive deploys).
5. **Domain** — assign the Coolify URL or `towerline.…` hostname.
6. **Healthcheck** — leave Coolify healthcheck enabled; the image includes `curl` and checks `/healthz` (180s start period for first migrate).

## Environment tab

Coolify injects DB_* when the database is linked. Add:

```env
APP_NAME=TowerLine
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...
APP_URL=https://YOUR-COOLIFY-URL
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_TIMEZONE=Africa/Mogadishu
LOG_LEVEL=error
SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local
RUN_MIGRATIONS=true
```

Generate `APP_KEY` with Coolify’s generator, or locally:

```bash
php artisan key:generate --show
```

Set `APP_URL` to the **https** URL Coolify shows (including the domain). Do not leave it as `http://localhost`.

## First deploy

1. Save the Coolify app, link MySQL, paste env, add the `/app/storage` volume.
2. Deploy. First build installs Composer + `npm run build` (a few minutes).
3. Open `/healthz` — should return `ok` (healthcheck). `/up` should show Laravel’s up page once storage is writable.
4. Open `/login`.

**Empty Coolify database (new site):** after the first migrate, run once from Coolify **Execute command**:

```bash
php artisan db:seed --force
```

That creates regions, operators, demo staff (`admin@mocit.local` / `password`). Change passwords immediately.

**Keep Bluehost / ministry data:** do **not** seed. Import your `towerline.sql` into the Coolify MySQL (phpMyAdmin or `mysql` client), then deploy with `RUN_MIGRATIONS=true` so new tables (complaints, etc.) still apply.

## After it is up

- [ ] `https://…/login` loads over HTTPS
- [ ] CSS/JS load (no mixed-content) — if assets are http, `APP_URL` or proxy trust is wrong
- [ ] Login as admin, open Map
- [ ] Upload a complaint photo and confirm it survives a **redeploy** (storage volume)

No cron is required for TowerLine today (unlike Nova’s invoice schedule).

## Files this uses

- `Dockerfile` — vendor + Vite build + FrankenPHP PHP 8.3
- `docker/entrypoint.sh` — storage link, caches, migrate
- `.dockerignore` — keeps `.env` and `node_modules` out of the image
