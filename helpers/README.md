# OpenSimulator Helpers

![Version 2.5.0](https://badgen.net/badge/Version/2.5.0/blue)
![Stable 2.5.0](https://badgen.net/badge/Stable/2.5.0/green)
![Requires PHP 5.7](https://badgen.net/badge/PHP/5.7/7884bf)
![License AGPLv3](https://badgen.net/badge/License/AGPLv3/552b55)

Collection of PHP scripts to complement OpenSimulator features.

## Description

Collection of PHP scripts to enable OpenSimulator features that are not implemented in the core, like search, currency, events in OpenSimulator grids (see Features below).

Most files are used directly by viewer to allow features not implemented in OpenSimulator, like classifieds, events, etc.

They were initially based on a collection of different projects (see Credits below), but were entirely rewritten to use an unified code and set of parameters, as well as for integration in larger projects like [w4os OpenSim WordPress Interface](https://w4os.org/).

Formerly known as Flexible Helper Scripts.

- Project URL: <https://github.com/magicoli/opensim-helpers>
- Donate: <https://w4os.org/donate/>

### Features

- **In-world Search**: enable standard search in the viewer for places, land for sale, events and classifieds.
- **Events**: sync events from HYPEvents server
- **Currency**: provide helpers for currency (MoneyServer, Gloebit and Podex)
- **Offline messaging**: add mail forwarding option to offline IM (according to user viewer settings)
- **Multi-grid**: can be used for standalone or closed grid as well as to provide a cross-grid search engine
- **Unified library**: rewritten to allow easier integration in bigger projects

### Roadmap

- [ ] Avatar authentication
- [ ] Avatar registration
- [ ] Web profiles
- [ ] Web assets server
- [ ] Web search
- [ ] Grid info
- [ ] Grid status
- [ ] Splash page

## Installation

For detailed installation instructions, please see [INSTALLATION.md](INSTALLATION.md).

If you encounter issues during installation or usage, please refer to [TROUBLESHOOTING.md](TROUBLESHOOTING.md).

### Quick Start Options

#### Option 1: Just enable in-world search (no helpers installation)

If you only need basic in-world search, you can use an external service like [2do directory](http://2do.directory/) without installing the helpers.

1. Download [OpenSimSearch.Module.dll](https://github.com/magicoli/opensim-helpers/tree/master/bin) and put it inside your OpenSim bin/ folder.

2. Add search settings to OpenSim.ini:
  ```
  [Search]
  SearchURL = "https://2do.directory/helpers/query.php?gk=${Const|BaseURL}:${Const|PublicPort}"

  [DataSnapshot]
  index_sims = true
  gridname = "Your Grid"
  snapshot_cache_directory = "./DataSnapshot"
  DATA_SRV_2do = http://2do.directory/helpers/register.php
  ```

3. Restart the simulators.

#### Option 2: WordPress website

If you have a WordPress website, we recommend installing [W4OS Plugin](https://w4os.org/) which includes these helpers along with many other features like avatar registration and web profiles.

#### Option 3: Full helpers installation

For a complete installation with all features (search, currency, events, etc.), see the detailed instructions in [INSTALLATION.md](INSTALLATION.md).

## Credits

Version 2.x is This is a complete rewrite. The initial project was basicly a meta installer, combining several other projects repositories. It has been completely rewritten to ease installation, as well as ease inclusion in other projects like [W4OS OpenSim Web Interface](https://w4os.org/).

While most of the code is new or rewritten from scratch, portions of code may remain from the original projects, including:

- [OpenSimSearch](https://github.com/kcozens/OpenSimSearch)
- [DTL/NSL Money Server for OpenSim](http://www.nsl.tuis.ac.jp/xoops/modules/xpwiki/?OpenSim%2FMoneyServer)
- [DTL/NSL Helper scripts](http://www.nsl.tuis.ac.jp/xoops/modules/xpwiki/?OpenSim%2FMoneyServer%2FHelper%20Script)
- [OpenSimMaps](https://github.com/hawddamor/opensimmaps)
- [Offline Messaging](http://opensimulator.org/wiki/Offline_Messaging)
- [w4os OpenSim Web Interface](https://w4os.org/)
- [2do HYPEvents](https://2do.directory)
- [Speculoos' Flexible helper scripts](https://github.com/magicoli/opensim-helpers)

Unless otherwise specified, code is distributed as part of Flexible Helper Scripts package, under Affero GPL v3 license.
