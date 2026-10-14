#!/usr/bin/env bash
#
# Renders the classes in input/ with phpDocumentor, with the template and the
# extensions of packages/typo3-api, and compares the JSON that api.typo3.org
# writes for each type with expected/.
#
# To update the expectation, run the test, look at what changed, and copy
# temp/json/ over expected/.
#
# Usage: run.sh <phpDocumentor Docker image>
set -euo pipefail

image="${1:?Pass the phpDocumentor Docker image}"
cd "$(dirname "$0")"

if [ ! -d expected ]; then
    echo "expected/ is missing. Run the test, check temp/json/, and copy it to expected/." >&2
fi

rm -rf temp
mkdir -p temp/.phpdoc
cp -r ../../packages/typo3-api/template temp/.phpdoc/template
cp -r ../../packages/typo3-api/extensions temp/.phpdoc/extensions
cp -r input phpdoc.xml temp/

if ! docker run --rm --user "$(id -u):$(id -g)" -v "$PWD/temp:/data" "$image" --no-progress -v > temp/phpdoc.log 2>&1; then
    cat temp/phpdoc.log
    exit 1
fi

failed=0

# phpDocumentor reports metadata that no template renders as a warning,
# for example after a renamed key.
if grep -iE 'not rendered|\[ERROR\]|\[WARNING\]' temp/phpdoc.log; then
    echo "phpDocumentor reported the problems above." >&2
    failed=1
fi

for extension in t3docs/typo3-api-events t3docs/typo3-api-json; do
    if ! grep -q "\[OK\] $extension:" temp/phpdoc.log; then
        echo "The extension $extension was not loaded." >&2
        failed=1
    fi
done

(cd temp/build/classes && find . -name '*.json' -exec install -D -m 644 {} ../../json/{} \;)
if ! diff -ru expected temp/json; then
    echo "The JSON differs from expected/." >&2
    failed=1
fi

# The HTML pages show the PSR-14 events in both directions.
check_page() {
    if ! grep -q "$2" "temp/build/classes/$1"; then
        echo "temp/build/classes/$1 does not contain \"$2\"." >&2
        failed=1
    fi
}
check_page MyVendor-MyExtension-Service-ThingService.html 'Dispatched events'
check_page MyVendor-MyExtension-Event-AfterThingSavedEvent.html 'Dispatched by'

if [ "$failed" -eq 0 ]; then
    echo "The API test passed."
fi
exit "$failed"
