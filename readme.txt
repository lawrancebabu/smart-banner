=== Smart Banner ===
Contributors: lawrancebabu
Tags: banner, announcement bar, notification bar, countdown, promotion
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.2.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Stackable site-wide announcement banners with scheduling, live countdowns, drag-to-reorder and remembered dismissals.

== Description ==

Smart Banner lets you run several announcement banners at once: at the top of the site, below the header or above the footer. Each banner can have a call-to-action button, its own colors, a live countdown and a start / end schedule.

Features:

* Multiple banners in three positions.
* Scheduling in the site timezone, stored in UTC.
* Live countdown timer.
* Call-to-action button and per-banner colors.
* Sticky top stack.
* Dismissed banners stay hidden for 24 hours, even on cached pages.
* Drag-to-reorder, pause / enable and live preview.
* Automatic cache purge for popular caching plugins and hosts.
* Diagnostics screen.
* Translation ready.

== Installation ==

1. In WordPress admin, go to Plugins > Add New > Upload Plugin.
2. Upload the plugin zip file and activate it.
3. Go to Smart Banner > Banners to create your first banner.

== Frequently Asked Questions ==

= My banner does not show up. =

Open Smart Banner > Diagnostics. It shows whether the banner is paused, outside its schedule or dismissed in your browser, and whether the banner script reaches the front end.

= Which timezone are schedules in? =

The site timezone from Settings > General. Dates are stored in UTC.

= What happens on uninstall? =

The banners table and plugin options are deleted.

== Screenshots ==

1. Banners on the front end.
2. Mobile layout.
3. Banner editor with live preview.
4. Diagnostics screen.

== Changelog ==

= 2.2.0 =
* Fixed: titles, messages and button labels with quotes were saved with backslashes.
* Fixed: opening the Debug screen shifted every banner's dates by the site's UTC offset on each visit.
* Fixed: admin CSS and JS (including an "unsaved changes" prompt) loaded on every admin screen.
* Fixed: the "Sticky" setting had no effect; non-sticky top banners now scroll with the page.
* Fixed: toggle and reorder redirects ran after page output.
* Fixed: dismissed banners reappeared on cached pages.
* Schedules now compare against UTC time from PHP instead of the database server clock.
* Removed debug HTML comments from every front-end page.
* Sticky banners respect the WordPress admin bar.
* Rebuilt the Debug screen as a read-only Diagnostics screen.
* Front-end CSS and JS are enqueued files instead of inline code.
* Position values are whitelisted and dates validated on save.
* Removed unverified host cache hooks; added SiteGround Optimizer purge function.
* Added translations support, uninstall cleanup and WordPress Coding Standards compliance.
* Removed empty placeholder files.

= 2.1 =
* Initial release.

== Upgrade Notice ==

= 2.2.0 =
Fixes backslashes in banner text and date shifts caused by the Debug screen. The plugin folder changed: deactivate and delete the old "WP Smart Banner" first (its banners are kept), then activate this one. Re-save any banner whose text already shows backslashes.
