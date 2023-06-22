# OpenSimulator Helpers

- Version: 2.1.8
- Project URL: <https://github.com/magicoli/opensim-helpers>
- Donate: <https://w4os.org/donate/>
- Tags: OpenSimulator, Second Life, metaverse, avatar, web interface, helpers
- Requires PHP: 7.3
- License: AGPLv3

## Description

Helpers needed to enable common functionalties like search, currency, events in OpenSimulator grids.

They were initially based on a collection of different projects (see Credits below), but were entirely rewritten to use an unified code and set of parameters, as well as for integration in larger projects like [w4os OpenSim WordPress Interface](https://w4os.org/).

Also known as Flexible Helper Scripts.

### Features

- **In-world Search**: enable standard search in the viewer for places, land for sale, events and classifieds.
- **Events**: sync events from HYPEvents server
- **Currency**: provide helpers for currency
- **Offline messaging**: add mail forwarding option to offline IM (according to user viewer settings)
- **Multi-grid**: can be used for standalone or closed grid as well as to provide a cross-grid search engine
- **Unified library**: rewritten to allow easier integration in bigger projects

## Installation

### Option 1: Just enable in-world search (don't install this)

Yes, my first suggestion is not to install this, funny, isn't it? Relying on an external search engine may have advantages:

- For small grids and standalone, it can be confusing to install the whole package, while it's easy to benefit of its main advantage.
- An external engine can collect and distribute data from several grids, which is good for an hypergrid-enabled installation.

[2do directory](http://2do.directory/) provides a cross-grid search engine. This is the easiest solution if you don't have specific needs (and you don't need classifieds, which require access to both Robust and search databases).

1. Download [OpenSimSearch.Module.dll](https://github.com/GuduleLapointe/flexible_helper_scripts/tree/master/bin) and put it inside your OpenSim bin/ folder.

2. Add the following settings to each simulator OpenSim.ini file. Replace "Your Grid" and "yourgrid.org:8002" with your own values.

  ```
  [Search]
  SearchURL = "http://2do.directory/helpers/query.php?gk=http://yourgrid.org:8002"

  [DataSnapshot]
  index_sims = true
  gridname = "Your Grid"
  snapshot_cache_directory = "./DataSnapshot"
  DATA_SRV_2do = http://2do.directory/helpers/register.php
  ;DATA_SRV_OtherEngine = http://example.org/helpers/register.py
  ```

3. Restart the simulators.

### Option 2: WordPress website

If you have a WordPress website, we suggest you to install [W4OS Plugin](https://w4os.org/) instead. It includes these helpers and a lot of other cool features like avatar registration, web profiles, web assets server, grid info (...), along with documented admin pages.

### Option 3: The real stuff

If you want to keep the control, or combine with a wider range of services, here is how to setup your search engine.

1. Copy the needed modules, according to your OpenSim version from this bin/ directory to your OpenSim bin/ directory. Copy only the ones you need, as OpenSim loads them all and will trigger errors if they are not properly configured. (e.g. do not copy MoneyServer if you use Gloebit and vice-versa, do not copy any of those if you don't plan to use currency.)
2. Copy the content of this directory on your web server, in a folder named "helpers" (or anything you want, adjust your settings accordingly). It should be reachable as something like <http://yourgrid.org/helpers/>
3. Rename includes/config.example.php as includes/config.php and edit with your grid and database settings
4. Edit your OpenSim config file for search (adjust your grid name, server url and login uri). Edit MoneyServer.ini or Gloebit.ini according to their instructions.

```
[Search]
SearchURL = "http://yourgrid.org/helpers/query.php?gk=http://yourgrid.org:8002"

[DataSnapshot]
index_sims = true
gridname = "Your Grid"
snapshot_cache_directory = "./DataSnapshot"
DATA_SRV_YourGrid = http://yourgrid.org/helpers/register.php
;DATA_SRV_2do = http://2do.directory/helpers/register.php
```

(you can add several DATA_SRV_* lines if you want to register on multiple search engines)

Use a task scheduler to parse data regularly. It's useless to parse data too often, as they don't change that fast, it's better to keep both search and opensim servers load low and avoid triggering spam reject. With cron, it could be something like this.

```
0 * * * * curl -s http://2do.directory/helpers/parse.php
30 */2 * * * curl -s http://2do.directory/helpers/eventsparser.php
```

## Credits

Version 2.x is This is a complete rewrite. The initial project was basicly a meta installer, combining several other projects repositories. It has been completely rewritten to ease installation, as well as ease inclusion in other projects like [W4OS OpenSim Web Interface](https://w4os.org/).

While most of the code is new or rewritten from scratch, portions of code may remain from the original projects, including:

- [OpenSimSearch](https://github.com/kcozens/OpenSimSearch)
- [DTL/NSL Money Server for OpenSim](http://www.nsl.tuis.ac.jp/xoops/modules/xpwiki/?OpenSim%2FMoneyServer)
- [DTL/NSL Helper scripts](http://www.nsl.tuis.ac.jp/xoops/modules/xpwiki/?OpenSim%2FMoneyServer%2FHelper%20Script)
- [OpenSimMaps](https://github.com/hawddamor/opensimmaps)
- [Offline Messaging](http://opensimulator.org/wiki/Offline_Messaging)
- [w4os OpenSim Web Interface](https://w4os.org/)
- [2do HYPEvents](https://2do.pm)
- [Speculoos' Flexible helper scripts](https://github.com/GuduleLapointe/flexible_helper_scripts)

Unless otherwise specified, code is distributed as part of Flexible Helper Scripts package, under Affero GPL v3 license.
