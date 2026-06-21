# SiteGround deploy runbook — periodismofederal.com

This site was **migrated** from a live, panel-managed SiteGround WordPress install into
this Roots Bedrock repo (via `/migrate-siteground-wp`). The database and media library
are **preserved production state** — never reinstalled. Below are the concrete values
for this site; the canonical conventions (the source of truth) follow further down.

## This site

| Item                | Value                                                          |
|---------------------|----------------------------------------------------------------|
| Domain              | `periodismofederal.com` (apex — live, real traffic)            |
| SSH host            | `ssh.periodismofederal.com` (IP `34.174.136.245`)              |
| SSH user            | `u569-dykz25wog4r8`                                             |
| SSH port            | `18765`                                                        |
| Deploy path         | `www/periodismofederal.com/bedrock`                            |
| Docroot symlink     | `www/periodismofederal.com/public_html` → `bedrock/web`        |
| DB name             | `dbmfvymsp85j01`                                                |
| DB user             | `ucjo2hfargvja`                                                 |
| DB host             | `127.0.0.1`                                                     |
| **DB table prefix** | **`qgx_`** (not `wp_` — must be `DB_PREFIX` in the server .env) |
| WP_HOME             | `https://periodismofederal.com`                                |
| WP core             | `7.0` (`roots/wordpress:7.0`)                                   |
| PHP (target)        | `8.3` / `php83` — PHP Manager MUST be ≥ 8.3 (Bedrock fatals otherwise) |
| Active theme        | `digital-newspaper` 1.1.18 (classic theme, Composer dep)       |
| Permalinks          | `/%postname%/`                                                 |

## Sourcing (Composer vs committed vs dropped)

- **Composer-pinned theme:** `wp-theme/digital-newspaper:1.1.18` (active). The default
  Bedrock `wp-theme/twentytwentyfive` is also pinned by the scaffold (harmless, ignored).
- **Composer-pinned plugins (28)** — all pinned to the exact live version:
  - Active: `ads-txt` `advanced-ads` `advanced-ads-adsense-in-feed`
    `advanced-custom-fields` `sg-ai-studio` `akismet` `wpcat2tag-importer`
    `wp-amp-it-up` `ga-google-analytics` `pdf-viewer-block` `jetpack` `zero-bs-crm`
    `newsletter` `redirection` `reveal-ids-for-wp-admin-25` `sg-security`
    `google-site-kit` `sg-cachepress` `kadence-starter-templates` `trinity-audio`
    `wordpress-importer` `wp-typography` `wordpress-popular-posts` `twitter-auto-publish`
  - Inactive but kept (on request): `accelerated-mobile-pages` `breeze` `contact-form-7`
    `notix-web-push-notifications`
- **Committed** (not on wp.org, under `web/app/plugins/`, un-ignored in `.gitignore`):
  - `google-typography` (active, removed from wp.org)
  - `advanced-post-types-order` (premium, inactive — kept on request)
- **Dropped** (inactive themes, unused): `kadence`, `newsair`, `newsup`. Originals remain
  in `public_html.pre-bedrock` on the server if ever needed.

## Migration notes

- **Backup (2026-06-21):** server `~/backups/daily/20260621/db.sql` (156M, verified) +
  downloaded to `~/backups/periodismofederal/20260621/db.sql`. Files: SiteGround daily
  auto-backups + the local rsync mirror under `local/public_html` + the renamed
  `public_html.pre-bedrock` (the in-place cutover only renames the old docroot, never
  deletes it).
- **Cutover = in-place atomic symlink swap.** `public_html` → `public_html.pre-bedrock`,
  then `ln -s bedrock/web public_html`, then DB path rewrite `/wp-content/` → `/app/`
  (run AFTER the swap). HTTPS already in the DB, so no protocol rewrite needed.
- **PHP 8.2 → ≥8.3:** the live docroot ran PHP 8.2; Bedrock's `platform_check.php`
  fatals (500) under < 8.3. Bump Site Tools → Devs → PHP Manager to ≥ 8.3 **before**
  cutover (the `php-manage` CLI can't change the site's active PHP version).
- **Double cache:** Breeze (inactive) + SG Optimizer (`sg-cachepress`, active). Keep
  `sg-cachepress` — it provides `wp sg purge`, used by the post-deploy step.
- **SG Optimizer CSS combine/minify disabled (Bedrock gotcha).** After cutover the site
  rendered unstyled: SG Optimizer's *Combine CSS* + *Minify CSS* produced an empty
  combined file (`siteground-optimizer-combined-css-d41d8cd98f00b204e9800998ecf8427e.css`,
  hash = MD5 of empty). SG Optimizer maps each stylesheet URL to a disk path assuming the
  classic `/wp-content/` layout under `ABSPATH`, but Bedrock serves content at `/app/`
  (a sibling of `web/wp`), so it reads nothing and combines to empty. Fix applied:
  `wp option update siteground_optimizer_combine_css 0` and
  `siteground_optimizer_optimize_css 0` (these are WP options, set on the server, not in
  git), then purge. The theme/plugin stylesheets now load directly from `/app/...` (all
  200). **JS combine was left ON** (it resolved correctly, non-empty). Re-enabling CSS
  combine later is possible but must be re-tested against the `/app/` paths.

## Rollback (one-liner intent)

If the cutover misbehaves, on the server (`PHP_BIN=php83 wp ... --path=public_html.pre-bedrock`):

```
wp search-replace '/app/' '/wp-content/' --skip-columns=guid   # reverse the path rewrite
rm public_html && mv public_html.pre-bedrock public_html        # restore old docroot
wp cache flush && wp sg purge
```

Worst case, restore `~/backups/daily/20260621/db.sql`.

## GitHub secrets status

| Secret            | Value                                              | Status            |
|-------------------|----------------------------------------------------|-------------------|
| `SSH_HOST`        | `ssh.periodismofederal.com`                        | set by skill      |
| `SSH_PORT`        | `18765`                                            | set by skill      |
| `SSH_USER`        | `u569-dykz25wog4r8`                                | set by skill      |
| `DEPLOY_PATH`     | `www/periodismofederal.com/bedrock`                | set by skill      |
| `SSH_PRIVATE_KEY` | account deploy key (`~/.config/siteground-wp/deploy_ed25519`) | set by skill |

**Manual one-time step:** add the deploy **public** key
(`~/.config/siteground-wp/deploy_ed25519.pub`) in Site Tools → Devs → SSH Keys Manager
so GitHub Actions deploys (`git push`) can rsync. The first cutover was done manually
over SSH with the personal login key, so this does not block the migration.

---

# Canonical conventions (source of truth)

The authoritative version lives at
`~/VisualProjects/infra.pantech/templates/siteground-wp/CONVENTIONS.md`. A copy follows
for self-containment; if they diverge, the canonical file wins.

# Bedrock-on-SiteGround autodeploy conventions

Source of truth for the `init-siteground-wp` skill. It describes how we build
**version-controlled WordPress sites** (Roots **Bedrock**) that **auto-deploy to
SiteGround** from GitHub on every push.

Context: the SiteGround plan is marketed as "WordPress hosting", but the
underlying shared hosting (Nginx + Apache + PHP + MySQL + SSH) happily serves any
PHP app placed under a subdomain document root. We exploit that to host fully
code-managed WordPress sites alongside the panel-managed one.

> If this doc and the skill ever disagree, **this doc wins** — fix the skill and
> tell the user about the drift.

---

## 1. Philosophy — what lives in Git vs what does not

A WordPress site is part code, part state. Only the **code** goes in the repo;
the **state** stays on the server.

| In Git (the repo)                                  | NOT in Git (server-only)                     |
|----------------------------------------------------|----------------------------------------------|
| `composer.json` / `composer.lock` (WP core + plugins as deps) | `vendor/` (rebuilt by `composer install`) |
| `config/` (Bedrock config + environments)          | `web/wp/` (WP core — installed by Composer)  |
| Custom theme(s) under `web/app/themes/<theme>`     | `web/app/uploads/` (media library)           |
| Custom plugins / mu-plugins you wrote              | `.env` (DB creds, salts, WP_HOME)            |
| `.github/workflows/deploy.yml`, `.deployignore`    | The MySQL **database** (content)             |

Rules of thumb:

- **Never commit secrets.** DB credentials, salts and `WP_HOME` live only in the
  server's `.env`. The repo ships `.env.example` with empty placeholders.
- **Never commit `web/app/uploads/`.** Media is server state; the deploy must
  *protect* it, never overwrite or delete it (see §6 — `--delete` footgun).
- **Plugins are dependencies, not vendored code.** Public plugins come from
  [WordPress Packagist](https://wpackagist.org): `composer require
  wpackagist-plugin/<slug>`. They are installed in CI, not committed. Plugins you
  *write yourself* live in `web/app/plugins/<plugin>` and **are** committed.
- **WP core is a dependency too** (`roots/wordpress`). Never edit core; bump the
  version in `composer.json`.

Bedrock's own generated `.gitignore` already encodes most of this. Keep it as-is;
only **append** our entries — don't rewrite Bedrock's internals by hand. The
canonical reference for Bedrock structure is <https://roots.io/bedrock/docs/>.

---

## 2. Project structure (canonical Bedrock)

Generated by `composer create-project roots/bedrock <dir>`:

```
├── composer.json            # WP core + plugins (wpackagist) as deps
├── composer.lock
├── .env.example             # template — real .env lives ONLY on the server
├── config/
│   ├── application.php       # replaces wp-config.php; reads .env
│   └── environments/
│       ├── development.php
│       ├── staging.php
│       └── production.php
├── web/                     # <-- DOCUMENT ROOT points here, not the repo root
│   ├── app/                 # = wp-content
│   │   ├── themes/          # our custom theme(s) live here (committed)
│   │   ├── plugins/         # composer-managed (ignored) + custom (committed)
│   │   ├── mu-plugins/      # bedrock-autoloader + our mu-plugins
│   │   └── uploads/         # media — server-only, never committed
│   ├── wp/                  # WP core — Composer-managed, never committed
│   ├── index.php
│   └── wp-config.php        # requires ../config/application.php
└── vendor/                  # Composer deps — never committed
```

We add on top (the skill generates these):

```
├── .github/workflows/deploy.yml   # GitHub Actions: build + rsync to SiteGround
├── .deployignore                  # rsync exclude list (protects uploads + .env)
└── docs/SITEGROUND_DEPLOY.md      # per-project runbook (copy of this doc + values)
```

---

## 3. Environments & config

Bedrock selects the environment from `WP_ENV` in `.env`:

- **development** — local machine. `.env` has local DB creds, `WP_HOME=http://localhost:8080`, `WP_ENV=development`. Debug on.
- **production** — the SiteGround server. `.env` has SiteGround MySQL creds, `WP_HOME=https://<your-domain>`, `WP_ENV=production`. Debug off.

`WP_SITEURL` is always `${WP_HOME}/wp` (Bedrock puts core in `/wp`).

The production `.env` is **created once, by hand, on the server** (see §7) and is
**never deployed** — the deploy explicitly excludes it so each push can't clobber
production credentials.

---

## 4. Local development

1. `composer install` (installs core + plugins + vendor locally).
2. `cp .env.example .env` and fill local values (`WP_ENV=development`, a local
   MySQL/MariaDB, `WP_HOME=http://localhost:8080`). Generate salts at
   <https://roots.io/salts.html> or `wp dotenv salts regenerate`.
3. Serve `web/` with any local stack. Recommended, in order of simplicity:
   - `php -S localhost:8080 -t web` (quick, no DB tooling) — needs a local MySQL.
   - A Docker Compose with `wordpress`/`mariadb` images pointed at `web/` (the
     skill can scaffold this if asked).
4. `wp` commands target the Bedrock core path: `wp <cmd> --path=web/wp`.

---

## 5. SiteGround server setup (one-time, per site)

All in **Site Tools** unless noted. Requires a multi-site plan (GrowBig/GoGeek) for
extra sites + SSH.

> **No customer API.** SiteGround does **not** expose a public provisioning REST API
> for normal accounts, and browser automation of Site Tools proved impractical. So a
> few panel steps are manual one-time per site (add the deploy SSH key; create the
> MySQL DB + user and **associate them**; set PHP ≥ 8.3); everything else — docroot
> symlink, server `.env`, `wp core install` — the skill does over **SSH** (see §6).

1. **Subdomain** — Site Tools → *Domain → Subdomains* (e.g. `demo.midominio.com`).
   This creates `~/www/<subdomain>/public_html`.
2. **Document Root → the Bedrock `web/` via a symlink.** SiteGround has no UI to
   repoint a domain's document root to a subfolder, so the standard Bedrock fix is
   a symlink. Deploy Bedrock to `~/www/<domain>/bedrock`, then over SSH:
   ```
   cd ~/www/<domain>
   rm -rf public_html            # back it up first if it has content
   ln -s bedrock/web public_html # docroot now resolves to Bedrock's web/
   ```
   Bedrock will not work if the served root is the project root — `web/` must be it.
3. **MySQL database** — Site Tools → *Site → MySQL → Databases* (create DB) and
   *Users* (create user, grant on the DB). Note name/user/password/host
   (`localhost`). These go in the server `.env`, never in Git.
4. **PHP version** — Site Tools → *Devs → PHP Manager*: set **8.3+**. It must
   satisfy `composer.json` `require.php` (current Bedrock needs `>=8.3`). The deploy
   workflow **auto-detects** the version from `composer.json`; keep PHP Manager ≥ it.
5. **SSH access** — Site Tools → *Devs → SSH Keys Manager*. Note the host
   (`ssh.<domain>`), username (`u####-...`) and **port `18765`** (SiteGround's SSH
   port, not 22), and add the deploy **public** key here. The **port** and the
   **deploy key** are account-wide, but because SiteGround isolates each site, the
   **host and username can differ per site** and the **public key is added per site**
   (Site Tools is per-site). The skill stores port + key (and per-site host/user
   defaults) in `~/.config/siteground-wp/account.env` (see §6 — account profile).

---

## 6. Deploy pipeline (GitHub Actions → SSH + rsync)

**Why not SiteGround's native Git (Site Tools → Devs → Git)?** It only pulls into a
server-side repo — no `composer install`, no asset build. With Bedrock that means
`vendor/` and `web/wp` (the WP core) would never be assembled, so the site can't
boot. We therefore build in CI and ship the result over SSH. (Native Git is fine
for *plain* static/PHP sites — just not for a Composer-based stack.)

Flow, on push to `main` (and `workflow_dispatch`):

1. Checkout.
2. Setup PHP (version **auto-detected** from `composer.json` `require.php` /
   `config.platform.php`) + Composer v2.
3. `composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader`
   → assembles `vendor/`, `web/wp` (core) and composer-managed plugins.
4. *(optional)* Build theme assets (`npm ci && npm run build`) when the theme has
   a JS/CSS toolchain. Buildless themes skip this entirely.
5. `rsync -avzr --delete --exclude-from=.deployignore ./ <user>@<host>:<DEPLOY_PATH>`
   over SSH (port 18765), using `SSH_PRIVATE_KEY`.
6. Post-deploy over SSH (non-fatal, pinned with `PHP_BIN=php<ver>`): re-activate SG
   Optimizer (`wp plugin activate sg-cachepress`), `wp cache flush`, then `wp sg purge`
   to purge the SiteGround Dynamic Cache. The purge only works because SG Optimizer is
   a Composer dep (§8) — without it the stale dynamic cache hides the deploy (§9).

### The `--delete` footgun (read this)

`rsync --delete` removes, on the server, anything not present in the source. The
media library (`web/app/uploads/`) and the server `.env` exist **only** on the
server. They MUST be in `.deployignore`:

- rsync **protects excluded paths from deletion** as long as you do **NOT** pass
  `--delete-excluded`. So `--delete` + `--exclude=web/app/uploads/` keeps uploads
  safe. **Never add `--delete-excluded`.**
- If `.deployignore` is wrong, the first deploy wipes every customer's media. Treat
  this file as production-critical.
- The `public_html` symlink (§5.2) is a **sibling** of `bedrock/`, not under the
  rsync target, so `--delete` never touches it.

Minimum `.deployignore`:

```
.git/
.github/
node_modules/
.env
.env.example
web/app/uploads/
docs/
README.md
.deployignore
.gitignore
.editorconfig
tests/
```

(Everything not excluded — `vendor/`, `web/wp/`, `config/`, themes, plugins — is
shipped. `web/wp` and `vendor` are gitignored but **present in the CI workspace**
after `composer install`, so they do get rsynced.)

### Required GitHub secrets

| Secret            | Value                                                        |
|-------------------|-------------------------------------------------------------|
| `SSH_HOST`        | SiteGround SSH host (IP or hostname)                         |
| `SSH_PORT`        | `18765`                                                      |
| `SSH_USER`        | SiteGround SSH username                                      |
| `SSH_PRIVATE_KEY` | private half of the deploy keypair (public half on the server)|
| `DEPLOY_PATH`     | Bedrock project root on the server (e.g. `www/<domain>/bedrock`)|

The deploy keypair is **dedicated to deploys** (never your personal/login key).
**One keypair for the whole account, reused by all its sites**: generated once
(`ssh-keygen -t ed25519`), stored under `~/.config/siteground-wp/`, its private half
pushed into each repo's `SSH_PRIVATE_KEY` secret. The same **public** half is added
to **each site's** SSH Keys Manager (Site Tools is per-site, so once per site) — it's
always the same key material, never regenerated per site.

### Account profile — capture SSH once, reuse everywhere

The **SSH port (18765)** and the **deploy key** are constant for the whole account;
the **SSH host, username and deploy path are per site** (SiteGround isolates sites)
and become each repo's GitHub secrets. The skill stores the universal bits — plus
per-site host/user *defaults* — once in `~/.config/siteground-wp/account.env` (mode
`600`, **never** in a repo) and auto-loads them, so a new site asks for little more
than the domain (plus a confirm of host/user). A site's deploy path is derived as
`${SITEGROUND_SITES_ROOT}/<domain>/bedrock` (docroot symlink → `.../web`). The
profile schema ships as `account.env.template` next to the skill.

### Server setup over SSH (the deterministic part)

Because SiteGround has no provisioning API (and Site Tools browser automation proved
impractical), the split is: a few **manual one-time panel steps** — add the deploy
**public key** (§5.5), create the **MySQL DB + user and associate them** (§5.3), set
**PHP ≥ 8.3** (§5.4) — then the skill does the rest **over SSH**:

- **Docroot symlink** (§5.2): `rm -rf public_html && ln -s bedrock/web public_html`.
- **Server `.env`**: built locally (DB creds + `WP_HOME` + `WP_SITEURL` + `WP_ENV` +
  8 salts), streamed over SSH (`cat local | ssh 'cat > .env'` — avoids quoting issues
  with special chars in the DB password), `chmod 600`. Never committed.
- **`wp core install`**: SiteGround's `wp` is a **wrapper script** whose default PHP
  is `php-recommended` (currently 8.6); force a version with `PHP_BIN=php83 wp ...`
  (never `php83 /usr/local/bin/wp`). Use `--path=web/wp`.
- **Pretty permalinks + SG Optimizer** (right after install): `wp rewrite structure
  "/%postname%/"` (a fresh install defaults to plain `?p=` permalinks, which 404 every
  page) and `wp plugin activate sg-cachepress`. The committed `web/.htaccess` (§9)
  must be present on the server for Apache to route the pretty URLs.
- **Verify**: `wp core is-installed`, `wp option get home`,
  `curl -skI -H "Host: <domain>" http://127.0.0.1/` → `200`, **and an inner page**
  (`.../sample-page/` → `200`, not `404`) to catch the permalink/`.htaccess` trap.

For an existing site, import the DB + `web/app/uploads/` instead of installing.

---

## 7. First deploy & WordPress install

After secrets + DB + (subdomain) are set:

1. Push to `main` → Actions runs → files land in `DEPLOY_PATH` (uploads/.env not
   touched).
2. SSH in (port 18765) and create the `public_html`→`bedrock/web` symlink (§5.2) if
   not done yet, then create the production `.env` **once** (it's excluded from
   deploys): `cp .env.example .env`, fill DB creds, `WP_ENV=production`,
   `WP_HOME=https://<domain>`, and salts (`wp dotenv salts regenerate` or paste
   from <https://roots.io/salts.html>).
3. Initialize WordPress (new site):
   ```
   wp core install --path=web/wp --url=https://<domain> \
     --title="<Site>" --admin_user=<admin> --admin_email=<email> --prompt=admin_password
   ```
   For an existing site, import the DB + `web/app/uploads/` instead.
4. Verify the document root resolves to `web/` and the site loads over HTTPS.

Subsequent deploys are just `git push` — Actions rebuilds and rsyncs the delta.

---

## 8. Conventions for themes & plugins

- **Custom theme** lives at `web/app/themes/<theme-slug>` and is committed. Prefer
  a **block theme** (FSE): `style.css` header, `theme.json`, `templates/`,
  `parts/`, `functions.php`. Buildless by default (plain CSS/JS) so the deploy
  needs no Node step; add a `package.json` + `npm run build` only when you want a
  bundler/Tailwind, and flip the build step on in the workflow.
- **Public plugins** → `composer require wpackagist-plugin/<slug>` (tracked in
  `composer.json`, installed in CI, **not** committed). Use the namespace the
  scaffolded Bedrock's repo exposes — current Bedrock ships `repo.wp-packages.org`,
  so it's `wp-plugin/<slug>` / `wp-theme/<slug>`.
- **SG Optimizer is a standard dep on every SiteGround site** →
  `composer require wp-plugin/sg-cachepress`. It provides `wp sg purge`, the only way
  the deploy can clear SiteGround's Dynamic Cache (§9). Shipping it via Composer (not
  a server-side install) keeps it version-controlled and safe from `rsync --delete`.
  Activate it during the server install and re-activate in the post-deploy step.
- **`web/.htaccess` is committed** (un-ignored from Bedrock's `.gitignore`) — Apache
  needs it for pretty permalinks and WP-CLI can't regenerate it under Bedrock (§9).
- **Premium/paid plugins** that aren't on Packagist → either a private Composer
  repo, or commit the plugin under `web/app/plugins/<slug>` and remove it from the
  `.deployignore`/gitignore exclusions deliberately. Document the choice.
- **Custom plugins / mu-plugins you author** → committed under
  `web/app/plugins` / `web/app/mu-plugins`.

---

## 9. SiteGround gotchas

- **Document root via symlink** (§5.2). SiteGround can't repoint a docroot in the
  panel, so `public_html` must be a symlink to `bedrock/web`. Most "Bedrock shows
  the file list / 500s" issues are a missing/wrong symlink.
- **SSH port is `18765`**, not 22. rsync/ssh must pass it.
- **Pretty permalinks need a committed `web/.htaccess`** — SiteGround serves PHP via
  Apache (behind nginx), which honours `.htaccess`. Two traps compound here:
  (1) Bedrock's default `.gitignore` ignores `/web/.htaccess`, so it never ships; and
  (2) `wp rewrite flush --hard` **cannot regenerate** it under Bedrock (it warns
  "Regenerating a .htaccess file requires special configuration" and writes nothing).
  Result: a green deploy where the home page (`/`) loads but **every inner page
  404s**. Fix: un-ignore and **commit** `web/.htaccess` with the standard WP rules,
  and set the structure in the DB with `wp rewrite structure "/%postname%/"` (a fresh
  install defaults to *plain* `?p=` permalinks). The home page works regardless
  because it's served at `/`, which masks the problem.
- **SiteGround Dynamic Cache serves stale pages after deploy** — the proxy cache
  (`x-proxy-cache-info: DT:1` = HIT) keeps serving the old HTML, so a successful
  deploy can still show the previous theme/content. `wp sg purge` only works if **SG
  Optimizer (`sg-cachepress`) is installed and active** — otherwise it's an unknown
  wp-cli command (silently swallowed by the workflow's `|| true`). Ship SG Optimizer
  as a Composer dep (§8) and activate it; the post-deploy step then purges on every
  push. To confirm it's purely cache, hit the URL with a cache-buster (`?cb=...`): if
  that renders correctly, only the cache is stale. Manual fallback: Site Tools →
  *Speed → Caching → Dynamic Cache → Flush*. (There is no end-user CLI/HTTP purge
  without the plugin: `PURGE` returns 403 and memcached isn't reachable from the
  shared shell.)
- **`wp` CLI needs `--path=web/wp`** because core lives in `/wp`.
- **mu-plugins autoloader** — Bedrock ships `bedrock-autoloader.php`; keep it.
- **PHP platform** — keep `composer.json` `config.platform.php` aligned with the
  PHP Manager version, or CI may resolve deps the server can't run.
- **`wp` is a wrapper, not a phar** — `/usr/local/bin/wp` is a shell script that
  execs wp-cli under `$PHP_BIN` (default `php-recommended`, currently 8.6). Force a
  version with `PHP_BIN=php83 wp ...`; running `php83 /usr/local/bin/wp` just makes
  PHP print the wrapper and do nothing.
- **MySQL: associate user ↔ database** — creating a DB user isn't enough; link it to
  the database (all privileges) in Site Tools or WP fails with `ERROR 1044` (login
  OK, no DB access). DB host is `localhost` (socket `/tmp/mysql.sock`).
- **SSH host + user are per site** (e.g. `gtxm1014.siteground.biz` / `u3185-...`),
  not per account — they go to each repo's secrets; only the deploy key + port
  (18765) are shared across the account.

---

## 10. Hard rules (non-negotiable)

- **All code comments and `.md` content in English** (user's global rule). Site
  copy can be in any language.
- **Never `git commit`** (user's global rule). The skill stages files and stops.
- **No secrets in the repo** — `.env`, DB creds, salts and keys are server-only or
  GitHub secrets.
- **Never deploy with `--delete-excluded`**, and always keep `web/app/uploads/`
  and `.env` in `.deployignore` (§6).
- **Don't hand-edit Bedrock's generated files** beyond what's needed; layer our
  additions on top and defer internals to the Bedrock docs.
