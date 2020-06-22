# WordPress interface for OpenSimulator
Contributors: magicoli69
Donate link: https://paypal.me/magicoli
Tags: opensimulator, second life, web interface
Requires at least: 5.0
Requires PHP: 5.6
Tested up to: 5.4.2
Stable tag: master
License: AGPLv3 (GNU Affero General Public License)
License URI: https://www.gnu.org/licenses/agpl-3.0.txt

## Description

WordPress interface for OpenSimulator is an WordPress plugin to allow
OpenSimulator administration and user registration.

The goal is to allow user registration, grid basic info and monitoring and, in
a perfect world, regions and simulators administration.

### Current status

Code is a mess. Don't blame me. I want to do it the right way but I'm not used to
WordPress plugins standards yet. I'll try to puth things on the right places
while getting more familiar with it. Feel free to give advices.

### Features

* OpenSim settings: grid name, login uri and database connection settings
* Grid info, as a shortcode and in admin pages
* Grid status, as a shortcode and in admin pages

* Create avatar from WooCommerce 'My account' dashboard
* Create avatar from custom profile page
* Choose new avatar apparence from default models
* Change avatar password (via web user password)
* Sync avatar and website passwords (partially done, not on avatar creation)

### Roadmap

* short term (for 1.0 release)
  * *NEXT MOVE: check and use website password when creating an avatar*
  * option to use WordPress name as avatar name (in this case, lock WordPress
    name changes once an avatar is set)
  * Create avatar from standard wp-admin/profile.php page

  * Check if avatar password is properly updated after a password reset request
  * Auth with avatar credential (as fallback to wp auth)
    * Create new WordPress user if auth by avatar

  * integrate web asset server

* middle term
  * User profile

  * Start / Stop regions
  * Create region from admin

  * Helpers (search, currency, map...)
  * Use sim/grid configuration file to fetch settings if on the same host
  * *Use cache for grid info*

* long term
  * Option to use console connection instead of database
  * Create own regions from user profile
  * Start / Stop / Backup own regions from user profile
  * Subscriptions with WooCommerce integration

## Frequently Asked Questions

## Screenshots

1. Avatar registration
