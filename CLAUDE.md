# HK Builders & Developers: Website + CRM

The complete context for this project. Claude Code loads this file automatically when the repo folder is opened, so any laptop starts with the same knowledge. Keep it current: when something important changes (new module, new migration, new rule), update this file in the same commit.

Last full review: 2026-10-27 (before moving to a new laptop).

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
3. **Push every finished change (and every finished phase) to both remotes** (pre-approved by the owner): `git push origin master` and `git push abdulrehman688 master:main`. If a push is rejected, `git fetch <remote>` + `git rebase <remote>/<branch>`, then push again. Claude Code's auto mode refuses to push to `origin master` itself (it is a production deploy) and refuses force pushes, so Claude pushes the mirror and gives the owner the one-line deploy command: `git -C C:/xampp/htdocs/hkbuildersanddevelopers push origin master`.
4. **Pushing to `origin master` deploys to the live site within minutes** (Hostinger auto-deploy). There is no staging. If the change needs a migration, the owner runs the SQL first, then deploys.
5. **Database changes are SQL migration files** in `crm/database/migrations/`. The owner runs them in Hostinger phpMyAdmin after exporting a backup. When code depends on a new migration, give the owner the SQL in a code block and tell them to run it right away, because the code is live the moment it's pushed.
6. **Never write secrets** (DB passwords, API keys, real user passwords) into docs, chat, commits or new files.
7. **Test before pushing.** CRM model methods catch SQL errors, log them and return empty arrays, so broken SQL shows up as silently empty pages, not errors. Lint PHP, test SQL against a real database, and check the UI on desktop and at phone width (375, 360, 320px). See section 11.
8. **How the owner likes to work:** short, plain-English replies. For new features, explain your understanding and the plan first and wait for a "yes". For bug reports, explain the cause in plain words first. Give SQL meant for phpMyAdmin as a ready-to-paste code block.

---

## 3. New laptop setup, A to Z (Windows)

### 3.1 Install
1. **Git for Windows** (includes Git Bash and Git Credential Manager).
2. **XAMPP** with PHP 8.2 into `C:\xampp` (gives Apache, MariaDB 10.4, phpMyAdmin). PHP 8.0 (XAMPP 8.0.30) also works; the code was tested on it.
3. **Node.js LTS** (website preview with `npx http-server`, and the Android app build).
4. **Claude Code** desktop app, signed in.
5. Only to build the Android app: Java 21 and the Android SDK, see 3.6.

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

Files that are **not in git** and must be copied by hand from the old laptop:
- **`%USERPROFILE%\hk-crm-signing\` (critical):** the Android app signing key `hk-crm-release.jks` and `PASSWORD-README.txt`. Without it no app update can be installed over the agents' current app. Copy it to the same place on the new laptop (and keep the backup on Google Drive / USB).
- `crm/config/config.local.php`, only if the credentials move in section 14 has been done.
- `.claude/` (local Claude Code settings, optional).

Recreated on the new laptop (see 3.6): `mobile-app/android/local.properties`, `mobile-app/android/keystore.properties`, `mobile-app/node_modules/`.

Old laptop note: on the second laptop there was also a stale clone at `C:\Users\NLN\OneDrive\Documents\projects\hkbuildersanddevelopers` (Claude Code's session folder there). Never work in it; only `C:\xampp\htdocs\hkbuildersanddevelopers` is current.

### 3.3 Local database (`crm_system`)
Open the XAMPP Control Panel and start **Apache** and **MySQL**. Then pick one:

**Option A, real data (recommended):** in Hostinger hPanel → Databases → phpMyAdmin, select `u813506845_crm` → Export → Go. Locally open http://localhost/phpmyadmin, create database `crm_system` (collation `utf8mb4_unicode_ci`), select it, Import the file. You then log in locally with your normal CRM login. This copy contains real client data, so keep the laptop protected.

**Option B, empty database with sample data** (tested 2026-10-06, run in PowerShell):
```powershell
cd C:\xampp\htdocs\hkbuildersanddevelopers\crm\database
$mysql = "C:\xampp\mysql\bin\mysql.exe"
& $mysql -u root -e "CREATE DATABASE crm_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
foreach ($f in "full_install.sql", "migrations/accounts_tables.sql", "migrations/builders_tables.sql", "migrations/builder_units.sql", "migrations/commission_payments_v2.sql", "migrations/builder_units_v2.sql", "migrations/contact_logs.sql", "migrations/agent_locations.sql") {
    & $mysql -u root crm_system -e "source $f"
}
```
This creates 24 tables plus sample users and leads (no sample clients). The sample admin login is in the "Done! Login" comment at the end of `crm/database/full_install.sql`. Sample agents have unknown passwords; to test as an agent locally, copy the admin's password hash onto that user in the local database only (`UPDATE users SET password=(SELECT p FROM (SELECT password p FROM users WHERE id=1) t) WHERE id=8;`).

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

If Apache or MySQL were started from a Claude Code shell, they stop when that session ends; start them from the XAMPP Control Panel instead.

### 3.6 Android app build tools (only to build a new APK)
No admin rights needed, about 1.5 GB. The Android SDK licence must be accepted (the owner accepted it on 2026-10-25).
```powershell
# Java 21 (Eclipse Temurin) into %USERPROFILE%\.jdks
curl.exe -L -o $env:TEMP\jdk21.zip "https://api.adoptium.net/v3/binary/latest/21/ga/windows/x64/jdk/hotspot/normal/eclipse"
Expand-Archive $env:TEMP\jdk21.zip "$env:USERPROFILE\.jdks"
# Android command-line tools into %LOCALAPPDATA%\Android\Sdk\cmdline-tools\latest
curl.exe -L -o $env:TEMP\cmdtools.zip "https://dl.google.com/android/repository/commandlinetools-win-11076708_latest.zip"
Expand-Archive $env:TEMP\cmdtools.zip $env:TEMP\ct
New-Item -ItemType Directory -Force "$env:LOCALAPPDATA\Android\Sdk\cmdline-tools" | Out-Null
Move-Item $env:TEMP\ct\cmdline-tools "$env:LOCALAPPDATA\Android\Sdk\cmdline-tools\latest"
# User environment variables (open a new terminal afterwards)
$jdk = (Get-ChildItem "$env:USERPROFILE\.jdks" -Directory | Where-Object Name -like 'jdk-21*')[0].FullName
[Environment]::SetEnvironmentVariable('JAVA_HOME', $jdk, 'User')
[Environment]::SetEnvironmentVariable('ANDROID_HOME', "$env:LOCALAPPDATA\Android\Sdk", 'User')
# SDK packages (accept the licence when asked)
$sm = "$env:LOCALAPPDATA\Android\Sdk\cmdline-tools\latest\bin\sdkmanager.bat"
& $sm --licenses
& $sm "platform-tools" "platforms;android-36" "build-tools;36.0.0"
```
Then, in `mobile-app\android\`:
- `local.properties`: one line `sdk.dir=C:/Users/<you>/AppData/Local/Android/Sdk`
- `keystore.properties` (never commit), using the password from `PASSWORD-README.txt`:
  ```
  storeFile=C:/Users/<you>/hk-crm-signing/hk-crm-release.jks
  storePassword=<password>
  keyAlias=hkcrm
  keyPassword=<password>
  ```
Build steps are in section 9a.

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
  .htaccess               DirectoryIndex index.php, RewriteBase /crm/, everything to index.php?url=..., security headers
  config/config.php       environment detection, DB credentials, APP_URL, timezone
  config/database.php     PDO singleton
  app/controllers/        AdminController, AgentController, AuthController, AccountsController, BuildersController, CallController, DistributeController, LocationController
  app/models/             Lead, User, Team, Client, Notice, Accounts, Builder, ContactLog, LeadDistributor, AgentLocation
  app/helpers/            Security, AuditLog, CsvImporter, CallUi (Call button, call popup, Contact History card)
  app/views/admin/        admin pages (accounts/ and builders/ subfolders)
  app/views/agent/        agent and sales manager pages
  app/views/shared/       pages used by both panels (distribute.php)
  app/views/layouts/      admin.php, agent.php (sidebar, topbar, notifications)
  app/views/auth/         login.php
  css/app.css             the whole CRM design system
  js/app.js               sidebar, openModal/closeModal, Call button + call popup
  database/               full_install.sql + migrations/ (web access denied)
  docs/                   older HTML planning docs (work-plan, project-insights)
mobile-app/               HK CRM Android app (Capacitor), see section 9a; web access denied
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
| `/admin/{dashboard, leads, lead/{id}, agents, agent/{id}, teams, clients, client/{id}, followups, calls, locations, notices, reports, csv-template, password, audit}` | AdminController |
| `/admin/accounts[/commission, /commission-ajax, /expenses, /salaries]` | AccountsController |
| `/admin/builders[/projects, /units, /payments, /detail/{id}, /statement/{id}]` | BuildersController |
| `/agent/{dashboard, leads, pool, clients, lead/{id}[/convert], claim/{id}, team, team-agent/{id}, followups, notices, password}` | AgentController |
| `/admin/notif-count`, `/agent/notif-count` | JSON for follow-up notification polling |
| `/admin/distribute`, `/agent/distribute` | DistributeController: Distribute Leads page (admin: all agents; sales manager: own team) |
| `/agent/location` | LocationController: JSON location ping (agents and sales managers; one point as form fields, or `{"points":[...]}` from the Android app) |
| `/admin/call-log`, `/agent/call-log` | CallController, JSON behind the Call button (`action=start` / `finish`) and the app's call log sync (`numbers` / `sync`), CSRF token in the `X-CSRF-Token` header |

**Config:** `APP_ENV` is `production` when the host contains `hkbuildersanddevelopers.com`, otherwise `development` (shows PHP errors). That decides the DB credentials and `APP_URL`. Timezone is `Asia/Karachi`, and the PDO session runs at `+05:00`. Never save a PHP file with a UTF-8 BOM: the BOM bytes are printed before any output and break JSON responses (`config.php` and `database.php` had BOMs until 2026-10-18).

**PDO:** exceptions on, real prepared statements (`ATTR_EMULATE_PREPARES` false), so a named placeholder can't be reused within one query.

**Controllers:** POST handlers check the CSRF token, perform the action, set `$_SESSION['success'|'error']`, then redirect (post/redirect/get). GET handlers load data and `require` the view.

**Models:** read methods use `try { ... } catch (\Throwable $e) { error_log(...); return []; }`. Write methods throw.

**Views:** set `$pageTitle` and `$activePage`, `ob_start()`, output HTML, then `$content = ob_get_clean(); require APP_ROOT . '/app/views/layouts/admin.php';`.

**Helpers:** `Security::` startSession, requireLogin/requireAdmin/requireAgent, csrfField (hidden input HTML) / verifyCsrfToken, `e()` for HTML escaping, rate limiting. (`Security::csrfToken()` does not exist.) `AuditLog::log($action, $userId, $targetType, $targetId, $detail)`.

**Roles** (`users.role`): `admin` (everything under `/admin`), `sales_manager` (agent panel plus My Team), `agent`.

---

## 7. CRM modules

- **Agent location** (`agent_locations`): while an agent or sales manager is logged in and the CRM is open, `js/app.js` sends their position (with accuracy and battery) at most once a minute: every 3 minutes, or sooner after moving 150 m. The server also skips web points less than 60 s apart. A top bar badge shows Allow location (amber, browser still asking) / Location on (green) / Location off (red). **Location is required (owner, 2026-10-19):** until the browser gives a position, a full-screen notice covers the CRM for agents and sales managers (not admin); it cannot be closed, only logged out of. Its Allow location button asks again (a request from a button press makes Chrome show its popup). It appears straight away when permission is blocked or not yet given, but waits up to 4 s when location is already allowed so pages do not flash. A weak signal after a good fix does not lock the CRM. Laptops need Windows location turned on. The Android app must enforce the same rule. Admin **Agent Locations** (`/admin/locations`): Leaflet + OpenStreetMap from cdnjs, no API key; live map of each agent's last position (green = point in the last 15 minutes, grey = older), refreshed every minute from `?format=json`; table with status, last seen, accuracy, battery; **Route** for one agent and day (`?agent_id=&date=`) with start/last markers, first/last seen and distance (fixes worse than 500 m are left out of the line and the distance). A browser only sends points while the page is open; the Android app sends background points through the same endpoint (`source = app`, deduplicated by `device_point_id`, no CSRF token needed for its JSON posts), see 9a.
- **Distribute Leads** (`/admin/distribute`, sales managers `/agent/distribute`): **Share Equally** deals unassigned leads one by one (hot first, then oldest) to the ticked agents, so each gets the same number; leftovers go to the agents with the fewest open leads (open = status not closed). Admin can pick any active agent or sales manager (agents ticked by default); a sales manager only the agents in their team. **Assign by Hand**: tick leads, pick an agent. Admin can also tick leads on All Leads and assign or reassign them in bulk. A lead is only taken while still unassigned, so a pool claim at the same moment wins and the lead is skipped. Every assignment adds a timeline entry ("Assigned to X by Y (auto distribute / manual / bulk)") and one audit log row. The pool and claiming stay as before.
- **Leads:** pool and claiming, assignment, statuses, sources, activity timeline, CSV import (template at `/admin/csv-template`), follow-ups and meetings. Overdue follow-ups show as a page banner, a sidebar badge and a count in the tab title; the browser polls `notif-count`.
- **Clients:** a lead converts to a client "file" with `file_status` mature/immature (plus a reason) and a booking amount. Agents see their own clients at `/agent/clients`.
- **Calls** (`contact_logs`): a green **Call** button (`CallUi::button`) on every lead and client (lists, detail pages, follow-ups, pool). Tapping it saves the call (`start`), then the phone dialer opens through the normal `tel:` link. When the agent comes back, a popup asks for the result (answered, no answer, busy, switched off, wrong number), talk time (pre-filled from the time away) and a note (`finish`); the first finish also adds a "Call" entry to the lead timeline. A lead and the client it became share one **Contact History** card (calls, answered, total talk time, last call, every call). Admin **Call Log** at `/admin/calls`: totals, per-agent talk time, every call, filters, print. Agents may call their own leads and clients and unclaimed pool leads; admin may call anyone. The phone number always comes from the record, never from the browser. Talk time from the popup is approximate (shown as "approx."); the Android app fills exact durations from the phone's call log (`source = phone_log`, deduplicated by `device_call_id`, shown as "exact") and also adds incoming and missed calls, see 9a. A popup without talk time keeps the exact time already synced.
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
- **24 tables:** users, teams, leads, lead_sources, lead_statuses, lead_activities, follow_ups, import_batches, clients, client_payments, notices, notice_completions, audit_log, commission_payments, commission_pending_reason, expenses, builders, builder_projects, builder_units, builder_payments, contact_logs, agent_locations, plus legacy `salaries` and `income` (the code doesn't use them).
- **Migration history, in order** (all applied on production as of 2026-09-30):
  1. `full_install.sql`: schema + v2 + v3 + sample data. Fresh databases only; the old `schema.sql`, `seed.sql`, `migration_v2*.sql` and `migration_v3.sql` are history it already contains.
  2. `migrations/accounts_tables.sql`: commission_payments, commission_pending_reason, expenses.
  3. `migrations/builders_tables.sql`: builders, builder_projects, builder_payments.
  4. `migrations/builder_units.sql`: builder_units.
  5. `migrations/commission_payments_v2.sql`: project, dates, payment source, reference on commission_payments.
  6. `migrations/builder_units_v2.sql`: unit maturity, builder_payments.unit_id, foreign keys, turned hand-marked "paid" units into payments.
  7. `migrations/contact_logs.sql`: contact_logs (calls with leads and clients, talk time). Applied on production 2026-10-18.
  8. `migrations/agent_locations.sql`: agent_locations (agent position while logged in). Applied on production 2026-10-25.
- **Rules for new migrations:** ids are `INT UNSIGNED`, and foreign key columns must match exactly (a mismatch causes errno 150). Use standard SQL that works on MySQL 5.7/8 and MariaDB. Test on a scratch database first, add the file to this list, and give the owner the SQL with "Export a backup first".

---

## 9. Deploy and production

- Hostinger auto-deploys `origin/master` (GitHub onecinfinity) into `public_html`: the website at the root and the CRM at `/crm`. No build, no staging.
- Database changes are manual: hPanel → Databases → phpMyAdmin → SQL tab. Always Export first.
- Backups: Hostinger takes automatic backups (how often depends on the plan); export manually before every migration.
- Security headers come from `crm/.htaccess`. Folders with code, config or SQL deny web access.

---

## 9a. Android app (HK CRM APK)

- **What it is:** `mobile-app/` is a Capacitor 8 app (package `com.hkbuilders.crm`, name HK CRM) that opens the live CRM (`server.url` in `capacitor.config.json`). CRM changes reach the app as soon as they are deployed; a new APK is only needed when `mobile-app/` itself changes. Shared directly with agents (WhatsApp), not the Play Store. `mobile-app/.htaccess` denies web access because the repo deploys to `public_html`.
- **Native part** (`mobile-app/android/app/src/main/java/com/hkbuilders/crm/`): `HkNativePlugin` (JS name `HkNative`: `getStatus`, `requestLocation`, `requestBackground`, `requestCallLog`, `requestNotifications`, `openAppSettings`, `openLocationSettings`, `requestBatteryUnrestricted`, `startTracking({url, login})`, `stopTracking`, `getCallLog({since})`) and `LocationService`, a foreground service (permanent notification) that posts location as JSON `{"points":[...]}` straight to `/agent/location` with the WebView's cookies, also with the app closed. Offline points are queued and resent. When the CRM answers "not logged in" it opens `/login` once with the remember-me cookie, stores the new cookies and retries, else stops. The login page ticks "Remember me" by default in the app (user agent contains `HKCRMApp`).
- **CRM side** (`js/app.js`, `hkNative()` is null in a normal browser): agents must finish 4 steps before the CRM opens: location, "Allow all the time", phone Location on, call logs. Then tracking starts, battery optimisation is asked once, and the call log syncs on open, on return to the app, every 5 minutes and after each call popup: `call-log` `action=numbers` gives the last 10 digits of the agent's lead/client numbers (plus pending Call-button calls), only matching calls are sent (`action=sync`), so personal calls never leave the phone. Sync fills the exact talk time of a Call-button call (same number within 5 minutes) or adds incoming, missed and dialer calls with the agent's leads and clients (`source = phone_log`, shown as "exact"). In the app the call popup does not ask for talk time. Logout stops the service.
- **Build** (Windows; JDK 21 in `%USERPROFILE%\.jdks`, Android SDK in `%LOCALAPPDATA%\Android\Sdk`, user env `JAVA_HOME` and `ANDROID_HOME`; `android/local.properties` has `sdk.dir`):
  ```powershell
  cd C:\xampp\htdocs\hkbuildersanddevelopers\mobile-app
  npm install; npx cap sync android
  cd android; .\gradlew.bat assembleRelease    # -> app\build\outputs\apk\release\app-release.apk
  ```
  `npm run icons` redraws the icon and splash (`make-icon.js`).
- **Signing key:** `%USERPROFILE%\hk-crm-signing\hk-crm-release.jks`, password in `PASSWORD-README.txt` beside it; `android/keystore.properties` (gitignored) points to it. Never commit either. Every update must use the same key or phones refuse it; the owner keeps a backup.
- **Releasing an update:** raise `versionCode` (and `versionName`) in `android/app/build.gradle`, build, send the APK; agents install it over the old one.
- **Current version:** 1.0 (versionCode 1), built 2026-10-25, sent to the owner as `HK-CRM-1.0.apk`. Not yet confirmed working on a real phone.
- **Offline page:** `www/offline.html` (Capacitor `server.errorPath`) shows on any failed first load, including an HTTP error from the server, not only when there is no internet. That is how the 403 on `/crm/` (fixed 2026-10-25 with `DirectoryIndex index.php` in `crm/.htaccess`) looked like "No internet connection".
- **iPhone:** not built. It needs a Mac with Xcode and an Apple Developer account (about $99/year); installs only through the App Store, TestFlight or Ad Hoc (no file sharing like the APK); iOS lets no app read the call log, so no exact talk time or dialer/incoming calls, and background location is less regular. iPhone agents can use the CRM in Safari (Share > Add to Home Screen); location then only sends while the page is open. Waiting for the owner to say how many agents use iPhones.

---

## 10. CRM UI conventions

- **Theme:** navy and gold, Cormorant Garamond plus Jost, CSS variables (`--navy`, `--gold`, `--bg`, `--bg-card`, `--text`, `--text-muted`, `--border`, `--border-light`). Money is shown as `'PKR ' . number_format($v, 0)`.
- **Page skeleton:** `.page-header` (h1, breadcrumb, `.page-header-actions` buttons), then sub-nav tabs `div.sub-nav`, stat chips `div.chip-row`, filters `.filter-bar > form[method=GET] > .filter-row > select.filter-input`, and a `.card` holding the table.
- **Tables:** `.card > div(overflow-x:auto) > table.data-table.table-cards`. Every CRM list table uses `table-cards` (except the printable builder statement). Mark cells with `.tc-title` (card heading), `.tc-sub` (line under it), `.tc-full` (full width), `.tc-actions` (buttons) and `.tc-hide` (hidden on phones, e.g. a row number). `js/app.js` fills in what a view leaves out: `data-label` from the column header, the first cell as `.tc-title`, and a button cell under an empty header as `.tc-actions`; anything set in the HTML wins. On phone screens each row turns into a card, while desktop and print keep the table.
- **Phone rules in `app.css`:** under 768px, inline grids written as `grid-template-columns:1fr 1fr`, `300px 1fr`, `290px 1fr` or `minmax(340px, ...)` collapse to one column, `repeat(4,1fr)` to two, and `.sub-nav` tab rows scroll sideways instead of overflowing. Prefer the classes `.two-col` / `.form-grid` for new layouts. `.stats-grid` stays two columns down to 320px.
- **Call button:** `CallUi::button($phone, $leadId, $clientId)` wherever a lead or client phone number is shown; it renders nothing when there is no number. Views that use it `require_once` `app/helpers/CallUi.php`.
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
- Ignored by git: `/.claude/`, `node_modules/`, `package*.json` (re-included for `mobile-app/`), `index.html.broken-backup`; in `mobile-app/`: build outputs, `local.properties`, `keystore.properties`, `*.jks`, `*.apk`.
- The local `main` branch on the second laptop is an old copy with duplicate commit IDs; work on `master` only.

---

## 13. Recent history (Sept to Oct 2026)

- Modal forms scroll properly everywhere; global print reset; print buttons on report pages.
- Contact page map now points at United Palm Greens.
- Accounts: Expenses period filters and date range; Commission overhaul (project dropdown, dates, payment source and reference, automatic paid amount, money chips).
- Builders: Units section; payments linked to units; maturity status and filters; commission, paid and unpaid on every builder page and the statement; phone card layout for all Builders tables.
- `full_install.sql` fixed (it created an outdated `expenses` table) and fresh-install steps verified; this file replaces `crm/SESSION-CONTEXT.md` and `crm/FEATURES.md`.
- 2026-10-18, phase 1 of the mobile app plan: Call button on every lead and client, call result popup, Contact History card, admin Call Log, agent My Clients page; phone card layout on every CRM table; inline grids collapse on phones; apostrophes no longer break Edit buttons; sales manager's team agent page shows booking and commission again (`booking_amount` key); marking a follow-up done no longer writes an invalid activity type.
- 2026-10-18, phase 2: Distribute Leads page for admin and sales managers (share equally, assign by hand), bulk assign on All Leads.
- 2026-10-19, phase 3: agent location sharing while logged in (top bar badge), admin Agent Locations map with live positions and daily routes.
- 2026-10-25, phase 4: HK CRM Android app (APK 1.0): background location service, required permissions screen, exact talk time from the phone call log, navy and gold icon.
- 2026-10-25: `/crm/` answered 403 on the live server (root `DirectoryIndex index.html`), so the app showed its offline page; fixed in `crm/.htaccess` and deployed.
- **Mobile app plan (owner approved 2026-10-18):** 1) calls and talk time (done), 2) lead distribution (done): Auto Distribute splits unassigned leads equally between active agents (admin: all agents, sales manager: own team), plus manual bulk assign; the pool and claiming stay, 3) agent location every few minutes the whole time an agent is logged in, any hour (owner confirmed), admin map (OpenStreetMap) (done 2026-10-19), 4) Android APK (Capacitor) wrapping the live CRM with background location and call-log sync for exact talk time (built 2026-10-25, see 9a). The APK is shared directly with agents, not through the Play Store.

---

## 14. Open items

1. **Security, high priority:** the production database password is committed in `crm/config/config.php`. It was also exposed in the old `crm/SESSION-CONTEXT.md`, which was removed on 2026-10-06 but is still in git history. Move the credentials to an untracked `crm/config/config.local.php` (create it on Hostinger by hand first), then change the database user's password in hPanel.
2. **Android app 1.0 not yet confirmed on a real phone.** After the 403 fix the owner should reopen the app (Try again). Then check: the 4 permission steps, the location notification, the agent staying on the admin map with the phone locked for 10 minutes, and exact talk time after a Call-button call. Fix anything found in version 1.1.
3. App 1.1 (next time the APK is rebuilt anyway): change the offline page wording from "No internet connection" to something like "Could not open the CRM", since it also shows for server errors.
4. iPhone: decision pending (see 9a).
5. Idea: link leads and clients to builder projects (`builder_id`, `project_id`) so a converted client can become a unit automatically.
