# Working in this repository

This renders the TYPO3 documentation. It is a thin layer on top of
[phpDocumentor/guides](https://github.com/phpDocumentor/guides), which sits in
`vendor/phpdocumentor/guides` and is worth reading before changing anything —
much of what looks like our behaviour is theirs, and a fix sometimes belongs
upstream rather than here.

## Commands

The `Makefile` is the single source of truth; `make help` lists everything.
Every target runs in Docker by default. Append `ENV=local` to use the PHP on
your host instead, which is faster.

    make pre-commit-test      # what CI runs: fix-code-style, test, code-style,
                              # static-code-analysis, test-monorepo
    make test-integration     # rendering fixtures (the slow one, ~2 min)
    make test-unit
    make code-style           # check;  make fix-code-style  applies
    make phpstan
    make docs                 # renders Documentation/ into Documentation-GENERATED-temp
    make assets               # rebuilds the theme's CSS and JS

Run `make pre-commit-test` before proposing a commit. `make githooks` installs
it as a pre-commit hook.

## Things that will bite you

**Built assets are committed.** `packages/typo3-docs-theme/resources/public/`
holds the compiled `css/theme.css` and `js/theme.min.js`. Change the SCSS in
`assets/sass/` or the JS in `assets/js/`, then run `make assets` and commit the
result. Never edit the compiled files by hand — the next build overwrites them,
and a PR whose SCSS and CSS disagree ships a change nobody sees. `make assets`
is reproducible: rebuilding an unchanged tree produces byte-identical files, so
a rebuild that shows no diff is the check that a compiled file is genuine.

**Integration fixtures are generated, not written.** Each case under
`tests/Integration/tests*/` has `input/` and `expected/`; running the test
writes `temp/`. To update an expectation, run the test, look at what changed,
and copy `temp/` over `expected/` — never hand-edit `expected/`. Cases under
`tests-full/` compare the whole HTML file, so a change to the `<head>` or the
menu shows up there; the others compare only the content region between
markers. A `temp/` directory is gitignored.

**`--config` is not optional.** `vendor/bin/guides` resolves the project
configuration from the working directory, not from the input path. Rendering a
manual whose `guides.xml` lives in a subdirectory without `--config=<that dir>`
silently renders with default settings — no theme, no interlinks — and the
output looks plausible. Always pass both:

    vendor/bin/guides run <dir> --output=<out> --config=<dir>

**A manual is the real test.** The fixtures are small. Before claiming a
rendering change works, render a real manual against this checkout and compare
it with a render from `main` — the TYPO3 Core Changelog, at around 3900 pages,
is the one that finds things. Use the `php:8.2-cli` container with
`-d memory_limit=4096M`, and mount the checkout and the manual under one root,
because the CLI resolves the input path relative to the working directory.

**The theme knows which manual it renders** by the `interlink-shortcode` in
`guides.xml` — that is what makes a page's permalink, and what the
changelog-specific behaviour keys on. A manual without one has no permalinks,
which several features have to tolerate.

## Layout

`packages/` holds six packages that are sub-split to their own read-only
repositories. Their `composer.json` files are kept in sync by
`monorepo-builder`: change the root `composer.json`, then run `make monorepo`;
`make test-monorepo` validates it.

`packages/typo3-docs-theme` is where almost everything lives — directives, text
roles, node renderers, Twig templates and the SCSS. `packages/typo3-docs-theme-md`
renders Markdown beside the HTML.

## Commits and pull requests

The documentation's commit conventions apply here too: see
[Commit messages](https://docs.typo3.org/permalink/h2document:commit-messages).
In short — prefix the summary with `[TASK]`, `[BUGFIX]` or `[FEATURE]`, explain
in the body why the change is needed rather than what the diff already shows,
and end with the trailers:

    Assisted-by: Claude Opus 5 <noreply@anthropic.com>
    Signed-off-by: Jane Doe

`Assisted-by:` names the tool or model whenever AI helped with more than a
spelling check, and goes before the human's `Signed-off-by:`. Credit a person
with `Co-authored-by:` instead — here that trailer is for people and for
dependabot, never for a model.

A `Releases:` trailer does not belong here: it marks which per-version branches
a change applies to, and this repository has none.

Branch from a freshly fetched `origin/main` and name the branch `task/<what>`.

Pull requests into `main` go through a merge queue — use **Merge when ready**
rather than merging directly. See `Documentation/Developer/Contributing.rst`.
