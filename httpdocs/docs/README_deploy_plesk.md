# Woork • Laravel 11 • Plesk Deployment Kit (Final Production)

This guide assumes:
- Plesk Obsidian (AlmaLinux/Ubuntu), PHP 8.2 + composer available
- Domain or subdomain dedicated to Woork
- SSH access with the subscription user

## 1) Create domain/subdomain
- In Plesk: Websites & Domains → Add Domain / Subdomain (e.g., `woork.example.com`)
- PHP: 8.2 (FPM)
- Document Root: `httpdocs/public`

## 2) Upload application
Option A) Via Git (recommended):
- Websites & Domains → Git → "Clone from remote" (your repo)
- Set "Deployment path" to `httpdocs`

Option B) Via ZIP:
- Upload `Woork_SaaS_v3_Production.zip` to `httpdocs/..`
- SSH:
  ```bash
  cd httpdocs
  unzip ../Woork_SaaS_v3_Production.zip -d .
  ```

## 3) Composer install (production)
```bash
cd httpdocs
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan storage:link
```

## 4) Database
- Create DB from Plesk → Databases (MySQL/MariaDB)
- Note credentials and fill `.env`

Run migrations & seed:
```bash
php artisan migrate --force
php artisan db:seed --class=WoorkSaaSSeeder --force
```

## 5) Configure Nginx/Apache
Plesk → Domain → Apache & nginx settings → Additional nginx directives:
```
# Woork: serve static and route to index.php
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
location ~ \.php$ {
    try_files $uri =404;
    include fastcgi_params;
    fastcgi_pass "unix:/var/www/vhosts/system/$domain_name/php-fpm.sock";
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
}
# Cache static
location ~* \.(?:jpg|jpeg|png|gif|svg|css|js|woff2?)$ {
    expires 30d;
    add_header Cache-Control "public, max-age=2592000";
}
```
*(leave Apache part as default; Plesk will merge)*

## 6) Environment (.env)
Copy `.env.production.example` → `.env` and edit values.

## 7) Scheduler (CRON)
Plesk → Scheduled Tasks → Add Task (Run a command):
```
/usr/bin/php -d detect_unicode=0 /var/www/vhosts/<domain>/httpdocs/artisan schedule:run >> /var/www/vhosts/<domain>/logs/schedule.log 2>&1
```
- **Run:** *Every minute*

## 8) Queue worker
### Option A (with root): Supervisor
Install supervisor and place `supervisor/woork-worker.conf`, then:
```
supervisorctl reread && supervisorctl update && supervisorctl start woork-worker:*
```

### Option B (no root): systemd --user
Enable lingering for the subscription user (requires admin):
```
loginctl enable-linger <plesk-username>
```
Create `~/.config/systemd/user/woork-queue.service` from kit and run:
```
systemctl --user daemon-reload
systemctl --user enable --now woork-queue.service
```

### Option C (fallback): Plesk Background Task (less reliable)
Keep a persistent command using the "Background Processes" extension, command:
```
/usr/bin/php /var/www/vhosts/<domain>/httpdocs/artisan queue:work --sleep=3 --tries=3
```

## 9) SSL
Plesk → SSL/TLS Certificates → Let's Encrypt → issue a certificate → enable for domain + mail + wildcard (optional).

## 10) Stripe webhooks
Set endpoint in Stripe Dashboard:
- URL: `https://<your-domain>/stripe/webhook`
- Events: `checkout.session.completed`, `customer.subscription.*`, `invoice.paid`, `invoice.payment_failed`

Deploy controller & route from `webhooks/StripeWebhookController.php` and `routes_snippet_web.php`.

## 11) Health & Logs
- App log: `storage/logs/laravel.log`
- Nginx/Apache logs: Plesk → Logs
- Consider Sentry/Bugsnag for error reporting

## 12) Performance
```
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
Enable Redis for sessions/cache if available (Plesk Docker/extension).

## 13) StandingTech WhatsApp
Put your token in `.env` and test OTP from `/login`. Check `storage/logs/laravel.log` if not received.
