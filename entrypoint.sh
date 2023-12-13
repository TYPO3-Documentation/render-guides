#!/usr/bin/env sh

# INPUT ARGUMENT SPLITTING:
#
# By default, the entrypoint assumes that the PHP entrypoint "/opt/guides/vendor/bin/guides"
# is executed, and all arguments passed on to that PHP script.
#
# When executing Docker via:
#
# $ docker run --rm -v $(pwd):/project -it typo3-docs:local --progress SomeDocsFolder"
#
# then this script receives the input "/opt/guides/vendor/bin/guides --progress SomeDocsFolder"
# as "$@" input. This is passed on then to the PHP binary.
#
# Before the final destination script is executed, this entrypoint performs some basic
# invocation checks, i.e. if Docker is run as root, or as a custom user, and if the
# internal user needs to be changed. This is needed so that the parameters
# "--user=X:Y" (i.e. "--user=$(id -u):$(id -g)" are not required to be passed to Docker.
#
# Several internal commands are allowed to be added as a prefix to alter the entrypoint:
#
# * render           [... arguments/options/input] (default; just as if omitted)
# * migrate          [... arguments/options/input] (migrates Settings.cfg to guides.xml)
# * lint-guides-xml  [... arguments/options/input] (lints all guides.xml files found in the directory)
# * configure        [... arguments/options/input] (allows to alter guides.xml variables)
#
# All of the commands can be prefixed with "verbose" to trigger additional information to be shown:
#
# $ docker run --rm -v $(pwd):/project -it typo3-docs:local verbose migrate SomeDocsFolderWithSettings"
#
# Only those commands shall be provided here that users of the "official Docker container"
# need to perform the rendering. The Container-microservice concept suggests to only have one
# "concern" (entrypoint) per container, so this concept is bending the idea of multiple commands
# and shall be used with care.
#
# ONLY PHP-SCRIPTS will (and shall) be executed by this entrypoint.

MY_UID="$(id -u)"

if [ "$1" = "verbose" ]; then
    echo "Full input arguments:  \$ $@"
    SHELL_VERBOSITY="3"
    # Removes "verbose" from the input argument list
    shift
    echo "Parsed input:          \$ $@"
elif [ -z "${SHELL_VERBOSITY}" ]; then
    SHELL_VERBOSITY="0"
else
    # SHELL_VERBOSITY is triggered via call-time pass (i.e. `SHELL_VERBOSITY=yes docker run ...`)
    SHELL_VERBOSITY="${SHELL_VERBOSITY}"
fi

if [ "${SHELL_VERBOSITY}" -gt 0 ]; then
    echo "SHELL_VERBOSITY is:    ${SHELL_VERBOSITY}"
    echo "UID of executing user: ${MY_UID}"
    echo "Parameter order:       $1 | $2 | $3 | $4 | ..."
fi

ENTRYPOINT_DEFAULT="/opt/guides/vendor/bin/guides"
ENTRYPOINT_SYMFONY_COMMANDS="/opt/guides/packages/typo3-guides-cli/bin/typo3-guides"

# This is intentionally a specified list of allowed scripts.
# If an allowed initial entrypoint is found, shift arguments by one.
if [ "$1" = "migrate" ]; then
    ENTRYPOINT="${ENTRYPOINT_SYMFONY_COMMANDS} migrate"
    shift
elif [ "$1" = "lint-guides-xml" ]; then
    ENTRYPOINT="${ENTRYPOINT_SYMFONY_COMMANDS} lint-guides-xml"
    shift
elif [ "$1" = "configure" ]; then
    ENTRYPOINT="${ENTRYPOINT_SYMFONY_COMMANDS} configure"
    shift
elif [ "$1" = "render" ]; then
    ENTRYPOINT="${ENTRYPOINT_DEFAULT}"
    shift
else
    # Default: "render"; no shifting.
    ENTRYPOINT="${ENTRYPOINT_DEFAULT}"
fi

if [ "${SHELL_VERBOSITY}" -gt 0 ]; then
    # Also pass shell verbosity as a fixed argument to the execution.
    ENTRYPOINT="${ENTRYPOINT} -vvv $@"
    echo "ENTRYPOINT:            \$ ${ENTRYPOINT}"
else
    ENTRYPOINT="${ENTRYPOINT} $@"
fi

if [ "${MY_UID}" -eq "0" ]; then

    UID=$(stat -c "%u" $(pwd))
    GID=$(stat -c "%g" $(pwd))

    if [ "$UID" -eq "0" ]; then
        if [ "${SHELL_VERBOSITY}" -gt 0 ]; then
            echo "Run-as:                root"
            echo "Invocation:            php ${ENTRYPOINT}"
        fi

        php ${ENTRYPOINT}
    else
        if [ "${SHELL_VERBOSITY}" -gt 0 ]; then
            echo "Run-as:                $UID (custom invocation)"
        fi

        addgroup typo3 --gid=$GID;
        adduser -h $(pwd) -D -G typo3 --uid=$UID typo3;

        # su behaves inconsistently with -c followed by flags
        # Workaround: run the entrypoint and commands as a standalone script
        echo "#!/usr/bin/env sh" > /usr/local/bin/invocation.sh
        echo >> /usr/local/bin/invocation.sh
        echo "export SHELL_VERBOSITY=${SHELL_VERBOSITY}" >> /usr/local/bin/invocation.sh
        for ARG in "${ENTRYPOINT}"; do
            printf "${ARG} " >> /usr/local/bin/invocation.sh
        done

        chmod a+x /usr/local/bin/invocation.sh

        if [ "${SHELL_VERBOSITY}" -gt 0 ]; then
            echo "Run-as:                root"
            echo "Invocation:"
            cat /usr/local/bin/invocation.sh
            echo ""
        fi

        su - typo3 -c "/usr/local/bin/invocation.sh"
    fi
else
    if [ "${SHELL_VERBOSITY}" -gt 0 ]; then
        echo "Run-as:                Owner ${MY_UID}"
        echo "Invocation:            php ${ENTRYPOINT}"
    fi

    php ${ENTRYPOINT}
fi
