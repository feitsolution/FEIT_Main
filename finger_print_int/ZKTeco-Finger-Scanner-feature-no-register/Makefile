# Makefile for Laravel Fingerprint Scanner Application

.PHONY: help build up down restart logs shell mysql-shell clean

# Default target
help:
	@echo "Available commands:"
	@echo "  build     - Build the Docker images"
	@echo "  up        - Start the application stack"
	@echo "  down      - Stop the application stack"
	@echo "  restart   - Restart the application stack"
	@echo "  logs      - Show application logs"
	@echo "  shell     - Access application container shell"
	@echo "  mysql     - Access MySQL shell"
	@echo "  clean     - Remove all containers, images, and volumes"

# Build Docker images
build:
	docker-compose build --no-cache

# Start the application
up:
	docker-compose up -d

# Stop the application
down:
	docker-compose down

# Restart the application
restart: down up

# Show logs
logs:
	docker-compose logs -f app

# Access application shell
shell:
	docker-compose exec app bash

# Access MySQL shell
mysql:
	docker-compose exec mysql mysql -u laravel_user -psecure_password fingerprint_db

# Clean everything
clean:
	docker-compose down -v --rmi all --remove-orphans
	docker system prune -f
