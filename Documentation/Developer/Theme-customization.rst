..  include:: /Includes.rst.txt

..  _theme-customization:

===================
Theme Customization
===================

The theme provided by this package is prepared to be integrated into the TYPO3 documentation
website. Templates are written in twig_ and uses components provided by
`phpDocumentor/guides`_. The theme is using bootstrap_ as a base framework.

To customize the components provided by `phpDocumentor/guides`_ you have to follow
the directory structure of the templates provided by this package. Every template
can be overwritten by creating a new template with the same name in the same
directory. The template engine will automatically use the new template.

In the template you always have the current ``node`` available. This node is the
element that is currently rendered. The node is an instance of
``phpDocumentor\Guides\Nodes\Node``. Every template has a different specialized
node. Consult the template of the node to see which properties are available.

When you are creating a template for a node that is a container (like a chapter)
you can access the children of the node by using ``node.children``. This is an
array of nodes. You can iterate over this array to render the children using the
``render_node`` function. This function will render the node using the correct
template.
