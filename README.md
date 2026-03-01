# Ecommerce API

## Tech Stack
- Laravel
- MySQL
- RESTful API

## Setup

```bash
git clone https://github.com/yourname/ecommerce-api.git
cd ecommerce-api
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve