# WordPress interface for OpenSimulator
Contributors: magicoli69
Donate link: https://paypal.me/magicoli
Tags: opensimulator, second life, web interface
Requires at least: 5.0
Requires PHP: 5.6
Tested up to: 5.4.2
Stable tag: master
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

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

### Dependencies

The project relies on an an external web asset server to display images from
simulator assets (profile pictures, model avatars...). In an undefined future
this will be handled by the plugin. In the meantime, Anthony Le Mansec's
opensimWebAssets is very efficient and easy to install:

  - dowload from github https://github.com/TechplexEngineer/opensimWebAssets
  - copy "src" folder inside your website and name it "assets". It can coexist
    with your WordPress installation, WordPress will ignore it.
  - edit assets/inc/config.php to suit your needs (essentially, change the value
    of ASSET_SERVER to http://your.login.uri:8002/assets/)
  - in WordPress OpenSim settings, change web asset server to
    http://your.website/assets/asset.php?id=

### shortcodes

* `[gridinfo]` display grid name and login URI
* `[gridstatus]` display number of users (current, active, local and HG) and regions

Both accept a title parameter to overwrite the defaults "Grid info"
and "Grid status". If set to "" the title is not displayed.

Example:
[gridinfo title="Who's there"]
[gridstatus title=""]

* `[w4os_profile]` show in-world profile if user has an avatar, or avatar
  creation form otherwise


## Frequently Asked Questions

## Screenshots

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the /assets directory or the directory that contains the stable readme.txt (tags or trunk). Screenshots in the /assets
directory take precedence. For example, `/assets/screenshot-1.png` would win over `/tags/4.3/screenshot-1.png`
(or jpg, jpeg, gif).
2. This is the second screen shot
