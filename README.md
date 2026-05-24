# TSP CMS Admin

Laravel + Inertia + Vue admin for businesses, users, and websites.

## Requirements

- PHP 8.2+
- [Composer](https://getcomposer.org/)
- [Node.js](https://nodejs.org/) (for the frontend)

## Setup

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate

# SQLite (default in .env.example)
touch database/database.sqlite
php artisan migrate --seed
php artisan storage:link
```

## Run locally

```bash
composer run dev
```

This starts everything you need in one terminal:

| Process | Purpose |
|---------|---------|
| `php artisan serve` | App at [http://127.0.0.1:8000](http://127.0.0.1:8000) |
| `npm run dev` | Vite (frontend hot reload) |
| `php artisan queue:listen` | Queue worker |
| `php artisan pail` | Log tail |

## Tests

```bash
php artisan test
```
