# OpenSim Wordpress plugin

## Description

OpenSim Wordpress plugin is an WordPress interface for OpenSimulator.

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
* Create avatar from custom profile page (partial, missing notifications)
* Change avatar password (via web user password)
* Sync avatar and website passwords (partially done, not on avatar creation)

### Roadmap

* short term (for 1.0 release)
  * Create avatar from custom profile page (fix notifications)
  * Create avatar from standard wp-admin/profile.php page
  * Verify and use website password when creating avatar

  * Check if avatar password is properly updated after a password reset request
  * Auth with avatar credential (as fallback to wp auth)
    * Create new web user if auth by avatar

  * Instructions to install GitHub Updater plugin

* middle term
  * User profile

  * Start / Stop regions
  * Create region from admin

  * Integrate various helpers (profile, search, currency, map...)
  * Use sim/grid configuration file to fetch settings if on the same host
  * *Use cache for grid info*

* long term
  * Option to use console connection instead of database
  * Create own regions from user profile
  * Start / Stop / Backup own regions from user profile
  * Subscriptions with WooCommerce integration

## Installation

1. Get the plugin
  * From wordpress
    * Download latest release as zip, from https://git.magiiic.com/opensimulator/w4os/releases
    * Go to Admin > Extensions > Add > Upload and select the zip file.
  * From git
    * go to wp-content/plugins folder
    * type
      git clone https://git.magiiic.com/opensimulator/w4os.git
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Visit OpenSim settings page and setup grid info and database connection
4. Visit admin OpenSim status page to see status

### shortcodes

* [gridinfo] displays grid name and login URI
* [gridstatus] displays number of users (current, active, local and HG) and regions

Both accept a title parameter to overwrite the defaults "Grid info"
and "Grid status". If set to "" the title is not displayed.

Example:
    [gridinfo title="Who's there"]
    [gridstatus title=""]

## Frequently Asked Questions

## Screenshots

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the /assets directory or the directory that contains the stable readme.txt (tags or trunk). Screenshots in the /assets
directory take precedence. For example, `/assets/screenshot-1.png` would win over `/tags/4.3/screenshot-1.png`
(or jpg, jpeg, gif).
2. This is the second screen shot
