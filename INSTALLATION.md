## Installation

- Robust server must be installed before setting up W4OS.
- To allow users to choose an avatar on registration, you must enable user
  profiles in Robust.ini (update [UserProfilesService], [ServiceList] and
  [UserProfiles] sections)
- You should have a working assets server (see Dependencies section below)

1. Download and activate the latest stable release
2. Visit OpenSim settings (admin menu > "Opensim" > "Settings")
  - Enter your grid name and grid URI (like example.org:8002 without http://)
  - Enter your robust database connection details and submit. If you get a
    database connection error, it might come from a case-sensitivity issue (see
    https://github.com/GuduleLapointe/w4os/issues/2#issuecomment-923299674)
  - insert `[gridinfo]` and `[gridstatus]` shortcodes in a page or in a sidebar widget
  - create a profile page for registered users, including `[gridprofile]` shortcode.
    This will display the an avatar creation form for users without in-world avatar.
    For accounts already having an avatar, it will display avatar details.
3. To create default avatars:
  - from ROBUST console (defaults creation is not allowed from the website),
    create users for your models. Name them according to W4OS settings: one part
    of the name is "Default", the other part is the name displayed on the form
    (for example, "Default Casual", "Default Rachel", "Default Tom"). Don't
    mention e-mail address to avoid counting them as regular accounts in stats.
  - log in-world with each of these model accounts and give them the desire
    appearance. Take a snapshot and use it as profile picture. It will be used
    for the web site avatar choosing form.

### Dependencies

The project relies on an an external web asset server to display images from
simulator assets (profile pictures, model avatars...). In an undefined future
this will be handled by the plugin. In the meantime, Anthony Le Mansec's
opensimWebAssets is very efficient and easy to install:

  - download from github https://github.com/TechplexEngineer/opensimWebAssets
  - copy "src" folder inside your website and name it "assets". It can coexist
    with your WordPress installation, WordPress will ignore it.
  - edit assets/inc/config.php to suit your needs (essentially, change the value
    of ASSET_SERVER to http://your.login.uri:8002/assets/)
  - in WordPress OpenSimulator settings, change web asset server to
    http://your.website/assets/asset.php?id=

### Shortcodes

- `[gridinfo]` display grid name and login URI
- `[gridstatus]` display users and regions stats
- `[gridprofile]` display users and regions stats

Both accept a title parameter to overwrite the defaults "Grid info"
and "Grid status". If set to "" the title is not displayed.

Example:
[gridinfo title="Who's there"]
[gridstatus title=""]

* `[gridprofile]` show in-world profile if user has an avatar, or avatar
  creation form otherwise. It can be inserted on any page

* Websites using WooCommerce don't need to set a specific profile page, as it is
  integrated within the WooCommerce account dashboard.
