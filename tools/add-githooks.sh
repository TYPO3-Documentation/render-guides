#!/usr/bin/env bash
set -e

# Get directory of this file.
SCRIPT_DIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )

# Ensure to be in the parent of this directory (where .git is located)
cd $SCRIPT_DIR/../

if [ ! -d .git ] || [ ! -d .git/hooks ] ; then
    echo "There is no .git(/hooks) directory to operate on."
    echo "Run via ./tools/add-githooks.sh on parent directory."
    exit 1;
fi

useGithookTemplate() {
    hook="$1"
    githook=".git/hooks/$hook"
    if [ -f $githook ] ; then
        echo "$githook already exists. Please maintain manually."
    else
        cp ./tools/githook-templates/$hook $githook
        chmod 755 $githook
        echo "Created $githook from template."
    fi
}

useGithookTemplate "pre-commit"
useGithookTemplate "commit-msg"
