# Farmers API - Docker Deployment

This guide explains how to deploy the Farmers API system using Docker and Docker Compose.

## 🐳 Docker Architecture

The deployment consists of 5 services:

- **app**: Laravel PHP-FPM application
- **nginx**: Web server for HTTP requests
- **mysql**: MySQL 8.0 database
- **redis**: Redis for caching and queues
- **queue**: Laravel queue worker
- **scheduler**: Laravel task scheduler

## 📋 Prerequisites

- Docker 20.10+
- Docker Compose 2.0+
- Git

## 🚀 Quick Start

### 1. Clone the Repository
```bash
git clone <repository-url>
cd api-farmers-system
```

### 2. Run the Deployment Script
```bash
chmod +x deploy.sh
./deploy.sh
```

### 3. Access the Application
- API URL: http://localhost:8000
- API Documentation: http://localhost:8000/api/docs

## 🔧 Manual Deployment

### Step 1: Environment Configuration
```bash
cp .env.example .env
# Edit .env with your configuration
```

### Step 2: Build and Start Containers
```bash
docker-compose build
docker-compose up -d
```

### Step 3: Database Setup
```bash
# Wait for MySQL to start (30 seconds)
docker-compose exec app php artisan migrate --force
docker-compose exec app php artisan db:seed --force
```

### Step 4: Optimize Application
```bash
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
docker-compose exec app php artisan storage:link
```

## 📁 Project Structure

```
api-farmers-system/
├── docker/
│   ├── nginx/
│   │   └── default.conf          # Nginx configuration
│   └── mysql/
│       └── my.cnf               # MySQL configuration
├── Dockerfile                   # PHP application container
├── docker-compose.yml           # Multi-container orchestration
├── .dockerignore               # Files to exclude from Docker build
└── deploy.sh                   # Automated deployment script
```

## 🔧 Configuration

### Environment Variables
Key environment variables in `.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=farmers_db
DB_USERNAME=root
DB_PASSWORD=secret123

CACHE_DRIVER=redis
REDIS_HOST=redis
REDIS_PORT=6379

QUEUE_CONNECTION=redis
```

### Database Configuration
The MySQL container uses:
- Database: `farmers_db`
- Username: `root`
- Password: `secret123`
- Port: `3306` (exposed to host)

### Nginx Configuration
- Listens on port 80
- Serves from `/var/www/html/public`
- PHP-FPM upstream: `app:9000`
- Gzip compression enabled
- Security headers configured

## 🛠️ Useful Commands

### Container Management
```bash
# View running containers
docker-compose ps

# View logs
docker-compose logs -f

# View specific service logs
docker-compose logs -f app
docker-compose logs -f nginx
docker-compose logs -f mysql

# Stop containers
docker-compose down

# Restart containers
docker-compose restart

# Access application container
docker-compose exec app bash

# Access MySQL
docker-compose exec mysql mysql -u root -p
```

### Application Management
```bash
# Run migrations
docker-compose exec app php artisan migrate

# Create new migration
docker-compose exec app php artisan make:migration create_table

# Run seeds
docker-compose exec app php artisan db:seed

# Clear cache
docker-compose exec app php artisan cache:clear

# Optimize
docker-compose exec app php artisan optimize

# Queue management
docker-compose exec app php artisan queue:work
docker-compose exec app php artisan queue:failed
```

### Development Workflow
```bash
# Build containers
docker-compose build

# Start in development mode
docker-compose up -d

# Watch logs
docker-compose logs -f app

# Make changes to code
# Changes are reflected automatically due to volume mounts

# Rebuild if dependencies change
docker-compose build --no-cache app
```

## 🔍 Monitoring and Debugging

### Health Checks
```bash
# Check container health
docker-compose ps

# Check application health
curl http://localhost:8000/api/health

# Check database connection
docker-compose exec app php artisan tinker
>>> DB::connection()->getPdo()
```

### Log Locations
- Application logs: `storage/logs/laravel.log`
- Nginx logs: Container logs (`docker-compose logs nginx`)
- MySQL logs: Container logs (`docker-compose logs mysql`)

### Performance Monitoring
```bash
# Monitor resource usage
docker stats

# Check MySQL performance
docker-compose exec mysql mysqladmin status

# Check Redis
docker-compose exec redis redis-cli info
```

## 🔒 Security Considerations

### Production Deployment
1. **Update passwords**: Change default database passwords
2. **SSL/TLS**: Configure HTTPS in production
3. **Firewall**: Restrict database access
4. **Environment**: Use production-ready environment variables
5. **Backups**: Implement regular database backups

### Security Headers
The Nginx configuration includes:
- X-Frame-Options: SAMEORIGIN
- X-XSS-Protection: 1; mode=block
- X-Content-Type-Options: nosniff
- Content-Security-Policy: default-src 'self'

## 📈 Scaling

### Horizontal Scaling
```yaml
# Scale the app service
docker-compose up -d --scale app=3
```

### Load Balancing
For production, consider:
- Using a dedicated load balancer
- Implementing health checks
- Configuring session affinity

### Database Scaling
- Read replicas for read-heavy workloads
- Connection pooling
- Database optimization

## 🔄 CI/CD Integration

### GitHub Actions Example
```yaml
name: Deploy to Production
on:
  push:
    branches: [main]
jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Deploy to server
        run: |
          docker-compose build
          docker-compose up -d
```

## 🐛 Troubleshooting

### Common Issues

1. **Container won't start**
   ```bash
   docker-compose logs <service-name>
   ```

2. **Database connection failed**
   ```bash
   # Check MySQL container
   docker-compose exec mysql mysql -u root -p
   # Verify credentials in .env
   ```

3. **Permission errors**
   ```bash
   # Fix storage permissions
   sudo chmod -R 775 storage bootstrap/cache
   ```

4. **Port conflicts**
   ```bash
   # Check port usage
   netstat -tulpn | grep :8000
   # Change port in docker-compose.yml
   ```

### Performance Issues
- Monitor container resource usage
- Check MySQL slow query log
- Optimize Nginx configuration
- Enable Redis caching

## 📞 Support

For deployment issues:
1. Check container logs
2. Verify environment configuration
3. Test database connectivity
4. Review system resources

## 🔄 Updates and Maintenance

### Updating the Application
```bash
# Pull latest code
git pull

# Rebuild containers
docker-compose build

# Restart services
docker-compose up -d

# Run migrations
docker-compose exec app php artisan migrate --force
```

### Database Backups
```bash
# Create backup
docker-compose exec mysql mysqldump -u root -p farmers_db > backup.sql

# Restore backup
docker-compose exec -T mysql mysql -u root -p farmers_db < backup.sql
```

This Docker setup provides a complete, production-ready environment for the Farmers API system with proper separation of concerns, security configurations, and scalability options.
