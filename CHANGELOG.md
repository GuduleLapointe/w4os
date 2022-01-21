## Changelog

### Unreleased
* new events parser (fetch events from 2do.pm or another HYPEvents server)
* new search helper
* new offline messages helper. Messages are stored in OfflineMessageModule V2 format, so one can switch between core and external service (fix #47)
* new currency helpers
* added currency conversion rate setting
* separate helpers settings page

### 2.2.10
* tested up to wp 5.8.3
* updated translations

### 2.2.9
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

### 2.1
* added login form to gridprofile shortcode when not connected instead of login message
* added w4os-shortcode classes
* added screenshots
* fix fatal error when trying to display  WooCommerce Avatar tab form in My Account
* fix localisation not loading
* shorter "Avatar" label, removed uuid in gridprofile shortcode

### 2.0.8
* Now distributed via WordPress plugins directory
* Official git repository changed to GitHub
* renamed plugin as W4OS - OpenSimulator Web Interface
* fix other WP plugins directory requirements
* fix localizations not loading
* fix regression, automatic updates restored. Users with version 2.0 to 2.0.3 will need to reinstall the plugin from source. Sorry.
* use plugin dir to detect slug instead of hardcoded value
* renamed [w4os_profile] shortcode as [gridprofile] for consistency. w4os_profile is kept for backwards compatibility

### 1.2.12
* fix #2 Database check fails if mysql is case insensitive
* fix #4  Database connection error triggered if userprofile table is absent
* fix #10 invalid JSON response when adding [w4os_profile] shortcode element
* fix wrong letter cases in auth table name
* fix only show profile form for current user
* better css loading
* only check once if w4os db is connected
* added login page link to message displayed when trying to see profile while not connected
* more detailed error messages for avatar creation

### 1.1.4
* added changelog, banners and icons to view details
* fix "Yes" and "No" translations
* fix typo in banners and icons urls, can't believe I didn't see this before...
* fixed conflict with other extensions settings pages
* changed update server library to [frogerme's WP Plugin Update Server](https://github.com/froger-me/wp-plugin-update-server)

### Previous
* For full change history see [GitHub repository](https://github.com/GuduleLapointe/w4os/commits/master)
