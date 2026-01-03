# Internship Manager API

Backend for the internship management system.

## Related Repository
The related frontend application: [internship-manager-web](https://github.com/YehorSk/internship-manager-web)

---

## Requirements
- PHP 8.2
- MariaDB 10.4+
- MinIO (RELEASE.2025-04-22T22-12-26Z)
- Mailpit (latest)
- Composer (latest)

---

## Local development on the host OS

1. Clone repository:
   ```sh
   git clone https://github.com/YehorSk/internship-manager-api.git
   cd internship-manager-api
   ```
2. In cloned repository copy the environment file:
   ```sh
   cp .env.example .env
   ```
3. Change variables in `internship-manager-api/.env` file:
   ```
   DB_HOST=127.0.0.1
   MAIL_HOST=127.0.0.1
   AWS_ENDPOINT=http://127.0.0.1:9100
   ```
4. Install and run local MariaDB service and create `internship_db` database.
5. Install and run local MinIO service on port 9100/9101 and create `internship-bucket` bucket`.
6. Install and run local Mailpit service on port 8025/1025.
7. Install PHP dependencies:
   ```sh
   composer install
   ```
8. Run custom command:
   ```sh
   php artisan reset:database
   ```
9. Start the development server:
   ```sh
   php artisan serve
   ```
10. Run queue worker (in a separate terminal, keep it running):
    ```sh
    php artisan queue:work --queue=reports,check-company-email,default --once
    ```
11. Frontend installation:
    The frontend installation and run instructions are located in the `README.md` of the frontend repository: https://github.com/YehorSk/internship-manager-web.

## Local development with Docker

When using Docker Desktop on Windows 10/11 with WSL2, always keep the project on the WSL filesystem (for example: `/home/<your-user>/`) to avoid performance issues.

1. Go to your project folder (execute inside WSL or Linux/Mac console).
   ```sh
   # in WSL console
   cd /home/$(whoami)
   # or in Linux/Mac console
   cd ~
   ```
2. Clone repositories:
   ```sh
   git clone https://github.com/YehorSk/internship-manager-api.git
   git clone https://github.com/YehorSk/internship-manager-web.git
   ```
3. In each cloned repository copy the environment file:
   ```sh
   cp .env.example .env
   ```
4. Build images and start containers:
   ```sh
   docker compose up -d --build
   ```
5. Initial project setup inside the PHP container (run once after first start):
   ```sh
   # open a shell in the PHP container
   docker exec -it php-fpm-internship bash
   # from inside the container run the first-time setup script
   first_init.sh
   ```
6. Common commands:
   ```sh
   # start containers
   docker compose up -d
   # stop containers
   docker compose stop
   # restart containers
   docker compose restart
   # stop and remove containers, networks and volumes
   docker compose down
   # rebuild images and start containers
   docker compose up -d --build
   ```

## Access services:

- Frontend Application: http://localhost:3000
- Backend API: http://localhost:8000/api
- MinIO Console: http://localhost:9101
- Mailpit Web Interface: http://localhost:8025
