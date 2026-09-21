---
title: Tools
slug: tools
excerpt: Migrate Yoast titles and descriptions, and manage the site llms.txt file.
related:
  - page
  - taxonomies
  - options
status: published
---

## Overview

Dirigible SEO adds two tools on **Dirigible → Tools**: a Yoast migration and an **llms.txt** editor.

## Where to find these tools

1. In the WordPress admin, go to **Dirigible → Tools**
2. Use **Migrate Yoast Data** or **LLMs.txt Manager**

You need an Administrator account.

## Migrate Yoast Data

Copies Yoast SEO titles and meta descriptions onto Dirigible SEO fields for posts, pages, and terms. Yoast must be active for the migration to run.

- Overwrites any Dirigible SEO title or description already saved on those items
- Converts common Yoast tokens (`%%title%%`, `%%sitename%%`, `%%sep%%`, `%%term_title%%`) into Dirigible pills
- Does not copy noindex, canonical URLs, or JSON-LD

After you migrate, deactivate Yoast so Dirigible SEO can print live tags.

## LLMs.txt Manager

Creates or updates an `llms.txt` file in the WordPress root so language models can read a short description of the site. See [llmstxt.org](https://llmstxt.org/).

- **Save llms.txt** writes the textarea to the file
- **Reload from File** reads the file back into the editor
- **Delete llms.txt** removes the file

This tool is not compatible with WordPress multisite.

## Tips

- Run the Yoast migration once, then turn Yoast off. Leaving both plugins on leaves Dirigible tags commented out.
- Treat `llms.txt` like a public file — do not paste private notes.
