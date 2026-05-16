# Peddi — CLAUDE.md

Project context for Claude Code. Read this at the start of every session.

---

## Project Overview

**Peddi** is a multilingual dictionary platform built as a capstone project. Visitors search
dictionaries; admins manage content, run reports, and compare dictionaries. It must be deployed
and demoed live on Bluehost — never localhost-only.

The platform is named after the lexicographer **Peddi Sambasiva Rao** (Andhra Pradesh), whose
dictionaries are the primary content. Developed by **Siva Jasthi** (siva.jasthi@gmail.com).

---

## Tech Stack

| Layer      | Choice                                       |
|------------|----------------------------------------------|
| Backend    | Plain PHP (no frameworks, no Composer)       |
| Frontend   | HTML5, CSS3, Bootstrap 5, jQuery, DataTables |
| Database   | MySQL                                        |
| Libraries  | CDN only — no npm, no build tools            |
| Local dev  | XAMPP at `C:\xampp\htdocs\peddi`             |
| Production | Bluehost shared hosting                      |

CDN links to use (pin these versions):
```html
<!-- Bootstrap 5 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css">
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<!-- DataTables (load via $loadDataTables flag — see Page Flags below) -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<!-- Google Fonts: Cinzel (logo) -->
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&display=swap" rel="stylesheet">
```

---

## Folder Structure

```
/peddi
  /api
    telugu_length_search.php  ← Two-phase Telugu character search endpoint
  /admin
    index.php         ← iPad-style admin hub (landing page for all admin sections)
    dashboard.php     ← Stats cards + Chart.js bar chart
    dictionaries.php  ← CRUD for dictionaries (DataTables + modals)
    entries.php       ← CRUD for dictionary entries (DataTables + modals)
    upload.php        ← CSV/JSON/XLSX import; can create new dict inline
    compare.php       ← Side-by-side dictionary comparison & gap analysis
    integrity.php     ← Duplicate detection & orphan check
    export.php        ← Export to CSV or JSON
    login.php         ← Admin login form
    logout.php        ← Session destroy → redirect to login
  /assets
    /css
      main.css        ← All custom styles (Peddi logo, admin tiles, etc.)
    /js
      search.js           ← Standard search + Telugu character search
      admin-dashboard.js  ← Chart.js initialization
      admin-dictionaries.js
      admin-entries.js
      admin-upload.js
    /images
      peddi-logo.svg  ← Open-book SVG with embedded P letter
  /includes
    config.php        ← APP_BASE constant + DB credentials (gitignored)
    db.php            ← PDO connection + loadPrefs() helper
    auth.php          ← session_start, requireAdmin(), getCurrentUser(), logout()
    header.php        ← Full HTML head + public navbar (used by ALL pages)
    footer.php        ← Closing scripts + HTML (used by ALL pages)
    search_helper.php ← executeSearch(), paginationHtml(), highlightTerm()
    admin_header.php  ← LEGACY: no longer used by any page; kept for reference
  /uploads            ← Uploaded dictionary files (CSV/JSON)
  index.php           ← Public home page
  search.php          ← Public search + Telugu character search panel
  catalog.php         ← Dictionary catalog card grid
  preferences.php     ← Cookie preference form (theme, mode, dict, per-page)
  about.php           ← Credits page (lexicographer + developer)
  CLAUDE.md
  db_schema.sql
```

---

## Database Schema

Four tables — always use these exact column names:

```sql
dictionaries        (id, name, language_code VARCHAR(20), description, created_at)
dictionary_entries  (id, dictionary_id, word, translation, created_at)
users               (id, username, password_hash, role, created_at)
preferences         (id, pref_key, pref_value)
```

- `language_code` is `VARCHAR(20)` — extended from 10 to support combined codes like `eng-tel-hin`
- `dictionary_entries.dictionary_id` — FK → `dictionaries.id` with `ON DELETE CASCADE`
- `users.role` — `'admin'` or `'visitor'`
- `preferences` — site-wide system defaults; per-user prefs live in cookies
- All `created_at` columns — `TIMESTAMP DEFAULT CURRENT_TIMESTAMP`

**Dev admin credentials:** username `admin`, password `admin123`

---

## Navigation Architecture

**Single unified nav — `header.php` is used by every page, including all admin pages.**
There is no separate admin nav/header rendered at the page level.

Public navbar items: Home · Catalog · Search · Preferences · About · **Admin** (admin only)

- When logged in as admin, an **Admin** nav-link (gold, shield icon) appears in the nav list.
- For non-admins, a small **Admin Login** button appears on the right.
- `admin_header.php` still exists but is not included by any page — do not use it for new pages.

Admin entry point: `admin/index.php` — iPad-style hub with tile cards for every admin section.
From the hub, clicking a tile opens that section, which still shows the same public nav.

To add a new admin section:
1. Create `admin/new-section.php` — use `header.php` (not `admin_header.php`).
2. Add a tile entry to the `$tiles` array in `admin/index.php`.

---

## Page Flags (set before including header.php)

```php
$pageTitle      = 'My Page';       // required — shown in <title>
$pageScript     = 'assets/js/x.js'; // optional — loaded by footer.php
$loadDataTables = true;            // optional — loads DataTables CSS (header) + JS (footer)
$loadChartJs    = true;            // optional — loads Chart.js (footer)
```

`header.php` outputs the DataTables CSS link when `$loadDataTables` is set.
`footer.php` outputs the DataTables JS and/or Chart.js when those flags are set.

---

## Brand & Logo

- **Font:** Cinzel 700 (Google Fonts), loaded via CDN in `header.php`
- **CSS classes:** `.peddi-logo` (base gradient text), `.peddi-logo-nav` (navbar size 1.45rem), `.peddi-logo-xl` (hero size 3rem) — all in `main.css`
- **SVG logo:** `assets/images/peddi-logo.svg` — open book with letter P embedded; use as `<img>` tag at needed height (e.g. `height="34"` in nav, `height="80"` in about hero)
- **Color palette:** amber/gold gradient (`#8b4400 → #ffd060 → #8b4400`) for the Peddi wordmark
- **Tagline:** "Words that connect languages, cultures, and people."

---

## Telugu Character Search

Two-phase feature on `search.php` with its own collapsible panel:

**Phase 1 (SQL):** `WHERE word LIKE '%char%'` — fast SQL substring filter, capped at 120 candidates.

**Phase 2 (API):** For each candidate, call the external API:
```
https://ananya.telugupuzzles.com/api.php/characters/logical?language=Telugu&string=<word>
```
Returns `{"success":true,"response_code":200,"data":["అ","క్ష","ర"],"result":[...]}`.
Implemented with `curl_multi` for parallel requests.

**SSL on XAMPP (Windows):** Auto-detects CA bundle at:
- `C:\xampp\php\extras\ssl\cacert.pem`
- `C:\xampp\phpMyAdmin\vendor\composer\ca-bundle\res\cacert.pem` ← confirmed present

**Filter logic (5 steps):**
1. Phase 1 SQL returns candidates containing the character
2. Skip words whose API call failed
3. Check if the input character appears in logical chars (exact or partial — see Exact Match)
4. Check logical char count equals requested length
5. If position given, check the char is at that 1-indexed position

**Exact Match toggle:** When ON, logical char must equal input char (NFC-normalized `===`).
When OFF, logical char only needs to contain input char (`mb_strpos`).

**API endpoint:** `api/telugu_length_search.php`
GET params: `char` (required), `length` (required), `position` (optional, 1-indexed), `dict_id` (optional), `exact` (optional, `1`=on).

---

## Coding Conventions

### PHP
- Use PDO with prepared statements everywhere — no raw string interpolation in queries.
- Every file that touches the DB: `require_once __DIR__ . '/../includes/db.php';` (adjust depth).
- Admin pages: first two lines after `<?php`:
  ```php
  require_once __DIR__ . '/../includes/auth.php';
  requireAdmin();
  ```
- Use `password_hash($pw, PASSWORD_DEFAULT)` / `password_verify()` for credentials.
- API endpoints: always `Content-Type: application/json`, always include `"success": true/false`.
- No closing `?>` tag at end of PHP-only files.

### HTML / Templates
- Every page uses `includes/header.php` and `includes/footer.php` — no exceptions.
- Bootstrap grid only — no custom float layouts.
- Form inputs must have matching `<label for="...">` elements.
- Use `APP_BASE` for all internal links/hrefs (portability between XAMPP and Bluehost).

### JavaScript / jQuery
- All custom JS in `/assets/js/`; one file per page.
- Use `$.ajax()` to call `/api/` endpoints — no inline PHP in `<script>` tags.
- Page JS is injected via `$pageScript` flag read by `footer.php`.
- DataTables: initialize with `{ responsive: true, pageLength: 25 }` unless overridden.
- JS reads `APP_BASE` from `<div id="appConfig" data-app-base="...">` — never hardcode paths.

### CSS
- Custom styles in `/assets/css/main.css` only.
- Bootstrap utilities first; custom CSS only when Bootstrap can't do it.
- Custom color utilities defined in `main.css`: `.text-purple` (`#6f42c1`), `.text-teal` (`#0d9488`).

### Security
- Sanitize all user input with `htmlspecialchars()` before output.
- Sessions managed in `auth.php` — never call `session_start()` in page files.
- No credentials or DB passwords in committed files — `config.php` is gitignored.

---

## User Roles & Access

| Role    | Can do                                                     |
|---------|------------------------------------------------------------|
| Visitor | Search (exact/prefix/suffix/substring), set cookie prefs   |
| Admin   | Everything + CRUD on dictionaries/entries, reports, export |

---

## Implemented Features

1. **Standard search** — exact/prefix/suffix/substring modes, single or all dicts, pagination, highlight
2. **Telugu character search** — two-phase (SQL + external API), length + position + exact match toggle
3. **Cookie preferences** — theme (light/dark), search mode, results per page, default dictionary
4. **Admin hub** — iPad-style tile landing page at `admin/index.php`
5. **Admin dashboard** — stat cards + Chart.js bar chart per dictionary
6. **Dictionaries CRUD** — DataTables list, add/edit/delete via modals
7. **Entries CRUD** — DataTables list, add/edit/delete via modals, filter by dictionary
8. **Upload / Import** — CSV, JSON, XLSX; option to create new dictionary inline during import
9. **Dictionary comparison** — side-by-side, gap analysis (words in A not in B)
10. **Data integrity** — duplicate detection, orphan check
11. **Export** — CSV and JSON download
12. **Catalog** — public card grid of all dictionaries with word counts
13. **About page** — credits for lexicographer (Peddi Sambasiva Rao) and developer (Siva Jasthi)

---

## Deployment Notes

- Production is Bluehost shared hosting — weekly demos must run on the live URL.
- DB credentials differ between local and production; use `config.php` (gitignored).
- File uploads: check `upload_max_filesize` and `post_max_size` in Bluehost PHP settings.
- Use `APP_BASE` (defined in `config.php`) for all URL prefixes — `/peddi` locally, `''` on Bluehost.
