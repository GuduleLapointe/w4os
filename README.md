# Flexible helper scripts 2

- Version: 2.0
- Project URL: <https://github.com/GuduleLapointe/flexible_helper_scripts>
- Donate: <https://w4os.org/donate/>
- Tags: OpenSimulator, Second Life, metaverse, avatar, web interface, helpers
- Requires PHP: 5.5.0
- License: AGPLv3

A compilation of scripts to complement OpenSimulator services or external modules:

- In-world Search
- Currency
- Offline messaging (with mail forwarding)
- Events

Version 2.x is This is a complete rewrite. The initial project was basicly an installer, relying on several other projects repositories. It has been completely rewritten to ease installation, as well as ease inclusion in other projects like [W4OS OpenSim Web Interface](https://w4os.org/).

## Installation

- Move this directory inside your web root directory and rename it as helpers (for example), so it is reachable as <http://example.org/helpers/>
- Copy includes/config.example.php to includes/config.php
- Edit includes/config.php with your grid and database settings
- Install required modules in your OpenSimulator (only fetch the .dll modules and OpenSim .ini files, this project provides all the web-side functionalties)

  - to use search, [OpenSimSearch](https://github.com/kcozens/OpenSimSearch)
  - to use currency, [DTL/NSL Money Server for OpenSim](http://www.nsl.tuis.ac.jp/xoops/modules/xpwiki/?OpenSim%2FMoneyServer)

- Edit your OpenSim (and Robust or grids) config file(s) accordingly (more on this later, help is on the way)

## Credits

Flexible helper scripts include new code and code rewritten from several sources, in the hope to ease their integration within another platform (e.g. in a WordPress plugin like W4OS).

While most of the code has been created or rewritten from scratch, portions of code may remain from the legacy projects, including:

- [w4os OpenSim Web Interface](https://w4os.org/)
- [2do HYPEvents](https://2do.pm)
- [Speculoos' Flexible helper scripts](https://github.com/GuduleLapointe/flexible_helper_scripts)
- [OpenSimSearch](https://github.com/kcozens/OpenSimSearch)
- [DTL/NSL Money Server for OpenSim](http://www.nsl.tuis.ac.jp/xoops/modules/xpwiki/?OpenSim%2FMoneyServer)
- [DTL/NSL Helper scripts](http://www.nsl.tuis.ac.jp/xoops/modules/xpwiki/?OpenSim%2FMoneyServer%2FHelper%20Script)
- [OpenSimMaps](https://github.com/hawddamor/opensimmaps)
- [Offline Messaging](http://opensimulator.org/wiki/Offline_Messaging)

Unless otherwise specified, code is distributed as part of Flexible Helper Scripts package, under Affero GPL v3 license.
