#!/bin/sh

KEEPFILES="config/config.php"

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
TMP=/tmp/$PGM.$$
trap 'rm -f $TMP $TMP.*' EXIT

cd "$INSTALLPATH" || exit 1
echo "installing in $INSTALLPATH"
echo
echo "Download DTL/NSL helper scripts?"
echo "If there is already a version of the scripts, it will be replaced"
echo "Your config files will be preserved."
echo "It is mandatory if you have no scripts yet"
yesno "Download DTL/NSL helper scripts?" || exit 0

mkdir -p tmp || exit 1

echo "saving current customised files"
for file in $KEEPFILES
do
  [ -f $file ] || continue
  echo "saving $file"
  rm -f $file.local
  cp -L $file $file.local || exit 1
done

echo "Downloading DTL/NSL scripts archive"
archive_url=http://www.nsl.tuis.ac.jp/DownLoad/SoftWare/OpenSim/helper_scripts-0.8.1.tar.gz
archive=tmp/$(basename "$archive_url")

[ -f $archive ] \
  && echo "archive already downloaded in $archive, remove it before launching install if you want to replace it" \
  || wget -P tmp/ $archive_url || exit 1

echo "Extracting $archive"
tar xvfz $archive --strip 1 || exit 1

echo "restoring local config files"
for file in $KEEPFILES
do
  [ -f $file.local ] || continue
  echo "restoring $file"
  mv $file $file.dist
  mv $file.local $file
done
