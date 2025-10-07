# Internship Manager API

Backend for the internship management system.

## Related Repository
The related frontend application: [internship-manager-web](https://github.com/YehorSk/internship-manager-web)

---

## Requirements
- PHP 8.2
- MariaDB 10.4+
- Composer 2.8.12+

---

## Environment Configuration
1. Copy the example environment file:
   ```sh
   cp .env.example .env
   ```
2. Edit `.env` to match your environment, including domains and database connection.

   Example configuration (for local development):
   ```env
   APP_URL=http://localhost
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=internship_db
   DB_USERNAME=root
   DB_PASSWORD=root
   ```

---

## Installation & Setup
1. Clone the repository:
   ```sh
   git clone https://github.com/YehorSk/internship-manager-api.git
   cd internship-manager-api
   ```
2. Install dependencies:
   ```sh
   composer install
   ```
3. Create a database in MariaDB and update `.env` accordingly.
4. **Generate application key (required!):**
   ```sh
   php artisan key:generate
   ```
   This will set the APP_KEY value in your .env file. The application will not work without this key.
5. Run migrations and seeders:
   ```sh
   php artisan migrate --seed
   ```
6. Clear and cache configuration:
   ```sh
   php artisan config:clear
   php artisan cache:clear
   php artisan config:cache
   ```
7. Start the development server:
   ```sh
   php artisan serve --port=80
   ```

---

## Deployment Notes
- The `public` folder must be the web root (for Apache/Nginx).
- For local development (e.g., XAMPP), you can run the frontend on one port and the API on another.
