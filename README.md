# Flexible helper scripts

This is an addition to NSL helpers scripts collection to allow better integration in
hypergrid environment.

Developed for Speculoos.world grid, it should be useable as is by other grids
(with config changes of course).

Based on Network System Laboratory scripts version 0.8.1, part of DTL/NSL project

DTL/NSL Project:
  http://www.nsl.tuis.ac.jp/xoops/modules/xpwiki/?OpenSim%2FMoneyServer

DTL/NSL Helper scripts page:
  http://www.nsl.tuis.ac.jp/xoops/modules/xpwiki/?OpenSim%2FMoneyServer%2FHelper%20Script

## INSTALLATION

### Manual
Assuming you already have working helper scripts installed.

Note: `current_helpers_install_path` is the parent path, not the path of helper/ subfolder.
``
mv flexible.helpers /current_helpers_install_path/
cd /current_helpers_install_path/
mv helper/currency.php  helper/currency.php.saved
ln -frs flexible.helpers/flexible.currency.php helper/currency.php
``
The new currency.php script should be compatible with your installations.

New functionalties are only enabled when specific config files are present.

To enable Gloebit currency:
``
cp flexible.helpers/gloebit.config.php.example /your/install/path/config/gloebit.config.php
``

Check gloebit.config.php to fit your needs, but it should work out of the box.

### Automatic
Assuming you don't have working helper scripts or want to replace them. 

- Chose one of the config methods:
  - *Easiest*: `mv hg_helpers_scripts /var/www/html/` (or whatever your web root directory is)
  - *Safest*: (recommended): add this line in your apache config:
  `Alias /helper /opt/opensim/lib/flexible_helper_scripts/helper`
  (replace path with your flexible_helper_scripts directory location, keep "/helper" subfolder even if it does not exiqst for now)
- cd to the new location
- run `./setup_dtl_nsl_scripts.sh` to install, 
  - answer "y" to replace or update dtl nsl core scripts
  - answer "y" to enable Gloebit currency
- edit config/*.php to suit your grid specific settings

(if you run the setup script again, your config.php file will be preserved)
