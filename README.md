# w4os - OpenSimulator Web Interface

![Stable 2.7.2](https://badgen.net/badge/Stable/2.7.2/00aa00)
![WordPress 5.3.0 - 6.4.2](https://badgen.net/badge/WordPress/5.3.0%20-%206.4.2/3858e9)
![Requires PHP 7.3](https://badgen.net/badge/PHP/7.3/7884bf)
![License AGPLv3](https://badgen.net/badge/License/AGPLv3/552b55)

WordPress interface for OpenSimulator (w4os).

## Description

Ready to use WordPress interface for [OpenSimulator](http://opensimulator.org/). Provides user registration, default avatar model choice, login info, statistics and a web assets server for grids or standalone simulators.

See Features and Roadmap sections for current and upcoming functionalties.

### Features

- **Avatar creation**:
  - Opensimulator section in standard wp account page
  - Avatar tab in account dashboard on WooCommerce websites
  - Avatar and website passwords are synchronized
  - Configuration instructions for new avatars
  - **Public avatar profile**: excerpt of the avatar's profile
  - **Avatar Models**: default outfits to choose from on registration
  - **Reserved names**: avatar whose first name or last name is "Default", "Test", "Admin" or the pattern used for appearance models are disallowed for public (such avatars must be created by admins from Robust console)
- **Search Engine**: enable in-world search
  - **places**
  - **land for sale**
  - **classifieds**
  - **events** (2do.directory integration)
- Shortcodes
  - **Grid info**: `[grid-info]` shortcode, Gutenberg block and Divi module
  - **Grid status**: `[grid-status]` shortcode, Gutenberg block and Divi module
  - **Grid status**: `[popular-places]` shortcode, Gutenberg block and Divi module
  - **Profile page**: `[avatar-profile]`  shortcode, Gutenberg block and Divi module
- **Web assets server**: the needed bridge to display in-world images on a website
- **Currency helpers**: integration with Podex, Gloebit and core money module
- **Offline messages e-mail forwarding**
- Manual and cron Grid/WP users sync
- Auth with avatar credentials (if no matching wp account, create one)

### Paid version

The free version from WordPress plugins directory and the [paid version](https://magiiic.com/wordpress/plugins/w4os/) are technically the same. The only difference is the way you support this plugin developement: with the free version, you join the community experience (please rate and comment), while the paid version helps us to dedicate resources to this project.

## Roadmap

See (https://github.com/GuduleLapointe/w4os/) for complete status and changelog.

### Medium term

- Destinations guide
- Web search
- Multiple avatars for same WordPress user
- Improve avatar profile
  - Switch to allow web profile
  - Better basic layout
  - Web edit profile
- 2do HYPEvents project integration <https://2do.pm>
- Gudz Teleport Board project integration (based on user picks)
- Admin Use sim/grid configuration file to fetch settings if on the same host
- Admin create users
- Admin create models (from current own avatar appearance)

### Long term

- Robust console connection
  - Admin Start / Stop regions
  - Admin Create region
  - User's own regions control (create, start, stop, backup)
- WooCommerce integration
  - paid accounts
  - regions orders
  - other pay-for services
- Deactivate (recommended) or delete (experimental) grid user when deleting wp account
- Split code between OpenSimulator and WordPress specific codes

## Frequently Asked Questions

### Do I need to run the website on the same server?

No, if your web server has access to your OpenSimulator database.

### Can I use this plugin for my standalone simulator?

Yes, it works too. Use OpenSim database credentials when requested for Robust credentials.

### Why can't I change my avatar name?

This is an OpenSimulator design limitation. Regions rely on cached data to
display avatar information, and once fetched, these are never updated. As a
result, if an avatar's name (or grid URI btw) is changed, the change would not
be reflected on regions already visited by this avatar (which will still show
the old name), but new visited regions would display the new name. This could be
somewhat handled for a small standalone grid, but never in hypergrid context.
There is no process to force a foreign grid to update its cache, and probably
never will.

### Shouldn't I copy the helpers/ directory in the root of my webiste ?

No, you don't need to and you shouldn't. The /helpers/ is virtual, it is served
as any other page of your website. Like there the /about/ URL website doesn't
match a /about/ folder your webste directory. Even if there is a helpers/
directory in w4os plugin, it has the same name for convenience, but he could
have been named anything. It's content is not accessed directly, it is used by
the plugin to generate the answers. On the opposite, if there was an actual
helpers/ folder in your website root, it would interfer with w4os.

### Should I create assets/ directory in the root of my webiste ?

Yes and No. It can improve a lot the images delivery speed, but you won't
benefit of the cache expiry, which would eventually correct any wrong or
corrupted image.

### I use Divi theme, I can't customize profile page

Divi Theme support is fixed in versions 2.4.5 and above.

## Screenshots

1. Grid info and grid status examples
2. Avatar registration form in WooCommerce My Account dashboard.
3. Settings page
4. Web assets server settings

