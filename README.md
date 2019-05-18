=== OpenSim Wordpress plugin ===
Contributors: magicoli69
Donate link: http://speculoos.world/
Tags: comments, spam
Requires at least: 5.0
Tested up to: 5.2
Stable tag: 5.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

OpenSim Wordpress plugin allows to connect a WordPress website to OpenSimulator grid or standalon server

== Description ==

OpenSim Wordpress plugin is an OpenSimulator web interface for WordPress.

The goal is to allow user registration, grid basic info and monitoring and, in
a perfect world, regions and simulators administration.

= Current features =

* OpenSim settings: grid name, login uri and database connection settings
* Grid info, as a shortcode and in admin pages
* Grid status, as a shortcode and in admin pages

== Roadmap ==

= short term =

* *Use cache for grid info*
* *Clean up code to properly use classes*
* Use sim configuration file to fetch settings if accessible from the web server
* Users grid registration

= middle term =

* Integrate various helpers (profile, search, currency, map...)
* Start / Stop regions
* Create region from admin
* option to use console connection instead of database

= Long term =
* Start / Stop / Backup own regions from user profile
* Create own regions from user profile
* Subscriptions

== Installation ==

1. Upload to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Visit OpenSim admin page or use shortcodes

== Frequently Asked Questions ==

== Screenshots ==

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the /assets directory or the directory that contains the stable readme.txt (tags or trunk). Screenshots in the /assets
directory take precedence. For example, `/assets/screenshot-1.png` would win over `/tags/4.3/screenshot-1.png`
(or jpg, jpeg, gif).
2. This is the second screen shot

== Changelog ==

= 0.1.0 =
* settings page
* grid info shortcode

= 0.2.0 =
* grit status shortcode
* admin grid status page

== Upgrade Notice ==

= 1.0 =
Nothing special


`<?php code(); // goes in backticks ?>`
