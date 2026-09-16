<div align="center">

# ✨ SalonHub

### The complete digital workspace for modern salons

Manage appointments, customers, staff, billing, inventory, payroll, loyalty programs, promotions, and daily salon operations from one powerful platform.

[![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?style=for-the-badge\&logo=laravel\&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge\&logo=php\&logoColor=white)](https://www.php.net)
[![Livewire](https://img.shields.io/badge/Livewire-3-FB70A9?style=for-the-badge\&logo=livewire\&logoColor=white)](https://livewire.laravel.com)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5-7952B3?style=for-the-badge\&logo=bootstrap\&logoColor=white)](https://getbootstrap.com)

</div>

---

## 🌟 About SalonHub

**SalonHub** is a multi-tenant salon management SaaS designed to simplify and automate everyday salon operations.

It gives salon owners and their teams a central workspace for managing appointments, customers, services, staff, payments, stock, expenses, commissions, payroll, memberships, promotions, reports, and business settings.

Whether you operate a single salon, a barbershop, a spa, or a growing multi-branch beauty business, SalonHub helps you stay organized, serve customers efficiently, and make better business decisions.

---

## 💡 Why SalonHub?

Running a salon involves much more than booking appointments. Owners must coordinate staff, track customer history, collect payments, manage stock, calculate commissions, control expenses, and monitor overall performance.

SalonHub brings those responsibilities together in one platform.

* 📅 Organize appointments and staff schedules
* 👥 Maintain complete customer profiles and visit history
* 💳 Process checkout, invoices, and payments
* 🧴 Monitor products and branch-level inventory
* 💰 Track expenses, salaries, and staff commissions
* 🎁 Build loyalty, membership, and promotional programs
* 📊 Understand business performance through dashboards and reports
* 🏢 Manage multiple salons and branches from one account

---

## 🚀 Core Modules

### 🏢 Multi-Tenant Salon Management

Each salon operates in its own secure workspace with separate branches, users, customers, appointments, financial records, and settings.

### 🌿 Branch Management

Create and manage multiple salon locations, opening hours, special operating hours, branch status, staff assignments, and branch-specific reports.

### ✂️ Service Management

Organize salon services into categories and configure their prices, duration, availability, assigned staff, and branch accessibility.

### 👩‍💼 Staff Management

Maintain staff profiles, branch assignments, roles, service capabilities, schedules, availability, and employment information.

### 👤 Customer Management

Store customer contact details, preferences, notes, appointment history, membership status, loyalty activity, and spending records.

### 📅 Appointment Management

Create and manage appointments with service selection, staff allocation, availability validation, pricing snapshots, status tracking, and calendar views.

Supported appointment stages include:

* Pending
* Confirmed
* Checked in
* In progress
* Completed
* Cancelled
* No-show

### 🧾 POS, Billing and Invoicing

Convert completed appointments into invoices, add retail products, apply eligible discounts, redeem loyalty points, record payments, and manage invoice histories.

### 💳 Payment Management

Support configurable payment methods such as cash, cards, bank transfers, and digital payments with transaction references and payment records.

### 📦 Inventory and Product Management

Manage product categories, brands, units, prices, inventory levels, stock movements, opening stock, adjustments, product sales, and sale returns.

### 💸 Expense Management

Record business expenses, organize expense categories, manage vendors, upload receipts, approve expenses, record payments, and review expense reports.

### 💰 Staff Commission Management

Configure commission rules, automatically calculate commissions from completed sales, review staff commission ledgers, and process commission payouts.

### 🧮 Payroll Management

Create payroll periods, calculate staff salaries, include approved commissions, deduct salary advances, review payroll runs, record payments, and generate payslip information.

### ⭐ Loyalty Management

Reward customers with points based on purchases, allow point redemption, track balances and transactions, configure earning rules, and manage point expiration.

### 💎 Membership Management

Create membership plans with prices, validity periods, service benefits, product discounts, complimentary services, and loyalty point multipliers.

### 🎉 Promotions and Discounts

Create automatic, manual, and coupon-based promotions with branch, customer, service, product, membership, date, spending, and usage conditions.

### 📊 Dashboard and KPI Management

Monitor appointments, customers, revenue, payments, expenses, inventory, staff performance, and other important business indicators.

### ⚙️ System Settings

Configure salon details, branding, regional preferences, operational settings, notification preferences, billing behavior, and module-specific options.

### 🛡️ Audit Logs and Activity Tracking

Track important system activities, authentication events, record changes, responsible users, timestamps, and historical values.

---

## 🏗️ Platform Architecture

SalonHub follows a multi-tenant SaaS structure.

```text
SalonHub Platform
│
├── Platform Administration
│   ├── Subscription Plans
│   ├── Salon Tenants
│   └── Platform Configuration
│
└── Salon Workspace
    ├── Branches
    ├── Staff and Users
    ├── Customers
    ├── Services
    ├── Appointments
    ├── Billing and Payments
    ├── Products and Inventory
    ├── Expenses
    ├── Commissions and Payroll
    ├── Loyalty and Memberships
    ├── Promotions
    ├── Reports
    └── Settings and Audit Logs
```

---

## 🛠️ Technology Stack

| Technology                | Purpose                          |
| ------------------------- | -------------------------------- |
| Laravel 11                | Backend application framework    |
| PHP 8.2+                  | Server-side programming language |
| Livewire 3                | Dynamic server-driven interfaces |
| Blade                     | Application view templates       |
| Bootstrap 5               | Responsive user interface        |
| MySQL                     | Relational database              |
| Laravel Sanctum           | API authentication               |
| Spatie Laravel Permission | Roles and permissions            |
| Yajra DataTables          | Searchable and paginated tables  |
| Laravel Socialite         | Social authentication            |
| Laravel Mix               | Frontend asset compilation       |

---

## 📋 Requirements

Before installing SalonHub, make sure your environment includes:

* PHP 8.2 or newer
* Composer
* MySQL or MariaDB
* Node.js and npm
* Required PHP extensions for Laravel
* A configured web server such as Apache or Nginx

---

## ⚙️ Local Installation

### 1. Clone the repository

```bash
git clone https://github.com/yohanawi/salonhub-saas.git
cd salonhub-saas
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Create the environment file

```bash
cp .env.example .env
```

### 4. Generate the application key

```bash
php artisan key:generate
```

### 5. Configure the database

Update the following values inside `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=salonhub
DB_USERNAME=root
DB_PASSWORD=
```

### 6. Run database migrations and seeders

```bash
php artisan migrate --seed
```

### 7. Create the public storage link

```bash
php artisan storage:link
```

### 8. Install frontend dependencies

```bash
npm install
```

### 9. Build frontend assets

For development:

```bash
npm run dev
```

For production:

```bash
npm run production
```

### 10. Start the application

```bash
php artisan serve
```

Open the application at:

```text
http://127.0.0.1:8000
```

---

## 🧪 Testing

Run the Laravel test suite with:

```bash
php artisan test
```

The project includes feature tests for major workflows such as:

* Authentication and registration
* Branch management
* Customer management
* Staff management
* Service management
* Appointment management
* Billing and POS
* Product inventory
* Expenses
* Staff commissions
* Payroll
* Loyalty and memberships
* Promotions and discounts
* Dashboard KPIs
* System settings
* Audit logs

---

## 🔐 Security and Data Isolation

SalonHub is designed around tenant-aware data access. Business records belong to a specific salon tenant and can also be restricted by branch, user role, and permission.

Before using the platform in production, complete a security review covering:

* Tenant and branch authorization
* User, role, and permission management
* Subscription enforcement
* Payment and financial workflows
* File upload handling
* Environment and server configuration
* Backup and recovery procedures

---

## 🗺️ Development Roadmap

Planned improvements may include:

* Customer-facing online appointment booking
* Dedicated salon websites
* SMS, email, and WhatsApp notifications
* Online payment gateway integration
* Automated appointment reminders
* Mobile applications
* Advanced financial reporting
* Staff attendance and shift management
* Product purchasing and supplier orders
* Customer feedback and review management
* Marketing automation
* Additional integrations and APIs

---

## 🤝 Contributing

Contributions, suggestions, and issue reports are welcome.

1. Fork the repository
2. Create a feature branch
3. Make and test your changes
4. Commit the changes
5. Push the branch
6. Open a pull request

Please use clear commit messages and explain the purpose of your changes.

---

## 🐛 Reporting Issues

If you discover a bug or unexpected behavior, open a GitHub issue and include:

* A clear description of the problem
* Steps needed to reproduce it
* The expected result
* The actual result
* Relevant screenshots or error messages
* Environment information when applicable

Please avoid publishing passwords, private customer data, API credentials, or other sensitive information.

---

## 📄 License

A license has not yet been specified for this project.

Before distributing, modifying, or using SalonHub commercially, add an appropriate `LICENSE` file and update this section.

---

## 👨‍💻 Author

Developed and maintained by [Yohan Awi](https://github.com/yohanawi).

---

<div align="center">

### Transform everyday salon operations into a smooth digital experience.

**SalonHub — Manage smarter. Serve better. Grow faster.**

⭐ If you find this project useful, consider giving the repository a star.

</div>
