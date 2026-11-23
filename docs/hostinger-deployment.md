# Hostinger deployment for webxpresstech.com.ng

This documents the live setup: Hostinger shared hosting runs the Laravel app, DNS and mail are at Hostinger, and SSH is used for release commands. Cloudflare is not in front of the site at launch.

The app needs PHP 8.2 or newer, MySQL, writable storage, outbound SMTP, inbound IMAP and one cron entry. The live account runs PHP 8.2.36, which satisfies the project's `^8.2` requirement.

## 1. DNS and mail at Hostinger

In hPanel, **Domains -> DNS** for `webxpresstech.com.ng`:

- `A` for `@` -> `212.1.211.49`
- `CNAME` for `www` -> `webxpresstech.com.ng`
- MX `mx1.hostinger.com` priority 5, `mx2.hostinger.com` priority 10
- SPF TXT `v=spf1 include:_spf.mail.hostinger.com ~all`
- DKIM CNAMEs for `hostingermail-a/b/c._domainkey`
- DMARC TXT `v=DMARC1; p=none`
- `autoconfig` and `autodiscover` CNAMEs

Hostinger CDN is **off**. With it on, `@` and `www` point to `*.cdn.hstgr.net`, Laravel sees the CDN IP instead of the visitor or Paystack, and the webhook IP allowlist breaks. Keep `TRUSTED_PROXIES` empty.

In **Email -> Email Domain Settings**, use Hostinger's automatic fix for MX, SPF, DKIM and DMARC. Do not enable Cloudflare Email Routing. Do not create a second SPF record.

One real mailbox is required: `hello@webxpresstech.com.ng`. It must be an IMAP mailbox, not a forwarder or alias. It is used for SMTP sending, Reply-To, team notifications and IMAP reply fetching.

Hostinger mail endpoints:

| Purpose | Host | Port | Security |
| --- | --- | --- | --- |
| IMAP inbox | `imap.hostinger.com` | 993 | SSL/TLS |
| SMTP send | `smtp.hostinger.com` | 465 | SSL/TLS |

SSL is installed for both `webxpresstech.com.ng` and `www.webxpresstech.com.ng`.

## 2. How the code is deployed

Hostinger's Git integration pulls the selected branch into:

```text
/home/u655735127/domains/webxpresstech.com.ng/public_html
```

`public_html` in the home directory is a symlink to that directory. It is the document root.

Hostinger Git **does not run a build step**. It only copies committed files. For that reason the live branch `deploy/hostinger-webxpresstech` commits the generated directories:

- `vendor/` from `composer install --no-dev --prefer-dist --optimize-autoloader`
- `public/build/` from `npm ci && npm run build`
- `public/css/filament/`, `public/js/filament/`, `public/fonts/filament/` from `php artisan filament:assets`

The root `.htaccess` rewrites every request into `public/`, and `public/.htaccess` handles Laravel pretty URLs. The root `.htaccess` also denies direct access to `.env` and `.git`.

`public/storage` cannot be created by `php artisan storage:link` because Hostinger disables `exec()`. Create it manually:

```sh
cd /home/u655735127/domains/webxpresstech.com.ng/public_html
ln -s ../storage/app/public public/storage
```

Make `storage/`, `storage/framework/`, `storage/logs/` and `bootstrap/cache/` writable:

```sh
chmod -R 775 storage bootstrap/cache
```

## 3. Production `.env`

Never commit this file. If it is generated on Windows, convert line endings on the server before using it:

```sh
sed -i 's/\r$//' .env
```

Key production values:

```dotenv
APP_NAME="Webxpress Technologies"
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://webxpresstech.com.ng

TRUSTED_PROXIES=
TRUSTED_HEADERS=30
FORCE_HTTPS=true
SESSION_SECURE_COOKIE=true

LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=info

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=u655735127_webxpress
DB_USERNAME=u655735127_webxpresstec
DB_PASSWORD="..."

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public

MAIL_MAILER=smtp
MAIL_SCHEME=smtps
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME="hello@webxpresstech.com.ng"
MAIL_PASSWORD="..."
MAIL_FROM_ADDRESS="hello@webxpresstech.com.ng"
MAIL_FROM_NAME="Webxpress Technologies"
MAIL_INQUIRIES_TO="hello@webxpresstech.com.ng"
MAIL_INQUIRIES_AUTO_ACK=true

IMAP_HOST=imap.hostinger.com
IMAP_PORT=993
IMAP_ENCRYPTION=ssl
IMAP_VALIDATE_CERT=true
IMAP_USERNAME="hello@webxpresstech.com.ng"
IMAP_PASSWORD="..."

PAYSTACK_PUBLIC_KEY=pk_live_...
PAYSTACK_SECRET_KEY=sk_live_...
PAYSTACK_BASE_URL=https://api.paystack.co

ADMIN_NAME="Webxpress Admin"
ADMIN_EMAIL=admin@webxpresstech.com.ng
ADMIN_PASSWORD="..."
ADMIN_PATH=panel-1dulps1jhfwot6akvo5iw5ei

CSP_ENABLED=true
CSP_REPORT_ONLY=false
ALLOW_DESTRUCTIVE_COMMANDS=false
```

`MAIL_SCHEME` must be `smtps`, not `ssl`. Symfony Mailer only supports `smtp` and `smtps`. `FILESYSTEM_DISK` must be `public` so Filament uploads resolve through `asset('storage/...')`.

`FORCE_HTTPS` performs a real redirect and reads from config, so it survives `config:cache`.

## 4. Release commands

Run these over SSH in the deploy directory:

```sh
cd /home/u655735127/domains/webxpresstech.com.ng/public_html
php artisan config:clear
php artisan migrate --force
php artisan db:seed --force
php artisan optimize
```

`php artisan db:seed --force` is safe to rerun. The seeders only fill missing rows; they do not overwrite live content.

Never run `migrate:fresh`, `db:wipe` or `migrate:reset` on this server.

## 5. Cron

SSH `crontab` is disabled on this Hostinger account. Add the job in hPanel -> **Advanced -> Cron Jobs** with all five fields set to `*`:

```text
Minute:   *
Hour:     *
Day:      *
Month:    *
Weekday:  *
```

Command:

```text
/usr/bin/php /home/u655735127/domains/webxpresstech.com.ng/public_html/artisan schedule:run
```

The scheduler then runs:

- queue worker every minute
- `inquiries:fetch-replies` every five minutes
- `deals:reconcile-payments` every ten minutes

## 6. Paystack

Register this webhook in the live Paystack dashboard:

```text
https://webxpresstech.com.ng/webhooks/paystack
```

Use live keys. The webhook IP allowlist stays enabled. There is no reverse proxy in front of the origin, so Laravel sees Paystack's real IP directly.

After saving, use Paystack's webhook test button and confirm the event appears in `webhook_events` and the admin panel.

## 7. Future deploys

1. Merge changes into `deploy/hostinger-webxpresstech`.
2. Rebuild locally: `composer install --no-dev --prefer-dist --optimize-autoloader`, `npm ci`, `npm run build`, `php artisan filament:assets`.
3. Force-add the generated directories, commit and push.
4. Wait for Hostinger Git to redeploy.
5. Re-upload `.env` if Hostinger removed it, then run `php artisan migrate --force`, `php artisan db:seed --force` and `php artisan optimize`.

Back up `.env` before every redeploy.

## 8. Verification

- `https://webxpresstech.com.ng/up` returns `ok`.
- Apex and `www` both load over HTTPS.
- `/.env` is not downloadable.
- Contact form sends real email and creates an inquiry in the panel.
- Admin login works at the random `ADMIN_PATH`.
- SMTP test sends to `hello@webxpresstech.com.ng`.
- `php artisan inquiries:fetch-replies --simulate` connects to IMAP without an error.
- Cron has run and `storage/logs` shows no scheduler errors.
- Paystack live webhook test records a payment event.
- Social preview uses the 1200 x 630 image at `public/images/og/webxpress-preview.png`.

## 9. Optional later: Cloudflare

If Cloudflare is added later:

- DNS proxy ON, orange cloud.
- SSL/TLS mode **Full (strict)**.
- **Always Use HTTPS** ON.
- Rocket Loader OFF and Email Address Obfuscation OFF.
- Paste current Cloudflare IPv4/IPv6 ranges into `TRUSTED_PROXIES`, keep `TRUSTED_HEADERS=30`.
- Leave Hostinger MX records in place. Do not enable Cloudflare Email Routing.

Never use Flexible SSL. It causes redirect loops and leaves origin traffic unencrypted.

## 10. Status page

Deploy `status/` separately to Cloudflare Pages, output directory `status`, no build command. Add `status.webxpresstech.com.ng` as the Pages domain and an external monitor such as UptimeRobot for the main site.
