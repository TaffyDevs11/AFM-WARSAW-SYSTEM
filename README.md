# AFM Warsaw Assembly — Website
## Warsaw Christian Centre

A complete, multi-page church website built with HTML, CSS, JavaScript, PHP, and MySQL.

---

## 🖥️ Local Setup (XAMPP)

### 1. Install XAMPP
Download from: https://www.apachefriends.org/

### 2. Place files
Copy the entire `afm-warsaw/` folder to:
```
C:\xampp\htdocs\afm-warsaw\          (Windows)
/Applications/XAMPP/htdocs/afm-warsaw/  (Mac)
```

### 3. Create the database
- Open XAMPP Control Panel → Start **Apache** + **MySQL**
- Visit: http://localhost/phpmyadmin
- Click **New** → Database name: `afm_warsaw` → Create
- Click **Import** → Choose `database/schema.sql` → Go

### 4. Configure DB (if needed)
Edit `php/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');        // XAMPP default: empty
define('DB_NAME', 'afm_warsaw');
define('SITE_URL', 'http://localhost/afm-warsaw');
```

### 5. Visit the site
```
http://localhost/afm-warsaw/index.html
```

### 6. Admin Dashboard
```
http://localhost/afm-warsaw/admin/login.php
Username: admin
Password: Admin@AFM2024
```
**⚠️ Change the admin password immediately after first login!**
To change password, update the hash in the DB:
```sql
UPDATE admin_users SET password_hash = '$2y$12$...' WHERE username = 'admin';
```
Generate hash with: `php -r "echo password_hash('YourNewPassword', PASSWORD_BCRYPT, ['cost'=>12]);"`

---

## 🌐 Hostinger Deployment

### 1. Upload files
- Via **File Manager** in hPanel → `public_html/`
- Or via **FTP**: host=`your-domain.com`, port=`21`

### 2. Create database on Hostinger
- hPanel → **MySQL Databases** → Create new DB + user
- Import `database/schema.sql` via phpMyAdmin

### 3. Update config for production
Edit `php/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'u123456789_afmwarsaw');   // your Hostinger DB username
define('DB_PASS', 'YourSecurePassword');
define('DB_NAME', 'u123456789_afmwarsaw');   // your Hostinger DB name
define('SITE_URL', 'https://www.afmwarsaw.org');
define('UPLOAD_PATH', '/home/u123456789/public_html/uploads/');
```

### 4. Set folder permissions
```
uploads/gallery/        → 755
uploads/blog/           → 755
uploads/announcements/  → 755
uploads/sermons/        → 755
```

---

## 📂 Project Structure

```
afm-warsaw/
├── index.html              ← Home
├── about.html              ← About Us
├── ministries.html         ← Ministries Hub
├── ministry-men.html       ← Men's Ministry
├── ministry-women.html     ← Women's Ministry
├── ministry-youth.html     ← Youth Ministry
├── ministry-sundayschool.html
├── dept-worship.html       ← Praise & Worship
├── dept-media.html         ← Sound & Media
├── dept-catering.html      ← Catering
├── dept-deco.html          ← Deco & Hospitality
├── dept-ushering.html      ← Ushering
├── dept-prayer.html        ← Prayer Intercessors
├── gallery.html            ← Gallery
├── blog.html               ← Blog & Sermons
├── online.html             ← Online Church
├── announcements.html      ← News & Announcements
├── contact.html            ← Contact
│
├── css/
│   └── main.css            ← All styles
├── js/
│   └── main.js             ← All JavaScript
├── images/
│   └── logo.png            ← AFM Warsaw logo
├── uploads/                ← Admin-uploaded content
│   ├── gallery/
│   ├── blog/
│   ├── announcements/
│   └── sermons/
│
├── php/
│   ├── config.php          ← DB config
│   ├── api.php             ← Data API
│   ├── contact.php         ← Contact form handler
│   └── register.php        ← Registration handler
│
├── admin/
│   ├── login.php           ← Admin login
│   ├── dashboard.php       ← Full admin panel
│   └── auth.php            ← Session auth
│
└── database/
    └── schema.sql          ← MySQL schema + seed data
```

---

## 🎨 Color Palette (from logo)
| Color     | Hex       | Usage                        |
|-----------|-----------|------------------------------|
| Navy Blue | `#1a2456` | Primary background, headers  |
| Deep Navy | `#0f1730` | Dark sections, footer        |
| Gold      | `#c9a227` | Accents, titles, CTAs        |
| Red       | `#cc1b1b` | Highlights, special accents  |
| White     | `#ffffff` | Text, backgrounds            |

---

## ✦ Features
- ✅ Custom animated page loader (logo + color palette)
- ✅ Responsive header (sticky, transforms on scroll)
- ✅ 18 complete HTML pages
- ✅ Dynamic gallery with category filtering
- ✅ Blog articles with author info
- ✅ Sermon video cards
- ✅ Weekly + special announcements
- ✅ Ministry registration forms
- ✅ Contact form on every page
- ✅ Secure admin dashboard (CRUD)
- ✅ Instagram Live embed section
- ✅ Scroll-reveal animations
- ✅ Fully responsive (mobile, tablet, desktop)
- ✅ MySQL with prepared statements
- ✅ PHP backend with session auth

---

## 🔐 Admin Panel — Complete Page List

| File | Purpose |
|------|---------|
| `admin/login.php` | Secure login with logo, password toggle, loading state |
| `admin/overview.php` | Dashboard: stats, recent registrations, inbox preview, ministry breakdown chart |
| `admin/gallery.php` | Upload images (drag & drop), filter by category, edit title/category, delete |
| `admin/blog.php` | Rich-text editor, featured image upload, author photo, edit/delete articles |
| `admin/sermons.php` | Add sermons with live video preview, grouped by month, edit/delete |
| `admin/announcements.php` | Weekly & special announcements, image upload, type toggle |
| `admin/registrations.php` | Ministry filter tabs, search, export CSV, view detail modal, bulk delete |
| `admin/contacts.php` | Full inbox with message modal, reply via email, export CSV, delete |
| `admin/settings.php` | Change username/password, storage usage bar chart, database stats |
| `admin/api.php` | Internal AJAX API (gallery, blog, sermons, announcements, contacts) |
| `admin/auth.php` | Session auth with `requireAdmin()` and `adminLogin()` |
| `admin/layout.php` | Shared sidebar + topbar layout (included by all pages) |
| `admin/logout.php` | Destroys session, redirects to login |

### Admin Features
- ✅ Sticky sidebar with active page highlighting
- ✅ Live clock in topbar
- ✅ Toast notifications for all actions
- ✅ Confirm-delete dialog for all destructive actions
- ✅ Drag & drop image upload zones with live preview
- ✅ Rich text editor (Bold, Italic, Lists, Alignment) for blog articles
- ✅ Live video embed preview when entering YouTube/Vimeo URLs
- ✅ Ministry filter tabs with registration counts
- ✅ CSV export for registrations and contacts
- ✅ Bulk delete for registrations
- ✅ Inline message view modal with reply-via-email
- ✅ Storage usage progress bars per upload folder
- ✅ Password change with match validation
- ✅ Mobile responsive sidebar (toggle button on small screens)
- ✅ Keyboard shortcut: Ctrl+S to save active form
- ✅ Auto-dismiss alerts after 5 seconds
- ✅ File size validation (5MB max)
- ✅ Session regeneration on login (security)
