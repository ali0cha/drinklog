# 🍹 DrinkLog

A self-hosted PHP web app to track your alcohol consumption against WHO guidelines, with a PWA mode for mobile.

![PHP](https://img.shields.io/badge/PHP-8.1+-blue)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-orange)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-purple)
![License](https://img.shields.io/badge/license-MIT-green)

## Features

- 📊 Daily & weekly consumption tracking vs. WHO limits
- 🌿 Dry Day logging
- 📅 Monthly calendar view with color-coded days
- ⭐ Personal drink presets + global presets
- 🌙 Dark / light theme (persisted per user)
- 🔒 "Remember me" login (30 days, rolling token)
- 📧 Password reset by email (PHPMailer / Gmail SMTP)
- 📱 Progressive Web App (installable on mobile)

## Requirements

- PHP 8.1+
- MySQL 5.7+ / MariaDB 10.3+
- A web server (Apache / Nginx)
- A Gmail account with an App Password (for password reset emails)
- [PHPMailer 6.x](https://github.com/PHPMailer/PHPMailer) (not bundled, see below)

## Installation

### 1. Clone or upload files

```bash
git clone https://github.com/yourname/drinklog.git /var/www/html/drinklog
```

### 2. Install PHPMailer

PHPMailer is not bundled. You have two options:

**Option A — Composer (recommended)**

```bash
cd /var/www/html/drinklog
composer require phpmailer/phpmailer
```

Then update the top of `includes/mailer.php` to use the Composer autoloader instead of the manual requires:

```php
require_once __DIR__ . '/../vendor/autoload.php';
```

**Option B — Manual**

Download the three files from the [PHPMailer GitHub releases](https://github.com/PHPMailer/PHPMailer/releases) and place them at:

```
includes/PHPMailer/Exception.php
includes/PHPMailer/PHPMailer.php
includes/PHPMailer/SMTP.php
```

`includes/mailer.php` already expects this path by default — no changes needed.

### 3. Create the database

```bash
mysql -u root -p -e "CREATE DATABASE drinklog CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p -e "CREATE USER 'drinklog_user'@'localhost' IDENTIFIED BY 'strongpassword';"
mysql -u root -p -e "GRANT ALL ON drinklog.* TO 'drinklog_user'@'localhost';"
mysql -u drinklog_user -p drinklog < install.sql
```

### 3. Configure the app

```bash
cp config.example.php config.php
nano config.php   # fill in DB credentials, APP_URL, SMTP settings
```

Key settings in `config.php`:

| Constant | Description |
|---|---|
| `DB_HOST` / `DB_NAME` / `DB_USER` / `DB_PASS` | Database connection |
| `APP_URL` | Full URL to the app root, no trailing slash |
| `WHO_DAILY_LIMIT` | Daily unit limit (default: 2.0) |
| `WHO_WEEKLY_LIMIT` | Weekly unit limit (default: 14.0) |
| `SMTP_USER` / `SMTP_PASS` | Gmail address + App Password |

### 4. Gmail App Password

1. Enable 2-Factor Authentication on your Google account
2. Go to **Google Account → Security → App Passwords**
3. Generate a password for "Mail"
4. Paste it as `SMTP_PASS` in `config.php`

## Unit calculation

> 1 unit (WHO Europe) = **10 g** of pure alcohol

Formula used:

```
units = volume_ml × (degree / 100) × 0.789 / 10
```

The `0.789` factor is the density of ethanol (g/ml), converting volume to mass.

## File structure

```
drinklog/
├── config.php            # ← your local config (not committed)
├── config.example.php    # template
├── install.sql           # database schema + default presets
├── index.php             # login / register
├── dashboard.php         # daily encoder
├── calendar.php          # monthly calendar
├── api.php               # AJAX endpoints (theme toggle)
├── logout.php
├── reset.php             # password reset request
├── reset_confirm.php     # password reset form
├── manifest.json         # PWA manifest
├── sw.js                 # Service Worker
├── assets/
│   ├── style.css
│   ├── icon-192.png
│   └── icon-512.png
└── includes/
    ├── header.php
    ├── footer.php
    ├── mailer.php
    └── PHPMailer/        # ← à créer manuellement ou via Composer (non fourni)
```

## Migrating existing data

If you had a previous version without the ethanol density correction, run:

```sql
-- Backup first
CREATE TABLE drink_log_backup AS SELECT * FROM drink_log;

-- Recalculate
UPDATE drink_log
SET units = (volume_ml * (degree / 100) * 0.789) / 10
WHERE is_dry_day = 0;
```

## License

MIT – use freely, contribute welcome.
