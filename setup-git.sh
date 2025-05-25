#!/bin/bash

BASEDIR=$(dirname "$0")
cd "$BASEDIR" || exit 1

echo "This script is intended for developers only, to set up some specific files 
when downloading/updating the project from git repository.
It should not be run on public releases."
echo

if [ ! -e helpers/includes/config.wp.php ]
then
    echo "The file helpers/includes/config.wp.php is missing."
    echo "Please make sure you have downloaded the project from git repository"
    echo "and fetched the latest commits."
    exit 1
fi

if [ -e helpers/includes/config.php ]
then
    diff helpers/includes/config.php helpers/includes/config.wp.php \
    && echo "helpers/includes/config.php is up to date." \
    && exit 0

    echo
    read -p "These changes will be applied to helpers/includes/config.php. Do you want to overwrite it? [y/N] " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]
    then
        cp -f helpers/includes/config.wp.php helpers/includes/config.php \
        && echo "The file helpers/includes/config.php has been updated." \
        || echo "The file helpers/includes/config.php could not be updated."
    else
        echo "The file helpers/includes/config.php has not been updated."
    fi
else 
    cp -f helpers/includes/config.wp.php helpers/includes/config.php \
    && echo "The file helpers/includes/config.php has been created." \
    || echo "The file helpers/includes/config.php could not be created."
fi
