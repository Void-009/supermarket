# Hosting / deployment checklist

Deployment notes for moving **this `supermarket` folder** to **Hostinger Cloud
Professional** (or any KVM VPS with 4 vCPU / 8 GB RAM). The Python POS agent
(store PC) keeps running locally and only talks to the hosted API over HTTPS.

> **Why not shared hosting?** This app runs a real-time chat server (Reverb),
> background queue workers, and a daily scheduler. Shared/cPanel plans cannot
> run those persistent processes. A Cloud or VPS plan is required.

---

## 1. Environment (`.env`) — the critical part

Edit `.env` to match the host. Change **at least** the lines below:

```
APP_NAME="M-DELIGHT SINGER STORES"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://YOUR-DOMAIN.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=YOUR-DB-NAME
DB_USERNAME=YOUR-DB-USER
DB_PASSWORD=YOUR-DB-PASS

SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true

CACHE_STORE=database
QUEUE_CONNECTION=database

LOG_LEVEL=warning
```

- **`SESSION_DRIVER` / `CACHE_STORE` / `QUEUE_CONNECTION` should ALL be `database`.**
  This keeps everything simple with no extra services to install. MySQL handles
  sessions, cache, and background jobs. For a local-market storefront this is
  perfectly fine.
- Keep `APP_KEY` the same **if you are importing the existing database**
  (encrypted data stays readable). If this is a **fresh** database, run
  `php artisan key:generate`.
- `SESSION_SECURE_COOKIE=true` only works once HTTPS is live (step 7).
- After editing, run: `php artisan config:clear && php artisan cache:clear && php artisan view:clear`.

## 2. Database

- Export the local `online_supermarket` DB (phpMyAdmin → Export) and import it
  on the host, **or** run `php artisan migrate` on a fresh DB.
- If you import the existing DB, the Sanctum token the agent already uses stays
  valid (tokens live in `personal_access_tokens`).
- If you start fresh, create the agent token once:
  `php artisan pos:token:create` and paste it into the agent's `config.json`
  (step 8).
- **Hostinger:** create the DB with a non-root user + strong password in
  hPanel → Databases → MySQL Databases.

## 3. Point the web server at `public/` — never the Laravel root

The front-controller is `public/index.php` with `.htaccess` present.

- **Hostinger hPanel (shared-style):** File Manager → app folder → `public` →
  "Set as document root".
- **VPS (Apache):** `DocumentRoot /var/www/supermarket/public` +
  `AllowOverride All`.
- **VPS (nginx):** `root /var/www/supermarket/public;` (see Laravel deploy docs).

## 4. Permissions + storage link

```
chmod -R 775 storage bootstrap/cache
php artisan storage:link
```

- The `public/storage` folder must be a **symlink** to `storage/app/public`
  (this app uses a Windows junction locally; on Linux it's a real symlink).
- Uploaded images (items, categories, slideshow) are stored there.

## 5. Assets (Vite + Tailwind 4)

Build the assets on the host (or locally and upload `public/build`):

```bash
npm install
npm run build
```

This produces `public/build/...`. Do **not** upload `node_modules`.

## 6. HTTPS + real-time (Reverb) WebSocket

## 7a. HTTPS on the domain
Install SSL in hPanel (or Let's Encrypt via certbot on a VPS) and force HTTPS.
After that the `.env` values from step 1 (`APP_URL=https://...`,
`SESSION_SECURE_COOKIE=true`) take effect. The agent's bearer token must never
travel over plain HTTP.

### 6b. Reverb server (powers the live chat)
Reverb is a PHP WebSocket server that must run permanently. On the VPS use PM2:

```bash
sudo npm install -g pm2
# from the app folder (must run from the project root so `artisan` is found):
pm2 start php --name reverb -- artisan reverb:start --host=0.0.0.0 --port=8130
pm2 save && pm2 startup
```

> **Port note:** do **not** use 8080 — nginx typically already binds 8080 on
> CloudPanel hosts, which makes Reverb crash with `Address already in use`
> (EADDRINUSE). This setup uses **8130** instead. If you want a different port,
> change it in the PM2 command AND in `.env` (`REVERB_PORT`, `REVERB_SERVER_PORT`)
> AND in the nginx reverse-proxy below — all three must match.

`.env` values for Reverb (browser vs. server ports are DIFFERENT on purpose):

```
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=802650
REVERB_APP_KEY=v1gipfshgd9p62i4fu0j
REVERB_APP_SECRET=fnh8xduuoe2lfw9ek6vi

# --- Browser-facing address (public, through the HTTPS reverse proxy) ---
REVERB_HOST=mdelightsingerstores.com
REVERB_PORT=443
REVERB_SCHEME=https
REVERB_ALLOWED_ORIGINS="https://mdelightsingerstores.com,https://www.mdelightsingerstores.com"

# --- Where Reverb actually binds (private; nginx forwards /app/ -> this) ---
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8130
```

- `REVERB_PORT=443` + `REVERB_SCHEME=https` tell the browser to connect over
  **WSS** via the normal HTTPS port (i.e. `wss://domain/...`).
- `REVERB_SERVER_PORT=8130` is where Reverb listens privately; nginx forwards
  `/app/` there. Never point `REVERB_SERVER_PORT` at a port nginx already uses.
- **Reverse proxy (nginx)** so clients reach WSS through port 443. Example
  location block to forward `/app/` to Reverb:

```nginx
location /app/ {
    proxy_pass http://127.0.0.1:8130;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
}
```

- **Fallback:** if the WebSocket is unreachable, the storefront still works —
  chat simply won't update in real time (users refresh to see new messages).

## 9. Queue workers (POS sync batches + emails)

Batches stay `pending` until a worker runs. Start **2 workers** for headroom:

```bash
pm2 start php --name queue1 -- artisan queue:work --queue=pos-sync,default --tries=3 --backoff=10,60,300
pm2 save
```

`pos-sync` queue = item batches from the agent. `default` = emails/receipts + legacy Excel sync.

## 10. Scheduler (daily cleanup, abandoned-cart emails)

Add one cron line (every minute; Laravel decides what actually runs):

```
* * * * * cd /var/www/supermarket && php artisan schedule:run >> /dev/null 2>&1
```

This handles `sync:cleanup --days=7` (daily 04:00) and the abandoned-cart email
job (every 30 min). Add via `crontab -e`.

## 11. After upload, on the agent PC

Edit `pos-sync-agent\config.json` — change ONLY `base_url` and `token`:

```json
"base_url": "https://YOUR-DOMAIN.com",
"token": "<same token if DB imported, else the new one>"
```

Then restart the agent (dashboard → Restart Agent) and use **Test connection**.
`sync.db` and `snapshots/latest_items.json` stay local to the agent — no changes.

> **Token expiry is automatic (no manual work):** Sanctum tokens expire after
> 30 days (`SANCTUM_TOKEN_EXPIRATION=43200`), but the agent renews its own key
> every 25 days (`api.token_renew_days` in `config.json`) via
> `POST /api/pos/sync/rotate-token`. The new key is saved into `config.json`
> automatically — staff never touch it. The running agent must be on version
> 1.2.0+ for this; restart it once after upgrading. `token_rotated_at` in
> `config.json` records when the last renewal happened.

## 12. Smoke test after deploying

1. Open `https://YOUR-DOMAIN.com` — storefront loads with styles (assets built).
2. Login as an admin → `Admin → POS Sync` — page loads with the agent card.
3. From the agent dashboard: **Test connection** → `pong`, then upload a small
   real change → it appears as a batch and finishes `completed` in
   `/admin/pos-sync`.
4. Send a test chat message as a customer and confirm the admin sees it in real
   time (confirms Reverb works).
5. Watch resources: `htop` — MySQL should stay low with database queue.

## 13. Realistic capacity (so you know what to expect)

- **Cloud Professional / 4 vCPU + 8 GB RAM:** comfortably handles a few
  hundred concurrent shoppers plus the admin processing orders.
- **"300,000 at the same time"** is Facebook-scale and no single server handles
  that — but a local-market storefront will realistically peak at dozens to a
  few hundred simultaneous users, which this setup handles smoothly.
- If you ever outgrow it, scale **vertically** (bigger VPS) then **horizontally**
  (add app servers behind a load balancer + Redis for shared sessions).
