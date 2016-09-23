#!/bin/sh

INSTALLPATH=$(dirname $(realpath "$0"))
KEEPFILES="config/config.php"

cd "$INSTALLPATH" || exit 1
echo "installing in $INSTALLPATH"

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
