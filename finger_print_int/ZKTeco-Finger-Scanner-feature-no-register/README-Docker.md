# Laravel Fingerprint Scanner ADMS - Docker Setup

This document explains how to run the Laravel Fingerprint Scanner ADMS application using Docker.

## Prerequisites

- Docker Desktop for Windows
- Docker Compose (included with Docker Desktop)

## Quick Start

1. **Clone and navigate to the project:**
   ```bash
   cd c:\Users\Ethan\Documents\finger-scanner\adms-server-ZKTeco
   ```

2. **Build and start the application:**
   ```bash
   docker-compose up --build -d
   ```

3. **Access the application:**
   - **Main Application**: http://localhost:8080
   - **Login Page**: http://localhost:8080/login
   - **phpMyAdmin**: http://localhost:8081
   - **MySQL**: localhost:3306

## Services

The Docker setup includes:

- **Laravel App** (Port 8080): Main fingerprint scanner application
- **MySQL 8.0** (Port 3306): Database server
- **phpMyAdmin** (Port 8081): Database management interface
- **Redis** (Port 6379): Caching and session storage

## Database Credentials

- **Database**: fingerprint_db
- **Username**: laravel_user
- **Password**: secure_password
- **Root Password**: root_password

## Docker Commands

### Using Docker Compose

```bash
# Start all services
docker-compose up -d

# Stop all services
docker-compose down

# View logs
docker-compose logs -f app

# Rebuild and start
docker-compose up --build -d

# Access application shell
docker-compose exec app bash

# Access MySQL shell
docker-compose exec mysql mysql -u laravel_user -psecure_password fingerprint_db
```

### Using Makefile (if available)

```bash
# Build images
make build

# Start application
make up

# Stop application
make down

# View logs
make logs

# Access shell
make shell

# Access MySQL
make mysql

# Clean everything
make clean
```

## File Structure

```
├── Dockerfile              # Main application container
├── docker-compose.yml      # Multi-service orchestration
├── .dockerignore           # Files to exclude from build
├── .env.docker            # Environment variables for Docker
├── docker-entrypoint.sh   # Startup script
└── Makefile               # Convenience commands
```

## Environment Configuration

The application uses `.env.docker` for containerized environments with:

- Database connection to MySQL container
- Redis connection for caching/sessions
- Production-ready settings

## Volumes

- **mysql_data**: Persistent MySQL database storage
- **redis_data**: Persistent Redis data
- **./storage**: Laravel storage directory (logs, cache, uploads)
- **./bootstrap/cache**: Laravel bootstrap cache

## Troubleshooting

### Application not starting
```bash
# Check logs
docker-compose logs app

# Ensure database is ready
docker-compose logs mysql
```

### Database connection issues
```bash
# Wait for MySQL initialization
docker-compose logs mysql | grep "ready for connections"

# Restart application after MySQL is ready
docker-compose restart app
```

### Permission issues
```bash
# Fix storage permissions
docker-compose exec app chown -R www-data:www-data /var/www/html/storage
docker-compose exec app chmod -R 755 /var/www/html/storage
```

### Reset everything
```bash
# Stop and remove all containers, networks, and volumes
docker-compose down -v
docker system prune -f

# Rebuild from scratch
docker-compose up --build -d
```

## Development vs Production

- The current setup is production-ready with optimizations
- For development, you may want to:
  - Set `APP_DEBUG=true` in `.env.docker`
  - Mount the entire application directory as a volume
  - Use `APP_ENV=local`

## Accessing the Application

Once running, you can:

1. **Visit the login page**: http://localhost:8080/login
2. **Register a new user** or use existing credentials
3. **Access device management**: http://localhost:8080/devices
4. **Manage database**: http://localhost:8081 (phpMyAdmin)

## Security Notes

- Change default passwords in production
- Use environment variables for sensitive data
- Consider using Docker secrets for production deployments
- Ensure firewall rules are properly configured
