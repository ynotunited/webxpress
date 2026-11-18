# Hostinger deployment for webxpresstech.com.ng

This is the launch plan for Hostinger shared hosting with Cloudflare in front.

The application needs PHP 8.3, MySQL, writable storage, outbound SMTP, inbound IMAP, and one cron entry. Hostinger shared hosting can provide all of that. Cloudflare can provide DNS, proxy, HTTPS redirect and WAF. It cannot run the Laravel app itself.

## 1. Decide the Cloudflare mode

Recommended launch setup:

- Cloudflare DNS: proxy ON, orange cloud.
- SSL/TLS encryption mode: **Full (strict)**. Hostinger's free SSL certificate stays installed on the origin.
- Always Use HTTPS: ON.
- Rocket Loader: OFF. The site has a strict CSP with nonces.
- Email Address Obfuscation: OFF. It injects script that the CSP blocks.
- Browser Integrity Check: ON is fine.
- Cloudflare Email Routing: OFF. The Hostinger mailbox needs the MX records.

If webhook source checks ever misbehave, temporarily set DNS to **DNS only** (grey cloud). That makes the origin see Paystack's real IP directly. Then fix trusted proxies before turning the orange cloud back on.

Do not use Flexible SSL. It lets Cloudflare talk HTTP to Hostinger, which causes redirect loops and leaks the origin traffic.

## 2. Hostinger setup

1. Point `webxpresstech.com.ng` and `www.webxpresstech.com.ng` at the Hostinger site.
2. Install a Hostinger SSL certificate for the domain.
3. Set PHP version to **8.3** in hPanel.
4. Create the MySQL database and user. Note the database name, username and password.
5. Create an email mailbox used by the app. `hello@webxpresstech.com.ng` is the sender and team recipient. `inquiries@webxpresstech.com.ng` can be the shared reply mailbox. Using one mailbox for both is acceptable.
6. In the Hostinger email settings, publish the SPF and DKIM records. If Cloudflare manages DNS, add those records in Cloudflare, not only in hPanel.

Hostinger mail endpoints, confirmed from Hostinger docs:

| Purpose | Host | Port | Security |
| --- | --- | --- | --- |
| IMAP inbox | `imap.hostinger.com` | 993 | SSL/TLS |
| SMTP send | `smtp.hostinger.com` | 465 | SSL/TLS |

Hostinger shared hosting has sending limits. If the site later sends more than the plan allows, switch `MAIL_*` to Resend SMTP and keep Hostinger IMAP for replies.

## 3. Put the code on Hostinger

Preferred layout is the Laravel project outside the web root, with the domain document root pointing at `public/`.

If the Hostinger plan will not let you change the document root, upload the project into the domain directory and keep the committed root `.htaccess`. It rewrites every request into `public/`. The Laravel `.htaccess` inside `public/` handles pretty URLs from there.

If SSH is available:

```sh
cd /home/uXXXXXXX/domains/webxpresstech.com.ng
git clone <your-project-repo> .
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
cp .env.example .env
# edit .env, then:
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan filament:assets
php artisan optimize
```

If SSH is not available, run these locally before uploading:

```sh
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan filament:assets
```

Then upload the whole project, including `vendor/`, `public/build/`, `public/css/filament/`, `public/js/filament/` and `public/fonts/filament/`, because Hostinger's Git integration does not run Composer, Vite or Filament asset publishing for you. Do not rely on Git deployment alone: this repository intentionally ignores those generated directories, so a fresh checkout will not contain them. Either run the SSH steps above or upload those directories manually after every deploy.

Make `storage/`, `storage/framework/`, `storage/logs/` and `bootstrap/cache/` writable by the PHP user.

After upload, check `https://webxpresstech.com.ng/.env`. It must not be downloadable.

## 4. Production `.env`

Never commit this file. Fill in the real values on the server.

```dotenv
APP_NAME="Webxpress Technologies"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://webxpresstech.com.ng

# Paste the current Cloudflare IPv4 and IPv6 ranges from
# https://www.cloudflare.com/ips/ as a comma-separated value.
# Leave empty only if Cloudflare proxy is OFF.
TRUSTED_PROXIES="173.245.48.0/20,103.21.244.0/22,..."
TRUSTED_HEADERS=30
FORCE_HTTPS=true
SESSION_SECURE_COOKIE=true

LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=info

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_hostinger_db
DB_USERNAME=your_hostinger_user
DB_PASSWORD="your_db_password"

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public

MAIL_MAILER=smtp
MAIL_SCHEME=ssl
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME="hello@webxpresstech.com.ng"
MAIL_PASSWORD="mailbox_password"
MAIL_FROM_ADDRESS="hello@webxpresstech.com.ng"
MAIL_FROM_NAME="Webxpress Technologies"
MAIL_INQUIRIES_TO="hello@webxpresstech.com.ng"

IMAP_HOST=imap.hostinger.com
IMAP_PORT=993
IMAP_ENCRYPTION=ssl
IMAP_USERNAME="inquiries@webxpresstech.com.ng"
IMAP_PASSWORD="mailbox_password"
IMAP_FOLDER=INBOX

PAYSTACK_PUBLIC_KEY=pk_live_...
PAYSTACK_SECRET_KEY=sk_live_...
PAYSTACK_BASE_URL=https://api.paystack.co
PAYSTACK_ALERT_EMAIL="hello@webxpresstech.com.ng"
PAYSTACK_NOTIFY_ON_PAYMENT=true

ADMIN_NAME="Webxpress Admin"
ADMIN_EMAIL="your_real_admin_email"
ADMIN_PASSWORD="a-long-random-password"
ADMIN_PATH="a-random-panel-path"

CSP_ENABLED=true
CSP_REPORT_ONLY=false
ALLOW_DESTRUCTIVE_COMMANDS=false
```

Generate `ADMIN_PATH` with:

```sh
php artisan tinker --execute="echo 'panel-'.Str::random(24);"
```

After writing `.env`, run:

```sh
php artisan key:generate
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

`FORCE_HTTPS` now performs a real redirect. It reads from config, so it survives `config:cache`. `TRUSTED_HEADERS=30` trusts `X-Forwarded-For`, `Host`, `Proto` and `Port`.

## 5. Cron

Add one cron job in hPanel, every minute:

```sh
* * * * * /usr/bin/php /home/uXXXXXXX/domains/webxpresstech.com.ng/artisan schedule:run
```

Replace the PHP path with the 8.3 binary shown in hPanel. If hPanel rejects a raw command, put that line in a `.sh` file and schedule the shell file as a custom cron job.

The scheduler then runs:

- queue worker every minute
- IMAP reply fetch every five minutes
- Paystack payment reconciliation every ten minutes

## 6. Paystack

Register this webhook URL in Paystack:

```text
https://webxpresstech.com.ng/webhooks/paystack
```

Use live keys. The webhook IP allowlist stays enabled. The trusted-proxy settings above are what let Laravel see Paystack's real IP through Cloudflare.

After saving the webhook, use Paystack's webhook test button and confirm the event appears in the admin panel.

## 7. DNS records to keep in Cloudflare

- `A` or `CNAME` for `@` and `www` pointing to the Hostinger site.
- Hostinger MX records for the mailbox. Do not enable Cloudflare Email Routing.
- SPF TXT and DKIM TXT records from Hostinger Email.
- Do not create duplicate SPF records. Merge if Hostinger and another sender both need to be authorised.

## 8. Status page

Deploy the `status/` directory separately to Cloudflare Pages. Use no build command and output directory `status`. Add `status.webxpresstech.com.ng` as the Pages domain.

Add an external monitor such as UptimeRobot for `https://webxpresstech.com.ng`. Keep the monitor outside Hostinger.

## 9. Launch verification

Before pointing real traffic at the domain:

- `https://webxpresstech.com.ng/up` returns OK.
- Homepage, work, services, insights, contact and admin login all load over HTTPS.
- `/.env` is not accessible.
- Contact form sends a real email and creates an inquiry in the panel.
- Admin login works at the random `ADMIN_PATH`.
- Paystack live webhook test records a payment event.
- `php artisan inquiries:fetch-replies` connects to IMAP without an error.
- Cron has run at least once and `storage/logs` shows no scheduler errors.
- Social preview uses `og:image` with the 1200 x 630 image.

Do not run `migrate:fresh`, `db:wipe` or `migrate:reset` on this server. The production safety guard blocks them unless explicitly overridden.
