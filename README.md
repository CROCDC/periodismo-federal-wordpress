# Periodismo Federal — periodismofederal.com

Version-controlled WordPress site for **periodismofederal.com**, managed with
[Roots Bedrock](https://roots.io/bedrock/) (Composer + environment-based config)
and auto-deployed to SiteGround from GitHub on every push to `main`.

## Project layout

Bedrock structure (see the [Bedrock docs](https://roots.io/bedrock/docs/installation/)):

```
config/                 # environment config (application.php, environments/)
web/                    # web root (document root points here)
  app/                  # wp-content equivalent
    themes/             # themes (Composer-managed; gitignored)
    plugins/            # plugins (Composer-managed; gitignored)
    mu-plugins/         # must-use plugins (single-file ones are committed)
    uploads/            # media library (server-only; never committed)
  wp/                   # WordPress core (Composer-installed; gitignored)
composer.json           # core, theme and plugin versions
.github/workflows/      # CI deploy to SiteGround
```

### Committed code (ours)

- `web/app/mu-plugins/pf-adsense-sidebar.php` — must-use plugin that injects a
  single AdSense display unit as the **first** widget of the main sidebar
  (`sidebar-1`). The placement lives in Git (not in an Advanced Ads DB record);
  it emits only the `<ins>` unit (Site Kit injects the loader), bails out on AMP,
  and shows a dev-preview box outside production. Rollback = delete the file.
- `web/app/mu-plugins/remove-twap-credit.php` — must-use plugin that hides the
  "Powered By XYZScripts.com" backlink the `twitter-auto-publish` plugin prints in
  the footer, by short-circuiting its `xyz_credit_link` option. Rollback = delete
  the file.
- `web/app/mu-plugins/pf-open-graph.php` — must-use plugin that supplements
  Jetpack's Open Graph output via the `jetpack_open_graph_tags` filter. Jetpack
  already emits correct per-article OG/Twitter tags; this only adds the site-wide
  and home values it leaves empty (`og:site_name`, `og:image:secure_url`,
  `og:image:type`, and the front-page `og:title`/`og:description`). Per-article
  previews are untouched. Rollback = delete the file.
- `web/app/mu-plugins/pf-front-page-title.php` — must-use plugin that sets the
  home page `<title>` (empty because the Site Title/Tagline are blank in
  Settings → General). Scoped to the front page via `document_title_parts`, so
  article titles and the theme header are untouched. Rollback = delete the file.
- `web/app/plugins/google-typography/` — active typography plugin, **removed from
  wp.org**, so it is version-controlled here instead of pulled via Composer.
- `web/app/plugins/advanced-post-types-order/` — premium post-ordering plugin
  (currently inactive, kept on request); not on Packagist, so committed.
- `web/.htaccess` — pretty-permalink rules (un-ignored from Bedrock's `.gitignore`).

### Composer-managed (built in CI, never committed)

WordPress core, the active theme (`digital-newspaper`) and the third-party plugins
are pinned in `composer.json` and installed during the CI build — they are
intentionally gitignored, not stored in the repo.

## Local development

```bash
cp .env.example .env      # then fill in DB + salts (https://roots.io/salts.html)
composer install          # installs core, theme and plugins into web/
```

`.env` is never committed; each environment (local / production) has its own.

## Deploy

Push to `main` → GitHub Actions builds with Composer (`--no-dev`) and rsyncs the
built tree to the server, then purges the cache. Media (`web/app/uploads/`) and
the server `.env` are excluded from the sync and never touched (see
`.deployignore`).

All deploy credentials live **outside the repo**: SSH/host values are GitHub
Actions **secrets**, and the production database credentials and salts live only
in the server's `.env`. Nothing sensitive is committed.

> Migration note: this site was moved from a panel-managed WordPress install to
> Bedrock; the existing database and media library were preserved (WordPress was
> not reinstalled). Bedrock serves media from `/app/uploads/` instead of
> `/wp-content/uploads/`, so that path was rewritten in the database at cutover.

## Credits

Built on [Bedrock](https://roots.io/bedrock/) by Roots. Licensed under the MIT
License (see [LICENSE.md](LICENSE.md)).
