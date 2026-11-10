# ResourceHub &mdash; Environment Installation Log

**Project:** ResourceHub Equipment Loan & Maintenance Management System  
**Reference Platform:** Ubuntu 26.04 LTS (x86_64)  
**Status:** Verified & Operational  

---

## 1. System Preparation & Prerequisites

- **Host OS:** Ubuntu 26.04 LTS (x86_64)
- **Editor / IDE:** VS Code / Terminal Editor
- **Version Control:** Git 2.43+
- **Browser:** Standards-compliant web browser (Chromium / Firefox)
- **Privileges:** Standard user with `sudo` administrative rights

```bash
# Verify OS release
lsb_release -a
# Description: Ubuntu 26.04 LTS

# Check available disk space
df -h /
# Adequate storage available (>10 GB free)
```

---

## 2. Package Installation Log

Execute repository update and install core web stack packages:

```bash
sudo apt update
sudo apt install -y apache2 php libapache2-mod-php php-pgsql php-mbstring postgresql postgresql-contrib git unzip
sudo systemctl enable --now apache2 postgresql
```

### Version & Module Verification

```bash
$ php -v
PHP 8.5.12 (cli) (built: 2026-03-15)
Copyright (c) The PHP Group
Zend Engine v4.5.12, Copyright (c) Zend Technologies

$ php -m | grep -E "pdo|pgsql|mbstring"
mbstring
pdo_pgsql
pgsql
PDO

$ psql --version
psql (PostgreSQL) 18.6 (Ubuntu 18.6-1.pgdg26.04+1)

$ sudo apache2ctl configtest
Syntax OK
```

---

## 3. Project Directory Setup & Permissions

```bash
cd /var/www/html
sudo git clone https://github.com/nyeya/resourcehub.git
cd resourcehub

# Assign project directory ownership to current user and web group
sudo chown -R $USER:www-data /var/www/html/resourcehub
sudo chmod -R 775 /var/www/html/resourcehub

# Ensure logs directory is writable by Apache
sudo chown -R www-data:www-data logs/
sudo chmod -R 775 logs/
```

---

## 4. PostgreSQL Database Provisioning Log

Execute database and application user creation:

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

---

## 5. Application Configuration Log

```bash
cp config/database.example.php config/database.php
```

`config/database.php` verified outside public web root and excluded from Git via `.gitignore`:
```php
<?php
return [
    'driver'   => 'pgsql',
    'host'     => '127.0.0.1',
    'port'     => 5432,
    'database' => 'resourcehub',
    'username' => 'resourcehub_app',
    'password' => 'root',
    'charset'  => 'utf8',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]
];
```

---

## 6. Database Initialization & Seeding Log

```bash
$ php database/reset.php
===========================================
  ResourceHub Database Initializer / Reset 
===========================================
1. Applying schema.sql...
 -> Schema applied successfully.
2. Seeding sample dataset...
 -> 6 users seeded (password for all: Password123!)
 -> 4 categories seeded
 -> 3 locations seeded
 -> 20 equipment assets seeded
 -> 1 open maintenance record seeded
 -> 12 loans with full audit history seeded
Seeding completed successfully!
===========================================
  Database setup and seeding completed!   
===========================================
```

---

## 7. Web Server Configuration & Verification

Create Apache VirtualHost `/etc/apache2/sites-available/resourcehub.conf`:

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

Enable site and restart Apache:

```bash
sudo a2enmod rewrite
sudo a2dissite 000-default.conf
sudo a2ensite resourcehub.conf
sudo systemctl restart apache2
```

### Offline Assets Verification
- `public/assets/css/bootstrap.min.css` (Bootstrap 5.3.3) loaded locally.
- `public/assets/css/bootstrap-icons.min.css` (Bootstrap Icons 1.11.3) loaded locally.
- `public/assets/js/bootstrap.bundle.min.js` loaded locally.
- System operates 100% offline with zero external CDN dependencies.

---

## 8. Summary of Parameters

| Parameter | Recorded Value |
| :--- | :--- |
| **Operating System** | Ubuntu 26.04 LTS (x86_64) |
| **Web Server** | Apache 2.4.69+ |
| **PHP Version** | PHP 8.5.12 |
| **Database** | PostgreSQL 18.6 / 16+ |
| **Local URL** | `http://localhost/` |
| **Bound Ports** | `80` (HTTP), `5432` (PostgreSQL) |
| **Apache DocumentRoot** | `/var/www/html/resourcehub/public` |
| **Apache Config** | `/etc/apache2/sites-available/resourcehub.conf` |
| **Application Log** | `/var/www/html/resourcehub/logs/error.log` |
| **Apache Error Log** | `/var/log/apache2/error.log` |

