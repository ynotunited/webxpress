# Hostinger deployment for webxpresstech.com.ng

This is the launch plan for Hostinger shared hosting. DNS is currently managed at Hostinger. Cloudflare is optional and can be added later.

The application needs PHP 8.2, MySQL, writable storage, outbound SMTP, inbound IMAP, and one cron entry. Hostinger shared hosting can provide all of that. Cloudflare can add a CDN and WAF later, but it cannot run the Laravel app itself.

## 1. DNS and email records at Hostinger

In hPanel, open **Domains -> DNS** for `webxpresstech.com.ng` and confirm:

- `A` record for `@` points to the IP address Hostinger assigned to this site. Use the value shown in hPanel, do not guess it.
- `CNAME` record for `www` points to `webxpresstech.com.ng`, or use the Hostinger default if it already exists.

Important: Hostinger CDN is enabled automatically on many shared hosting plans. If `@` or `www` point to a `*.cdn.hstgr.net` hostname, disable the CDN before launch at **Websites -> Manage site -> Performance -> CDN -> Disable**. Wait a few minutes, then confirm the root and `www` records have been restored to the hosting plan's own A/CNAME records.

Keep the CDN disabled unless trusted-proxy handling is added later. With the CDN on, Laravel sees Hostinger's proxy IP rather than the real visitor or Paystack IP, which breaks rate limits and the Paystack webhook allowlist. With the CDN off, `TRUSTED_PROXIES` stays empty.

For mail, open **Email -> Email Domain Settings** and let Hostinger add or fix the records. Because DNS is hosted at Hostinger, hPanel can do this automatically. It should result in:

- MX records for Hostinger mail
- SPF TXT record for Hostinger
- DKIM TXT record
- DMARC TXT record

Do not enable Cloudflare Email Routing at any point. The mailbox needs Hostinger's MX records.

With DNS at Hostinger and no CDN in front, leave `TRUSTED_PROXIES` empty in `.env`. If Cloudflare is added later:

- Set DNS proxy to ON, orange cloud.
- Use SSL/TLS mode **Full (strict)**.
- Turn on **Always Use HTTPS**.
- Turn off Rocket Loader and Email Address Obfuscation.
- Paste the current Cloudflare IP ranges into `TRUSTED_PROXIES` and keep `TRUSTED_HEADERS=30`.

Do not use Flexible SSL with Cloudflare. It lets Cloudflare talk HTTP to Hostinger, which causes redirect loops and leaks the origin traffic.

## 2. Hostinger setup

1. Point `webxpresstech.com.ng` and `www.webxpresstech.com.ng` at the Hostinger site.
2. Install a Hostinger SSL certificate for the domain.
3. Set PHP version to **8.2** in hPanel.
4. Create the MySQL database and user. Note the database name, username and password.
5. Create one real mailbox: `hello@webxpresstech.com.ng`. It must be a mailbox with IMAP access, not a forwarder or alias. This app uses the same address for sending, Reply-To, team notifications and IMAP reply fetching, so keep `MAIL_FROM_ADDRESS`, `MAIL_INQUIRIES_TO`, `MAIL_USERNAME` and `IMAP_USERNAME` pointed at it.
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

# DNS is at Hostinger and the CDN is off, so no proxy is trusted.
# If Cloudflare is added later, paste its current IPv4/IPv6 ranges here.
TRUSTED_PROXIES=""
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
MAIL_SCHEME=smtps
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
IMAP_USERNAME="hello@webxpresstech.com.ng"
IMAP_PASSWORD="mailbox_password"

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
* * * * * /usr/bin/php /home/u655735127/domains/webxpresstech.com.ng/public_html/artisan schedule:run
```

Replace the path with the actual install directory if it differs. If hPanel rejects a raw command, put that line in a `.sh` file and schedule the shell file as a custom cron job.

The scheduler then runs:

- queue worker every minute
- IMAP reply fetch every five minutes
- Paystack payment reconciliation every ten minutes

## 6. Paystack

Register this webhook URL in Paystack:

```text
https://webxpresstech.com.ng/webhooks/paystack
```

Use live keys. Leave the IP whitelist empty: Hostinger shared hosting has no stable origin IP to allowlist, and the webhook verifies Paystack's HMAC signature. `TRUSTED_PROXIES` stays empty because Cloudflare is not in front of the site.

The current Paystack dashboard has no test button. Verify the endpoint by sending a locally signed event and confirm that one `webhook_events` row is recorded, then delete that row.

## 7. DNS records at Hostinger

- `A` or `CNAME` for `@` and `www` pointing to the Hostinger site.
- Hostinger MX records for the mailbox.
- SPF TXT and DKIM TXT records from Hostinger Email.
- Do not create duplicate SPF records. Merge if Hostinger and another sender both need to be authorised.
- Cloudflare is not active. Do not enable Cloudflare Email Routing.

## 8. Status page

Deploy the `status/` directory separately from the Laravel app. It is static HTML/CSS/JS with no build step. Cloudflare Pages works, but because DNS is at Hostinger you must add the `status` CNAME that Cloudflare gives you in hPanel; putting the main domain behind Cloudflare is not required.

Add an external monitor such as UptimeRobot for `https://webxpresstech.com.ng`. Keep the monitor outside Hostinger so it can detect an outage of the hosting itself.

## 9. Launch verification

Before pointing real traffic at the domain:

- `https://webxpresstech.com.ng/up` returns OK.
- Homepage, work, services, insights, contact and admin login all load over HTTPS.
- `/.env` is not accessible.
- Contact form sends a real email and creates an inquiry in the panel.
- Admin login works at the random `ADMIN_PATH`.
- Paystack live webhook is saved and a signed test event records one `webhook_events` row.
- `php artisan inquiries:fetch-replies` connects to IMAP without an error.
- Cron has run at least once and `storage/logs` shows no scheduler errors.
- Social preview uses `og:image` with the 1200 x 630 image.

Do not run `migrate:fresh`, `db:wipe` or `migrate:reset` on this server. The production safety guard blocks them unless explicitly overridden.
