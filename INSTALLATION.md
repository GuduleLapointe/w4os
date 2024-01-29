# Installation

Before installing and configure W4OS plugin, you must already have OpenSimulator up and running, as well as a functional WordPress website.

## OpenSimulator installation

**Please refer to [OpenSimulator](https://opensimulator.org/) documentation to install and configure your simulator.**

- Choose MySQL storage
- To allow default outfits on registration and web user profiles, you must enable user profiles in Robust.HG.ini (update [UserProfilesService], [ServiceList] and [UserProfiles] sections)
- Start  the simulator, create the first (admin) avatar and a first region, and connect in-world to make sure the grid is working properly

## WordPress installation

**Please refer to [WordPress](https://wordpress.org/) documentation to install and configure WordPress.**

- Permalinks need to be enabled (set to any other choice than the default "Plain" setting)
- PHP minimum version: 7.3
- The PHP modules curl, xml, xml-rpc and imagick are needed to allow full functionalty of the plugin (and they are also recommended for WordPress anyway). Without these plugins, some important functionalties will not be available.

## Plugin installation and configuration

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

## Avatar models

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
