#!/bin/bash

# BetLedger Setup Script
# Run this script to automatically set up BetLedger on Linux/Mac

set -e

echo "========================================="
echo "BetLedger - Setup Script"
echo "========================================="
echo ""

# Check PHP
if ! command -v php &> /dev/null; then
    echo "❌ PHP is not installed. Please install PHP 8.0 or higher."
    exit 1
fi

PHP_VERSION=$(php -v | grep -oP '(?<=PHP )\d+\.\d+' | head -1)
echo "✓ PHP $PHP_VERSION found"

# Check MySQL
if ! command -v mysql &> /dev/null; then
    echo "⚠️  MySQL is not installed. Please install MySQL 8.0 or MariaDB 10.5+"
    echo "   Or use Docker: docker-compose up -d"
    exit 1
fi

echo "✓ MySQL found"

# Create .env file if not exists
if [ ! -f .env ]; then
    echo ""
    echo "Creating .env file..."
    cp .env.example .env
    echo "✓ .env file created. Please edit it with your database credentials."
fi

# Create log directory
if [ ! -d logs ]; then
    mkdir -p logs
    chmod 755 logs
    echo "✓ Created logs directory"
fi

# Create uploads directory
if [ ! -d uploads ]; then
    mkdir -p uploads
    chmod 755 uploads
    echo "✓ Created uploads directory"
fi

# Install database schema
echo ""
echo "Setting up database..."

read -p "Enter MySQL hostname [localhost]: " db_host
db_host=${db_host:-localhost}

read -p "Enter MySQL username [root]: " db_user
db_user=${db_user:-root}

read -sp "Enter MySQL password: " db_pass
echo ""

read -p "Enter database name [bet_tracker_db]: " db_name
db_name=${db_name:-bet_tracker_db}

# Test connection
echo "Testing database connection..."
if mysql -h "$db_host" -u "$db_user" -p"$db_pass" -e "SELECT 1" > /dev/null 2>&1; then
    echo "✓ Database connection successful"
else
    echo "❌ Failed to connect to database. Please check your credentials."
    exit 1
fi

# Create database and import schema
echo "Creating database and importing schema..."
mysql -h "$db_host" -u "$db_user" -p"$db_pass" -e "CREATE DATABASE IF NOT EXISTS $db_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -h "$db_host" -u "$db_user" -p"$db_pass" "$db_name" < database/schema.sql

# Optionally import seed data
read -p "Would you like to import sample data? (y/n) [y]: " import_seed
import_seed=${import_seed:-y}

if [ "$import_seed" = "y" ]; then
    mysql -h "$db_host" -u "$db_user" -p"$db_pass" "$db_name" < database/seed_data.sql
    echo "✓ Sample data imported"
fi

# Update .env file
sed -i.bak "s/DB_HOST=.*/DB_HOST=$db_host/" .env
sed -i.bak "s/DB_USER=.*/DB_USER=$db_user/" .env
sed -i.bak "s/DB_PASS=.*/DB_PASS=$db_pass/" .env
sed -i.bak "s/DB_NAME=.*/DB_NAME=$db_name/" .env

echo "✓ Database setup complete"

# Set correct permissions
echo ""
echo "Setting permissions..."
chmod 755 public
chmod 644 public/index.php
chmod 644 public/.htaccess
chmod 755 config
chmod 644 config/*.php
chmod 755 src
chmod 644 src/**/*.php

echo "✓ Permissions set"

# Summary
echo ""
echo "========================================="
echo "✓ Setup Complete!"
echo "========================================="
echo ""
echo "Next steps:"
echo "1. Edit .env file if needed"
echo "2. Start your web server:"
echo "   - PHP: php -S localhost:8000 -t public"
echo "   - Apache: Check DocumentRoot points to public/"
echo "   - Nginx: Check config points to public/"
echo "3. Access the application:"
echo "   - http://localhost:8000"
echo "4. Register a new account"
echo "5. Start tracking bets!"
echo ""
echo "Default test account (if seed data imported):"
echo "   Email: test@example.com"
echo "   Password: (register new account)"
echo ""
