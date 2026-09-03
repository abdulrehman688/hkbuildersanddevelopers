# CRM Features Tracker

---

## Pending / Not Built

### 1. Accounts Section (Admin Panel - Sidebar after Teams)
No separate role or login. Admin-only section. Sidebar: Dashboard > Leads > Agents > Clients > Teams > **Accounts** > Reports > Follow-ups > Notices > Audit Log

**A. Financial Dashboard (Accounts home)**
- Total revenue from mature files (booking amounts)
- Total commission earned / paid / remaining
- Total marketing spend this month / all time
- Total salaries paid this month / all time
- General expenses summary
- Net overview

**B. Agent Commission Management**
- Table: Agent | Mature commission | Immature commission (held) | Paid | Remaining
- Mature file commission = booking_amount × commission_rate (mature files only, payable now)
- Immature commission = held until file status changed to mature
- Click agent → their files breakdown (mature/immature list) + payment history
- Record commission payment (amount, date, note)
- Remaining due = mature commission earned - total paid

**C. Marketing Expenses**
- Manually add: campaign name, amount, date, category (Facebook / Banners / Events / Other)
- Monthly and all-time spend summary
- Per-campaign breakdown table

**D. Salaries**
- Record monthly salary payment per agent/staff
- Payroll history log
- base_salary already stored in users table

**E. General Expenses**
- Office rent, utilities, misc
- Category-based, manually entered

**F. Commission Pending Reason Tracking**
- When commission is not paid, admin marks WHY:
  - `immature_file` - file not yet matured, payment on hold
  - `insufficient_funds` - file is mature but company has not received enough from builder yet
- This reason shows on agent commission page and agent detail page
- Helps admin explain to agent why their commission is pending

**New DB tables needed:**
- `commission_payments` (agent_id, amount, date, note, created_by)
- `commission_pending_reason` (agent_id, client_id, reason: immature_file/insufficient_funds, note, marked_by, created_at)
- `expenses` (type: marketing/salary/general, category, amount, date, note, created_by)

---

### 2. Builder Management Section (Admin Panel)
Track what each builder owes HK Builders for closed deals.

**Structure:**
- One builder can have multiple projects
- One project belongs to one builder
- Each client/deal gets linked to a builder + project

**A. Builders List Page** (`/admin/builders`)
- Table: Builder name, contact person, phone, total projects, total files, total owed, total received, remaining
- Add / Edit builder button

**B. Builder Detail Page** (`/admin/builder/{id}`)
- Builder profile (name, contact, phone, email, address, notes)
- Projects list for this builder with summary per project
- All files (clients) linked to this builder - mature/immature, deal value, commission
- Commission summary: Total Earned | Received | Remaining
- Payment history (payments received FROM builder)
- Record new payment button
- **Print Statement** button - shows full deal-by-deal breakdown + summary, printable/PDF

**C. Project Detail** (under builder)
- Project name, location
- Commission type: fixed amount per file OR percentage of booking amount (admin sets this per project)
- All files under this project
- Financial summary for this project

**D. Commission Tracking per Builder**
- Commission = booking_amount × commission_rate% OR fixed amount (per project setting)
- Mature files: commission payable now
- Immature files: commission held/pending
- Received: manually recorded payments from builder
- Remaining = mature commission - received

**E. Builder Detail - Summary Chips (Clickable)**
On builder detail page, show clickable stat chips:
```
Total Files: 48    Mature: 31    Immature: 17
Commission Received: Rs. 2.1M    Not Received: Rs. 890,000
```
- Click "Mature 31" → filtered table showing only mature files
- Click "Immature 17" → filtered table showing only immature files
- Click "Not Received" → filtered table of files with pending commission
- Each file row shows: client name, agent name, booking amount, commission amount, file status, date

**F. Agent breakdown inside Builder**
- Show which agents closed files for this builder
- Per agent: mature files count, immature files count, commission earned from this builder

**G. Printable Statement**
- Summary page: total mature, immature, received, remaining
- Full breakdown: each file - client name, file status, booking amount, commission, date
- HK Builders header/branding, builder name, date range filter
- Print button + print CSS

**New DB tables needed:**
- `builders` (id, name, contact_person, phone, email, address, notes, created_at)
- `projects` (id, builder_id, name, location, commission_type: amount/percentage, commission_value, notes, created_at)
- `builder_payments` (id, builder_id, project_id, amount, date, note, created_by, created_at)

**DB changes needed:**
- `leads` table: add `builder_id`, `project_id` columns (currently `project` is just free text)
- `clients` table: add `builder_id`, `project_id` columns (same issue)

---

### 3. Admin Dashboard - Lifetime File Stats
Add to existing admin dashboard:
- Clickable stat chips for files (clients):
```
Total Files: 79    Mature: 48    Immature: 31
```
- Click "Mature 48" → clients list filtered to mature files
- Click "Immature 31" → clients list filtered to immature files
- These are lifetime totals, shown alongside existing lead stats

---

### 4. Agent Detail Page - Full Career History
Enhance existing `/admin/agent/{id}` page with:
- Year-by-year business breakdown (2024 | 2025 | 2026: leads, won, business value, commission)
- Commission summary card: Mature earned | Immature (held) | Paid | Remaining due
- File status column (Mature/Immature) added to Won Deals table
- Commission payment history (linked to accountant payments once built)

---

### 5. Activity Feed - Missing Audit Log Events
Add `AuditLog::log()` calls to AdminController for all missing actions.
Activity feed at `/admin/notices?tab=activity` should show everything.

**Currently NOT logged (need to add):**
| Action | Where to add |
|--------|-------------|
| Agent created | `AdminController::createAgent()` |
| Agent suspended | `AdminController::toggleAgent()` |
| Agent activated | `AdminController::toggleAgent()` |
| Agent password reset by admin | `AdminController::resetAgentPassword()` |
| Agent profile edited | `AdminController` agent update |
| Team created | `AdminController::teams()` create block |
| Team updated | `AdminController::teams()` edit block |
| Team deleted | `AdminController::teams()` delete block |
| Agent assigned to team | `AdminController::teams()` assign_agent block |
| Agent removed from team | `AdminController::teams()` remove_agent block |
| Notice created | notice create handler |
| Notice deleted | notice delete handler |
| Lead converted to client | `AgentController` convert block |
| CSV leads imported | `AdminController::processImport()` |
| Lead assigned to agent | `AdminController` reassign handler |

---

### 6. Reports Page - Agent Performance Leaderboard
Enhance existing agent performance table in `/admin/reports`.

**What exists already:**
- Agent performance table sorted by total leads DESC
- Date from/to filter
- Shows: total, active, won, lost, last activity

**What's missing:**
- **Rank number** (#1, #2, #3) shown as badge on each row
- **Team name** shown next to agent name
- **Sorted by WON/closed leads** (not total leads) - most closed on top
- **Quick month picker** - dropdown to pick a specific month (Jan 2026, Feb 2026 etc.) instead of only date range
- **Visual rank badges** - gold for #1, silver for #2, bronze for #3
- Sales managers included in ranking (currently query only fetches `role = 'agent'`)

**How it should look:**
```
#   Agent          Team          Total  Won  Lost  Active  Conv%
1   Ahmed Khan     Alpha Team    47     19   8     20      70%   [gold badge]
2   Bilal Mirza    Beta Team     38     15   10    13      60%   [silver badge]
3   Sara Ahmed     Alpha Team    29     12   5     12      71%   [bronze badge]
4   Ali Hassan     —             22     5    9     8       36%
```

---

## Already Exists (Do Not Rebuild)

| # | Feature | Where |
|---|---------|-------|
| 1 | Follow-ups & Meetings | `/agent/followups`, `/admin/followups` |
| 2 | Notifications (banner, badge, tab title, auto-refresh) | Layout `admin.php` |
| 3 | Commission rate per agent | Admin > Agents > Agent Detail |
| 4 | Mature / Immature file status | `clients.file_status` column, set when converting lead to client. Mature = complete deal, Immature = flagged/incomplete. Agent sets this during conversion. |
| 5 | Booking amount per client | `clients.booking_amount` column |
| 6 | Estimated commission on agent detail | Admin > Agents > click agent (estimate only, no paid/remaining) |
| 7 | Payroll table skeleton | `database/schema.sql` - has columns but no UI built |
