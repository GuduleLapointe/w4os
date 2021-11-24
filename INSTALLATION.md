## Installation

- Robust server must be installed before setting up W4OS.
- To allow users to choose an avatar on registration, you must enable user
  profiles in Robust.ini (update [UserProfilesService], [ServiceList] and
  [UserProfiles] sections)
- You should have a working assets server (see Dependencies section below)

### WordPress configuration

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

### Create avatar models

Avatar models are displayed on new avatar registration and allow new users to start with another appearance than Ruth.

* Check (or change) their naming convention in Admin > OpenSimulator > Settings > Avatar models
* From robust console, create a user named accordingly (for example, "Female Default", Default John", ...).
    ```
    R.O.B.U.S.T. # create user Default John
    Password: ************************
    Email []: (leave empty)
    User ID (enter for random) []:  (leave empty)
    Model name []: (leave empty)
    15:27:58 - [USER ACCOUNT SERVICE]: Account Default John xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx created successfully
    ```
  - A password is needed as you need connect in-world to configure it.
    Choose a strong password, any hack could affect all new users.
  - FirstName or LastName has to match your W4OS Avatar models settings
  - The rest of the name will be displayed in the form, so make it relevant
  - You can leave "Email" and "User ID" blank
  - Leave "Model Name" blank, you are creating a model, not using an existing model to create a user
* Connect in-world as this avatar and change outfit. Any worn clothing or attachment will be passed to the new avatars. Be sure to wear only transfer/copy items.
* Make a snapshot and attach it to this account profile

The model will now appear in new avatar registration form, with its profile picture.

These accounts will be excluded from grid statistics.

### Dependencies

* **Web Asset Server**: the project requires a web asset server to convert simulator assets (profile pictures, model avatars...) and display them on the website. W4OS provides a web assets service, or you can specify an external web assets service URL instead.

* **PHP Modules**: while they are not required, WordPress recommends activating PHP **curl** and **xml** modules. They are also recommended by W4OS for full functionalties.

