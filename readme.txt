# OpenSim Wordpress plugin

Contributors: magicoli69
Donate link: https://paypal.me/magicoli
Tags: opensimulator, second life, web interface
Requires at least: 5.0
Tested up to: 5.4.1
Stable tag: master
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

OpenSim Wordpress plugin allows to connect a WordPress website to OpenSimulator grid or standalon server

## Description

OpenSim Wordpress plugin is an OpenSimulator web interface for WordPress.

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


### Roadmap

* short term
  * *Use cache for grid info*
  * add suggestion to install GitHub Updater plugin
  * Use sim/grid configuration file to fetch settings if on the same host
  * Users grid registration

* middle term
  * Integrate various helpers (profile, search, currency, map...)
  * User profile
  * Start / Stop regions
  * Create region from admin
  * Option to use console connection instead of database

* long term
  * Start / Stop / Backup own regions from user profile
  * Create own regions from user profile
  * Subscriptions
  * WooCommerce integration

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