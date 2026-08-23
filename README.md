# YathraNest — PHP + MySQL CMS

Travel site for **YathraNest** with a plain PHP admin panel and MySQL database (HostMaria-ready — no Laravel).

## Stacks

- Public site: PHP templates + existing CSS/JS
- Admin: `/admin` (session login, CSRF)
- Database: MySQL via PDO
- Forms: POST to `handlers/enquiry.php` (AJAX)

**Pricing is never displayed online.** CTAs remain enquiry-only.

## HostMaria setup

1. Create a MySQL database and user in cPanel / HostMaria.
2. Upload the site (or push to `main` for FTPS deploy).
3. Copy [`config/config.example.php`](config/config.example.php) to `config/config.php` on the server and set DB credentials + `base_url` if the site lives in a subdirectory.
4. Import [`sql/schema.sql`](sql/schema.sql) in phpMyAdmin.
5. On a machine with PHP + Node:
   - `node scripts/export-packages-json.mjs` (creates `sql/seed-data.json`)
   - Copy `sql/seed-data.json` to the server (or run locally against remote DB)
   - `php scripts/seed-from-js.php`
6. Make `uploads/` writable (`chmod 755` or `775`).
7. Log in at `/admin/login.php`

**Default admin (change immediately):**

- Email: `admin@yathranest.com`
- Password: `ChangeMe123!`

## Local development

1. Install PHP (with PDO MySQL) and MySQL/MariaDB (XAMPP/WAMP/Laragon).
2. Create DB `yathranest`, import `sql/schema.sql`.
3. Edit `config/config.php`.
4. Seed the database (pick one):

   **Option A — HostMaria phpMyAdmin (recommended if PHP is not on PATH):**
   ```bash
   node scripts/export-packages-json.mjs
   node scripts/seed-data-to-sql.mjs
   ```
   Then in phpMyAdmin: import `sql/schema.sql`, then `sql/seed-import.sql`.

   **Option B — PHP CLI (XAMPP on Windows):**
   ```powershell
   .\scripts\seed.ps1
   ```
   Or: `C:\xampp\php\php.exe scripts/seed-from-js.php`

   Note: HostMaria MySQL often blocks connections from your PC. Use Option A for remote hosting.
5. Serve the project root:
   ```bash
   php -S localhost:8080
   ```
6. Open `http://localhost:8080/index.php` and `http://localhost:8080/admin/login.php`.

## Structure

```text
yn/
├── index.php
├── pages/*.php
├── admin/                 # CMS
├── handlers/enquiry.php
├── includes/              # bootstrap, layouts, models
├── config/
├── sql/schema.sql
├── uploads/
├── css/ js/ assets/
└── .htaccess
```

## Admin modules

Packages · Places · Resorts · Getaways · Gift cards · Investment · Inquiries · Page content · Settings

## Deploy notes

GitHub Actions FTPS deploy excludes secrets (`config/config.php`, `.env`), seed JSON, and `scripts/`. Keep a server-only `config/config.php`. After first deploy, run schema + seed once.
