# Operator runbook — server-config items from readiness 2026-05-08

These are the steps **only the operator can run** (host access, DNS access, plugin install). Each one closes one or more readiness-audit BLOCKERs / HIGHs that no plugin code can fix.

Run in any order; none are interdependent. Each section ends with a "Verify" check you can paste into the shell to confirm it landed.

---

## 1. CSP — flip from Report-Only to Enforce (B3)

**Why:** the response header is currently `Content-Security-Policy-Report-Only`. The directive list is correct (built by `seo/class-headers.php`), but no policy is enforced — XSS or third-party-script injection still runs. The plugin reads `NK_CSP_ENFORCE` and switches to enforcement when true.

**Before flipping:** browse the site for ~24h with the report-only header on a real-traffic day; collect any reports your endpoint logs. Confirm no third-party script you actually need is being reported.

**Change in `wp-config.php`** on live (`/home/fsalmansour/novakeys.store/wp-config.php`):

```php
/* CSP enforce switch — readiness 2026-05-08 BLOCKER B3 */
define( 'NK_CSP_ENFORCE', true );
```

Place above the `/* That's all, stop editing! */` line.

**Verify:**
```bash
curl -sS -I https://www.novakeys.store/ | command grep -i 'content-security-policy'
# Expect: `content-security-policy:` (no `-report-only`)
```

**Rollback:** change `true` → `false` (or delete the line). Header returns to Report-Only.

---

## 2. AdSense publisher ID (G6)

**Why:** `/ads.txt` falls back to a placeholder until `nk_adsense_client_id` is set. Google AdSense rejects sites whose ads.txt is missing the `pub-XXXXXXXXXXXXXXXX` line.

```bash
ssh -p 21098 fsalmansour@162.254.39.146 \
  "cd /home/fsalmansour/novakeys.store && wp option update nk_adsense_client_id pub-XXXXXXXXXXXXXXXX --url=https://www.novakeys.store"
```

Replace `pub-XXXXXXXXXXXXXXXX` with the publisher ID from the AdSense console.

**Verify:**
```bash
curl -sS https://www.novakeys.store/ads.txt
# Expect a line: google.com, pub-XXXXXXXXXXXXXXXX, DIRECT, f08c47fec0942fa0
```

**Rollback:** `wp option delete nk_adsense_client_id` reverts to placeholder.

---

## 3. wp-cron — disable internal trigger + add system cron (B7)

**Why:** `/wp-cron.php` is publicly reachable (HTTP 200). Anyone can repeatedly hit it to amplify load. The fix is to disable WP's "spawn cron on every front-end request" mechanism, then run a real system cron at a fixed interval.

**Add to `wp-config.php`** on live:

```php
/* readiness 2026-05-08 BLOCKER B7 — wp-cron is publicly reachable */
define( 'DISABLE_WP_CRON', true );
```

**Add a server cron job.** SSH in and edit crontab (`crontab -e`):

```
*/5 * * * * /usr/bin/curl -fsSL --max-time 60 'https://www.novakeys.store/wp-cron.php?doing_wp_cron' >/dev/null 2>&1
```

Runs every 5 minutes. WC's Action Scheduler tasks pick up on the cadence; nothing in the plugin uses sub-5min cron.

**Verify:**
```bash
ssh -p 21098 fsalmansour@162.254.39.146 'crontab -l | command grep wp-cron'
# Expect the */5 line above

curl -sS -o /dev/null -w '%{http_code}\n' https://www.novakeys.store/wp-cron.php
# Returns 200 still (the endpoint stays reachable for the cron to hit it),
# but WP's internal spawn no longer fires on every page view.
```

A stricter alternative blocks wp-cron.php for non-localhost callers via `.htaccess`:

```apache
<Files "wp-cron.php">
    Order deny,allow
    Deny from all
    Allow from 127.0.0.1
</Files>
```

That breaks the curl-from-cron approach above; pick one route, not both.

---

## 4. DMARC — TXT record (HIGH)

**Why:** SPF alone lets attackers spoof outbound mail and have it pass. DMARC tells receiving servers what to do with messages that fail SPF/DKIM alignment.

**DNS panel:** add a TXT record at `_dmarc.novakeys.store`:

```
v=DMARC1; p=quarantine; rua=mailto:dmarc@novakeys.store; ruf=mailto:dmarc@novakeys.store; pct=100; sp=quarantine; aspf=r; adkim=r
```

`p=quarantine` is the recommended starting policy. After 30 days of clean reports you can step up to `p=reject`.

`rua` / `ruf` need to be deliverable mailboxes — set `dmarc@novakeys.store` to forward to your support inbox (Namecheap email console).

**Verify:**
```bash
dig TXT _dmarc.novakeys.store +short
# Expect the policy string above
```

---

## 5. DKIM — selector + key (HIGH)

**Why:** the audit found 9 selectors empty. DKIM is provider-specific — you can't generate it from this side without the outbound mail provider.

**Steps depend on who sends mail for novakeys.store:**

- **Namecheap Private Email** (current MX `162.254.39.146`): admin → Domain settings → DKIM → enable. Namecheap publishes the selector + key automatically.
- **Postmark / SendGrid / SES** (if you migrate): the dashboard gives you 1–2 CNAME records to publish.
- **WP Mail SMTP plugin** (if you use it for transactional): the plugin docs walk through adding a DKIM CNAME.

After publishing, send yourself a test email from the contact form and inspect the headers — `Authentication-Results: ... dkim=pass` should appear.

**Verify:**
```bash
# Replace `default` with your actual selector once you know it.
dig TXT default._domainkey.novakeys.store +short
# Expect a base64-ish key starting v=DKIM1; k=rsa; p=...
```

---

## 6. CAA — restrict cert issuance (HIGH)

**Why:** without a CAA record, any CA can issue a cert for `novakeys.store` if they pass domain validation. CAA pins issuance to your chosen CA.

The current cert is Sectigo (per the readiness audit). DNS panel — add TXT records at apex `novakeys.store`:

```
0 issue "sectigo.com"
0 issue "comodoca.com"
0 issuewild "sectigo.com"
0 iodef "mailto:security@novakeys.store"
```

If you ever migrate to Let's Encrypt, add `0 issue "letsencrypt.org"` BEFORE the migration so the new CA is allowed; remove old entries after the cutover.

**Verify:**
```bash
dig CAA novakeys.store +short
# Expect three or four lines matching the values above
```

---

## 7. LSCache — install + configure (HIGH B3)

**Why:** TTFB is 1.8–2.4s on repeat hits. LiteSpeed serves the site (`x-turbo-charged-by: LiteSpeed` header is present) but no `x-litespeed-cache` hits are seen, meaning the LSCache plugin isn't enabled.

```bash
ssh -p 21098 fsalmansour@162.254.39.146 \
  "cd /home/fsalmansour/novakeys.store && wp plugin install litespeed-cache --activate --url=https://www.novakeys.store"
```

After install, in WP Admin → LiteSpeed Cache → Cache → Cache:

- Enable Cache: ON
- Cache Logged-in Users: OFF
- Cache Commenters: OFF
- Cache REST API: OFF (we don't want our nk/v1 routes cached)
- Cache Mobile: ON
- TTL Public Cache: 86400
- TTL Private Cache: 1800

Then → ESI → Enable ESI: ON (so the cart-count fragment + nonces stay fresh inside cached pages).

**Verify:**
```bash
curl -sS -I https://www.novakeys.store/ | command grep -i 'x-litespeed-cache\|x-litespeed-tag'
# After the second hit, expect: x-litespeed-cache: hit
```

**LSCache exclusions** — already needed, paste into the plugin's "Do Not Cache URIs":
```
/cart
/checkout
/my-account
/wp-json
/?nk_news=
/?ref=
```

---

## 8. CDN — Cloudflare (HIGH)

**Why:** single SPOF on Namecheap shared host. A CDN gives you DDoS scrubbing, edge caching, and a static-asset offload.

**Free tier setup:**
1. Sign up at cloudflare.com, add `novakeys.store`.
2. Switch nameservers at Namecheap → Cloudflare's two NS records.
3. SSL/TLS mode: **Full (Strict)** — origin already has a valid Sectigo cert.
4. Caching → Configuration: cache level Standard, browser cache 4h.
5. Speed → Optimization: Brotli ON, Auto Minify (CSS+JS) ON, Rocket Loader OFF (breaks WC blocks).
6. Page Rules:
   - `*novakeys.store/wp-admin/*` → Cache Level: Bypass, Always Online: Off
   - `*novakeys.store/wp-login.php` → Cache Level: Bypass
   - `*novakeys.store/wp-json/*` → Cache Level: Bypass
   - `*novakeys.store/cart/`, `/checkout/`, `/my-account/*` → Cache Level: Bypass

**Verify:**
```bash
curl -sS -I https://www.novakeys.store/ | command grep -i 'cf-ray\|server'
# Expect: cf-ray: <hex>-<location>, server: cloudflare
```

---

## 9. Uptime Kuma — register the site (HIGH)

**Why:** zero monitoring today. If the site goes down at 03:00 you'll find out from a customer email.

If you already have an Uptime Kuma instance:
1. Add monitor: Type HTTP(s), URL `https://www.novakeys.store/`, Heartbeat Interval 60s, Retries 3.
2. Add a second monitor for `https://www.novakeys.store/wp-json/nk/v1/points` with HTTP method GET — this confirms the plugin REST surface is reachable, not just nginx.
3. Notification → Telegram or email; threshold "down ≥ 2 consecutive checks".

If you need to spin one up:
```bash
docker run -d --restart=always -p 3001:3001 -v uptime-kuma:/app/data --name uptime-kuma louislam/uptime-kuma:1
```
Browse to `http://<host>:3001`, complete first-run setup, then add the two monitors above.

**Verify:** the dashboard shows two green monitors after the first 60s heartbeat.

---

## 10. Vault-key off-host backup (HIGH)

**Why:** the gift-card vault derives its AES key from `wp_salt('logged_in')`. If `wp-config.php` is lost (host failure, accidental wipe), every encrypted code in `wp_woocommerce_order_itemmeta` becomes permanently unreadable. Customers' purchased gift-card codes are gone — full data-loss event.

**Quick mitigation (5 minutes):**
1. SSH in: `ssh -p 21098 fsalmansour@162.254.39.146 "cd /home/fsalmansour/novakeys.store && grep -E 'LOGGED_IN_KEY|LOGGED_IN_SALT' wp-config.php"`
2. Copy the two values.
3. Store off-host in your password manager (Bitwarden/1Password) under "novakeys.store / WP salts (vault decryption)".

**Better mitigation (separate plan):**
- Daily `wp-config.php` backup to Cloudflare R2 / S3, encrypted at rest.
- Document the salt-rotation policy: never rotate `LOGGED_IN_KEY` / `LOGGED_IN_SALT` while there are unredeemed gift-card codes in customer inventory; if you do, run `Vault::rewrap_v1_to_v2()` first (not yet implemented — separate plan).

---

## What's NOT in this runbook (intentional)

These need scoping decisions before any automation can be drafted:

- **ZATCA Phase-2 FATOORA integration** — needs SDK choice (PHP wrapper vs direct REST), ZATCA test-portal credentials, decision on QR-on-thank-you-page vs QR-in-email body
- **PDPL cookie consent banner** — needs UX decision (modal / pill / footer ribbon), bilingual cookie inventory copy review, integration with TranslatePress
- **Vault key rotation path** — full rewrap-on-rotate flow (Vault v1 → v2 was lazy; v2 → vN needs an explicit migration)

When you're ready to scope any of these, say so and I'll draft the plan.
