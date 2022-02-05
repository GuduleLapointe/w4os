# Flexible helper scripts 2

- version: 2.0
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

## Installation

- move this directory in your root web directory and rename it as helpers, to make it reachable as <http://example.org/helpers/>
- copy includes/config.example.php to includes/config.php
- edit includes/config.php with your grid and database settings
- edit your OpenSim (and Robust or grids) config file(s) accordingly (more on this later, help is on the way)

## Credits

Flexible helper scripts include new code and code rewritten from several sources, in the hope to ease their integration within another platform (e.g. in a WordPress plugin like W4OS).

While most of the code has been created or rewritten from scratch, portions of code may remain from the legacy projects, including:

- [Speculoos' Flexible helper scripts](https://github.com/GuduleLapointe/flexible_helper_scripts)
- [2do HYPEvents](https://2do.pm)
- [OpenSimSearch](https://github.com/kcozens/OpenSimSearch)
- [DTL/NSL Money Server for OpenSim](http://www.nsl.tuis.ac.jp/xoops/modules/xpwiki/?OpenSim%2FMoneyServer)
- [DTL/NSL Helper scripts](http://www.nsl.tuis.ac.jp/xoops/modules/xpwiki/?OpenSim%2FMoneyServer%2FHelper%20Script)
- [OpenSimMaps](https://github.com/hawddamor/opensimmaps)
- [Offline Messaging](http://opensimulator.org/wiki/Offline_Messaging)

Unless otherwise specified inside the files, code is distributed as part of Flexible Helper Scripts package, under Affero GPL v3 license.
