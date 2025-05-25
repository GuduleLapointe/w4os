## Installation

### Requirements

- OpenSimulator 0.9.x (0.9.2.2 recommended). 0.8.x and earlier version might work and used to, but are definitely not supported anymore
- Latest WordPdress release
- PHP 7.3 or later, and the PHP extensions recommended by WordPress (particularly xmlrpc, curl and ImageMagick )

### OpenSimulator installation

Please check [OpenSimulator](https://opensimulator.org/) documentation to install and configure your simulator.

- Choose MySQL storage
- To allow default outfits on registration and web user profiles, you must enable user profiles in Robust.HG.ini (update [UserProfilesService], [ServiceList] and [UserProfiles] sections)
- Start  the simulator, create the first (admin) avatar and a first region, and connect in-world to make sure the grid is working properly

### WordPress installation

Please check the [WordPress documentation](https://wordpress.org/) for detailed instructions on installing and configuring WordPress.

- Permalinks must be enabled (choose any option other than the default "Plain" setting).
- PHP minimum version: 7.3.
- The PHP modules `curl`, `xml`, `xml-rpc`, and `imagick` are required for full functionality of the plugin (and are also recommended for WordPress). Without these modules, some important features will not be available.

### Plugin Installation and Configuration

- *Note:** If upgrading from a different distribution (e.g. switching from GitHub to the WordPress Plugin Directory), disable the previous version before activating the new one.

1. Download and activate the latest stable release (for the latest development version, follow the instructions in DEVELOPERS.md).
2. Visit the `OpenSimulator > Settings` page in your WordPress admin.
   - Enter your grid name and grid URI (e.g. yourgrid.org:8002, without the "http://").
   - Enter your robust database connection details and submit. If you encounter a database connection error, it might be due to a case-sensitivity issue (see https://github.com/GuduleLapointe/w4os/issues/2#issuecomment-923299674).
3. Set permalinks and the profile page:
   - Visit `OpenSimulator > Settings > Permalinks`, ensure the permalink structure is not set to "Plain," and adjust the W4OS slugs to your preferences.
   - Note the slug chosen for the profile base and create a page with that slug.
4. Visit `OpenSimulator > Settings > Web assets server` and ensure the option is enabled. (If you have a third-party web assets server running, you can disable this option and enter its full URL below.)
5. You should now be able to register a new avatar from the website. Customize your site with shortcodes or blocks, such as Grid Info, Grid Status, or Popular Places (see the complete list and descriptions in `OpenSimulator > Settings > Shortcodes`).

- *Some plugin features require updating corresponding parameters in OpenSimulator itself. These options are documented in the interface. Adjust the INI files accordingly and restart OpenSimulator for any changes to take effect.*

### Avatar models

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

### Troubleshooting

See [TROUBLESHOOTING.md](https://gudulelapointe.github.io/w4os/TROUBLESHOOTING.html) for more information.



