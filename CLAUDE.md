# HK Builders & Developers: Website + CRM

The complete context for this project. Claude Code loads this file automatically when the repo folder is opened, so any laptop starts with the same knowledge. Keep it current: when something important changes (new module, new migration, new rule), update this file in the same commit.

Last full review: 2026-10-06.

---

## 1. What this is

**The business.** HK Builders & Developers is a Karachi real estate marketing company. It sells plots and units for builders (for example Al Waheed Group's *United Palm Greens*, and the Falaknaz projects) and earns a commission per unit from the builder. "Commission", "rebate" and "profit" all mean the same thing to the owner. HK's own agents in turn earn commission from HK.

**One git repo, two apps:**

| App | Tech | Live URL |
|---|---|---|
| Website | Static HTML/CSS/JS, no build step | https://www.hkbuildersanddevelopers.com |
| CRM | PHP 8 custom MVC, PDO, MySQL/MariaDB | https://www.hkbuildersanddevelopers.com/crm |

The website and the CRM are not connected: the website's contact form goes to Web3Forms and WhatsApp, not into the CRM.

---

## 2. Working rules (read before changing anything)

1. **Never use the em dash character** (the long dash) anywhere: code, comments, UI text, SQL, docs, commit messages, chat. Use a comma, hyphen, colon or middot instead. When you edit a file that already contains one, replace it.
2. **One local clone only:** `C:\xampp\htdocs\hkbuildersanddevelopers`. An older second clone (`D:\remote job\hkbuilders`, with the remote names swapped) caused push conflicts; don't work there.
3. **Push every finished change to both remotes** (pre-approved by the owner): `git push origin master` and `git push abdulrehman688 master:main`. If a push is rejected, `git fetch <remote>` + `git rebase <remote>/<branch>`, then push again.
4. **Pushing to `origin master` deploys to the live site within minutes** (Hostinger auto-deploy). There is no staging.
5. **Database changes are SQL migration files** in `crm/database/migrations/`. The owner runs them in Hostinger phpMyAdmin after exporting a backup. When code depends on a new migration, give the owner the SQL in a code block and tell them to run it right away, because the code is live the moment it's pushed.
6. **Never write secrets** (DB passwords, API keys, real user passwords) into docs, chat, commits or new files.
7. **Test before pushing.** CRM model methods catch SQL errors, log them and return empty arrays, so broken SQL shows up as silently empty pages, not errors. Lint PHP, test SQL against a real database, and check the UI on desktop and at phone width (375, 360, 320px). See section 11.
8. **How the owner likes to work:** short, plain-English replies. For new features, explain your understanding and the plan first and wait for a "yes". For bug reports, explain the cause in plain words first. Give SQL meant for phpMyAdmin as a ready-to-paste code block.

---

## 3. New laptop setup, A to Z (Windows)

### 3.1 Install
1. **Git for Windows** (includes Git Bash and Git Credential Manager).
2. **XAMPP** with PHP 8.2 into `C:\xampp` (gives Apache, MariaDB 10.4, phpMyAdmin).
3. **Node.js LTS** (only used to preview the static website with `npx http-server`).
4. **Claude Code** desktop app, signed in.

### 3.2 Clone and set remotes
```powershell
cd C:\xampp\htdocs
git clone https://github.com/onecinfinity/hkbuildersanddevelopers.git
cd hkbuildersanddevelopers
git remote add abdulrehman688 https://github.com/abdulrehman688/hkbuildersanddevelopers.git
git config user.name "Abdul Rehman"
git config user.email "you@example.com"   # your real email
git fetch abdulrehman688
```
Remotes must end up as: `origin` = onecinfinity (Hostinger deploys its `master`), `abdulrehman688` = mirror (push `master` to its `main`). The first push opens a GitHub sign-in; use an account with write access to both repos.

Files that are **not in git** and must be copied by hand if they exist on the old laptop: `crm/config/config.local.php` (only if the credentials move described in section 14 has been done), and `.claude/` (local Claude Code settings, optional).

### 3.3 Local database (`crm_system`)
Open the XAMPP Control Panel and start **Apache** and **MySQL**. Then pick one:

**Option A, real data (recommended):** in Hostinger hPanel → Databases → phpMyAdmin, select `u813506845_crm` → Export → Go. Locally open http://localhost/phpmyadmin, create database `crm_system` (collation `utf8mb4_unicode_ci`), select it, Import the file. You then log in locally with your normal CRM login. This copy contains real client data, so keep the laptop protected.

**Option B, empty database with sample data** (tested 2026-10-06, run in PowerShell):
```powershell
cd C:\xampp\htdocs\hkbuildersanddevelopers\crm\database
$mysql = "C:\xampp\mysql\bin\mysql.exe"
& $mysql -u root -e "CREATE DATABASE crm_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
foreach ($f in "full_install.sql", "migrations/accounts_tables.sql", "migrations/builders_tables.sql", "migrations/builder_units.sql", "migrations/commission_payments_v2.sql", "migrations/builder_units_v2.sql", "migrations/contact_logs.sql") {
    & $mysql -u root crm_system -e "source $f"
}
```
This creates 23 tables plus sample users and leads (no sample clients). The sample admin login is in the "Done! Login" comment at the end of `crm/database/full_install.sql`.

### 3.4 Run it
- **One-time CRM junction (required).** `crm/.htaccess` uses `RewriteBase /crm/` (correct for production), so locally `/crm` must also exist inside htdocs. Without it every CRM page except real files returns 404. No admin rights needed:
  ```powershell
  New-Item -ItemType Junction -Path C:\xampp\htdocs\crm -Target C:\xampp\htdocs\hkbuildersanddevelopers\crm
  ```
  Alternative (used on the second laptop): an `Alias /crm "C:/xampp/htdocs/hkbuildersanddevelopers/crm"` block with `DirectoryIndex index.php` at the end of `C:\xampp\apache\conf\extra\httpd-xampp.conf`. The `DirectoryIndex` line also fixes the 403 on the bare `/crm` folder URL, which the root `.htaccess` (`DirectoryIndex index.html`) otherwise causes.
- **CRM:** http://localhost/hkbuildersanddevelopers/crm. `crm/config/config.php` detects localhost and uses `crm_system` / `root` / no password.
- **Website:** XAMPP can't show it, because the root `.htaccess` redirects every request to `https://www.`. From the repo root run `npx http-server -c-1 -p 8123` and open http://localhost:8123.

### 3.5 Claude Code
Open the folder `C:\xampp\htdocs\hkbuildersanddevelopers` in Claude Code; this file loads automatically. For the website preview, create `.claude/launch.json` in the repo root (the folder is gitignored):
```json
{
  "version": "0.0.1",
  "configurations": [
    { "name": "website", "runtimeExecutable": "npx", "runtimeArgs": ["http-server", "-c-1", "-p", "8123"], "port": 8123 }
  ]
}
```
Claude Code's personal memory does not move between laptops, but everything it held for this project is in this file.

---

## 4. Repository map

```
/                         website root (deployed to public_html)
  index.html              home page
  about/ clients/ contact/ projects/ project/        pages (each folder has index.html)
  falaknaz-hills-view/ falaknaz-overseas-block/ falaknaz-wonder-city/ united-palm-greens/   project landing pages
  assets/css/loader.css   page loader styles
  assets/js/loader.js     page loader script (included on every page)
  assets/js/projects.json        project data (project/?id=<id> renders from it)
  assets/js/clients-gallery.json satisfied-clients gallery data
  assets/images/...       project and client photos; assets/images.zip is an archive copy
  sitemap.xml robots.txt  SEO
  *Schema.txt, Real Estate.txt   notes and JSON-LD drafts (not used by pages)
  CNAME                   leftover from GitHub Pages (hkbuildersanddevelopers.arehman.me)
  .htaccess               www + HTTPS redirects, caching, gzip, blocks *.md files
  CLAUDE.md               this file
crm/
  index.php               front controller and route table
  .htaccess               RewriteBase /crm/, everything to index.php?url=..., security headers
  config/config.php       environment detection, DB credentials, APP_URL, timezone
  config/database.php     PDO singleton
  app/controllers/        AdminController, AgentController, AuthController, AccountsController, BuildersController, CallController
  app/models/             Lead, User, Team, Client, Notice, Accounts, Builder, ContactLog
  app/helpers/            Security, AuditLog, CsvImporter, CallUi (Call button, call popup, Contact History card)
  app/views/admin/        admin pages (accounts/ and builders/ subfolders)
  app/views/agent/        agent and sales manager pages
  app/views/layouts/      admin.php, agent.php (sidebar, topbar, notifications)
  app/views/auth/         login.php
  css/app.css             the whole CRM design system
  js/app.js               sidebar, openModal/closeModal, Call button + call popup
  database/               full_install.sql + migrations/ (web access denied)
  docs/                   older HTML planning docs (work-plan, project-insights)
```
`crm/app`, `crm/config` and `crm/database` each have an `.htaccess` with `Require all denied`.

---

## 5. Website

- Plain HTML pages, one folder per page, no build. Edit the HTML directly.
- **Projects:** add or change entries in `assets/js/projects.json` (ids like `united-palm-greens`), add images under `assets/images/projects/<project>/`, optionally add a landing page folder, and update `sitemap.xml`.
- **Contact page:** Web3Forms (`fetch('https://api.web3forms.com/submit')`), WhatsApp links (`wa.me/923252208153`), and a Google Maps iframe pointing at United Palm Greens.
- Design: navy and gold, the same palette as the CRM; the page loader is shared by every page.

---

## 6. CRM architecture

**Request flow:** `crm/.htaccess` rewrites `/crm/<path>` to `index.php?url=<path>` (query strings kept). `index.php` splits the path into `section/action/param/sub` and dispatches:

| URL | Handler |
|---|---|
| `/login`, `/logout` | AuthController |
| `/admin/{dashboard, leads, lead/{id}, agents, agent/{id}, teams, clients, client/{id}, followups, calls, notices, reports, csv-template, password, audit}` | AdminController |
| `/admin/accounts[/commission, /commission-ajax, /expenses, /salaries]` | AccountsController |
| `/admin/builders[/projects, /units, /payments, /detail/{id}, /statement/{id}]` | BuildersController |
| `/agent/{dashboard, leads, pool, clients, lead/{id}[/convert], claim/{id}, team, team-agent/{id}, followups, notices, password}` | AgentController |
| `/admin/notif-count`, `/agent/notif-count` | JSON for follow-up notification polling |
| `/admin/call-log`, `/agent/call-log` | CallController, JSON behind the Call button (`action=start` / `finish`), CSRF token in the `X-CSRF-Token` header |

**Config:** `APP_ENV` is `production` when the host contains `hkbuildersanddevelopers.com`, otherwise `development` (shows PHP errors). That decides the DB credentials and `APP_URL`. Timezone is `Asia/Karachi`, and the PDO session runs at `+05:00`. Never save a PHP file with a UTF-8 BOM: the BOM bytes are printed before any output and break JSON responses (`config.php` and `database.php` had BOMs until 2026-10-18).

**PDO:** exceptions on, real prepared statements (`ATTR_EMULATE_PREPARES` false), so a named placeholder can't be reused within one query.

**Controllers:** POST handlers check the CSRF token, perform the action, set `$_SESSION['success'|'error']`, then redirect (post/redirect/get). GET handlers load data and `require` the view.

**Models:** read methods use `try { ... } catch (\Throwable $e) { error_log(...); return []; }`. Write methods throw.

**Views:** set `$pageTitle` and `$activePage`, `ob_start()`, output HTML, then `$content = ob_get_clean(); require APP_ROOT . '/app/views/layouts/admin.php';`.

**Helpers:** `Security::` startSession, requireLogin/requireAdmin/requireAgent, csrfField (hidden input HTML) / verifyCsrfToken, `e()` for HTML escaping, rate limiting. (`Security::csrfToken()` does not exist.) `AuditLog::log($action, $userId, $targetType, $targetId, $detail)`.

**Roles** (`users.role`): `admin` (everything under `/admin`), `sales_manager` (agent panel plus My Team), `agent`.

---

## 7. CRM modules

- **Leads:** pool and claiming, assignment, statuses, sources, activity timeline, CSV import (template at `/admin/csv-template`), follow-ups and meetings. Overdue follow-ups show as a page banner, a sidebar badge and a count in the tab title; the browser polls `notif-count`.
- **Clients:** a lead converts to a client "file" with `file_status` mature/immature (plus a reason) and a booking amount. Agents see their own clients at `/agent/clients`.
- **Calls** (`contact_logs`): a green **Call** button (`CallUi::button`) on every lead and client (lists, detail pages, follow-ups, pool). Tapping it saves the call (`start`), then the phone dialer opens through the normal `tel:` link. When the agent comes back, a popup asks for the result (answered, no answer, busy, switched off, wrong number), talk time (pre-filled from the time away) and a note (`finish`); the first finish also adds a "Call" entry to the lead timeline. A lead and the client it became share one **Contact History** card (calls, answered, total talk time, last call, every call). Admin **Call Log** at `/admin/calls`: totals, per-agent talk time, every call, filters, print. Agents may call their own leads and clients and unclaimed pool leads; admin may call anyone. The phone number always comes from the record, never from the browser. Talk time from the popup is approximate; the Android app (phase 4) will fill exact durations from the phone's call log (`source = phone_log`, deduplicated by `device_call_id`).
- **Agents and teams:** agent detail page with performance, yearly history and print; teams led by sales managers.
- **Notices:** announcements and tasks with completions; activity feed at `/admin/notices?tab=activity`.
- **Reports:** agent performance leaderboard, CSV export, print. **Audit log** at `/admin/audit`.
- **Accounts** (`/admin/accounts`):
  - **Commission** = what HK owes its agents (`commission_payments`). The project is picked from builder projects; records have sale and due dates and a payment source and reference. Payment status is paid or pending, and `paid_amount` is set automatically (the full amount when paid, 0 when pending). Chips show Total, Paid, Remaining and Recently Paid. Picking an agent and project calls an AJAX summary of existing records.
  - **Expenses:** type marketing/salary/general, period pills plus a date range, print.
  - **Salaries:** expenses with `type='salary'`.
- **Builders** (`/admin/builders`) = what builders owe HK:
  - **Builders list:** commission, paid and unpaid per builder.
  - **Projects:** units, commission, paid, unpaid per project.
  - **Units:** the main page. Each unit sold has block, category, size, total cost, down payment, commission and maturity (one-click toggle). Paid comes from the payments linked to the unit, giving a status of Paid, Partial or Unpaid. **Mark Paid** records a real payment (full or part); **Mark Unpaid** deletes that unit's payments.
  - **Payments:** commission received from builders, optionally linked to a unit. The unit decides the project and the project decides the builder.
  - **Builder detail** and a printable **Statement** with Balance Due.
- **Money rules:** Unpaid = commission minus paid, floored at 0 (the global total is summed per builder). Payments not linked to a unit still count in totals; the Units page shows a "not linked to any unit" notice for them.

---

## 8. Database

- **Local:** `crm_system` (user `root`, no password). **Production:** `u813506845_crm` on Hostinger (MariaDB-style errors such as errno 150). Credentials live in `crm/config/config.php`; never copy them anywhere else.
- **23 tables:** users, teams, leads, lead_sources, lead_statuses, lead_activities, follow_ups, import_batches, clients, client_payments, notices, notice_completions, audit_log, commission_payments, commission_pending_reason, expenses, builders, builder_projects, builder_units, builder_payments, contact_logs, plus legacy `salaries` and `income` (the code doesn't use them).
- **Migration history, in order** (all applied on production as of 2026-09-30):
  1. `full_install.sql`: schema + v2 + v3 + sample data. Fresh databases only; the old `schema.sql`, `seed.sql`, `migration_v2*.sql` and `migration_v3.sql` are history it already contains.
  2. `migrations/accounts_tables.sql`: commission_payments, commission_pending_reason, expenses.
  3. `migrations/builders_tables.sql`: builders, builder_projects, builder_payments.
  4. `migrations/builder_units.sql`: builder_units.
  5. `migrations/commission_payments_v2.sql`: project, dates, payment source, reference on commission_payments.
  6. `migrations/builder_units_v2.sql`: unit maturity, builder_payments.unit_id, foreign keys, turned hand-marked "paid" units into payments.
  7. `migrations/contact_logs.sql`: contact_logs (calls with leads and clients, talk time). Added 2026-10-18; **not yet run on production** until the owner confirms.
- **Rules for new migrations:** ids are `INT UNSIGNED`, and foreign key columns must match exactly (a mismatch causes errno 150). Use standard SQL that works on MySQL 5.7/8 and MariaDB. Test on a scratch database first, add the file to this list, and give the owner the SQL with "Export a backup first".

---

## 9. Deploy and production

- Hostinger auto-deploys `origin/master` (GitHub onecinfinity) into `public_html`: the website at the root and the CRM at `/crm`. No build, no staging.
- Database changes are manual: hPanel → Databases → phpMyAdmin → SQL tab. Always Export first.
- Backups: Hostinger takes automatic backups (how often depends on the plan); export manually before every migration.
- Security headers come from `crm/.htaccess`. Folders with code, config or SQL deny web access.

---

## 10. CRM UI conventions

- **Theme:** navy and gold, Cormorant Garamond plus Jost, CSS variables (`--navy`, `--gold`, `--bg`, `--bg-card`, `--text`, `--text-muted`, `--border`, `--border-light`). Money is shown as `'PKR ' . number_format($v, 0)`.
- **Page skeleton:** `.page-header` (h1, breadcrumb, `.page-header-actions` buttons), then sub-nav tabs `div.sub-nav`, stat chips `div.chip-row`, filters `.filter-bar > form[method=GET] > .filter-row > select.filter-input`, and a `.card` holding the table.
- **Tables:** `.card > div(overflow-x:auto) > table.data-table.table-cards`. Every cell gets `data-label="..."`. Mark cells with `.tc-title` (card heading), `.tc-sub` (line under it), `.tc-full` (full width) and `.tc-actions` (buttons). On phone screens each row turns into a card automatically, while desktop and print keep the table.
- **Modals:** `.modal-overlay#id > .modal > .modal-header + form > (.modal-body + .modal-footer)`, opened with `openModal('id')`. The form inside `.modal` is a flex column; keep that chain or the body stops scrolling. Edit modals copy the Add modal's body HTML, then fill in values.
- **Alerts:** `.alert.alert-success`, `.alert-error`, `.alert-info`, `.alert-warning`.
- **Data in onclick attributes:** `$js = fn($v) => json_encode($v, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);` then `onclick='editX(<?= $js($row) ?>)'` (single-quoted attribute). Plain `json_encode` breaks on apostrophes.
- **Security:** escape all output with `Security::e()`, put `Security::csrfField()` in every POST form, and use prepared statements only.
- **Print:** `app.css` has a global `@media print` that hides the app chrome. Pages add a `body::before` branded header and hide filters and actions; wide reports use `@page { size: A4 landscape }`.

---

## 11. Testing

- **Lint:** `C:\xampp\php\php.exe -l <file>`.
- **Quick manual test:** XAMPP Apache and MySQL on, local `crm_system`, http://localhost/hkbuildersanddevelopers/crm.
- **Throwaway-database browser test** (never touches `crm_system`): start MariaDB, create e.g. `crm_scratch` with the Option B commands in 3.3, then serve the CRM with PHP's built-in server and a router that sets the DB first (`auto_prepend_file` does not apply to router scripts):
  ```php
  <?php // router.php (keep it outside the repo)
  $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
  if ($path !== '/' && is_file($_SERVER['DOCUMENT_ROOT'] . $path)) return false;
  error_reporting(E_ALL & ~E_WARNING);   // config.php will try to redefine these
  define('DB_HOST', '127.0.0.1'); define('DB_NAME', 'crm_scratch');
  define('DB_USER', 'root'); define('DB_PASS', ''); define('APP_URL', 'http://localhost:8765');
  $_GET['url'] = ltrim($path, '/');
  require $_SERVER['DOCUMENT_ROOT'] . '/index.php';
  ```
  Run `C:\xampp\php\php.exe -S localhost:8765 -t C:\xampp\htdocs\hkbuildersanddevelopers\crm router.php`. Model SQL errors appear only in that server's log (search for `SQLSTATE`). Drop the scratch database when done.
- **Phone check:** at 375, 360 and 320px wide, no page should scroll sideways.

---

## 12. Git

- **Commits:** a short summary line plus a bulleted body explaining why. Claude adds its Co-Authored-By line.
- **Push:**
  ```powershell
  git fetch origin; git rebase origin/master; git push origin master
  git fetch abdulrehman688; git rebase abdulrehman688/main; git push abdulrehman688 master:main
  ```
- Ignore the old branches `origin/main`, `abdulrehman688/master` and `abdulrehman688/imgbot`.
- Ignored by git: `/.claude/`, `node_modules/`, `package*.json`, `index.html.broken-backup`.

---

## 13. Recent history (Sept to Oct 2026)

- Modal forms scroll properly everywhere; global print reset; print buttons on report pages.
- Contact page map now points at United Palm Greens.
- Accounts: Expenses period filters and date range; Commission overhaul (project dropdown, dates, payment source and reference, automatic paid amount, money chips).
- Builders: Units section; payments linked to units; maturity status and filters; commission, paid and unpaid on every builder page and the statement; phone card layout for all Builders tables.
- `full_install.sql` fixed (it created an outdated `expenses` table) and fresh-install steps verified; this file replaces `crm/SESSION-CONTEXT.md` and `crm/FEATURES.md`.

---

## 14. Open items

1. **Security, high priority:** the production database password is committed in `crm/config/config.php`. It was also exposed in the old `crm/SESSION-CONTEXT.md`, which was removed on 2026-10-06 but is still in git history. Move the credentials to an untracked `crm/config/config.local.php` (create it on Hostinger by hand first), then change the database user's password in hPanel.
2. Other CRM views (accounts pages, leads, clients, agents, teams, notices) still put plain `json_encode` inside onclick attributes, so a name or note with an apostrophe breaks the Edit button. Switch them to the `$js` helper (section 10).
3. Apply the phone card layout (`table-cards`) to the Accounts tables and the other CRM tables.
4. Idea: link leads and clients to builder projects (`builder_id`, `project_id`) so a converted client can become a unit automatically.
