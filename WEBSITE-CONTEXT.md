# HK Builders & Developers: Website UI & Structure Context

This document contains the visual inspection, user interface architecture, design tokens, and functional structure of the public-facing marketing website for HK Builders & Developers.

Date of inspection: 2026-11-12
Inspected environment: Local preview server (http://localhost:8123) matching production (https://www.hkbuildersanddevelopers.com).

---

## 1. Overview & Technical Stack

- **Technology:** Static HTML5, modern CSS3, vanilla JavaScript. No build step, bundler, or framework.
- **Hosting & Serving:** Deployed to Hostinger `public_html/`. Root `.htaccess` manages canonical redirects to `https://www.`, browser caching, gzip compression, and security rules.
- **Local Preview:** Served via `npx http-server -c-1 -p 8123` (XAMPP Apache redirects to https://www by default due to root `.htaccess`).
- **Separation of Concerns:** The marketing website is completely decoupled from the CRM (`/crm`). Leads from the website route to Web3Forms and direct WhatsApp chats, not directly into CRM tables.

---

## 2. Design System & Visual Tokens

The website shares its luxury aesthetic with the CRM, using a consistent corporate identity.

### Color Palette
- **Deep Navy (Backgrounds & Canvas):** `#050B14`, `#0B1528`, `#0F1D36`
- **Card & Overlay Navy:** `#13223E`, `rgba(11, 21, 40, 0.85)`
- **Champagne & Metallic Gold (Accents & Highlights):** `#C5A059`, `#D4AF37`, `#E0C588`
- **Gold Linear Gradients:** `linear-gradient(135deg, #C5A059 0%, #F5E6BE 50%, #C5A059 100%)`
- **Text & Neutral Contrast:** Crisp off-white (`#F8F9FA`), muted grey-silver (`#9AA5B5`, `#A0AEC0`), dark text on gold badges (`#0B1528`)
- **Borders & Dividers:** Subtle gold borders (`rgba(197, 160, 89, 0.25)` to `rgba(197, 160, 89, 0.6)`)

### Typography
- **Headings & Display:** Cormorant Garamond serif display font. Applied to hero headlines, section titles, and key pull-quotes to convey elegance and architectural prestige.
- **Body & Controls:** Clean geometric sans-serif (Jost / system UI fallback) for navigation, buttons, chips, tabular specs, and descriptive body copy.
- **Micro-Copy & Badges:** Uppercase letter-spaced badges (`letter-spacing: 2px` or `1.5px`) with rounded pills and subtle borders.

### Interactive Components & Motion
- **Page Preloader:** Shared `assets/css/loader.css` and `assets/js/loader.js` rendering a branded monogram with an animated loading percentage bar on page initialization.
- **Trust Counters:** JavaScript-driven numerical counter animation triggering when stats scroll into view.
- **Infinite Marquee:** Continuous horizontal text ribbons showcasing project titles and brand values.
- **Orbital Graphics:** Circular rotating orbit effect behind the company emblem and experience badges.
- **Floating Contact Hub:** Persistent floating WhatsApp button (`wa.me/923252208153`) on the bottom-right corner across all pages.

---

## 3. Page Structure & UI Breakdown

### A. Home Page (`/index.html`)
- **Hero Banner:** Full-viewport dark hero featuring headline "Building Trust. Creating Futures.", gold badge "PREMIER REAL ESTATE AGENCY", and quick actions ("EXPLORE PROJECTS" and "CONTACT US").
- **Trust Stats Ribbon:** 4 metric counters:
  - 10+ Years Experience
  - 500+ Happy Families
  - 4 Prime Projects
  - 100% Verified Titles
- **Spotlight Project:** Heroic showcase of "United Palm Greens" (Al Waheed Group) branded as "Most Profitable Project", detailing location (Malir / Scheme 45), payment plan (3-year quarterly), and direct CTA.
- **Marketing Projects Grid:** 4 structured cards featuring high-res imagery, gold hover borders, and project specs:
  1. *United Palm Greens* (Al Waheed Group: Gated Community)
  2. *Falaknaz Wonder City* (Falaknaz Group: Residential & Commercial)
  3. *Falaknaz Hills View* (Falaknaz Group: Scenic Living)
  4. *Falaknaz Overseas Block* (Falaknaz Group: Expat-Focused Residential)
- **Client Reviews & Testimonials:** 5-star gold ratings with genuine client feedback quotes.
- **Global Footer:** 4-column layout including mission statement, quick navigation links, marketed projects, direct contact details, and copyright.

### B. Projects Directory (`/projects/`)
- **Hero:** "Our Portfolio: Exclusive Projects in Prime Locations".
- **Project Catalog:** Detailed breakdowns of partnered projects with unit types, installment schedules, floor sizes, and direct enquiry modal/links.
- **Data Hook:** Syncs with `assets/js/projects.json` for dynamic filtering and project attributes.

### C. Dedicated Project Landing Pages
Each major project has an independent landing page tailored for targeted ad traffic:
- `/united-palm-greens/`: Comprehensive layout with site plan, installment schedule, location map, amenities list, and booking CTA.
- `/falaknaz-wonder-city/`: Highlights commercial and residential opportunities on Main Northern Bypass.
- `/falaknaz-hills-view/`: Features hill-facing units, natural topography, and gated features.
- `/falaknaz-overseas-block/`: Targets overseas Pakistani investors with flexible remittance options.

### D. About Us (`/about/`)
- **Mission & Vision:** Narrative focusing on safe investments and developer partnerships.
- **Experience Emblem:** 10+ Years in Market visual orbital graphic.
- **Core Pillars:** 4 cards highlighting "Integrity First", "Verified Titles Only", "End-to-End Support", and "Direct Developer Pricing".
- **Leadership & Values:** Background on HK's advisory role in Karachi's real estate sector.
- **Founder spotlight + leader cards:** "The Visionary Behind HK" (Hunain Azmat Khan, Founder & Chairman), then three cards (Chairman, CFO, COO) linking to the Board page. The same founder spotlight is on the Home page.

### D2. Board of Directors (`/board-of-directors/`)
- **Hero:** breadcrumb, "Board of Directors", the three names as jump links.
- **Chairman:** founder spotlight (portrait in an offset gold frame, name plate, lead text, signature, 3 facts).
- **Executive Leadership:** zigzag rows for Shayan Azmat Khan (CFO) and Tahnan Azmat Khan (COO) with role badge, text and focus chips.
- **Brand Portraits:** the client's three original posters as a gallery ("As Featured in Our Campaigns"); a swipe row on phones. Each opens in a `<dialog>` lightbox (arrows, keyboard, swipe, Esc). Each leader also has a "View Brand Portrait" link that opens the same lightbox. Files: `<slug>-poster-640/1000/1600.webp` and `-poster-640.jpg`.
- **Share image:** `og-board-of-directors.jpg` (1200x630, Hunain's poster on a blurred copy of itself, gold edge).
- **Leadership Principles:** Integrity, Vision, Client First, Accountability.
- **CTA:** Get In Touch and WhatsApp.
- Styles: `assets/css/leadership.css`. JSON-LD: Organization with founder and employees, Person per leader, BreadcrumbList.

### E. Satisfied Clients (`/clients/`)
- **Proof & Trust:** "Stories of Trust & Delivery" hero banner.
- **Featured Client Profile:** Detailed spotlight on verified file buyers (e.g. Mr. Babar & Family for Falaknaz Hills View) with photos, unit types, and verified badges.
- **Client Gallery:** Testimonial grid reading data from `assets/js/clients-gallery.json`.

### F. Contact Us (`/contact/`)
- **Hero Banner:** "Get in Touch: Let's Talk Property".
- **Direct Channels:** 4 card blocks:
  - Phone & WhatsApp: `+92 325 2208153`
  - Email: `hkbuildersanddevelopers21@gmail.com`
  - Office: Karachi, Pakistan
  - Hours: Monday to Saturday (10:00 AM to 8:00 PM)
- **Interactive Form:** Inquiry form routing via Web3Forms API (`https://api.web3forms.com/submit`).
- **Interactive Map:** Embedded Google Maps iframe centered on United Palm Greens / scheme location.

---

## 4. Key Data Files & Assets

| File / Folder | Purpose |
|---|---|
| `assets/js/projects.json` | Master project catalog (slug, name, developer, location, payment plans, features) |
| `assets/js/clients-gallery.json` | Client testimonials, buyer pictures, purchased projects, review text |
| `assets/images/projects/` | Project photography folders: `united-palm-green/`, `falaknaz-hills-view/`, `Falaknaz-overseas-block/`, `Falaknaz-wonder-city/` |
| `assets/images/clients/` | Client photos and handover ceremony pictures |
| `assets/css/loader.css` | Splash screen styles and keyframe animations |
| `assets/js/loader.js` | Loader dismiss logic and percentage counting |
| `sitemap.xml` | SEO index listing all active pages and landing pages |
| `robots.txt` | Crawler directives allowing full public crawling |

---

## 5. UI/UX Observations & Notes

1. **Asset Directory Naming:**
   - The on-disk image folder is `assets/images/projects/united-palm-green/` (singular), while the web page route is `/united-palm-greens/` (plural). Image references in `index.html` correctly reference the singular path.
2. **Navigation Scheme:**
   - The home page uses a full navbar with quick links.
   - Internal subpages (`/projects/`, `/about/`, `/clients/`, `/contact/`) rely on the branded gold hamburger drawer menu for navigation, which provides access to all subpages, contact numbers, and social links.
3. **Mobile Responsiveness:**
   - Grid cards collapse cleanly from 4 columns to 2 columns on tablets, and 1 column on phones (< 768px).
   - Touch targets for phone calling (`tel:`) and WhatsApp chat (`wa.me`) are prominent for mobile users.
4. **No Build Step:**
   - Direct edits to HTML, CSS, and JSON files take immediate effect without compilation or bundling.
