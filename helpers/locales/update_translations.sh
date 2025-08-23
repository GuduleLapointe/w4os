#!/bin/bash

find * -iname "*.php" | egrep -v -e vendor -e node_modules | xargs -d"\n" xgettext --from-code=UTF-8 -o locales/messages.pot

pot=locales/messages.pot

for lang in fr_FR de_DE cy_GB it_IT pt_BR
do
  file="locales/$lang/LC_MESSAGES/messages.po"

  mkdir -p "$(dirname "$file")" && touch $file \
  && gpt-po sync --po "$file" --pot "$pot" \
  && gpt-po translate --po "$file" -l $lang \
  && poedit "$file"

done
