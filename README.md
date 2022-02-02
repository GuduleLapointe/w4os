# w4os - OpenSimulator Web Interface
* Contributors: gudulelapointe,magicoli69
* Donate link: https://w4os.org/donate/
* Tags: OpenSimulator, Second Life, metaverse, avatar, web interface, grids, standalone, hypergrid, 3D
* Requires at least: 5.3.0
* Requires PHP: 5.6
* Tested up to: 5.9
* Stable tag: 2.2.10
* License: AGPLv3
* License URI: https://www.gnu.org/licenses/agpl-3.0.txt

WordPress interface for OpenSimulator (w4os)

## Description

Ready to use WordPress interface for [OpenSimulator](http://opensimulator.org/). Provide user registration, default avatar model choice, login info, statistics and a web assets server for grids or standalone simulators.

See Features and Roadmap sections for current and upcoming functionalties.

### Features

- **Grid info**: `[gridinfo]` shortcode and admin dashboard widgets
- **Grid status**: `[gridstatus]` shortcode and admin dashboard widgets
- **Avatar creation**:
  - Opensimulator section in standard wp account page
  - `[gridprofile]` shortcode can be inserted in any custom page
  - Avatar tab in account dashboard on WooCommerce websites
- Choose avatar look from default models
- Avatar and website passwords are synchronized
- **Web profiles**: excerpt of the avatar's profile
- **Reserved names**: avatar whose first name or last name is "Default", "Test", "Admin" or the pattern used for appearance models are disallowed for public (such avatars must be created by admins from Robust console)
- **Web assets server**: the needed bridge to display in-world images on a website
- **Helpers**: currency, search, offline messages
- **OpenSimulator settings page**:
  - grid name, login uri and database connection settings
  - naming scheme of default models
  - exclude models from grid stats
- Web asset server
- Login page / Widget
- Manual and cron Grid/WP users sync
- Public avatar profile
- Auth with avatar credentials (if no matching wp account, create one)

### Paid version

The free version from WordPress plugins directory and the [paid version](https://magiiic.com/wordpress/plugins/w4os/) are technically the same. The only difference is the way you support this plugin developement: with the free version, you join the community experience (please rate and comment), while the paid version helps us to dedicate resources to this project.

## Roadmap

See (https://github.com/GuduleLapointe/w4os/) for complete status and changelog.

### Medium term

- [x] get grid info from http://login.uri:8002/get_grid_info
- [x] Web Assets server
- [x] Helpers (search, currency, map...)
- Improve avatar profile
  - Switch to allow web profile
  - Switch set in-world prefs for public profiles
  - Better basic layout
  - Web edit profile
- Admin Start / Stop regions
- Admin Create region
- Admin Use sim/grid configuration file to fetch settings if on the same host

### Long term

- Admin create users
- Admin create models (from current appearance)
- Deactivate (recommended) or delete (experimental) grid user when deleting wp account
- Choice between Robust console or database connection
- User's own regions control (create, start, stop, backup)
- WooCommerce Subscriptions integration for user-owned Regions or other pay-for services
- 2do HYPEvents project integration <https://2do.pm>
- Gudz Teleport Board project integration (based on user picks)
- separate OpenSimulator libraries and WordPress specific code, to allow easy integration in other CMS

## Frequently Asked Questions

### Do I need to run the website on the same server?

No, if your web server has access to your OpenSimulator database.

### Can I use this plugin for my standalone simulator?

Yes, it works too. Use OpenSim database credentials when requested for Robust credentials.

### Why can't I change my avatar name?

This is an OpenSimulator design limitation. Regions rely on cached data to display avatar information, and once fetched, these are never updated. As a result, if an avatar's name (or grid URI btw) is changed, the change will not be reflected on regions already visited by this avatar (which will still show the old name), but new visited region will display the new one. This could be somewhat handled for a small standalone grid, but never in hypergrid context. There is no process to force a foreign grid to update its cache, and probably never will.

## Screenshots

1. Grid info and grid status examples
2. Avatar registration form in WooCommerce My Account dashboard.
3. Settings page
4. Web assets server settings

