## Installation

1. Install the plugin
  * From your website admin interface:
    - Download latest release as zip, from https://git.magiiic.com/opensimulator/w4os/releases
    - Go to Admin > Extensions > Add > Upload and select the zip file.
  * From git:
    - go to wp-content/plugins folder
    - type
      git clone https://git.magiiic.com/opensimulator/w4os.git
  * With GitHub Updater
    - Install GitHub Updater from https://github.com/afragen/github-updater/
    - In GitHub Updater settings, to "Install plugin" tab and insert this project
      git repository address. Select GitLab as Remote Repository Host if taken
      from git.magiiic.com
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Visit OpenSimulator settings page and setup grid info and database connection
4. Visit admin OpenSimulator status page to see status

### Dependencies

The project relies on an an external web asset server to display images from
simulator assets (profile pictures, model avatars...). In an undefined future
this will be handled by the plugin. In the meantime, Anthony Le Mansec's
opensimWebAssets is very efficient and easy to install:

  - dowload from github https://github.com/TechplexEngineer/opensimWebAssets
  - copy "src" folder inside your website and name it "assets". It can coexist
    with your WordPress installation, WordPress will ignore it.
  - edit assets/inc/config.php to suit your needs (essentially, change the value
    of ASSET_SERVER to http://your.login.uri:8002/assets/)
  - in WordPress OpenSimulator settings, change web asset server to
    http://your.website/assets/asset.php?id=

While in pre-release status, to get automatic updates, install GitHub Updater:
https://github.com/afragen/github-updater/

### Shortcodes

* `[gridinfo]` display grid name and login URI
* `[gridstatus]` display number of users (current, active, local and HG) and regions

Both accept a title parameter to overwrite the defaults "Grid info"
and "Grid status". If set to "" the title is not displayed.

Example:
[gridinfo title="Who's there"]
[gridstatus title=""]

* `[w4os_profile]` show in-world profile if user has an avatar, or avatar
  creation form otherwise. It can be inserted on any page

* Websites using WooCommerce don't need to set a specific profile page, as it is
  integrated within the WooCommerce account dashboard.
