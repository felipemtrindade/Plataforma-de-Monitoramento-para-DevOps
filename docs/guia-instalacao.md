# Guia de Instalacao

## Requisitos

- PHP 8.3 ou superior
- Composer
- Node.js
- MySQL

## Passos

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Crie o banco:

```sql
CREATE DATABASE devops_monitor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Configure `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=devops_monitor
DB_USERNAME=root
DB_PASSWORD=
```

Execute migrations e seeders:

```bash
php artisan migrate:fresh --seed
```

Inicie backend e frontend:

```bash
php artisan serve
npm run dev
```

Coleta manual:

```bash
php artisan monitor:check
```
