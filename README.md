# W4OS OpenSimulator Interface

WordPress interface for OpenSimulator

## Description

The first ready to use WordPress interface for OpenSimulator. Provides user
registration and basic grid info. See enabled features below and ROADMAP file
for upcoming functionalties.

## Features

* **Grid info**: `[gridinfo]` shortcode and admin dashboard widgets
* **Grid status**: `[gridstatus]` shortcode and admin dashboard widgets
* **Avatar creation**:
  - `[w4os_profile]` shortcode can be inserted in any page
  - Avatar tab in account dashboard on WooCommerce websites
  - Choose avatar look from default models
* Avatar and website passwords are synchronized
* **Reserved names**: avatar whose first name or last name is "Default",
  "Test", "Admin" or the pattern used for appearance models are disallowed for
  public (such avatars must be created by admins from Robust console)
* **OpenSimulator settings page**:
  - grid name, login uri and database connection settings
  - naming scheme of default models
  - exclude models from grid stats

## Install from git repository

* Download the latest pagkage https://git.magiiic.com/opensimulator/w4os/-/archive/master/w4os-master.zip
* Unzip it, rename `w4os-master` as `w4os` and move it inside `wp-content/plugins/`
* Activate from your plugins page
* Go to admin menu OpenSimulator > Settings to enter your grid details

To receive updates, install [GitHub Updater](https://github.com/afragen/github-updater)

## Contributing

If you improve this software, please give back to the community, by submitting
your changes on the git repository or sending them to the authors.

* GitLab repository: https://git.magiiic.com/opensimulator/w4os
* GitHub repository: https://github.com/GuduleLapointe/w4os
