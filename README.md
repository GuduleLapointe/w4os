# W4OS - OpenSimulator Web Interface
* Contributors: gudulelapointe,magicoli69
* Donate link: https://paypal.me/magicoli
* Tags: OpenSimulator, Second Life, metaverse, avatar, web interface
* Requires at least: 5.0
* Requires PHP: 5.6
* Tested up to: 5.8.1
* Stable tag: 2.1
* License: AGPLv3
* License URI: https://www.gnu.org/licenses/agpl-3.0.txt

WordPress interface for OpenSimulator (w4os)

## Description

Ready to use WordPress interface for [OpenSimulator](http://opensimulator.org/) grids. Provide user registration, default avatar model choice and basic grid info.

See Features and Roadmap sections for current and upcoming functionalties.

### Features

- **Grid info**: `[gridinfo]` shortcode and admin dashboard widgets
- **Grid status**: `[gridstatus]` shortcode and admin dashboard widgets
- **Avatar creation**:
  - Opensimulator section in standard wp profile page
  - `[gridprofile]` shortcode can be inserted in any custom page
  - Avatar tab in account dashboard on WooCommerce websites
- Choose avatar look from default models
- Avatar and website passwords are synchronized
- **Reserved names**: avatar whose first name or last name is "Default", "Test", "Admin" or the pattern used for appearance models are disallowed for public (such avatars must be created by admins from Robust console)
- **Web assets server**: the needed bridge to display in-world images on a website
- **OpenSimulator settings page**:
  - grid name, login uri and database connection settings
  - naming scheme of default models
  - exclude models from grid stats

### Paid version

The free version from WordPress plugins directory and the [paid version](https://magiiic.com/wordpress/plugins/w4os/) are technically the same. The only difference is the way you support this plugin developement: with the free version, you join the community experience (please rate and comment), while the paid version helps us to dedicate resources to this project.

## Installation

Robust server must be installed before setting up W4OS.

To allow users to choose an avatar on registration, you must enable user profiles in Robust.ini (see [UserProfilesService], [ServiceList] and [UserProfiles] sections)

1. Download [the latest stable release](https://magiiic.com/updates/?action=download&slug=w4os), unzip it in your wp-content/plugins and activate it.
2. Visit OpenSim settings (admin menu > "Opensim" > "Settings")
  - Enter your grid name and grid URI (like example.org:8002 without http://)
  - Enter your robust database connection details and submit. If you get a database connection error, it might come from a case-sensitivity issue, see (https://github.com/GuduleLapointe/w4os/issues/2#issuecomment-923299674).
  - insert `[gridinfo]` and `[gridstatus]` shortcodes in a page or in a sidebar widget
  - create a profile page for registered users and include `[gridprofile]` shortcode. This will display the an avatar creation form for users without in-world avatar. For accounts already having an avatar, it will display avatar details.
  - if you upgraded from a version older than 2.2 (#eb4769081), check "Provide web assets service" to activate new internal web assets server
3. To create default avatars:
  - from ROBUST console (defaults creation is not allowed from the website), create users for your models. Name them according to W4OS settings: one part of the name is "Default", the other part is the name displayed on the form (for example, "Default Casual", "Default Rachel", "Default Tom"). Don't mention e-mail address to avoid counting them as regular accounts in stats.
  - log in-world with each of these model accounts and give them the desire appearance. Take a snapshot and use it as profile picture. It will be used for the web site avatar choosing form.

See INSTALLATION.md for more details.

## Roadmap

See (https://github.com/GuduleLapointe/w4os/projects/1) for up-to-date status.

### Short term (v2.2)

- [x] Include web asset server
- [x] Add avatar picture to gridprofile output
- [x] Sidebar grid info and grid status widgets.
- [x] Use avatar profile pic as WP avatar
- [x] Show avatar picture in user lists
- [x] Login page / Widget
- Public avatar profile
- Auth with avatar credential (as fallback to wp auth).
  Create new WordPress user if auth by avatar.
- Option to show avatar name instead of real name in user lists

### Medium term

- [x] get grid info from http://login.uri:8002/get_grid_info
- Admin Start / Stop regions
- Admin Create region
- Admin Use sim/grid configuration file to fetch settings if on the same host
- Helpers (assets [x], search, currency, map...)

### Long term

- Admin create users
- Admin create models (from current appearance)
- Choice between Robust console or database connection
- User's own regions control (create, start, stop, backup)
- WooCommerce Subscriptions integration for user-owned Regions or other pay-for services

## Frequently Asked Questions

### Do I need to run the website on the same server?
No, if your web server has access to your OpenSimulator database.

### How to create avatar models

Avatar models are displayed on new avatar registration and allow new users to start with another appearance than Ruth.

* Check (or change) their naming convention in Admin > OpenSimulator > Settings > Avatar models
* From robust console, create a user named accordingly (for example, "Female Default", Default John", ...).
    ```
    R.O.B.U.S.T.# create user Default John
    Password: ************************
    Email []: (leave empty)
    User ID (enter for random) []:  (leave empty)
    Model name []: (leave empty)
    15:27:58 - [USER ACCOUNT SERVICE]: Account Default John xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx created successfully
    ```
  - A password is needed as you need connect in-world to configure it.
    Choose a strong password, any hack could affect all new users.
  - One of the parts of the name has to match your W4OS Avatar models settings
  - The other part will be displayed in the form, so make it relevant
  - You can leave "Email" and "User ID" blank
  - Leave "Model Name" blank, you are creating a model, not using an existing model to create a user
* Connect in-world as this avatar and change outfit. Any worn clothing or attachment will be passed to the new avatars. Be sure to wear only transfer/copy items.
* Make a snapshot and attach it to this account profile

The model avatar will now appear in new avatar registration form, with its profile picture.

These accounts will be excluded in the grid statistics.

### Can I use this plugin for my standalone simulator?

Maybe, but we didn't check yet. If you give it a try, please send us your feedback. Otherwise, you can try [OpenSimulator Bridge](https://fr.wordpress.org/plugins/opensimulator-bridge/), which is dedicated to standalone simulators.

### Whi can't I change my avatar name?

This is an OpenSimulator design limitation. Regions rely on cached data to display avatar information, and once fetched, these are never updated. As a result, if an avatar's name (or grid URI btw) is changed, the change will not be reflected on regions already visited by this avatar (which will still show the old name), but new visited region will display the new one. This could be somewhat handled for a small standalone grid, but never in hypergrid context. There is no process to force a foreign grid to update its cache, and probably never will.

## Screenshots

1. Grid info and grid status examples
2. Avatar registration form in WooCommerce My Account dashboard.
3. Settings page
4. Web assets server settings
