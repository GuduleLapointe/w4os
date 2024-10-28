=== w4os - OpenSimulator Web Interface ===
Contributors: gudulelapointe,magicoli69
Donate link: https://w4os.org/donate/
Tags: OpenSimulator, Second Life, web interface, hypergrid, standalone
Requires at least: 5.3.0
Requires PHP: 7.3
Tested up to: 6.7-RC1
Stable tag: 2.8
License: AGPLv3
License URI: https://www.gnu.org/licenses/agpl-3.0.txt

WordPress interface for OpenSimulator (w4os)

== Description ==

Ready to use WordPress interface for [OpenSimulator](http://opensimulator.org/). Provides user registration, default avatar model choice, login info, statistics and a web assets server for grids or standalone simulators.

Full installation instructions: (https://gudulelapointe.github.io/w4os/INSTALLATION.html)

See Features and Roadmap sections for current and upcoming functionalties.

= Features =

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

= Paid version =

The free version from WordPress plugins directory and the [paid version](https://magiiic.com/wordpress/plugins/w4os/) are technically the same. The only difference is the way you support this plugin developement: with the free version, you join the community experience (please rate and comment), while the paid version helps us to dedicate resources to this project.

== Requirements ==

* OpenSimulator 0.9.x (0.9.2.2 recommended). 0.8.x and earlier version might work and used to, but are definitely not supported anymore
* Latest WordPdress release
* PHP 8.x or later (8.2 recommended), and the PHP extensions recommended by WordPress (particularly xmlrpc, curl and ImageMagick )

== Installation ==

= OpenSimulator installation =

Please check [OpenSimulator](https://opensimulator.org/) documentation to install and configure your simulator.

- Choose MySQL storage
- To allow default outfits on registration and web user profiles, you must enable user profiles in Robust.HG.ini (update [UserProfilesService], [ServiceList] and [UserProfiles] sections)
- Start  the simulator, create the first (admin) avatar and a first region, and connect in-world to make sure the grid is working properly

= WordPress installation =

Please check [WordPress](https://wordpress.org/) documentation to install and configure WordPress.

- Permalinks need to be enabled (set to any other choice than the default "Plain" setting)
- PHP minimum version: 7.3
- The PHP modules curl, xml, xml-rpc and imagick are needed to allow full functionalty of the plugin (and they are also recommended for WordPress anyway). Without these plugins, some important functionalties will not be available.

= Plugin installation and configuration =

Note: if upgrading from a different distribution (e.a. switching from github to WordPress Plugin Directory), make sure you disabled the previous version before activating the new one.

1. Download and activate the latest stable release
2. Visit `OpenSimulator > Settings` page in admin
   - Enter your grid name and grid URI (like yourgrid.org:8002, without http://)
   - Enter your robust database connection details and submit. If you get a database connection error, it might come from a case-sensitivity issue (see https://github.com/GuduleLapointe/w4os/issues/2#issuecomment-923299674)
3. Set permalinks and profile page
   - Visit `OpenSimulator > Settings > Permalinks`, make sure permalink structure is NOT set to "Plain", and adjust W4OS slugs to your preferences
   - Take note of the slug chosen for profile base and create a page with the same slug
4. Visit `OpenSimulator > Settings > Web assets server` and make sure the option is enabled. (You can disable it you have a third party web assets server up and running, and enter its full URL below)
5. You should be able to register a new avatar from the website. You can customize your website with shortcodes or blocks, like Grid Info, Grid Status or Popular Places (see full list and descriptions in `OpenSimulator > Settings > Shortcodes`)

**Several options of the plugin require the update of a related parameter in OpenSimulator itself, they are documented in the interface. Make sure to adjust the ini files accordingly and restart OpenSimulator for any change to take effect.**

= Avatar models =

Models are displayed on new avatar registration form, to allow chosing an initial appearance other than Ruth. They are made by creating model avatar accounts and adjusting their appearance.

Model avatars can only be used for this purpose. **Under no circumstances** should an avatar belonging to a user be used as a model.

- Visit `OpenSimulator > Settings > Avatar Models` and confirm or customize the naming structure for your models. It will be use to select automatically avatars to display as models in registration form
- From robust console, create a user named according to these settings (for example, "Female Default", Default John", ...).
    ```
    R.O.B.U.S.T. # create user Default John
    Password: ************************ (use a strong password)
    Email []: (leave empty)
    User ID (enter for random) []:  (leave empty)
    Model name []: (leave empty)
    15:27:58 - [USER ACCOUNT SERVICE]: Account Default John xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx created successfully
    ```
  - A **password is required** to configure the model in-world
  Choose a strong password, any unauthorized access could affect all new users and compromise the security of the grid.
  - The rest of the name will be displayed in the form, so make it relevant
  - You can leave Email and User ID blank
  - **Leave Model Name blank** (you are creating a model, not using an existing model to create a user)
- Connect in-world as each avatar and change outfit
  - Any worn clothing or attachment will be passed to the new avatars. Be sure to wear only transfer/copy items
  - Take a snapshot to set model avatar profile picture
  - Disconnect the model avatar after modifications, to make sure changes will be taken in account immediately

The models will appear in new avatar registration form, with their profile picture.

== Troubleshooting ==

See [TROUBLESHOOTING.md](https://gudulelapointe.github.io/w4os/TROUBLESHOOTING.html) for more information.

== Roadmap ==

See [github.com/GuduleLapointe/w4os](https://github.com/GuduleLapointe/w4os/) for complete status and changelog.

= Medium term =

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

= Long term =

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

== Frequently Asked Questions ==

= Do I need to run the website on the same server? =

No, if your web server has access to your OpenSimulator database.

= Can I use this plugin for my standalone simulator? =

Yes, it works too. Use OpenSim database credentials when requested for Robust credentials.

= Why can't I change my avatar name? =

This is an OpenSimulator design limitation. Regions rely on cached data to
display avatar information, and once fetched, these are never updated. As a
result, if an avatar's name (or grid URI btw) is changed, the change would not
be reflected on regions already visited by this avatar (which will still show
the old name), but new visited regions would display the new name. This could be
somewhat handled for a small standalone grid, but never in hypergrid context.
There is no process to force a foreign grid to update its cache, and probably
never will.

= Shouldn't I copy the helpers/ directory in the root of my webiste ? =

No, you don't need to and you shouldn't. The /helpers/ is virtual, it is served
as any other page of your website. Like there the /about/ URL website doesn't
match a /about/ folder your webste directory. Even if there is a helpers/
directory in w4os plugin, it has the same name for convenience, but he could
have been named anything. It's content is not accessed directly, it is used by
the plugin to generate the answers. On the opposite, if there was an actual
helpers/ folder in your website root, it would interfer with w4os.

= Should I create assets/ directory in the root of my webiste ? =

Yes and No. It can improve a lot the images delivery speed, but you won't
benefit of the cache expiry, which would eventually correct any wrong or
corrupted image.

= I use Divi theme, I can't customize profile page =

Divi Theme support is fixed in versions 2.4.5 and above.

== Screenshots ==

1. Grid info and grid status examples
2. Avatar registration form in WooCommerce My Account dashboard.
3. Settings page
4. Web assets server settings

== Changelog ==

= 2.8 =
* fix database credential not shown in settings
* fix PHP Fatal error:  Uncaught Error: Call to undefined method MetaBoxSupportArr::to_depth (updated metabox dependencies)
* Tested up to 6.7-RC1

= 2.7.9 =
* updated Economy settings instructions
* fix Offline Message url instructions for Robust (we used 'message' variable, which Firestorm Viewer seems to use for another purpose)
* offline message : move sender info after the message for better display in mailbox list

= 2.7.8 =
* fix scheduled jobs list growing indefinitely
* fix w4os_get_urls_statuses and w4os_sync_users scheduled action failng (not properly registered)
* fix empty content passed to ImageMagic
* fix PHP Fatal error  Zero size image string passed to Imagick
* fix deprecation warnings Optional parameter  declared before required parameter

= 2.7.7 =
* Tested up to WP 6.6.1 (fix #78)
* don't process template content if original language page is not found
* minor dev changes

= 2.7.6 =
* fix eventsparser crashing (undefined varfiable $array)
* (dev) get helpers as submodule instead of composer dependency

= 2.7.5 =
* tested up to WP 6.5

= 2.7.3 =
* updated INSTALLATION.md, link to githup pages in README
* updated libraries

= 2.7.2 =
* added permalink option for helpers slug

= 2.7.1 =
* added clearer instructions for missing requirements on status page
* fix web search output unnecessary closing tag

= 2.7 =
* new web search block (experimental)
* new destinations guide (experimental)
* added bases for localization
* added helpers/guide.php
* fix avatar creation form not displayed for new accounts
* search settings: only show the applicable SearchURL to avoid confusions
* use dialog modal box for avatar creation form when form container is too small

= 2.6.4 =
* fix W4OS_DIR not defined when database is not configured

= 2.6.3 =
* Not stable: bugs introduced in this version were fixed in 2.6.4
* fix regression in 41dfe8e 2.6.2 (some admin menus missing)
* fixed profile displayed twice when profile page is personalized with Divi Builder

= 2.6.2 =
* Not stable: bugs introduced in this version were fixed in 2.6.4
* added terms of service checkbox

= 2.6.0 =
* prevent fatal errors with wrongly formatted translations or any other sprintf() error
* added support for WPML and Polylang (other translation plugin don't need it)
* added Italian and Portuguese translations (to already present French, Dutch, German and Welsh)
* added admin bar menu
* Popular Places block displays now only local results and exclude land for sale by default, added options to override
* helpers: show only local results in popular places

= 2.5 =

Stable release, includes updates from 2.4.2 to 2.4.7, mainly:
* optimized Grid Status, Grid Info, Popular Places and Avatar Profile Gutenberg block
* added "mini profile" option to avatar profile
* added Divi modules for Grid Status, Grid Info, Popular Places and Avatar Profile
* added option to define avatar models by a custom list in addition to name rules
* added Gloebit configuration instructions
* reorganized settings in several page
* fix potential crash due to incorrectly formatted translations
* and a bunch of other fixes and enhancements detailed below

= 2.4.7 =
* fix crash caused by translation on nl and de pages
* fix popular-place page would crash if mpty answer given by the helper

= 2.4.6 =
* added Gloebit configuration instructions
* added link to economy binaries download

= 2.4.5 =
* added "mini profile" option to avatar block
* added Grid Status, Grid Info and Avatar Profile Divi module
* fixed Grid Status, Grid Info and Avatar Profile Gutenberg block
* reorganized Search Engine, Economy and Offline Messages settings
* clarified profile page settings
* fixed Podex redirect message broken

= 2.4.4 =
* added title level option to Popular Places block, shortcode and Divi module

= 2.4.3 =
* added Popular Places Divi Builder module
* added separate web assets server settings page
* added separate Shortcode admin page
* added [popular-places] to Shortcode page
* renamed shortcodes for clarity and constistency:
  - [grid-info] instead of [gridinfo]
  - [grid-status] instead of [gridstatus]
  - [avatar-profile] instead of [gridprofile]
  - Legacy shortcodes kept for backwards compatibility
* added warning when attempting to edit profile page (or any page generated by w4os) with Divi Builder.
* prettier and more efficient db credentials options in settings page (getting ready for optimized use in the future)

= 2.4.2 =
* added option to define avatar models with a custom list in addition to name rule
* update available models dynamically on models settings page
* fix settings action link displayed in the wrong plugin row on plugin page

= 2.4.1 =
* fix fatal error when updating from WP directory ("MetaBoxUpdaterOption" not found)

= 2.4 =
* new Avatar Models settings page, including list of available avatars
* added defaults for plugin-provider or external search engines
* added troubleshooting guide
* added instructions for nginx users
* optimized assets rendering from cache
* fix profile and avatar models pictures broken
* fix regression arguments not accepted for query.php
* fix invalid DATA_SRV_ example variable when gridname contains invalid characters
* fix helpers nginx icompatibility (use REQUEST_URI instead of REDIRECT_URL)
* fix helpers settings hints missing http:// protocol for gatekeeper
* fix no result if gatekeeper is passed without http:// protocol
* fix search and register url settings

= 2.3.10 > 2.3.15 =
* restored WooCommerce Account Dashboard avatar section
* fix array_unique(): Argument #1 ($array) must be of type array, null given on plugin first activation
* fix Undefined constant "W4OS_PROFILE_URL" fatal error
* fix wrong event time in in-world search (UTC shown instead of grid time)
* fix w4os_profile_sync() fatal error when profiles are disabled
* fix fatal error when wp object is passed as user_id
- minor fixes (profile page title, profile image, profile text display)

= 2.3.9 =
* new search helper
* new offline messages helper. Messages are stored in OfflineMessageModule V2 format, so one can switch between core and external service (fix #47)
* new currency helpers
* new Popular Places block and [popular-places] shortcode
* new events parser (fetch events from 2do.pm or another HYPEvents server)
* added password reset link to profile page
* added prebuilt binaries for opensim 0.9.1 and 0.9.2
* added currency conversion rate setting
* separate helpers settings page
* updated translations
* fix userprofile table queried even if not present (issue #64) when User Profiles are not enabled on robust
* fix fatal error Argument #2 ($haystack) must be of type array, bool given (issue #64)
* fix offline messages not forwarded by mail (opensim db not properly loaded by helpers)
* fix profile picture aspect ratio (4/3, as in viewer)
* fix fatal error in helpers for poorly encoded unicode text sources
* fix fatal errors in helpers when database is not connected
* fix #57 password not updated on grid when using password recovery in WordPdress
* fix fatal error and warnings with popular-places shortcode
* avoid fatal error if php xml-rpc is not installed, show error notice instead
* helpers migrated from old mysqli db connection method to PDO
* dropped aurora and OpenSim 0.6 support

= 2.2.10 =
* new web assets server
* new profile page
* new config instructions for new grid users
* new blocks support
* new grid and wordpress users sync
* new grid based authentication; if wp user exists, password is reset to grid password; if not, a new wp user is created
* new admin can create avatars for existing users
* new grid info settings are fetched from Robust server if set or localhost:8002
* new check grid info url validity (cron and manual)

* added option to replace name by avatar name in users list
* added profile image to gridprofile
* added assets permalink settings
* added states in admin pages list for known urls (from grid_info)
* added lost password and register links on login page
* added buttons to create missing pages on status dashboard
* added Born and Last Seen columns to users list
* added hop:// link to login uri
* added in-world profile link to profile page
* added Partner, Wants, Skills and RL to web profile

* removed Avatar section from WooCommerce account page until fixed
* removed W4OS Grid Info and W4OS Grid Status widgets (now available as blocks)
* fix duplicate admin notices
* fix squished profile picture
* fix avatar not created, or not created at first attempt
* fix inventory items not transferred to new avatars
* fix errors not displayed on avatar creation page
* fix avatar model not shown if default account never connected
* fix missing error messages on login page
* fix user login broken if w4os_login_page is set to profile and OpenSim database is not connected
* fix a couple of fatal errors
* fix slow assets, store cached images in upload folder to serve them directly by the web server
* fix Fatal error Call to undefined function each()

* show a link to profile page instead of the form in profile shortcode
* responsive profile display for smartphones
* show image placeholder if profile picture not set
* added imagick to the recommended php extensions
* lighter template for profiles when loaded from the viewer
* guess new avatar name from user_login if first name and last name not provided
* replace wp avatar picture with in-world profile picture if set
* use version provided by .version if present
* More comprehensive database connection error reporting

= 2.1 =
* added login form to gridprofile shortcode when not connected instead of login message
* added w4os-shortcode classes
* added screenshots
* fix fatal error when trying to display  WooCommerce Avatar tab form in My Account
* fix localisation not loading
* shorter "Avatar" label, removed uuid in gridprofile shortcode

= 2.0.8 =
* Now distributed via WordPress plugins directory
* Official git repository changed to GitHub
* renamed plugin as W4OS - OpenSimulator Web Interface
* fix other WP plugins directory requirements
* fix localizations not loading
* fix regression, automatic updates restored. Users with version 2.0 to 2.0.3 will need to reinstall the plugin from source. Sorry.
* use plugin dir to detect slug instead of hardcoded value
* renamed [w4os_profile] shortcode as [gridprofile] for consistency. w4os_profile is kept for backwards compatibility

= 1.2.12 =
* fix #2 Database check fails if mysql is case insensitive
* fix #4  Database connection error triggered if userprofile table is absent
* fix #10 invalid JSON response when adding [w4os_profile] shortcode element
* fix wrong letter cases in auth table name
* fix only show profile form for current user
* better css loading
* only check once if w4os db is connected
* added login page link to message displayed when trying to see profile while not connected
* more detailed error messages for avatar creation

= 1.1.4 =
* added changelog, banners and icons to view details
* fix "Yes" and "No" translations
* fix typo in banners and icons urls, can't believe I didn't see this before...
* fixed conflict with other extensions settings pages
* changed update server library to [frogerme's WP Plugin Update Server](https://github.com/froger-me/wp-plugin-update-server)

= Previous =
* For full change history see [GitHub repository](https://github.com/GuduleLapointe/w4os/commits/master)
