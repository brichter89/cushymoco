#!/bin/bash

scriptDir="$(dirname $0)"

cd "$scriptDir"

testrunner="runtests.sh"
filter=""

for option in "$@"; do
    case $option in
        '-c')
            rm -rf "$scriptDir/coverage"
            testrunner="runcoverage.sh"
            ;;
        '--filter='*)
            filter="$filter $option"
            ;;
    esac
done

echo "Use flag -c to run coverage."
echo

if [ "$oxADMIN_PASSWD" == "" ]; then
    oxADMIN_PASSWD="password"
fi

if [ "$OXID_VERSION" == "" ]; then
    OXID_VERSION="CE"
fi

if [ "$oxPATH" == "" ]; then
    oxPATH="/var/www/oxideshop_ce_50/source/"
fi

oxADMIN_PASSWD="$oxADMIN_PASSWD" \
    OXID_VERSION="$OXID_VERSION" \
    oxPATH=$oxPATH \
    bash $testrunner $filter mf_cushymocoTestsUnit.php
