#!/bin/bash

if [ ! "$1" ]
then
  echo "Usage:"
  echo "  $0 [remotehost:]/destination/path/to/plugin/" >&2
  exit 1
fi

PGM=$(basename $0)
TMP=/tmp/$PGM.$$

for destination in $@
do
  echo $PGM: updading $destination >&2
  sed -E "s:^([a-z]+)(/|$):/\\1/:" .distignore > $TMP.distignore
  sed -E "s:^([a-z]+)(/|$):/\\1/:" .gitignore > $TMP.gitignore
  rsync --delete -Wavz ./ $destination/ --include=vendor/composer/ --exclude-from=$TMP.distignore --exclude-from=$TMP.gitignore
  result=$?
  rm -f $TMP.gitignore $TMP.distignore
  if [ $result -ne 0 ]
  then
    echo "error $result while deploying to $destination"
    exit $result
  fi
  echo $PGM: $destination updated >&2
done
