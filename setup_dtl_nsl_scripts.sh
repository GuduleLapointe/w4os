#!/bin/sh

yesno() {
    default=n
    choice="y/N"
    [ "$1" = "y" ] && default=y && choice="Y/n" && shift
    [ "$@" ] && message="$@" || message="Answer"
    message="$message ($choice) "
    read -p "$message" answer
    [ "$answer" = "" ] && answer=$default
    answer=$(echo "$answer" | tr "[:upper:]" "[:lower:]")
    [ "$answer" = "y" ] && return 0
    [ "$answer" = "yes" ] && return 0
    return 1
}

INSTALLPATH=$(dirname $(realpath "$0"))
PGM=$(basename $0)
mkdir -p tmp || exit 1
TMP=$PWD/tmp/$PGM.$$
trap 'rm -f $TMP $TMP.*' EXIT

cd "$INSTALLPATH" || exit 1
echo "installing in $INSTALLPATH"

echo "saving current customised files"
KEEPFILES=config/*.php
for file in $KEEPFILES
do
  [ -f $file ] || continue
  echo "saving $file"
  rm -f $file.local
  cp -L $file $file.local || exit 1
done
echo

echo "(Re)Install DTL/NSL helper scripts"
echo "   If there is already a version of the scripts, it will be replaced"
echo "   Your config files will be preserved."
echo "   It is mandatory if you have no scripts yet"
if yesno "Download DTL/NSL helper scripts?"
then
  echo "Downloading DTL/NSL scripts archive"
  archive_url=http://www.nsl.tuis.ac.jp/DownLoad/SoftWare/OpenSim/helper_scripts-0.8.1.tar.gz
  archive=tmp/$(basename "$archive_url")

  [ -f $archive ] \
    && echo "archive already downloaded in $archive, remove it before launching install if you want to replace it" \
    || wget -P tmp/ $archive_url || exit 1

  echo "Extracting $archive"
  tar xvfz $archive --strip 1 || exit 1
fi
echo

echo "Replacing core currency script by flexible one"
ln -frs flexible.helpers/flexible.currency.php helper/currency.php
echo

if yesno "Enable Gloebit currency? "
then
  cp flexible.helpers/gloebit.config.php.example config/gloebit.config.php
fi
echo

echo "Restoring local config files"
for file in $KEEPFILES
do
  [ -f $file.local ] || continue
  echo "restoring $file"
  mv $file $file.dist
  mv $file.local $file
done

config=config/config.php

grep -q  "define('WEBSITE_LOGO_URL'," $config \
  || echo "define('WEBSITE_LOGO_URL', '/helper/images/login_screens/logo.png');" >> $TMP.config
grep -q  "define('CURRENCY_MODULE'," $config \
  || echo "define('CURRENCY_MODULE', 'Gloebit');" >> $TMP.config
grep -q  "\$otherRegistrars *=" $config \
  || echo "
//
// Forward regions registrations to other compatible registrars
//
// \$otherRegistrars=array(
// 	'http://metaverseink.com/cgi-bin/register.py',
// );
  " >> $TMP.config
if [ -f $TMP.config ]
then
  egrep -B1000 "XMLGROUP_WKEY|WEBSITE_LOGO_URL|CURRENCY_MODULE" $config > $TMP.config.compiled
  echo >> $TMP.config.compiled
  cat $TMP.config >> $TMP.config.compiled
  echo >> $TMP.config.compiled
  egrep -A1000 "XMLGROUP_WKEY|WEBSITE_LOGO_URL|CURRENCY_MODULE" $config \
  | egrep -v "XMLGROUP_WKEY|WEBSITE_LOGO_URL|CURRENCY_MODULE" >> $TMP.config.compiled
  cat $TMP.config.compiled > config/config.php
fi

#cat $TMP.config
