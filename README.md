# TowerLine

Wasaaradda Isgaarsiinta iyo Technology — tower registry, map, inspections, frequencies, reports, and approval letters for Somaliland MoCIT.

English is the default. Somali is available from the header toggle.

## Local demo

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan storage:link
php artisan serve --host=127.0.0.1 --port=8001
```

Open http://127.0.0.1:8001 and sign in as `admin@mocit.local` / `password`. Other demo accounts are listed in **[HANDOVER.md](HANDOVER.md)**.

Public (no login): **[Guidelines](http://127.0.0.1:8001/guidelines)** and **[Apply](http://127.0.0.1:8001/apply)**.

Staff training, pilot steps, and server notes: **[HANDOVER.md](HANDOVER.md)**. Locked product decisions: **[CONTEXT.md](CONTEXT.md)**.

## Tests

```bash
php artisan test
```
