# ResourceHub &mdash; Installation &amp; Setup Guide

ResourceHub is a local equipment loan and maintenance management system built with beginner-friendly traditional PHP, PDO, PostgreSQL, and Bootstrap 5.

---

## 1. Environment &amp; System Specifications

ResourceHub is verified on the reference Ubuntu LTS stack with exact package versions:

- **Operating System:** Ubuntu 26.04 LTS (x86_64)
- **Web Server:** Apache 2.4.69+ (`apache2`, `libapache2-mod-php`)
- **PHP Runtime:** PHP 8.5.12 with required extensions:
  - `pdo`
  - `pdo_pgsql`
  - `session`
  - `filter`
  - `mbstring`
  - `ctype`
- **Database Engine:** PostgreSQL 16+ / 18.6 (running on `127.0.0.1:5432` with UTF-8 encoding)
- **Web Browser:** Modern standards-compliant browser with JavaScript enabled

---

## 2. Directory Architecture (Section 8 Standard)

| Location | Purpose (Section 8 Standard) |
| :--- | :--- |
| `public/` | Apache document root; `index.php` front controller and locally stored Bootstrap assets. |
| `src/` | Controllers, database queries, permission checks, and workflow services. |
| `templates/` | Shared layout, forms, and view templates with escaped output. |
| `config/` | Tracked example configuration; ignored local credentials outside `public/`. |
| `database/` | Ordered migrations and separate reproducible sample data script. |
| `tests/` &amp; `docs/` | Focused checks, test evidence, installation guide, and user guide. |

```text
resourcehub/
├── public/                  # Apache DocumentRoot (publicly accessible web root)
│   ├── assets/              # Local Bootstrap 5.3.3 CSS, JS, and Icons
│   ├── index.php            # Front Controller & entry router
│   ├── .htaccess            # Prevent directory listing & route clean paths
│   └── *.php                # Clean entry delegates to Router
├── src/                     # Controllers, queries, permissions & services
│   ├── Controllers/         # Application action controllers
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── EquipmentController.php
│   │   ├── LoanController.php
│   │   ├── MaintenanceController.php
│   │   ├── ReportController.php
│   │   └── UserController.php
│   ├── Auth.php             # Login, session, and role checks
│   ├── Database.php         # PDO database connection
│   ├── Equipment.php        # Inventory queries & management
│   ├── Loan.php             # Reservations, checkouts, and returns
│   ├── Maintenance.php      # Repair tickets and resolution
│   ├── Report.php           # Analytics & CSV exports
│   ├── Router.php           # Front controller dispatcher
│   └── User.php             # Account management & guards
├── templates/               # Shared layout, forms & view templates
│   ├── layout/              # Shared header, footer & alert templates
│   ├── auth/                # Login template
│   ├── dashboard/           # Overview template
│   ├── equipment/           # Catalogue, item view, and form templates
│   ├── loans/               # Loan lists, request, and view templates
│   ├── maintenance/         # Ticket list and add form templates
│   ├── reports/             # Summary and distribution chart templates
│   ├── users/               # User list and user form templates
│   └── errors/              # 403 Access Denied & 404 Not Found templates
├── config/                  # Database and application configuration
│   ├── config.php
│   ├── database.php
│   └── database.example.php
├── database/                # Relational schema and seed data
│   ├── schema.sql
│   ├── seed.php
│   └── reset.php            # 1-command fresh reset script
├── docs/                    # Documentation and credentials
├── includes/                # Shared bootstrap and initialization
│   ├── init.php             # Autoloader & service initializer
│   └── functions.php        # Helper functions (escaping, flash, CSRF, view)
├── logs/                    # Application error logs
└── tests/                   # Verification test scripts
```

---

## 3. Ubuntu Package Installation

Execute these commands on Ubuntu 26.04 LTS:

```bash
sudo apt update
sudo apt install -y apache2 php libapache2-mod-php php-pgsql php-mbstring postgresql postgresql-contrib git unzip
sudo systemctl enable --now apache2 postgresql
```

Verify service states and versions:

```bash
php -v
php -m | grep -E "pdo|pgsql|mbstring"
psql --version
sudo apache2ctl configtest
```

---

## 4. Clone &amp; Directory Preparation

Clone the repository into the standard web directory:

```bash
cd /var/www/html
sudo git clone https://github.com/nyeya/resourcehub.git
cd resourcehub

# Assign directory ownership to your current user and web group
sudo chown -R $USER:www-data /var/www/html/resourcehub
sudo chmod -R 775 /var/www/html/resourcehub

# Grant write permissions for local application logs
sudo chown -R www-data:www-data logs/
sudo chmod -R 775 logs/
```

---

## 5. Database Provisioning &amp; Configuration

1. Connect to PostgreSQL and provision the database and user:
   ```bash
   sudo -u postgres psql
   ```
   ```sql
   CREATE DATABASE resourcehub WITH ENCODING 'UTF8';
   CREATE USER resourcehub_app WITH ENCRYPTED PASSWORD 'root';
   GRANT ALL PRIVILEGES ON DATABASE resourcehub TO resourcehub_app;
   \c resourcehub
   GRANT ALL ON SCHEMA public TO resourcehub_app;
   ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO resourcehub_app;
   ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO resourcehub_app;
   \q
   ```

2. Copy the template to `config/database.php`:
   ```bash
   cp config/database.example.php config/database.php
   ```

3. Ensure credentials match in `config/database.php`:
   ```php
   return [
       'driver'   => 'pgsql',
       'host'     => '127.0.0.1',
       'port'     => 5432,
       'database' => 'resourcehub',
       'username' => 'resourcehub_app',
       'password' => 'root',
   ];
   ```

---

## 6. Database Initialization &amp; Seeding

To create all database tables and populate the demonstration dataset:

```bash
php database/reset.php
```

---

## 7. Running the Application

### Option A: Apache HTTP Server 2.4 (Ubuntu)

Configure `/etc/apache2/sites-available/resourcehub.conf`:

```apache
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot /var/www/html/resourcehub/public

    <Directory /var/www/html/resourcehub/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/resourcehub_error.log
    CustomLog ${APACHE_LOG_DIR}/resourcehub_access.log combined
</VirtualHost>
```

Enable rewrite, site, and restart:
```bash
sudo a2enmod rewrite
sudo a2dissite 000-default.conf
sudo a2ensite resourcehub.conf
sudo systemctl restart apache2
```

Navigate to: `http://localhost/`

### Option B: PHP Built-in Development Server

From the project root directory:
```bash
php -S 127.0.0.1:8000 -t public
```

Then visit: `http://127.0.0.1:8000/`

---

## 8. Troubleshooting &amp; Common Pitfalls

| Issue | Cause | Solution |
| :--- | :--- | :--- |
| `HTTP 500: could not find driver` | `php-pgsql` module is disabled or not installed. | Run `sudo apt install php-pgsql` and verify with `php -m \| grep pgsql`. Restart Apache. |
| `HTTP 403 Forbidden` on asset loading | File permissions on `public/assets/` are restrictive. | Run `sudo chmod -R 755 /var/www/html/resourcehub/public`. |
| `HTTP 404 Not Found` on clean paths (e.g. `/loans/request`) | Apache `mod_rewrite` is disabled or `AllowOverride All` is omitted. | Execute `sudo a2enmod rewrite` and ensure `AllowOverride All` is inside `/etc/apache2/sites-available/resourcehub.conf`. |
| `SQLSTATE[08006] Connection refused` | PostgreSQL service is inactive or listening on wrong port. | Run `sudo systemctl status postgresql` and verify port 5432 in `config/database.php`. |
| `Password authentication failed for user resourcehub_app` | Password mismatch in `config/database.php`. | Re-run `ALTER USER resourcehub_app WITH ENCRYPTED PASSWORD 'root';` in `psql`. Avoid solving errors by granting broad permissions. |
| `cp: cannot create regular file 'config/database.php': Permission denied` | Repository was cloned with `sudo`, leaving directory ownership under `root`. | Run `sudo chown -R $USER:www-data /var/www/html/resourcehub` and `sudo chmod -R 775 /var/www/html/resourcehub`. |

