# W4OS
Contributors: magicoli69
Tags: opensimulator, second life, web interface
Donate link: https://paypal.me/magicoli
Requires at least: 5.0
Requires PHP: 5.6
Tested up to: 5.4.2
Stable tag: master
License: AGPLv3
License URI: https://www.gnu.org/licenses/agpl-3.0.txt

WordPress interface for OpenSimulator

## Description

The first ready to use WordPress interface for OpenSimulator. Provides user
registration and basic grid info. See current Features below, and Roadmap
section for upcoming functionalties.

## Features

- **Grid info**: `[gridinfo]` shortcode and admin dashboard widgets
- **Grid status**: `[gridstatus]` shortcode and admin dashboard widgets
- **Avatar creation**:
  - `[w4os_profile]` shortcode can be inserted in any page
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

### Roadmap

#### short term (for 1.0 release)

- option to use WordPress name as avatar name (in this case, lock WordPress
  name changes once an avatar is set)
- Create avatar from standard wp-admin/profile.php page
- Check if avatar password is properly updated after a password reset request
- Auth with avatar credential (as fallback to wp auth)
  * Create new WordPress user if auth by avatar
- integrate web asset server
- sidebar grid info and grid status widgets

#### middle term

- User profile
- Start / Stop regions
- Create region from admin
- Helpers (assets, search, currency, map...)
- Use sim/grid configuration file to fetch settings if on the same host
- *Use cache for grid info*

#### long term

- Choice between Robust console or database connection
- User own regions control (Create / Start / Stop / Backup)
- WooCommerce Subscriptions integration for user-owned Regions or other pay-for services

## Frequently Asked Questions

### Current status

Code is a mess. Don't blame me. I want to do it the right way but I'm learning
as I progress in the project. I'll try to put things on the right places while
getting more familiar with it. Feel free to give advices. Yeah, it's not a
question, but you might wonder.

## Screenshots
1. Avatar registration
