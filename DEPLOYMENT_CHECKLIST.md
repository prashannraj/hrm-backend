# ERP Deployment Checklist and Migration Strategy

## Phase 10: Production Hardening

### 1. Database Migration Strategy

#### SQLite to MySQL/MariaDB Migration
- The application uses SQLite for development. For production, migrate to MySQL/MariaDB.
- Run `php artisan migrate` on production database.
- Use `php artisan db:seed --class=ErpPermissionsSeeder` to seed permissions.

#### Migration Steps:
1. Create MySQL database with UTF8MB4 charset
2. Update `.env` with MySQL connection details
3. Run migrations: `php artisan migrate --force`
4. Run seeders: `php artisan db:seed --class=ErpAccountingCoreSeeder`
5. Run permissions seeder: `php artisan db:seed --class=ErpPermissionsSeeder`

### 2. Backup Strategy

#### Daily Backups
```bash
# Database backup
mysqldump -u [user] -p [database] > backup_$(date +%Y%m%d).sql

# Files backup
tar -czf files_backup_$(date +%Y%m%d).tar.gz storage/
```

#### Automated Backup Script
Create `laravel/scripts/backup.sh`:
```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups"

# Database backup
php artisan db:backup --path="$BACKUP_DIR/db_$DATE.sql"

# Files backup
tar -czf "$BACKUP_DIR/files_$DATE.tar.gz" storage/

# Keep last 30 days
find $BACKUP_DIR -name "db_*.sql" -mtime +30 -delete
find $BACKUP_DIR -name "files_*.tar.gz" -mtime +30 -delete
```

### 3. Rollback Strategy

#### Database Rollback
- Use Laravel migrations: `php artisan migrate:rollback --step=1`
- For multiple steps: `php artisan migrate:rollback --step=N`

#### Application Rollback
- Keep tagged releases in version control
- Deploy with: `git checkout v1.0.0 && composer install --no-dev`

### 4. Performance Optimization

#### Query Optimization
- All report queries use indexed columns
- Use `EXPLAIN` to verify query performance
- Consider query caching for frequently accessed reports

#### Caching Strategy
```php
// Cache dashboard data for 5 minutes
Cache::remember('dashboard_' . $companyId, 300, fn() => $this->reportService->dashboard(...));
```

### 5. Security Checklist

- [x] Role-based permissions implemented
- [x] Fiscal year close/reopen with privileged access
- [x] Data integrity checks available
- [x] Database indexes for performance
- [x] Soft-delete policies for non-posted data
- [x] Audit logging for all critical operations

### 6. Monitoring

- Monitor database size and growth
- Set up alerts for failed integrity checks
- Track API response times for report endpoints

### 7. Deployment Steps

1. **Pre-deployment**
   - Run test suite: `php artisan test`
   - Check for syntax errors: `php artisan config:cache`
   - Clear cache: `php artisan cache:clear`

2. **Deployment**
   - Pull latest code
   - Install dependencies: `composer install --no-dev --optimize-autoloader`
   - Run migrations: `php artisan migrate --force`
   - Build frontend: `npm run build`

3. **Post-deployment**
   - Warm up cache
   - Verify critical endpoints
   - Run integrity check: `GET /api/v1/admin/integrity-checks`

### 8. Environment Variables

Required for production:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hrm_erp
DB_USERNAME=erp_user
DB_PASSWORD=secure_password

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

APP_ENV=production
APP_DEBUG=false