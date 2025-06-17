#!/bin/bash

[ ! "$1" ] && echo "usage $0" /path/to/svn/repo && exit 1

svn="$1"
dev=$(git rev-parse --show-toplevel) || exit $?

# dev=/home/magic/domains/w4os.org/www/wp-content/plugins/w4os-dev
# svn=/home/magic/Projects/wordpress/svn/w4os-opensimulator-web-interface
cd $dev && pwd || exit $?
release=$(echo $(grep -h "Version:" *.php | head -1 | cut -d : -f 2))
cd $svn && pwd || exit $?
ls -d tags/$release 2>/dev/null && echo "release $release already deployed" && exit

echo sync $release from $dev to $svn
rsync --delete -Wavz $dev/ $svn/trunk/ --exclude-from $dev/.distignore --exclude-from $dev/.wpignore --exclude assets/ || exit $?
rsync -Wavz $dev/assets/ $svn/assets/ || exit $?
rsync -Wavz trunk/ tags/$release/
svn add tags/$release/
svn status | grep "^\?" | while read f file
do
  svn add "$file"
done
svn status
echo "if result is ok execute:
cd $svn
svn ci -m \"version $release\""
