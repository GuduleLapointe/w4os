### 0.11.0
* added shortcode and dashboard widget to show last users

### 0.10.2
* fixed https://github.com/GuduleLapointe/w4os/issues/1 undefined constant. Thx djphil
* merged latest translations from https://git.magiiic.com/opensimulator/w4os-translations
* Changed OpenSim to OpenSimulator

### 0.10.1
* Translation bug was fixed, translations updates are now working with the
  develop branch of GitHub Updater, probably available soon in the stable
  release too.
* fix grid info and grid status tables

### 0.10
* welcome back languages/ folder, force loading with load_plugin_textdomain()
  and now it works
* found some untranslated strings
* added missing text domains in localizations

### 0.9.8.3
* since 0.9.6.1, many unsuccessful attemps to get the localizations updates with
  GitHub updater, switch back to our own gitlab server for now, but it won't
  work until a bug in GitHub Updater is fixed
  https://github.com/afragen/github-updater/issues/890

### 0.9.6
* removed languages/ folder, it doesn't seem to be taken in account and
  localizations have their own git repo
* Added short install instructions for git page

### 0.9.5
* Renamed project W4OS OpenSimulator Inter
* Attempt to phpdoc but it's long, not finished


### 0.9.4
* use WordPress password for avatar creation
* auto-fill Avatar name with WordPress user name
* some cosmetic changes including clarify OpenSimulator settings page, readmes and
  changelog

### 0.9.3.3
* added settings for model selection
* sort models alphabetically

* exclude models from stats count (optional, default yes)
* exclude accounts without mail adddress from stats count (optional, default yes)
* exclude  accounts with 'active' field set to false from stats

* fix: don't try to show model picture if web asset server is not set
* fix: model name display without pictures when no picture
* fix: modelname display strip value from model first and last name setting
  instead of 'Default'
* strip "Ruth2" in model name, and add "Ruth 2.0" at the end
* same for Roth2
* fix: picture max-width relative, to be more responsive

### 0.9.2
* Upgraded LICENSE to AGPLv3
* added instructions to install a web asset server
* moved installation instructions in a specific INSTALLATION file
* disambiguation renamed "Asset server" setting to "Web asset server"

### 0.9
* choose avatar look from a choice of models.
    For now, available choices are existing avatars with
    FirstName or LastName = "Default" (they have to be created from the
    console as these names are disallowed from the website)

### 0.8
* copy needed inventory items from default avatar
* fixed multiple attachments not added in inventory
* fixed missing outfit error message in FireStorm
* fixed hardcoded home region uuid, use "Welcome" instead (still need an option
  in settings page)
* fixed wrong password hash for new registrations

### 0.7.7
* renamed as "WordPress interface for OpenSimulator" which is more accurate
* update avatar password when registering corresponding new user on the website
* updated features and roadmap

### 0.7.6
* fixed deleted language pack url (not working)

### 0.7.5
* website password change syncs avatar password

### 0.7.4
* added required tag to required fields on avatar register form
* languages updates
* fixed notices css affecting admin section
* added avatar registration screen capture in assets

### 0.7.3
* added language pack repository, testing translations updates

### 0.7.2
* localisation updates, including French translation and .pot

### 0.7.1
* added: translation .pot file
* changed: w4os_profile shortcode now shows new wc_edit output
* fixed: only display w4os_profile shortcode if user is connected

### 0.7 Avatar registration working
* Now it is possible to register an avatar from WooCommerce account page.
  It should now be easy to integrate in WP profile page and/or a custom page.
* fix undefined result variable

### 0.6.2
* fix wp-cli crashing
* fix some PHP warnings
* updated some strings

### 0.6.1
* added resync uuid and avatar name from os db before opening user profile
* fixed use translation domain for plugin-specific strings

### 0.6.0
* added avatar names to user list
* added check for required tables in os database
* added github updater to roadmap
* fixed bug (is_user_logged_in constant instead of function)

### 0.5.2
* added icons and banner

### 0.5.0
* simplify
* simplify
* and simplify (get rid of unused boilerplate stuff)

### 0.4.1
* Added Github Updater plugin support

### 0.3.2
* gridinfo and gridstatus dashboard

### 0.3.0
* slug changed from "opensim" to "w4os" to avoid confusion between this project
  main and childs and side projects

### 0.2.4
* marked as "stable"

### 0.2.2
* grid status admin page

### 0.2.0
* grit status shortcode
* admin grid status page

### 0.1.0
* settings page
* grid info shortcode
