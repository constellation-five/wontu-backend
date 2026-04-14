# Wontu Backend

Laravel API backend for Wontu.

## Requirements

- PHP 8.3+
- Composer 2.x
- MySQL 8.4
- OpenSSL PHP extension (required for app key generation)

## Setup

1. Install dependencies:

```bash
composer install
```

2. Create environment file:

```bash
copy .env.example .env
```

3. Configure database in `.env`:

- `DB_CONNECTION=mysql`
- `DB_HOST=127.0.0.1`
- `DB_PORT=3306`
- `DB_DATABASE=wontu_backend`
- `DB_USERNAME=...`
- `DB_PASSWORD=...`

4. Generate app key:

```bash
php artisan key:generate
```

5. Run migrations:

```bash
php artisan migrate
```

## Running the Dev Server

1. Start server:

```bash
php artisan serve
```

2. Open: http://localhost:8000
