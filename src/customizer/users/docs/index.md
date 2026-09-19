---
title: Author archives
slug: users
excerpt: Hide an author’s archive from search engines.
related:
  - taxonomies
  - page
status: published
---

## Overview

Each user profile has one SEO control: whether search engines may index that author’s archive (`/author/name/`).

## Where to find this setting

1. In the WordPress admin, go to **Users**
2. Edit a user
3. Find **SEO Visibility** → **Author Archive No-Index**

## Fields

- **Stop search engines from indexing this author's archive page?** — prints a `noindex` robots tag on that author archive

## Tips

- Turn this on for staff accounts that should not have a public author index, or when you already list people on a directory page.
- This does not noindex posts the person wrote — only the archive that lists them.
