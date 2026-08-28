# CRM Session Context — July 12, 2026

## Current State

### Folder Structure
- **Main folder**: `C:\xampp\htdocs\hkbuildersanddevelopers` (website + CRM + `.git`)
- CRM files: `C:\xampp\htdocs\hkbuildersanddevelopers\crm\`
- Website files: `C:\xampp\htdocs\hkbuildersanddevelopers\` (root level)
- Junction: `C:\xampp\htdocs\CRM` → `hkbuildersanddevelopers\crm` (backward compat)
- **Local CRM URL**: `http://localhost/hkbuildersanddevelopers/crm/`
- **Local Website URL**: `http://localhost/hkbuildersanddevelopers/`
- **Production CRM**: `https://hkbuildersanddevelopers.com/crm`
- **Production Website**: `https://hkbuildersanddevelopers.com`

### Git Info
- Repo: `https://github.com/onecinfinity/hkbuildersanddevelopers.git`
- Branch: `master`
- Repo root: `C:\xampp\htdocs\hkbuildersanddevelopers`
- Git works directly from this folder
- **Push rule**: `git add crm/` for CRM changes only
- Hostinger auto-deploys from GitHub to `public_html/`

### Config Auto-Detection
- `config.php` auto-detects environment: `localhost` = development, else = production
- `.htaccess` has `RewriteBase /crm/` for production Hostinger routing
- No more manual toggling or sync scripts

### Tech Stack
- PHP (XAMPP local), custom MVC, PDO, MySQL/MariaDB
- DB local: `crm_system` / `root` / no password
- DB prod: `u813506845_crm` / `u813506845_user` / `Hunain@1212`

### CSS Design System
- Navy/Gold theme, Cormorant Garamond + Jost fonts
- CSS vars: `--navy`, `--gold`, `--gold-light`, `--bg`, `--bg-card`, `--text`, `--text-muted`, `--border`, `--border-light`
- Modal class: `modal-overlay` (NOT `modal-backdrop`), toggled with `openModal(id)`/`closeModal(id)` and `.open` class

### Key Patterns
- Views: set `$pageTitle`, `$activePage`, call `ob_start()`, output HTML, `$content = ob_get_clean(); require layouts/admin.php;`
- Security: `Security::csrfField()` returns hidden input HTML. **`Security::csrfToken()` DOES NOT EXIST**
- Audit: `AuditLog::log(string $action, ?int $userId, ?string $targetType, ?int $targetId)`

---

## Completed Work

1. **Fixed admin notices page** — `Security::csrfToken()` fatal error → replaced with `csrfField()`
2. **Added assigned/unassigned filter** to admin leads page filter bar
3. **Dashboard stat cards clickable** — Total, Unclaimed, In Progress, Won, Lost → link to filtered leads
4. **Task completion names** visible on admin notices page (who marked done)
5. **Mark-done activity** logged in Activity Feed + Audit Log
6. **File download** — attachments download instead of opening in browser
7. **Folder restructure** — single main folder at `hkbuildersanddevelopers`, auto-detect config, no sync script
8. **Follow-ups & Meetings feature** — `/agent/followups` and `/admin/followups` pages, sidebar links, model methods, routes, overdue badges on both dashboards

---

## Pending Work

### Notifications (Approved, Not Started)
1. Top banner alert (red=overdue, amber=within 30 min) on every page
2. Sidebar badge on Follow-ups link
3. Browser tab title with count: `(3) Dashboard — HK Builders CRM`
4. Auto-refresh JS every 5 minutes on dashboard

---

## Key Files Reference

| File | Purpose |
|------|---------|
| `crm/config/config.php` | Environment auto-detection, DB creds |
| `crm/index.php` | Router — routes for admin/agent sections |
| `crm/app/models/Lead.php` | Lead CRUD + follow-up methods |
| `crm/app/models/Notice.php` | Notices + completions |
| `crm/app/controllers/AgentController.php` | Agent actions |
| `crm/app/views/admin/dashboard.php` | Admin dashboard (clickable stat cards) |
| `crm/app/views/admin/leads.php` | Admin leads list (with claimed filter) |
| `crm/app/views/admin/notices.php` | Admin notices (with completion names) |
| `crm/app/views/agent/dashboard.php` | Agent dashboard (has follow-ups section) |
| `crm/app/views/agent/notices.php` | Agent notices |
| `crm/app/views/layouts/admin.php` | Layout wrapper (sidebar, topbar, CSS/JS) |
| `crm/app/helpers/AuditLog.php` | Audit logging helper |
| `crm/app/helpers/Security.php` | CSRF, auth helpers |
| `crm/database/schema.sql` | Full schema including follow_ups table |
