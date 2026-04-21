# Laravel Fingerprint Scanner ADMS - Setup Guide

This guide explains how to set up the dependencies and run the Laravel Fingerprint Scanner ADMS application on your local machine.

## Prerequisites

- **PHP 8.1+**
- **Composer** (https://getcomposer.org/)
- **Node.js & npm** (https://nodejs.org/)
- **MySQL** (or MariaDB)
- **Git** (optional, for cloning and keeping the code updated)

## 1. Clone the Repository

If you haven't already, clone the project:

```bash
git clone https://github.com/ethanchristoff/ZKTeco-Finger-Scanner.git
cd adms-server-ZKTeco
```

## 2. Install PHP Dependencies

Use Composer to install backend dependencies:

```bash
composer install
```

## 3. Install JavaScript Dependencies

Use npm to install frontend dependencies (you could additionally use a different node package manager such as bun, etc):

```bash
npm install
```

## 4. Environment Configuration

Copy the example environment file and update it as needed:

```bash
cp .env.example .env
```

Edit `.env` and set your database credentials and other settings:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

## 5. Generate Application Key

```bash
php artisan key:generate
```

## 6. Run Database Migrations

Make sure your database is running, then run the following to stage the database:

```bash
php artisan migrate
```

## 7. (Optional) Seed the Database

If you want to seed the database with test data:

```bash
php artisan db:seed
```

## 8. Build Frontend Assets

```bash
npm run build
```

For development, you can use hot reloading:

```bash
npm run dev
```

## 9. Run the Application

Start the Laravel development server:

```bash
php artisan serve
```

The app will be available at [http://localhost:8000](http://localhost:8000)

## 10. Access the App

- **Login/Register**: [http://localhost:8000/login](http://localhost:8000/login)
- **Device Management**: [http://localhost:8000/devices](http://localhost:8000/devices)

---

## Troubleshooting

- Ensure your database credentials in `.env` are correct and the database server is running.
- If you change `.env`, restart the server and rerun `php artisan config:cache`.
- For permissions issues, ensure `storage` and `bootstrap/cache` are writable:
  ```bash
  chmod -R 775 storage bootstrap/cache
  ```

---

## Docker Option

If you prefer Docker, see `README-Docker.md` for a full containerized setup.

---

**Enjoy using the Laravel Fingerprint Scanner ADMS!**
