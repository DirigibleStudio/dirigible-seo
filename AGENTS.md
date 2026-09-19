# AGENTS.md — Dirigible SEO

Guidance for AI agents working in this WordPress SEO plugin.

## Theme settings

The Title Separator lives in Studio’s grouped options (`ds_settings_*`), not `theme_mods_*`. After Studio 5.30, `get_theme_mod()` alone is empty on migrated sites.

```php
function_exists('ds_get_setting')
  ? ds_get_setting('ds_seo_separator', '-')
  : get_theme_mod('ds_seo_separator', '-');
```

Keep that ternary while mixed parent versions exist. Do not read Dirigible keys from theme mods alone.

## Block documentation (Settings & API)

Builder-facing docs are published by **dirigible-support**. This plugin has no blocks. Prose and Customizer glosses live here:

| File | Role |
|------|------|
| `docs/overview.md` | Plugin landing copy |
| `src/customizer/{slug}/docs/index.md` | Usage pages (Options, Page SEO, taxonomies, authors, tools) |
| `src/customizer/options/docs/glosses.json` | Title Separator gloss |

**Audience, tone, and what counts as useful copy:** [dirigible-support/AGENTS.md](../dirigible-support/AGENTS.md).

Write for website builders. Trace `src/DirigibleSEO.php`, `src/seo.json`, and `src/ds-seo.js` before claiming what a control does. After editing glosses, from dirigible-support:

```bash
npm run docs:refresh-sigs -- --plugin dirigible-seo --block options
npm run docs:build -- --only dirigible-seo
```
