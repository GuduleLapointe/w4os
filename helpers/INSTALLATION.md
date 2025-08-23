# OpenSim Helpers Installation

If you only need in-world search (places, land for sale, events), do not install the helpers, follow "Option 1" instructions in README.md to use 2do.directory service instead.

If you use w4os WordPress plugin, you do not need to install helpers, they are already provided by the plugin.

Proceed to full installation if you have at least one of these requirements
- you need to implement in-world currency
- you need to implement classifieds
- you prefer to implement your own search engine

Even in one of these cases, it's a good idea to first use 2do directory service only, to make sure the basic requirements are met before proceeding to the full helpers install.

## Get OpenSimulator up and running

Download the latest OpenSimulator release from http://opensimulator.org/wiki/Download

Extract the archive in a location outside your web root directory, e.g. `/opt/opensim/` or `/usr/local/share/opensim`. Standard OpenSimulator installation contains configuration files with db credentials and other sensitive data, they cannot be accessible directly from your website.

Follow instructions in Robust(.HG).ini to get the grid up and running.

**Keep it simple and stupid**: for a new installation, first only change the basic settings: BaseURL, Public and Private ports, ConnectionString. The other settings will be changed later according to helpers configuration.

OpenSim Helpers should be compatible with any OpenSimulator 0.9.x release. Earlier versions are not supported anymore.

## Add helpers/ on your website

Copy the opensim helpers directory as helpers/ in your website document root.

E.g. if your document root is `/var/www/html`, this directory should be in `/var/www/html/helpers/` and should be reachable by calling https://example.org/helpers/

Copy `includes/config.example.php` as `includes/config.php` and adjust values according to your Robust settings.

Required:
- OPENSIM_GRID_NAME ("Your Grid")
- OPENSIM_LOGIN_URI ("yourgrid.org:8002")
- ROBUST_DB credentials (also used for standalone simulators)

Optional:
- search settings
- currency settings

### Database Configuration

The helpers need access to various databases. You can use the same database as your Robust installation for everything, but for better security and organization, you might want to use separate databases:

- **Robust DB**: Main OpenSimulator database (required)
- **Search DB**: For search functionality (required if using local search)
- **Currency DB**: For Podex/MoneyServer (required if using this currency system)

Each database configuration in `config.php` needs host, database name, username and password.

At this stage, the helpers should be accessible and working. Most of them would only display a blank page if called directly.

Use cron or crontab to trigger the database update on a regular basis
```cron
0 * * * * curl -s http://example.org/helpers/parser.php
30 */2 * * * curl -s http://example.org/helpers/eventsparser.php
```

## Install required OpenSim modules (dll)

According to your needs, copy modules provided in addons/bin in your opensim bin/ directory (alongside Robust.exe) or download them from their original authors.

**Only copy the modules you actually need**: some of them might crash your simulator if not configured.

To enable **in-world search**, you need:
- `OpenSimSearch.Modules.dll`

To enable **Gloebit currency**
- `Gloebit.dll`

To enable **Podex currency** (or fake currency)
- Install and run an instance of MoneyServer (this is an executable, not a DLL)
- Follow MoneyServer instruction to configure, create a certificate and a Banker avatar

## Configure Robust grid-wide settings (or standalone simulator)

According to your setup, you need to adjust some settings in one of these files:
- `Robust.HG.ini` (grid with hypergrid enabled)
- `Robust.ini` (private grid, hypergrid not enabled)
- `config-include/StandaloneCommon.ini` (standalone simulator, no grid)

```Robust.HG.ini
[LoginService]
  ; Currency = YC$ ;; Your Currency symbol, optional

  ;; V3 viewer search, not implemented yet in helpers
  ;; This is not same as SearchURL value in OpenSim.ini
  ; SearchURL = https://example.org/helpers/search.php

[GridInfoService]
  economy = https://example.org/helpers/

  ;; V3 viewer search, not implemented yet in helpers
  ;; This is not same as SearchURL value in OpenSim.ini
  ; search = https://example.org/helpers/search.php
```

## Configure simulators (for grids only)

Each simulator also needs specific settings. They are usually set in
- `OpenSim.ini`
- or `config-include/StandaloneCommon.ini`

### Search Configuration

To enable in-world search, add the following configuration to each simulator:

```OpenSim.ini
[Search]
  Module = OpenSimSearch
  SearchURL = "http://example.org/helpers/query.php?gk=http://yourgrid.org:8002"

[DataSnapshot]
  index_sims = true
  gridname = "Your Grid"
  ;; Multiple data servers can be enabled to register on multiple engines
  DATA_SRV_YourGrid = "https://example.org/helpers/register.php"
  DATA_SRV_2do = "http://2do.directory/helpers/register.php"
  ; DATA_SRV_OtherEngine = "http://example.org/register.php"
```

The search system works in two parts:
1. The `[DataSnapshot]` section tells the simulator to send parcel and region data to the specified helper URLs
2. The `[Search]` section tells the simulator where users' search queries should be sent

Make sure both sections are properly configured for search to work correctly.

### Currency Configuration

#### For Gloebit currency:

Gloebit is a third-party currency provider that works across multiple grids. It requires registering on the [Gloebit website](https://www.gloebit.com/) to get API keys.

```OpenSim.ini
[Economy]
  economymodule = Gloebit
  economy = https://example.org/helpers/
  SellEnabled = true
  ; PriceUpload = 0
  ; PriceGroupCreate = 0
[Gloebit]
  Enabled = true
  GLBEnvironment = production
  GLBKey = (your Gloebit app key)
  GLBSecret = (your Gloebit app secret)
  GLBOwnerName = Banker Name
  GLBOwnerEmail = banker@example.org
  ; GLBSpecificConnectionString = "Data Source=localhost;Database=gloebit;User ID=opensim;Password=your_password;Old Guids=true;"
```

If you have issues with Gloebit showing "TrustFailure" or "CERTIFICATE_VERIFY_FAILED" errors, please see the TROUBLESHOOTING.md file for specific instructions on fixing certificate issues on Linux.

#### For Podex or fake currency:

This method requires running a separate MoneyServer instance that handles the currency transactions.

Step 1: Install and configure MoneyServer
- Download MoneyServer (not included in the helpers - you'll need to get it from the original source)
- **Important note**: MoneyServer is a standalone executable (.exe), not a DLL. Don't look for "MoneyServer.dll" as it doesn't exist
- Run MoneyServer.exe as a separate process on a different port than Robust (e.g., port 8004)
- Your processes should include:
  - Robust.exe (grid services)
  - MoneyServer.exe (currency services)
  - OpenSim.exe (region simulators)

Step 2: Configure OpenSim to use MoneyServer
```OpenSim.ini
[Economy]
  economymodule = DTLMoneyModule
  ; economymodule = DTLNSLMoneyModule ;; for some MoneyServer versions
  
  economy = https://example.org/helpers/
  SellEnabled = true
  ; PriceUpload = 0
  ; PriceGroupCreate = 0
```

Step 3: Configure MoneyServer settings
```MoneyServer.ini
;; See additional parameters in MoneyServer.ini.example
[MoneyServer]
  EnableScriptSendMoney = true
  MoneyScriptAccessKey = 1234567890 ; CHANGE THIS VALUE, copy it in includes/config.php
[MySql]
  hostname = localhost
  database = moneyserver ; Create this database separately
  username = opensim
  password = (your password)
```

Make sure the MoneyScriptAccessKey value in MoneyServer.ini matches the one in your config.php file.

## Load new configuration

- Restart Robust instance
- Restart MoneyServer (if using Podex/DTL currency)
- Restart all simulator instances
- Enable search for at least one parcel
- Set at least one parcel for sale
- Test in-world search, land sale, object sales...

**Note:** the search database is refreshed by a cron task. Some data might not be available immediately. In the testing phase, it might be useful to restart region after a test change to ensure search data is immediately updated.

See TROUBLESHOOTING.md if you encounter issues.
