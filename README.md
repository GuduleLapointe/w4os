# W4OS OpenSimulator Interface

WordPress interface for OpenSimulator

## Description

The first ready to use WordPress interface for OpenSimulator. Provides user
registration and basic grid info. See current Features below, and Roadmap
section in readme.txt for upcoming functionalties.

This README.md contains a brief introduction and information specific to the git
development version. Complimentary details in WordPress standard readme.txt
file.

## Features

- **Grid info**: `[gridinfo]` shortcode and admin dashboard widgets
- **Grid status**: `[gridstatus]` shortcode and admin dashboard widgets
- **Avatar creation**:
  - Opensimulator section in standard wp profile page
  - `[w4os_profile]` shortcode can be inserted in any custom page
  - Avatar tab in account dashboard on WooCommerce websites
- Choose avatar look from default models
- Avatar and website passwords are synchronized
- **Reserved names**: avatar whose first name or last name is "Default",
  "Test", "Admin" or the pattern used for appearance models are disallowed for
  public (such avatars must be created by admins from Robust console)
- **OpenSimulator settings page**:
  - grid name, login uri and database connection settings
  - naming scheme of default models
  - exclude models from grid stats

## Install from git repository

* Download the latest pagkage
  https://git.magiiic.com/opensimulator/w4os/-/archive/master/w4os-master.zip
* Unzip it, rename `w4os-master` to `w4os`, move it inside `wp-content/plugins/`
* Activate from your plugins page
* Go to admin menu OpenSimulator > Settings to enter your grid details

To receive updates, install
[GitHub Updater](https://github.com/afragen/github-updater)
(temporarily, if you get a PCLZIP_ERR_BAD_FORMAT error message, be sure to use
their develop branch instead of the master release).

## Contributing

If you improve this software, please give back to the community, by submitting
your changes on the git repository or sending them to the authors.

* GitLab repository: https://git.magiiic.com/opensimulator/w4os
* GitHub repository: https://github.com/GuduleLapointe/w4os
* Localization repository: https://git.magiiic.com/opensimulator/w4os-translations
