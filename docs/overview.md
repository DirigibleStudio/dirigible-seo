# Dirigible SEO

Dirigible SEO writes the search and social tags for your pages: title, description, Open Graph image, canonical URL, and optional noindex. It also lets you replace a page’s JSON-LD and manage an `llms.txt` file for AI crawlers.

Use the sections below for the Customizer separator, the **SEO** sidebar on posts and pages, category and tag archives, author archives, and the Tools screens.

## Getting started

- Install and activate **Dirigible SEO** with the Dirigible theme.
- Open any public post, page, or custom post type and find the **SEO** box in the sidebar.
- Set a title and description (or keep the Title / Site pills). The search-engine preview updates as you type.
- Change the character between the page name and the site name under **Appearance → Customize → SEO → Options**.
- If **Yoast SEO** is also active, Dirigible SEO still lets you edit fields, but it will not print live tags until Yoast is deactivated. Use **Dirigible → Tools** to migrate Yoast titles and descriptions first.

## What gets printed

On the front end, Dirigible SEO adds:

- The document title in the browser tab
- `og:title`, `og:description`, `og:image`, `og:url`, `og:site_name`, and `og:type`
- A meta description
- A canonical URL (WordPress’s built-in canonical is removed so this one is the only one)
- A `noindex` robots tag when you mark a page, term, or author archive private

The share image is not a field you pick. It uses the featured image, or the first background or Image block on the page if there is no featured image.

## Where to find settings

1. In the WordPress admin, go to **Appearance → Customize**
2. Open the **SEO** panel
3. Select **Options**
