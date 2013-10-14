#!/bin/bash

cd "$(dirname $0)"

testrunner="runtests.sh"

if [ "$1" == "-c" ]; then
    testrunner="runcoverage.sh"
fi

echo "Use flag -c to run coverage."
echo

OXID_VERSION=CE oxPATH=/var/www/oxideshop_ce_50/source/ bash $testrunner mf_cushymocoTestsUnit.php
