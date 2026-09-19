---
title: Categories and tags
slug: taxonomies
excerpt: Noindex and canonical URL on public category, tag, and custom taxonomy archives.
related:
  - page
  - users
status: published
---

## Overview

Public taxonomies — categories, tags, and custom taxonomies — get two SEO fields on the **Edit** term screen. Use them when an archive should stay out of search results, or when it duplicates another URL.

## Where to find these settings

1. In the WordPress admin, open a taxonomy (for example **Posts → Categories**)
2. Edit an existing term
3. Find **SEO Visibility** and **Canonical URL**

These fields appear only on the edit screen, not on the “Add new” form.

## Fields

- **Stop search engines from indexing this category?** — prints a `noindex` robots tag on that archive
- **Canonical URL** — leave empty to use the term’s own archive URL. Fill it in only when this archive is a duplicate of another page

## Titles and descriptions

The edit screen does not include a title or description field. If a term already has those values (for example after a Yoast migration), Dirigible SEO will use them. Otherwise the title is the term name plus the site name, and the description falls back to the WordPress term description, then the site default.

## Tips

- Noindex thin or overlapping archives (empty tags, duplicate brand taxonomies) instead of deleting them.
- Do not set a canonical unless you are pointing visitors and crawlers at a better URL.
