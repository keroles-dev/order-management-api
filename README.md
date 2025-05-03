# Order Management API

A Laravel-based REST API for managing orders.

## Requirements

- PHP 8.2+
- Composer
- Laravel 12.x
- Postman (for testing the API endpoints) - Collection available at `docs/order_management_api_collection.postman_collection.json`

## Installation

1. Clone the repository
2. Install dependencies with Composer:
   ```bash
   composer install
   ```
3. Copy the environment file:
   ```bash
   cp .env.example .env
   ```
4. Generate application key:
   ```bash
   php artisan key:generate
   ```
5. Configure your database settings in `.env` (SQLite is used by default)
6. Run database migrations:
   ```bash
   php artisan migrate
   ```
7. Start the development server:
   ```bash
   compose run dev
   ```