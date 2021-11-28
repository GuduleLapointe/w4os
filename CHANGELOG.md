## Changelog

### 2.2
* new grid based authentication; if wp user exists, password is reset to grid password; if not, a new wp user is created
* new profile page
* new grid and wordpress users sync
* new basic blocks support
* new Grid info settings are checked against Robust server. If Login URI is not set, localhost:8002 is checked.
* new 'Grid info' and 'Grid status' sidebar widgets
* new internal web assets server
* added option to replace name by avatar name in users list
* added internal update process
* added assets permalink settings
* added profile image to gridprofile output
* fix slow assets, store cached images in upload folder to serve them directly by the web server
* fix #21 Fatal error Call to undefined function each()
* assets optimized (write converted images inside upload/ folder to let them serve directly by the web server)
* replace wp avatar picture with in-world profile picture if set
* use version provided by .version if present
* More comprehensive database connection error reporting
* show internal or external asset server uri according to provide web assets service value

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
