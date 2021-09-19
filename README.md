# W4OS OpenSimulator Interface
* Contributors: gudulelapointe,magicoli69
* Donate link: https://paypal.me/magicoli
* Tags: opensimulator, second life, web interface
* Requires at least: 5.0
* Requires PHP: 5.6
* Tested up to: 5.6
* Requires PHP: master
* License: AGPLv3
* License URI: https://www.gnu.org/licenses/agpl-3.0.txt

WordPress interface for OpenSimulator

## Description

The first ready to use WordPress interface for OpenSimulator. Provides user
registration and basic grid info. See current Features below, and Roadmap
section for upcoming functionalties.

### Features

- **Grid info**: `[gridinfo]` shortcode and admin dashboard widgets
- **Grid status**: `[gridstatus]` shortcode and admin dashboard widgets
- **Avatar creation**:
  - Opensimulator section in standard wp profile page
  - `[w4os_profile]` shortcode can be inserted in any custom page
  - Avatar tab in account dashboard on WooCommerce websites
- Choose avatar look from default models
- Avatar and website passwords are synchronized
- **Reserved names**: avatar whose first name or last name is "Default",
  "Test", "Admin" or the pattern used for appearance models are disallowed for
  public (such avatars must be created by admins from Robust console)
- **OpenSimulator settings page**:
  - grid name, login uri and database connection settings
  - naming scheme of default models
  - exclude models from grid stats

## Installation

1. Download [the latest stable release](https://magiiic.com/updates/?action=download&slug=w4os)
    and unzip it in your wp-content/plugins
2. Activate

## Roadmap

### Short term (version 1.0, WordPress repository release)

- Login page (with grid FirstName, LastName and password).
  Optional redirect of standard login page
- Auth with avatar credential (as fallback to wp auth).
  Create new WordPress user if auth by avatar
- Option to use WordPress name as avatar name (in this case, lock WordPress
  name changes once an avatar is set)
- Use avatar profile pic
- Check if avatar password is properly updated after a password reset request
- sidebar grid info and grid status widgets

### Middle term

- Public avatar profile
- Admin Start / Stop regions
- Admin Create region
- Admin Use sim/grid configuration file to fetch settings if on the same host
- get grid info from http://login.uri:8002/get_grid_info
- Helpers (assets, search, currency, map...)
- Use cache for grid info
- Integrate web asset server

### Long term

- Admin create users
- Admin create models (from current appearance)
- Choice between Robust console or database connection
- User's own regions control (create, start, stop, backup)
- WooCommerce Subscriptions integration for user-owned Regions or other pay-for services

## Frequently Asked Questions

### Current status

Code is a mess. Don't blame me. I want to do it the right way but I'm learning
as I progress in the project. I'll try to put things on the right places while
getting more familiar with it. Feel free to give advices. Yeah, it's not a
question, but you might wonder.

## Changelog

### 1.2
* fix issue #1 Database check fails if mysql is case insensitive

### 1.1.4
* fix bug in d6fe07c62bb6be189a820c416bc8402f7f5de56a

### 1.1.3
* update authors
* fix "Yes" and "No" translations

### 1.1.2
* fix typo in banners and icons urls, can't believe I didn't see this before...

### 1.1.1
* use transparent icons

### 1.1
* added changelog, banners and icons to view details
* changed update server to [frogerme's WP Plugin Update Server](https://github.com/froger-me/wp-plugin-update-server)
* fixed conflict with other extensions settings pages
* fixed w4os_updater var name
* fixed view details not shown

### 1.0
* use plugin repository for stable releases updates, GitHub Updater no longer
  required

### Previous
* See full history in changelog.txt
