#!/bin/bash

TESTDIR="$(dirname $0)"

cd "$TESTDIR"

__DEBUG=""
__OPTIONS=""
__VERBOSE="--verbose"
__COVERAGE=""
__CONFIG="--configuration=mf_cushymocoTestsUnit.xml"
__FILTER=""
__BOOTSTRAP=""
__START="${TESTDIR}/mf_cushymocoTestsUnit.php"
__ARGUMENTS=""

showHelp() {
    echo "Usage:"
    echo "    $(basename $0) [<options>]"
    echo ""
    echo "Options:"
    echo "    --no-verbose                    No verbose output"
    echo "    --verbose=[no|off|false]        Alias for --no-verbose"
    echo "    --run-coverage             -c   Alias for --coverage-html \"\${TESTDIR}/coverage\""
    echo "    --debug                    -d   Use env-variable 'XDEBUG_CONFIG=\"idekey=phpunit\"'"
    echo "    --help                     -h   Show this help"
    echo ""
    echo "You can also use all options of php-unit cli (see 'phpunit --help')."
    echo ""
}

for option in "$@"; do
    case $option in
        '--verbose=no' | '--verbose=off' | '--verbose=false'|'--no-verbose')
            __VERBOSE=""
            ;;
        '--run-coverage' | '-c')
            rm -rf "$TESTDIR/coverage"
            __COVERAGE="--coverage-html \"${TESTDIR}/coverage\""
            ;;
#        '--configuration='*)
#            __CONFIG="$option"
#            ;;
#        '--bootstrap='*)
#            __BOOTSTRAP="$option"
#            ;;
#        '--start='*)
#            __START="${option:8}"
#            ;;
        '--debug' | '-d')
            __DEBUG="XDEBUG_CONFIG=\"idekey=phpunit\""
            ;;
        '--help' | '-h')
            showHelp
            exit
            ;;
        *)
            __OPTIONS="$__OPTIONS $option"
            ;;
    esac
done

echo "Use flag -c to run coverage."
echo ""

if [ "$oxADMIN_PASSWD" == "" ]; then
    oxADMIN_PASSWD="password"
fi

if [ "$OXID_VERSION" == "" ]; then
    OXID_VERSION="CE"
fi

if [ "$oxPATH" == "" ]; then
    oxPATH="/var/www/oxideshop_ce_50/source/"
fi

if [ "$oxMETADATA" == "" ]; then
    oxMETADATA="$TESTDIR/../metadata.php"
fi


cmd=$(echo "$__DEBUG" \
    oxADMIN_PASSWD="$oxADMIN_PASSWD" \
    oxMETADATA="$oxMETADATA" \
    OXID_VERSION="$OXID_VERSION" \
    oxPATH="$oxPATH" \
    php -d 'memory_limit=1024M' /usr/bin/phpunit \
        $__OPTIONS $__VERBOSE $__COVERAGE $__CONFIG $__FILTER $__BOOTSTRAP $__START
)

echo $cmd
echo ""

eval "$cmd"
