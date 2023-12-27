..  include:: /Includes.rst.txt

..  _theme-customization:

===================
Theme Customization
===================

The theme provided by this package is prepared to be integrated into the TYPO3
documentation website. Templates are written in Twig_ and uses components
provided by `phpDocumentor/guides`_. The theme is using Bootstrap_ as a
base framework. You can find the theme in ``packages/typo3-docs-theme``.
Templates can be found in :file:`packages/typo3-docs-theme/resource/template/`.

To customize the components provided by `phpDocumentor/guides`_ you have to follow
the directory structure of the templates provided by this package. Every template
can be overwritten by creating a new template with the same name in the same
directory. The template engine will automatically use the new template.

In the template you always have the current ``node`` available. This
node is the element that is currently rendered. The node is an instance of
:php:`phpDocumentor\Guides\Nodes\Node`. Every template has a different specialized
node. Consult the template of the node to see which properties are available.

When you are creating a template for a node that is a container (like a chapter)
you can access the children of the node by using ``node.children``. This is an
array of nodes. You can iterate over this array to render the children using the
``render_node`` function. This function will render the node using the correct
template.

Build Sources
=============

To build the public assets execute the following commands:

..  code-block:: shell

    ddev ssh
    cd packages/typo3-docs-theme
    npm ci
    npm run build

Or use the custom ddev commands:

..  code-block:: shell

    ddev npm-ci
    ddev npm-build


Debug assets
============

You can build the assets for debugging with the following commands:

..  code-block:: shell

    ddev ssh
    cd packages/typo3-docs-theme
    npm ci
    npm run debug

Or use the custom ddev commands:

..  code-block:: shell

    ddev npm-ci
    ddev npm-debug

The generated assets are copied directly into :file:`Documentation-GENERATED-temp/_resources`
and source maps are not removed. Upon inspection in the browsers web developer
tools you can therefore see in which source scss file certain styles were
defined. Before committing you must run :shell:`npm run build` so that you can
commit the generated asset files into the theme.


..  _Bootstrap: https://getbootstrap.com/
..  _phpDocumentor/guides: https://github.com/phpDocumentor/guides
..  _Twig: https://twig.symfony.com/
