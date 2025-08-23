# Troubleshooting

Before troubleshooting a specific issue, make sure the basics are met:
- The grid is up and running, you can log in-world properly
- The website is up and running
- You can reach your helpers subfolder (e.g. https://example.org/helpers/). It should display the opensim-helpers version.

## Search Not Working

1. **Verify Module Installation**
   - Make sure `OpenSimSearch.Modules.dll` is in your OpenSim bin/ directory

2. **Check Configuration**
   - Ensure both the `[Search]` and `[DataSnapshot]` sections are properly configured in OpenSim.ini
   - Double check the URLs in your configuration (they should point to your helpers installation)

3. **Verify Data Collection**
   - Make sure cron jobs are running (parser.php and eventsparser.php)
   - Check your search database tables to see if they contain data:
     ```sql
     SELECT * FROM regions LIMIT 10;
     SELECT * FROM parcels LIMIT 10;
     ```

4. **Force Data Update**
   - Try restarting the affected region after making configuration changes
   - Manually run `parser.php` to force an update of search data

5. **Viewer Settings**
   - Make sure you're using the right search server in your viewer
   - Some features like places search require clicking "All" or "Anywhere" to display results

### How to Use Search in Viewer

Many users are confused about how to actually use search once it's configured. Here's how:

1. **Places Search**: 
   - Click the Search button in the bottom taskbar of the viewer
   - Select "Places" tab
   - Choose "All" or "Anywhere" in the dropdown
   - Type your search term and press Enter

2. **Land Sales**:
   - Click the Search button
   - Select "Land Sales" tab
   - Set your criteria and press Search
   - Make sure some parcels are actually for sale on your grid

3. **Events**:
   - Click the Search button
   - Select "Events" tab
   - Choose the category or "All" and press Search
   - Note: Events need to be added to the database before they appear

4. **Classifieds**:
   - Click the Search button
   - Select "Classifieds" tab
   - Type a search term and press Enter

If search is properly configured but no results appear, it's likely that either:
- The search data hasn't been collected yet (wait for cron job or run parser.php manually)
- There are no matching items in your grid
- You need to use "All" or "Anywhere" instead of limiting to a specific region

### Classic Search vs. Web Search in Modern Viewers

It's important to understand that there are two different search systems in modern viewers:

1. **Classic Search Tabs** (implemented by this project):
   - Places, Land Sales, Events, and Classifieds tabs
   - These use the OpenSimSearch module and the helpers in this project
   - They are accessed through the individual tabs in the search panel
   - These work with the OpenSimSearch.Modules.dll and the search databases
   - **Configuration**: These use the `SearchURL` parameter in `[Search]` section of OpenSim.ini (simulator config)
   ```ini
   [Search]
     Module = OpenSimSearch
     SearchURL = "http://example.org/helpers/query.php?gk=http://yourgrid.org:8002"
   ```

2. **Global/Web Search** (not implemented in this project yet):
   - In modern viewers like Firestorm, there's also a "Web" or "All" tab
   - This is meant to load a web page with more advanced search capabilities
   - This feature is **not yet implemented** in the helpers
   - This tab will display the page provided by a different search URL setting
   - **Configuration**: This uses the `SearchURL` parameter in `[LoginService]` section of Robust.ini (grid config)
   ```ini
   [LoginService]
     SearchURL = "http://example.org/search.php"
   ```

   **What to put at this URL while waiting for implementation:**
   - You can create a simple HTML page explaining search functionality
   - You can redirect to your grid's website or a custom search page
   - You can set it to a third-party search engine like opensimworld.com
   - Note: Always set this to a valid URL to avoid errors in server logs

When troubleshooting search, make sure you're testing the right type of search and that you've configured the correct URL in the appropriate configuration file:
- Use the specific tabs (Places, Land Sales, Events, Classifieds) to test the functionality provided by these helpers
- The "Web" or "All" tab requires a separate web interface that is not part of this package yet

## Currency Issues

### Gloebit Issues

1. **Certificate/SSL Problems**
   If you're seeing errors like "TrustFailure" or "CERTIFICATE_VERIFY_FAILED":
   
   ```bash
   sudo apt install ca-certificates-mono
   sudo cert-sync /etc/ssl/certs/ca-certificates.crt
   ```
   
   For LetsEncrypt certificates specifically:
   ```bash
   # Download ISRG Root X1 certificate
   wget https://letsencrypt.org/certs/isrgrootx1.pem
   sudo cert-sync isrgrootx1.pem
   ```

2. **Configuration Issues**
   - Verify that `Gloebit.dll` is correctly copied to the bin/ directory
   - Ensure your API keys are correct in OpenSim.ini
   - Check that the `economy` URL in OpenSim.ini points to your helpers installation

### Podex/MoneyServer Issues

1. **MoneyServer Confusion**
   - **Important**: MoneyServer is a standalone executable program, not a DLL
   - You need to download and run MoneyServer.exe separately from OpenSim
   - The simulator module that connects to MoneyServer is called DTLMoneyModule.dll
   - Do not look for a file named "MoneyServer.dll" as it doesn't exist

2. **Running MoneyServer**
   - MoneyServer should run as a separate process like Robust
   - It typically uses a different port (e.g., 8004) than Robust (8002/8003)
   - The process hierarchy should be:
     - Robust.exe (grid services)
     - MoneyServer.exe (currency services)
     - OpenSim.exe (one or more region simulators)

3. **MySQL SSL Issues**
   If you're having connection issues with MySQL, try disabling SSL for the connection:
   
   ```
   [mysqld]
   ssl=0
   skip-ssl
   disable-ssl
   ```

4. **Configuration Mismatch**
   - Make sure MoneyScriptAccessKey in MoneyServer.ini matches the one in config.php
   - Verify database settings in MoneyServer.ini
   - Check that `DTLMoneyModule` (or `DTLNSLMoneyModule`) is properly configured in OpenSim.ini

## Database Issues

### Database Configuration

1. **Multiple Databases**
   - OpenSim helpers can use multiple databases for different functions:
     - Robust database (grid data)
     - Search database (can be the same as Robust)
     - MoneyServer database (separate for Podex/DTL)
   - In config.php, make sure each database section has correct credentials

2. **Database Creation**
   - The Robust database is created during OpenSim installation
   - The Search database tables are created automatically by the helpers
   - For MoneyServer, you may need to create a new database:
     ```sql
     CREATE DATABASE moneyserver;
     GRANT ALL PRIVILEGES ON moneyserver.* TO 'opensim'@'localhost';
     ```

3. **Connection Failures**
   - Verify database credentials in config.php
   - Check that the database server is running and accessible
   - Ensure the user has appropriate permissions

4. **Missing Tables**
   - Tables will be created automatically by the modules, but they need proper permissions
   - If tables are missing, you might need to manually create them

5. **Separate Databases**
   - If using separate databases for different functions (search, currency), make sure each is properly configured
   - For MoneyServer, a separate database is typically used and needs to be created beforehand

## HTTPS/SSL Issues

1. **Connection Failed**
   - If using HTTPS URLs and experiencing connection issues, try switching to HTTP for testing
   - Ensure your web server has a valid SSL certificate

2. **Mono Certificate Issues**
   - Mono has its own certificate store that needs to be updated
   - Use cert-sync to update the mono certificate store (see Gloebit troubleshooting above)

## Configuration Hierarchy

Understanding which configuration goes where can be confusing:

1. **Robust.ini / Robust.HG.ini (Grid-wide settings)**
   - Contains grid-wide settings that affect all regions
   - Set currency symbol and economy URL here
   - Example:
     ```
     [GridInfoService]
       economy = https://example.org/helpers/
     [LoginService]
       Currency = YC$
     ```

2. **OpenSim.ini (Simulator-specific)**
   - Contains settings for regions run by this simulator
   - Each simulator needs its own search and economy configuration
   - You can test changes on one simulator before updating all
   - Example:
     ```
     [Economy]
       economymodule = DTLMoneyModule
       economy = https://example.org/helpers/
     ```

3. **MoneyServer.ini**
   - Completely separate configuration file
   - Only needed if using Podex/DTL currency
   - Must match access key with config.php

## General Troubleshooting

1. **Check Log Files**
   - OpenSim log files (typically in bin/OpenSim.log)
   - Web server error logs
   - MoneyServer logs (if using Podex)

2. **PHP Errors**
   - Enable PHP error reporting in config.php or .htaccess:
     ```php
     error_reporting(E_ALL);
     ini_set('display_errors', 1);
     ```

3. **Permissions**
   - Ensure web server has write access to any directories requiring it
   - Check file permissions on configuration files

4. **Version Compatibility**
   - These helpers are designed for OpenSimulator 0.9.x
   - Some features might not work with older or newer versions
