---
title: Page SEO
slug: page
excerpt: SEO title, meta description, noindex, canonical URL, and custom structured data for each page.
related:
  - options
  - taxonomies
  - tools
status: published
---

## Overview

Posts, pages, and other public content get an **SEO** box in the editor sidebar. Use it to set how each page appears in search results and social shares. The **Search Engine Preview** at the top of the box updates as you type.

## Where to find it

1. Edit a post, page, or any other public item
2. Open the sidebar and find the **SEO** panel

The same box appears on the posts page, the front page, and WooCommerce shop or product pages. Those pages use the SEO fields saved on that WordPress page.

## Fields

- **SEO Title** — use the **Title**, separator, and **Site** pills, or type a custom title. Empty titles fall back to the page name, the Customizer separator, and the site name. The homepage falls back to the site name alone.
- **SEO Description** — the meta description shown in search results and social shares. If you leave it blank, Dirigible SEO uses the excerpt, or the first 320 characters of the content.
- **Private Page (Do Not Index)** — asks search engines to leave this page out of their results
- **Override canonical URL** — turn this on only when this page is a duplicate. Then enter the original URL. Leave it off to use this page’s own permalink.
- **Enable Custom JSON-LD** — replace the theme’s default structured data for this page
- **Custom JSON-LD** — paste your structured data without `<script>` tags. It only takes effect when **Enable Custom JSON-LD** is on and the field is not empty

Pills in the title field:

- **Title** — the WordPress title (or term name on archives)
- The separator pill — the Customizer **Title Separator**
- **Site** — the site name from **Settings → General**

## Share image

There is no image picker. On save, Dirigible SEO stores a share image from:

1. The featured image (large size), or
2. The first block background image, or
3. The first **Image** block

## Tips

- Write the description for people, not for keywords. Check it in the preview.
- Use **Private Page** for thank-you pages, test pages, and anything that should stay out of Google.
- Do not override the canonical unless two public URLs show the same content.
- If Yoast is still active, you can fill these fields, but live tags stay off until Yoast is deactivated.
