# 🧾 Stock System — Digital Stock Book (PHP + Bootstrap + Supabase)

## 📖 Overview

The **Stock System** is a lightweight web application designed to digitally replicate the traditional physical stock book used by small to mid-size businesses. It demonstrates how an existing manual inventory flow can be seamlessly translated into a digital format — maintaining the same fields, structure, and accessibility while improving speed, accuracy, and data visibility.

The application aims to convince potential clients to **port from paper-based stock management to a digital system**, without overwhelming them with unnecessary complexity.

---

## 🎯 Objectives

- Replicate a **physical stock book** experience in digital form.
- Provide a simple **authentication → dashboard → stock book** flow.
- Showcase the **benefits of digital inventory systems** using familiar data points.
- Serve as a **proof of concept (PoC)** for transitioning clients to a cloud-based solution.
- Demonstrate clean, modular PHP architecture with reusability in mind.

---

## 🧩 Scope of the System

- **Authentication** (Login, Register, Logout)
- **Dashboard** (Basic overview page with statistics)
- **Stock Book**:
  - Displays tabular stock entries
  - Fields: `S/N`, `CODE`, `COLOR`, `NET WEIGHT (KG)`, `GAUGE`, `SALES STATUS`, `NO. OF METERS`, and `QUICK ACTION BUTTONS (VIEW, UPDATE, DELETE)`
  - CRUD operations (Create, Read, Update, Delete)
  - Connected to a **PostgreSQL database** via **Supabase**
- **Route protection** using middleware
- **Reusable layouts** for faster UI composition (Bootstrap-based)

---

## 🧱 Folder Structure

```
stock-system/
│
├── index.php                     # Entry point / router bootstrap
├── login.php                     # Login entry page
├── register.php                  # Registration entry page
├── logout.php                    # Session termination & redirect
│
├── config/
│   ├── db.php                    # Database connection (Supabase via connection string)
│   └── constants.php             # Global constants and enums (colors, sale statuses, etc.)
│
├── controllers/
│   ├── authC.php                 # Authentication controller (login, register)
│   ├── routes.php                # Routing system (handles URL navigation)
│   └── records/
│       ├── CRUD/                 # CRUD logic grouped per record type
│       │   ├── create.php
│       │   ├── read.php
│       │   ├── update.php
│       │   └── delete.php
│       └── index.php             # Central export for all CRUD methods
│
├── layout/
│   ├── header.php
│   ├── footer.php
│   ├── navbar.php
│   ├── sidebar.php
│   ├── table-item.php            # Reusable row component for the stock book
│   └── quick-action-buttons.php # View / Update / Delete buttons component
│
├── models/
│   ├── user.php                  # User model (for authentication)
│   └── record.php                # Record model (represents one stock book entry)
│
├── utils/
│   ├── authMiddleware.php        # Middleware to protect routes
│   └── helpers.php               # Utility functions and formatters
│
└── views/
    ├── auth/
    │   ├── login.php
    │   └── register.php
    ├── dashboard.php
    └── stockbook.php
```

---

## 🚀 Quick Setup & Run Guide

### Prerequisites

- PHP 7.4 or higher
- PostgreSQL database (via Supabase or local)
- Web server (Apache, Nginx, or PHP built-in server)

### Step 1: Clone or Download the Project

```bash
git clone <your-repository-url>
cd stock-system
```

### Step 2: Configure Supabase Connection

1. **Create a Supabase Project**:

   - Go to [supabase.com](https://supabase.com)
   - Create a new project
   - Note down your database credentials

2. **Update Database Configuration**:
   Open `config/db.php` and update the following:

```php
define('DB_HOST', 'your-project-ref.supabase.co');
define('DB_PORT', '5432');
define('DB_NAME', 'postgres');
define('DB_USER', 'postgres');
define('DB_PASS', 'your-supabase-password');
```

**Finding your Supabase credentials:**

- Go to Project Settings → Database
- Connection string will be in format: `postgresql://postgres:[YOUR-PASSWORD]@db.[YOUR-PROJECT-REF].supabase.co:5432/postgres`

### Step 3: Database Tables Setup

The application will **automatically create tables** on first run:

- `users` table (for authentication)
- `records` table (for stock entries)

No manual SQL execution required!

### Step 4: Run the Application

#### Option A: Using PHP Built-in Server (Easiest)

```bash
php -S localhost:8000
```

Then open: `http://localhost:8000`

#### Option B: Using XAMPP/WAMP/MAMP

1. Copy the project folder to your `htdocs` directory
2. Start Apache
3. Visit: `http://localhost/stock-system`

#### Option C: Using Docker (Optional)

```bash
docker run -p 8000:80 -v $(pwd):/var/www/html php:8.0-apache
```

### Step 5: Create Your First Account

1. Visit the application URL
2. Click "Register here"
3. Fill in your details:
   - Full Name
   - Email Address
   - Password (minimum 6 characters)
4. Login with your credentials

---

## ⚙️ Conventions & Architecture

### 1. MVC-Inspired Structure

The app loosely follows the **MVC (Model-View-Controller)** pattern:

- **Models**: Represent and manipulate data (`models/`)
- **Controllers**: Contain business logic and CRUD operations (`controllers/`)
- **Views**: Handle presentation (`views/`)
- **Layouts**: Modular UI components (`layout/`)

### 2. Routing Convention

- `index.php` loads `controllers/routes.php`, which maps query parameters (e.g., `?page=stockbook`) to specific views.
- Authentication routes (`login.php`, `register.php`, `logout.php`) act as direct entry points for simplicity.

### 3. CRUD Design

Each CRUD operation for a record is isolated in its own file under:

```
controllers/records/CRUD/
```

Each file has **a single responsibility**, making the system modular and DRY (Don't Repeat Yourself). They are all imported and exported centrally via:

```
controllers/records/index.php
```

### 4. Database

The system uses **Supabase** (PostgreSQL backend) via a **connection string** defined in:

```
config/db.php
```

This ensures flexibility and secure configuration management.

### 5. Constants & Enums

Common enums like `COLORS`, `SALE_STATUSES`, etc., are defined in:

```
config/constants.php
```

These constants can be reused across views and controllers to keep logic consistent and prevent hardcoding.

### 6. Middleware

`utils/authMiddleware.php` ensures that protected routes (dashboard, stockbook) are only accessible to authenticated users.

Usage:

```php
require_once './utils/authMiddleware.php';
checkAuth();
```

### 7. Layout System

All UI building blocks (header, sidebar, footer, table row, buttons) are modular and reusable. They are included in main pages like `dashboard.php` or `stockbook.php` to maintain consistency:

```php
include './layout/header.php';
include './layout/sidebar.php';
include './layout/navbar.php';
```

---

## 🧠 Design Philosophy

> "Simplicity convinces."

This project isn't about building a complex SaaS — it's about **replicating a familiar process** in digital form to build **trust and clarity** with prospective clients.

The architecture is:

- **Readable** for new developers
- **Extensible** for scaling (adding reports, analytics, or API later)
- **Consistent** through DRY conventions and modularity

---

## 📋 Usage Guide

### Dashboard

- View statistics (Total Records, Available, Sold, Total Meters)
- Quick access to Stock Book
- View recent records

### Stock Book

**Add New Record:**

1. Click "Add New Record" button
2. Fill in all required fields:
   - Code (auto-generated, can be modified)
   - Color (dropdown)
   - Net Weight in KG
   - Gauge (dropdown)
   - Sales Status (dropdown)
   - Number of Meters
3. Click "Add Record"

**View Record:**

- Click the eye icon (👁️) to view full details

**Edit Record:**

- Click the pencil icon (✏️)
- Modify fields (Note: Code cannot be changed)
- Click "Update Record"

**Delete Record:**

- Click the trash icon (🗑️)
- Confirm deletion

---

## 🚀 Future Extensions

- Add product categorization and filtering
- Add search functionality
- Add simple reporting (daily/weekly sales)
- Add export to Excel/PDF
- Move CRUD API logic to AJAX for a smoother experience
- Introduce lightweight audit logs for record edits
- Add barcode/QR code generation for stock items

---

## 🧰 Tech Stack

| Layer           | Tool                            |
| --------------- | ------------------------------- |
| Frontend        | Bootstrap 5 (via CDN)           |
| Backend         | PHP 7.4+ (Procedural + Modular) |
| Database        | Supabase (PostgreSQL)           |
| Architecture    | MVC-inspired Modular Structure  |
| Version Control | Git                             |

---

## 🧑‍💻 Developer Notes

- Always load the environment via `/config/db.php` before calling models or controllers.
- Keep all enums and constant values inside `/config/constants.php`.
- When creating new records, follow existing CRUD patterns under `/controllers/records/CRUD/`.
- Each new view must include layout files in the order: `header → navbar → sidebar → view-content → footer`.

---

## 🐛 Troubleshooting

### Database Connection Errors

**Error:** "Failed to connect to database"

**Solution:**

1. Verify your Supabase credentials in `config/db.php`
2. Ensure your Supabase project is active
3. Check if your IP is whitelisted in Supabase settings
4. Test connection using PostgreSQL client

### Session Errors

**Error:** "Headers already sent"

**Solution:**

1. Ensure no output before `session_start()`
2. Check for whitespace before `<?php` tags
3. Save files with UTF-8 encoding without BOM

### Page Not Found

**Solution:**

1. Ensure `.htaccess` is configured (if using Apache)
2. Check file permissions
3. Verify web server is running

---

## 📄 License

This project is open source and available for educational and commercial use.

---

## 💬 Summary

The **Stock System** is a transitional tool — bridging the gap between **manual record-keeping** and **digital management**. Its structure balances simplicity, maintainability, and scalability, allowing future developers to easily expand the system without disrupting its core philosophy.

---

## 📞 Support

For questions or issues, please contact your development team or create an issue in the project repository.

**Happy Stock Managing! 📦✨**
