#!/bin/bash

if [ -f .phpcfbignore ]
then
  ignore=$(cat .phpcfbignore | tr "\n" ",")
fi
ignore="${ignore}vendor/*,node_modules/*,lib/meta-box,lib/wp-package-updater-lib/wp-package-updater,lib/wp-package-updater-lib/plugin-update-checker/,sources/,*.asset.php,*/*/*.asset.php,*.map,includes/fullcalendar/fullcalendar.*"

PGM=$(basename $0)

echo "# checking minimum PHP required" >&2

minphp=$(minphp 2>/dev/null)
[ "$minphp" =  "" ] && minphp=$(grep "Requires PHP:" readme.txt | sed "s/.*PHP: *//")
[ "$minphp" =  "" ] && read -p "Test minimum PHP: " minphp
php=$(minphp -x || which php)
[ "$php" = "" ] && exit $?

trap 'previous_command=$this_command; this_command=$BASH_COMMAND' DEBUG
trap 'ret=$?; [ $ret -ne 0 ] && echo "$PGM: $previous_command failed (error $ret)" && exit $ret || echo "$PGM: success"' EXIT

echo "# check compatibility with minimum PHP version required $minphp"
phpcs -p . --standard=PHPCompatibility --ignore=$ignore,*js,*css --runtime-set testVersion ${minphp}- \
&& echo "# normalize code" \
&& { phpcbf --standard=WordPress --ignore=$ignore ./ || phpcbf --standard=WordPress --ignore=$ignore ./ ; } \
&& echo "# $minphp composer update" \
&& $php $(which composer) update --no-dev \
&& if [ -f "package.json" ]; then
    echo "# npm build"
    npm run build
  else
    echo "# no package.json, skipping npm build"
fi
