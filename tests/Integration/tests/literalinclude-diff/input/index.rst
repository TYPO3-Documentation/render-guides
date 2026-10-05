===========================
Literal include with a diff
===========================

The changes from one file to the other:

..  literalinclude:: _Snippets/After.yaml
    :diff: _Snippets/Before.yaml
    :caption: config/sites/my-site/config.yaml

With `:visible-lines: all` the whole diff is shown, nothing folded:

..  literalinclude:: _Snippets/After.yaml
    :diff: _Snippets/Before.yaml
    :visible-lines: all

Two files that are the same show the file itself:

..  literalinclude:: _Snippets/After.yaml
    :diff: _Snippets/After.yaml

A file to compare with that does not exist is an error:

..  literalinclude:: _Snippets/After.yaml
    :diff: _Snippets/Missing.yaml
