#!/bin/bash

# Farmers API Deployment Script
echo "🚀 Starting Farmers API deployment..."

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo "❌ Docker is not installed. Please install Docker first."
    exit 1
fi

# Check if Docker Compose is installed
if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose is not installed. Please install Docker Compose first."
    exit 1
fi

# Create necessary directories
echo "📁 Creating necessary directories..."
mkdir -p docker/nginx
mkdir -p docker/mysql
mkdir -p storage/logs
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p bootstrap/cache

# Set proper permissions
echo "🔐 Setting permissions..."
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Copy environment file if it doesn't exist
if [ ! -f .env ]; then
    echo "📝 Creating environment file..."
    cp .env.example .env
    echo "⚠️  Please update your .env file with your database credentials"
fi

# Build and start containers
echo "🔨 Building Docker containers..."
docker-compose build

echo "🚀 Starting containers..."
docker-compose up -d

# Wait for MySQL to be ready
echo "⏳ Waiting for MySQL to be ready..."
sleep 30

# Install dependencies and optimize
echo "📦 Installing PHP dependencies..."
docker-compose exec app composer install --no-dev --optimize-autoloader

# Generate application key
echo "🔑 Generating application key..."
docker-compose exec app php artisan key:generate --force

# Run database migrations
echo "🗄️ Running database migrations..."
docker-compose exec app php artisan migrate --force

# Seed the database
echo "🌱 Seeding database..."
docker-compose exec app php artisan db:seed --force

# Clear and cache configurations
echo "🧹 Clearing and caching configurations..."
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:clear
docker-compose exec app php artisan view:cache

# Create storage link
echo "🔗 Creating storage link..."
docker-compose exec app php artisan storage:link

# Show running containers
echo "📊 Running containers:"
docker-compose ps

# Show logs
echo "📋 Application logs:"
docker-compose logs app

echo "✅ Deployment completed successfully!"
echo "🌐 Your API is now available at: http://localhost:8000"
echo "🔍 API Documentation: http://localhost:8000/api/docs"
echo ""
echo "📝 Useful commands:"
echo "  View logs: docker-compose logs -f"
echo "  Stop containers: docker-compose down"
echo "  Restart containers: docker-compose restart"
echo "  Access container: docker-compose exec app bash"
