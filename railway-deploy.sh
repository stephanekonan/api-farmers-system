#!/bin/bash

echo "🚀 Deploying Farmers API to Railway..."

if ! command -v railway &> /dev/null; then
    echo "❌ Railway CLI is not installed. Please install it first:"
    echo "npm install -g @railway/cli"
    exit 1
fi

echo "🔐 Checking Railway authentication..."
railway status

if [ ! -f .railway/project.json ]; then
    echo "📦 Linking to Railway project..."
    railway link
fi

# Add MySQL service if not exists
echo "🗄️ Setting up MySQL service..."
railway add mysql || echo "MySQL service already exists"

# Add Redis service if not exists
echo "🔴 Setting up Redis service..."
railway add redis || echo "Redis service already exists"

# Deploy the application
echo "🚀 Deploying application..."
railway up

# Wait for deployment to complete
echo "⏳ Waiting for deployment..."
sleep 30

# Run database migrations
echo "🗄️ Running database migrations..."
railway run php artisan migrate --force

# Seed the database
echo "🌱 Seeding database..."
railway run php artisan db:seed --force

# Clear and cache
echo "🧹 Optimizing application..."
railway run php artisan config:clear
railway run php artisan config:cache
railway run php artisan route:clear
railway run php artisan route:cache
railway run php artisan view:clear
railway run php artisan view:cache

# Get the deployment URL
echo "🌐 Getting deployment URL..."
RAILWAY_URL=$(railway domain)
echo "✅ Deployment completed!"
echo "🌐 Your API is available at: $RAILWAY_URL"
echo "📊 Railway dashboard: https://railway.app/project/$(railway project id)"
