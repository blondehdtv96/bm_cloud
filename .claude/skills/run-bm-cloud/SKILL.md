---
name: run-bm-cloud
description: Set up, run, start, build, test, smoke-test and screenshot bm_cloud (SMKBM Cloud Storage, Laravel 12 + Vue SPA). Use when asked to run/jalankan the app, start the server, log in as admin, check an upload/download/API change, or take a screenshot of a page (dashboard, drive, admin).
---

SMKBM Cloud Storage: Laravel 12 API (Sanctum bearer tokens) + Vue 3 SPA built by Vite.
Drive it with `php artisan serve` plus two drivers next to this file:
`smoke.sh` (curl API flow) and `browse.mjs` (zero-dependency headless Chrome
over CDP: real login form, then a screenshot per route).

All paths are relative to the repo root. Verified on Windows 10 + XAMPP
(PHP 8.2.12), Composer, Node 26, Google Chrome, from the Bash tool (Git Bash).

## Prerequisites

- PHP >= 8.2 with `pdo_sqlite`, `fileinfo`, `zip` (XAMPP's `C:\xampp\php` has them).
- Composer, Node >= 22 (`browse.mjs` uses the global `WebSocket`), Google Chrome.
- No MySQL needed: `.env.example` defaults to SQLite.

## Setup (once, on a fresh clone)

```bash
composer install --no-interaction
cp -n .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --force --seed      # roles, permissions, admin user
php artisan storage:link
npm ci                                   # not `npm install`: that rewrites package-lock.json
npm run build                            # -> public/build (the SPA is served from here)
```

Seeded login: **username `admin` / password `admin123`** (email `admin@smkbm.sch.id`, role Super Admin).

## Run (agent path)

Start the server in the background (Bash tool `run_in_background: true`):

```bash
php artisan serve --host=127.0.0.1 --port=8000 --no-ansi
```

Wait for it, then drive it:

```bash
timeout 30 bash -c 'until curl -sf http://127.0.0.1:8000/ >/dev/null; do sleep 1; done'

# API: login -> /me -> create folder -> upload -> list -> download+compare -> delete
bash .claude/skills/run-bm-cloud/smoke.sh                 # ends with "SMOKE OK"

# UI: logs in via the login form, screenshots each route
node .claude/skills/run-bm-cloud/browse.mjs dashboard drive admin/users
```

`browse.mjs` writes `storage/logs/shots/login.png`, `dashboard.png`,
`drive.png`, `admin_users.png` (gitignored), prints the first 120 chars of each
page's text, and prints any JS exceptions or `console.error` output as `PAGE ERRORS`.
**Open the PNGs with Read and look at them.**

To interact (change a filter, click a button) set `BM_EVAL` to an async JS body. It runs
in the page after the route's screenshot, may call `sleep(ms)`, its return value is
printed, and then `<route>_after.png` is taken. Example, tested on the log page:

```bash
BM_EVAL='const s = document.querySelectorAll(".glass-card select")[0]; s.value = "delete"; s.dispatchEvent(new Event("change")); await sleep(900); return document.querySelector(".glass-card .border-t span").innerText;' \
  node .claude/skills/run-bm-cloud/browse.mjs admin/logs
```

Options (env vars): `BM_URL`, `BM_USER`/`BM_PASS` (default admin/admin123),
`BM_WIDTH=390 BM_HEIGHT=844` for the mobile layout, `SHOTS=<dir>`, `CHROME=<path>`.
Routes (from `resources/js/router/index.js`): `dashboard`, `drive`, `drive/<folderId>`,
`shared`, `favorites`, `trash`, `search`, `profile`, `drive-monitor`, `admin`,
`admin/users`, `admin/roles`, `admin/logs`, `admin/backup`.

Manual API call with a token:

```bash
T=$(curl -s -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}' http://127.0.0.1:8000/api/login \
  | php -r 'echo json_decode(stream_get_contents(STDIN))->access_token;')
curl -s -H "Accept: application/json" -H "Authorization: Bearer $T" http://127.0.0.1:8000/api/me
```

After changing anything under `resources/js` or `resources/css`, rerun
`npm run build` before `browse.mjs`. The server serves `public/build`, not Vite.

Stop: kill the background `php artisan serve` task.

## Run (human path)

`composer dev` runs serve + queue listener + pail logs + Vite HMR together. Open
http://127.0.0.1:8000. Useless headless. Not used by the drivers.

## Test

```bash
php artisan test --no-ansi     # 2 passed (only the Laravel example tests exist)
```

## Gotchas

- **Git Bash rewrites `/dashboard` to `C:/Program Files/Git/dashboard`** (MSYS path
  conversion) before Node sees it, and Chrome rejects it with
  `Page.navigate: Cannot navigate to invalid URL`. Pass routes without the leading
  slash (`dashboard`). `browse.mjs` also strips the Git prefix if it gets one.
- **`GET /api/files` returns 500** (`Call to undefined method FileController::index()`):
  `apiResource('files')` registers `index`, but the controller has none. The SPA lists
  folder contents with `GET /api/folders?folder_id=<id>` instead, and so does `smoke.sh`.
- **Tailwind width utilities lose to `.form-control`**: `resources/css/app.css` is unlayered,
  so `.form-control { width: 100% }` beats Tailwind v4's layered utilities (`sm:w-40`,
  `w-full`, ...), and controls overflow their container. Size form controls with scoped CSS
  in the component (see `AdminLogs.vue`). Check with `BM_EVAL` + `getBoundingClientRect()`.
- **The login API field is `username`** (it also accepts the email in that field), not `email`.
- **Delete is soft**: files deleted by `smoke.sh` go to the trash (Sampah) and still count
  toward the dashboard's "Total File" and storage numbers. Expect leftover `smoke.txt` entries.
- **Vue v-model inputs**: setting `.value` alone does nothing. Dispatch an `input` event,
  as `browse.mjs` does. The SPA keeps its token in `localStorage['bmclouds_token']`.
- `npm install` warns that `vue-demi` install scripts were skipped. The build still works.
