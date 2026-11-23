#!/usr/bin/env bash
#
# Renders real TYPO3 manuals twice, with this repository and two versions of
# phpDocumentor/guides, and reports how the output differs:
#
#   - "base": the guides checkout at the merge base of its HEAD and BASE
#   - "head": the working tree of the guides checkout, uncommitted changes too
#
# Usage: tools/compare-guides.sh <guides-checkout> [<base-ref>]
#
# Environment:
#   MANUALS  Names of the manuals to render, separated by spaces.
#            Default: the manuals marked "default" in the list below.
#            MANUALS=all renders every manual in the list.
#   ENV      "docker" (default) renders in php:8.2-cli without network, so
#            that both renders see the same answers. "local" uses the php of
#            the host.
#
# The manuals are cloned into .cache/compare-guides/manuals on the first run
# and updated on each later run. Output, logs and reports go to
# .cache/compare-guides as well.

set -euo pipefail

# name | repository | branch | documentation directory | default
MANUAL_LIST='
guide-h2d|https://github.com/TYPO3-Documentation/TYPO3CMS-Guide-HowToDocument.git|main|Documentation|default
reference-tca|https://github.com/TYPO3-Documentation/TYPO3CMS-Reference-TCA.git|main|Documentation|default
reference-typoscript|https://github.com/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript.git|main|Documentation|default
reference-viewhelper|https://github.com/TYPO3-Documentation/TYPO3CMS-Reference-ViewHelper.git|main|Documentation|default
coreapi|https://github.com/TYPO3-Documentation/TYPO3CMS-Reference-CoreApi.git|main|Documentation|default
changelog|https://github.com/TYPO3/typo3.git|main|typo3/sysext/core/Documentation|
exceptions|https://github.com/TYPO3-Documentation/TYPO3CMS-Exceptions.git|main|Documentation|
'

GUIDES=${1:-}
BASE=${2:-origin/main}
ENV=${ENV:-docker}
PHP_IMAGE=php:8.2-cli
PHP_ARGS=(-d memory_limit=4096M -d date.timezone=UTC)

ROOT=$(cd "$(dirname "$0")/.." && pwd)
WORK_RELATIVE=.cache/compare-guides
WORK=$ROOT/$WORK_RELATIVE

fail() {
    echo "compare-guides: $*" >&2
    exit 1
}

[ -n "$GUIDES" ] || fail "usage: $0 <guides-checkout> [<base-ref>]"
[ -d "$GUIDES/packages/guides" ] || fail "$GUIDES is not a checkout of phpDocumentor/guides (no packages/guides)"
[ -f "$ROOT/vendor/autoload.php" ] || fail "vendor/ is missing; run \"make vendor\" first"
GUIDES=$(cd "$GUIDES" && pwd)

# --- Select the manuals -----------------------------------------------------

# Field 2 (repository), 3 (branch) or 4 (documentation directory) of a
# manual. A function, not an associative array: macOS ships bash 3.2.
manual_field() {
    echo "$MANUAL_LIST" | awk -F'|' -v name="$1" -v field="$2" '$1 == name { print $field }'
}

ALL_MANUALS=$(echo "$MANUAL_LIST" | awk -F'|' 'NF { printf "%s ", $1 }')
if [ "${MANUALS:-}" = all ]; then
    SELECTED=($ALL_MANUALS)
elif [ -n "${MANUALS:-}" ]; then
    SELECTED=($MANUALS)
else
    SELECTED=($(echo "$MANUAL_LIST" | awk -F'|' '$5 == "default" { print $1 }'))
fi
for name in "${SELECTED[@]}"; do
    [ -n "$(manual_field "$name" 2)" ] || fail "unknown manual \"$name\"; known: $ALL_MANUALS"
done

# --- Prepare the two versions of guides -------------------------------------

BASE_COMMIT=$(git -C "$GUIDES" merge-base HEAD "$BASE") \
    || fail "cannot find the merge base of HEAD and $BASE in $GUIDES"
HEAD_COMMIT=$(git -C "$GUIDES" rev-parse HEAD)
DIRTY=
[ -z "$(git -C "$GUIDES" status --porcelain -- packages)" ] || DIRTY=" with uncommitted changes"

echo "base: guides $(git -C "$GUIDES" log -1 --format='%h %s' "$BASE_COMMIT")"
echo "head: guides $(git -C "$GUIDES" log -1 --format='%h %s' "$HEAD_COMMIT")$DIRTY"

# Two renders of the same code can only be identical.
if [ "$BASE_COMMIT" = "$HEAD_COMMIT" ] && [ -z "$DIRTY" ]; then
    fail "head is base, so both renders would be the same.
The branch has no commits against $BASE, and packages/ has no uncommitted changes.
Check out a branch with changes in $GUIDES,
or set BASE to the commit to compare with, for example BASE=$(git -C "$GUIDES" rev-parse --short HEAD)^"
fi

rm -rf "$WORK/base" "$WORK/head" "$WORK/output" "$WORK/report"
mkdir -p "$WORK/source/base" "$WORK/source/head" "$WORK/manuals" "$WORK/output" "$WORK/report"
rm -rf "$WORK/source/base/packages" "$WORK/source/head/packages"
git -C "$GUIDES" archive "$BASE_COMMIT" packages | tar -x -C "$WORK/source/base"
cp -a "$GUIDES/packages" "$WORK/source/head/"

for variant in base head; do
    target=$WORK/$variant
    mkdir -p "$target"
    cp -a "$ROOT/vendor" "$target/vendor"
    # The autoloader finds our own packages and tests beside vendor/, and
    # guides-cli loads the extensions from the guides.xml beside it.
    ln -s ../../../packages "$target/packages"
    ln -s ../../../tests "$target/tests"
    ln -s ../../../guides.xml "$target/guides.xml"
    for package in "$target"/vendor/phpdocumentor/*; do
        source=$WORK/source/$variant/packages/$(basename "$package")
        [ -d "$source" ] || continue
        rm -rf "$package"
        cp -a "$source" "$package"
    done
done

# Only the code is replaced; Composer installs nothing. A changed "require"
# is worth knowing about, because a new dependency is then missing.
for composer in "$WORK"/source/head/packages/*/composer.json; do
    package=$(basename "$(dirname "$composer")")
    [ -d "$ROOT/vendor/phpdocumentor/$package" ] || continue
    if ! diff -q <(sed -n '/"require"/,/}/p' "$WORK/source/base/packages/$package/composer.json" 2>/dev/null) \
                 <(sed -n '/"require"/,/}/p' "$composer") > /dev/null; then
        echo "warning: the dependencies of $package differ between base and head;" \
             "new dependencies are not installed" >&2
    fi
done

# --- Fetch the manuals ------------------------------------------------------

for name in "${SELECTED[@]}"; do
    clone=$WORK/manuals/$name
    if [ -d "$clone/.git" ]; then
        git -C "$clone" fetch --quiet --depth 1 origin "$(manual_field "$name" 3)"
        git -C "$clone" checkout --quiet --force FETCH_HEAD
    else
        echo "cloning $name"
        git clone --quiet --depth 1 --filter=blob:none --sparse \
            --branch "$(manual_field "$name" 3)" "$(manual_field "$name" 2)" "$clone"
        git -C "$clone" sparse-checkout set "$(manual_field "$name" 4)"
    fi
done

# --- Render -----------------------------------------------------------------

render() {
    local variant=$1 name=$2
    local docs
    docs=$WORK_RELATIVE/manuals/$name/$(manual_field "$name" 4)
    local arguments=(
        "$WORK_RELATIVE/$variant/vendor/bin/guides" run --no-progress "$docs"
        --output="$WORK_RELATIVE/output/$variant/$name" --config="$docs"
    )
    mkdir -p "$WORK/output/$variant"
    if [ "$ENV" = local ]; then
        (cd "$ROOT" && php "${PHP_ARGS[@]}" "${arguments[@]}")
    else
        docker run --rm --network none --user "$(id -u):$(id -g)" \
            -v "$ROOT:/project" -w /project "$PHP_IMAGE" php "${PHP_ARGS[@]}" "${arguments[@]}"
    fi > "$WORK/output/$variant/$name.log" 2>&1
}

for name in "${SELECTED[@]}"; do
    echo "rendering $name ($(git -C "$WORK/manuals/$name" rev-parse --short HEAD))"
    render base "$name" & base_pid=$!
    render head "$name" & head_pid=$!
    wait "$base_pid" || fail "base render of $name failed, see $WORK_RELATIVE/output/base/$name.log"
    wait "$head_pid" || fail "head render of $name failed, see $WORK_RELATIVE/output/head/$name.log"
done

# --- Compare ----------------------------------------------------------------

# Removes what differs between any two renders: the render time.
mask() {
    sed -E \
        -e 's/[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9:.]+(Z|[+-][0-9:]+)?/TIMESTAMP/g' \
        -e 's/Last rendered:[^<"]*/Last rendered: TIMESTAMP/g' \
        -e 's/[A-Z][a-z]{2}, [0-9]{2} [A-Z][a-z]{2} [0-9]{4} [0-9:]+ [+-][0-9]{4}/TIMESTAMP/g' \
        "$1"
}

# The files below a directory, without "./"; BSD find has no -printf.
list_files() {
    (cd "$1" && find . -type f | sed 's|^\./||' | sort)
}

# Without network, every render fails to load the same inventories, but
# guides reports only some of the identical failures, not always the same.
log_messages() {
    sed -E 's/^\[[^]]*\] //' "$1" \
        | grep -E '^app\.(WARNING|ERROR|CRITICAL)' \
        | grep -vE 'could not be loaded|Could not resolve host' \
        | sort || true
}

echo
printf '%-22s %s\n' manual result
total=0
for name in "${SELECTED[@]}"; do
    report=$WORK/report/$name.diff
    base_out=$WORK/output/base/$name
    head_out=$WORK/output/head/$name
    : > "$report"

    diff <(list_files "$base_out") <(list_files "$head_out") \
        | sed -n -e 's/^< /only in base: /p' -e 's/^> /only in head: /p' >> "$report" || true
    only=$(grep -c '^only in' "$report" || true)

    changed=0
    files=0
    while read -r file; do
        files=$((files + 1))
        [ -f "$head_out/$file" ] || continue
        if ! cmp -s <(mask "$base_out/$file") <(mask "$head_out/$file"); then
            changed=$((changed + 1))
            diff -u <(mask "$base_out/$file") <(mask "$head_out/$file") \
                | sed -e "1s|.*|--- base/$file|" -e "2s|.*|+++ head/$file|" >> "$report" || true
        fi
    done < <(list_files "$base_out")

    messages=$(diff <(log_messages "$WORK/output/base/$name.log") <(log_messages "$WORK/output/head/$name.log") || true)
    if [ -n "$messages" ]; then
        printf '\n=== warnings and errors (< base, > head)\n%s\n' "$messages" >> "$report"
    fi

    if [ "$changed" -eq 0 ] && [ "$only" -eq 0 ] && [ -z "$messages" ]; then
        printf '%-22s identical (%d files)\n' "$name" "$files"
        rm "$report"
    else
        total=$((total + 1))
        result="$changed of $files files differ"
        [ "$only" -eq 0 ] || result="$result, $only only on one side"
        [ -z "$messages" ] || result="$result, warnings differ"
        printf '%-22s %s\n%-22s %s\n' "$name" "$result" '' "$WORK_RELATIVE/report/$name.diff"
    fi
done

echo
if [ "$total" -eq 0 ]; then
    echo "No differences."
else
    echo "Differences in $total of ${#SELECTED[@]} manuals. Rendered output: $WORK_RELATIVE/output/{base,head}/"
fi
