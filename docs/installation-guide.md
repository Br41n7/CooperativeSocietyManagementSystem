# Cooperative Society Management System - Installation Guide

## Prerequisites

Before installing the Cooperative Society Management System, ensure your server meets the following requirements:

- PHP 8.1 or higher
- Composer 2.0 or higher
- MySQL 8.0 or higher
- Node.js 18.x or higher (for frontend assets)
- Nginx or Apache web server
- SSL Certificate (recommended for production)

## Installation Steps

### 1. Clone the Repository

```bash
git clone https://github.com/yourusername/cooperative-society.git
cd cooperative-society
```

### 2. Install PHP Dependencies

```bash
composer install --optimize-autoloader --no-dev
```

### 3. Install Node.js Dependencies

```bash
npm install
npm run build
```

### 4. Environment Configuration

Copy the example environment file:

```bash
cp .env.example .env
```

Edit the `.env` file and configure the following settings:

```env
APP_NAME="Cooperative Society"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cooperative_society
DB_USERNAME=your_db_username
DB_PASSWORD=your_db_password

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=public
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@cooperative.com"
MAIL_FROM_NAME="${APP_NAME}"

TWILIO_SID=your_twilio_sid
TWILIO_TOKEN=your_twilio_token
TWILIO_FROM=your_twilio_phone_number
```

### 5. Generate Application Key

```bash
php artisan key:generate
```

### 6. Create Database

Create a new MySQL database:

```sql
CREATE DATABASE cooperative_society CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 7. Run Migrations

```bash
php artisan migrate --force
```

### 8. Seed the Database

```bash
php artisan db:seed --force
```

This will create:
- 5 default roles (super_admin, chairman, secretary, treasurer, member)
- 11 demo users (1 admin + 3 executives + 7 members)
- Sample data for testing

### 9. Set File Permissions

```bash
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 10. Create Symbolic Link

```bash
php artisan storage:link
```

### 11. Configure Web Server

#### Nginx Configuration

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com;
    root /var/www/cooperative-society/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

#### Apache Configuration

Ensure `mod_rewrite` is enabled and create a `.htaccess` file in the public directory:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

### 12. Setup SSL (Recommended)

Use Let's Encrypt for free SSL certificates:

```bash
sudo certbot --nginx -d yourdomain.com
```

### 13. Setup Queue Worker (Optional)

For processing background jobs:

```bash
php artisan queue:work --daemon
```

Configure Supervisor to keep the queue worker running:

```ini
[program:cooperative-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/cooperative-society/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/cooperative-society/storage/logs/queue.log
```

### 14. Setup Cron Jobs

Add to crontab (`crontab -e`):

```bash
* * * * * cd /var/www/cooperative-society && php artisan schedule:run >> /dev/null 2>&1
```

### 15. Setup Email Notifications

Configure your email settings in `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
```

### 16. Setup SMS Notifications (Optional)

For Twilio SMS:

```env
TWILIO_SID=your_account_sid
TWILIO_TOKEN=your_auth_token
TWILIO_FROM=+1234567890
```

## Default Login Credentials

After installation, use these credentials to access the system:

### Admin Accounts

| Role | Email | Password |
|------|-------|----------|
| Super Admin | admin@cooperative.com | admin123 |
| Chairman | chairman@cooperative.com | chairman123 |
| Secretary | secretary@cooperative.com | secretary123 |
| Treasurer | treasurer@cooperative.com | treasurer123 |

### Member Accounts

| Email | Password |
|-------|----------|
| member4@cooperative.com | member123 |
| member5@cooperative.com | member123 |
| ... | ... |
| member10@cooperative.com | member123 |

**IMPORTANT:** Change all default passwords immediately after first login!

## Post-Installation Tasks

1. **Update Application Details**: Edit the cooperative name, address, and contact information
2. **Configure Backup Strategy**: Set up automated database backups
3. **Enable Two-Factor Authentication**: Recommended for admin accounts
4. **Setup Monitoring**: Configure error tracking and performance monitoring
5. **Review Security Settings**: Ensure proper firewall rules and access controls
6. **Test All Features**: Verify all functionality works as expected
7. **Train Users**: Provide training for all system users

## Updating the System

To update to the latest version:

```bash
git pull origin main
composer install --optimize-autoloader --no-dev
php artisan migrate --force
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

## Backup Strategy

### Database Backup

```bash
# Automated daily backup
0 2 * * * mysqldump -u username -p'password' cooperative_society | gzip > /backups/cooperative_$(date +\%Y\%m\%d).sql.gz
```

### Files Backup

```bash
# Backup storage and uploads
rsync -avz /var/www/cooperative-society/storage/ /backups/storage/
```

## Troubleshooting

### Common Issues

1. **Permission Denied Errors**
   ```bash
   chmod -R 755 storage bootstrap/cache
   chown -R www-data:www-data storage bootstrap/cache
   ```

2. **Database Connection Errors**
   - Verify `.env` database credentials
   - Check MySQL server is running
   - Ensure database exists

3. **Queue Not Processing**
   ```bash
   php artisan queue:restart
   ```

4. **Cache Issues**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan view:clear
   ```

## Support

For issues or questions:
- Email: support@cooperative.com
- Documentation: https://docs.cooperative.com
- Issues: https://github.com/yourusername/cooperative-society/issues

## Security Considerations

1. Keep PHP and dependencies updated
2. Use strong passwords
3. Enable HTTPS
4. Regular security audits
5. Limit failed login attempts
6. Monitor access logs
7. Backup regularly
8. Use firewall rules
9. Disable debug mode in production
10. Review user permissions regularly

## Performance Optimization

1. Enable OPcache
2. Use Redis for caching
3. Implement CDN for static assets
4. Optimize database queries
5. Use queue for heavy operations
6. Enable gzip compression
7. Use HTTP/2
8. Implement lazy loading
9. Optimize images
10. Minify CSS and JS