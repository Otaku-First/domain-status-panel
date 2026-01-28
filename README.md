# Domain Status Panel

A simple uptime monitoring tool built with Laravel and Vue.js. Keep track of your websites and get notified when something goes wrong.

## What it does

- Monitors your domains at configurable intervals
- Tracks response times and HTTP status codes
- Sends email alerts when a site goes down (and when it recovers)
- Shows uptime statistics and response time charts
- Supports GET and HEAD request methods

## Requirements

- PHP 8.2+
- MySQL 8.0+ or PostgreSQL
- Node.js 18+
- Composer

## Quick Start

Clone the repo and install dependencies:

```bash
git clone https://github.com/your-username/domain-status-panel.git
cd domain-status-panel

composer install
npm install
```

Set up your environment:

```bash
cp .env.example .env
php artisan key:generate
```

Configure your database in `.env`, then run migrations:

```bash
php artisan migrate
```

Build assets and start the server:

```bash
npm run build
php artisan serve
```

## Running with Docker (Laravel Sail)

If you prefer Docker:

```bash
./vendor/bin/sail up -d
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
./vendor/bin/sail artisan migrate
```

## Background Jobs

The monitoring runs via scheduled jobs. You'll need to set up the scheduler and a queue worker.

Add this to your crontab:

```
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

Run the queue worker:

```bash
php artisan queue:work
```

With Sail:

```bash
./vendor/bin/sail artisan queue:work
```

## Email Notifications

Configure your mail settings in `.env`:

```
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_FROM_ADDRESS=alerts@yourdomain.com
MAIL_FROM_NAME="Domain Monitor"
```

You'll get an email when:
- A domain goes down
- A domain comes back up

## Running Tests

```bash
php artisan test
```

Or with Sail:

```bash
./vendor/bin/sail artisan test
```

## Tech Stack

- **Backend:** Laravel 12, PHP 8.4
- **Frontend:** Vue 3, Inertia.js, TypeScript
- **UI:** Tailwind CSS, shadcn/ui
- **Charts:** ApexCharts

## Project Structure

```
app/
├── Enums/          # CheckResult enum
├── Http/
│   ├── Controllers/
│   └── Resources/  # API resources
├── Jobs/           # Queue jobs for domain checks
├── Models/         # Domain, DomainCheck, User
├── Notifications/  # Email notifications
└── Services/       # DomainService, DomainCheckerService

resources/js/
├── components/
│   ├── domain/     # Domain-specific components
│   └── ui/         # shadcn/ui components
├── pages/          # Inertia pages
└── types/          # TypeScript types
```

