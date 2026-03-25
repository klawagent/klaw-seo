=== Klaw SEO ===
Contributors: klawagency
Tags: seo, meta tags, sitemap, schema, redirects
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lightweight, agency-grade WordPress SEO plugin. Meta titles, descriptions, Open Graph, sitemaps, schema markup, redirects, and more.

== Description ==

Klaw SEO is a comprehensive yet lightweight SEO plugin built for agencies and developers who want full control without bloat. It covers all the essentials of on-page SEO, structured data, and technical optimization in a single, well-organized package.

**Core Features:**

* **Meta Titles & Descriptions** — Per-post SEO titles and descriptions with configurable templates and token support. Live character counters and Google search preview.
* **Open Graph & Twitter Cards** — Full social media meta tag support with per-post overrides and a default fallback image.
* **XML Sitemaps** — Auto-generated sitemap index and per-post-type sitemaps. Noindexed posts are excluded. Pagination at 1000 URLs. Auto-ping Google and Bing on publish.
* **Schema / JSON-LD** — LocalBusiness, Event (with configurable field mapping), BreadcrumbList, and FAQPage structured data output.
* **301/302 Redirects** — Manage URL redirects with hit tracking, CSV import/export, and object-cached lookups for performance.
* **Robots.txt Editor** — Virtual robots.txt management with a reset-to-default option.
* **Alt Text Automation** — Auto-fill alt text on upload from post titles or cleaned filenames. Optional AI-powered alt text via Claude or OpenAI vision APIs.
* **Broken Link Checker** — Scheduled scans with DOMDocument link extraction, batched HTTP checks, dashboard widget, and email notifications.
* **Admin Columns** — SEO Title and Noindex status columns on all post type list tables.
* **Conflict Detection** — Warns if Yoast SEO, Rank Math, or All in One SEO is active to avoid duplicate output.

**Built for Agencies:**

* Single consolidated settings option for clean database usage.
* No upsells, no ads, no tracking.
* WordPress coding standards throughout.
* Prepared SQL statements for security.
* Extensible architecture.

== Installation ==

1. Upload the `klaw-seo` folder to `/wp-content/plugins/`.
2. Activate the plugin through the Plugins menu in WordPress.
3. Navigate to **Klaw SEO** in the admin sidebar to configure settings.
4. Edit any post or page to set per-post SEO titles, descriptions, and more.

== Frequently Asked Questions ==

= Can I use this alongside another SEO plugin? =

We recommend deactivating other SEO plugins to avoid duplicate meta tags and schema output. Klaw SEO will display a warning if it detects Yoast SEO, Rank Math, or All in One SEO.

= How do I configure Event schema? =

Go to **Klaw SEO > Local Business** and scroll to the Event Schema Mapping section. Select the post type that represents events, then enter the meta key names for date, time, venue, and description fields. This works with any theme or plugin that stores event data in post meta.

= Does the sitemap support custom post types? =

Yes. Go to **Klaw SEO > Sitemaps** and check the post types you want included.

= How does the AI alt text feature work? =

When enabled, images uploaded to the media library are sent to either Claude (Anthropic) or OpenAI for a vision-based description. You need to provide your own API key. The feature is optional and can be disabled at any time.

== Changelog ==

= 1.0.0 =
* Initial release.
* Meta titles and descriptions with templates.
* Open Graph and Twitter Card support.
* XML sitemap generation with ping on publish.
* LocalBusiness, Event, Breadcrumb, and FAQ schema.
* URL redirect management with CSV import/export.
* Robots.txt editor.
* Alt text automation with optional AI.
* Broken link checker with scheduled scans.
* Admin columns for SEO title and noindex status.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
