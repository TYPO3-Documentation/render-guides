## Proposed POC Plan: Markdown Changelog Support in render-guides

The core insight from the real files is that the changelog structure is extremely repetitive — nearly every
file is:
  .. include:: /Includes.rst.txt        ← boilerplate, can be dropped
  .. _some-anchor:                      ← can become front matter
  === Title ===                         ← heading
  See :issue:`12345`                    ← link
  Description / Impact / Migration      ← sections
  :php:`ClassName`, :yaml:`key`         ← inline code with syntax type
  .. index:: Tag, NotScanned            ← metadata, can be dropped

This means the actual work is small and targeted. Here's the prioritised approach:
──────
### Step 1 — Silently drop .. include:: and .. index:: (covers 100% of files)

Add a SilentDropParser in the Markdown parser for HTML comment blocks. In Markdown, these two RST lines simply
don't need to exist:
  <!-- these can just be omitted entirely in .md -->

Or alternatively: extend BlockQuoteParser.php to treat HTML block comments (<!-- include -->, <!-- index -->)
as no-ops. Since .. include:: in the real files only ever pulls in Includes.rst.txt (which itself is just a
comment), it can safely be dropped. .. index:: is metadata for Sphinx that render-guides doesn't use either.
Effort: very low — add null-returning parsers for these HTML comment patterns.
──────
### Step 2 — Add missing admonitions: hint, attention, seealso (covers 15 files)

Extend the hardcoded switch in BlockQuoteParser.php with 3 more cases:

  case '[!HINT]':
      return new AdmonitionNode('hint', ...);
  case '[!ATTENTION]':
      return new AdmonitionNode('attention', ...);
  case '[!SEEALSO]':
      return new AdmonitionNode('seealso', ...);

Changelog Markdown files would then use > [!HINT] etc.

Effort: trivial — 3 new switch cases in one file, then a test fixture in tests/Integration/tests/markdown/.
──────
### Step 3 — Add TYPO3 inline roles via an HTML-comment syntax (covers 280+ files)
The roles (:php:, :issue:, :yaml:, :guilabel:, etc.) already have full PHP implementations in . The problem is
the Markdown parser never calls them.
The least invasive POC approach: reuse existing HTML inline parser already in the DI config (HtmlParser.php).
Authors write inline HTML in their .md files:
  The :php:`\TYPO3\CMS\Core\Page\AssetCollector` class...

──────
...stays literally as RST syntax and a new inline span parser is added to guides-markdown.php that recognises
:rolename:\value`` patterns inside paragraph text and dispatches to the existing TextRole implementations.

Effort: medium — one new InlineParsers/RoleParser.php that runs a regex over plain text nodes and calls the
existing TextRole service map.
### Step 4 — Front matter for the anchor + .. _label: (covers ~431 files, cosmetic)

RST hyperlink targets (.. _breaking-107397:) become YAML front matter in Markdown:

  ---
  label: breaking-107397-1756543200
  ---

The FrontMatter system is already wired in via FrontMatterParser.php. A new LabelParser field handler can pick
up label: and register it as an anchor target.
──────
### POC scope summary

 Step                    |            Files fixed | Effort                 | Where in codebase
-------------------------|------------------------|------------------------|----------------------------------
 Drop include + index    |                    431 | Trivial                | New no-op HTML comment parser
 Add 3 more admonitions  |                     15 | Trivial                | 3 lines in BlockQuoteParser.php
 Inline :role:\`` parser |                   280+ | Medium                 | New InlineParsers/RoleParser.php
 Front matter label      |                    431 | Small                  | New FrontMatter/LabelParser.php

Where to start concretely? The admonitions are 15 minutes of work and immediately testable — want me to
implement Step 2 first so you have something to run and verify?
