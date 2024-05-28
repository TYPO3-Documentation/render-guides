..  include:: /Includes.rst.txt

..  _AjaxVersions:

=====================
AJAX version switcher
=====================

Rendered documentation provides a version switcher on top of the
navigation.

For documentation deployed on `docs.typo3.org` clicking on the version
switcher will perform an AJAX API request that lists all available
versions and languages for the current documentation.

For local rendering however, there are two issues that will lead to
the version switcher not working properly:

* Rendered HTML files viewed via the `file:///` notation may not
  execute any JavaScript due to security considerations/configuration
  of your browser.
* When viewing HTML files via `localhost` or a `.ddev.site` webserver,
  the AJAX call will fail due to `CORS <https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS>`__
  (Cross-Origin Resource Sharing) security restrictions.

Developers of the `render-guides` project may need to simulate menu rendering though.

For them, navigation can be proxied and simulated.

..  _AjaxVersions-proxy:

Making the proxy available
==========================

When using the DDEV integration to view the documentation, the DocumentRoot of the
DDEV webserver is set to `Documentation-GENERATED-temp`. This does not
contain active PHP files by default.

The file :file:`packages/typo3-docs-theme/assets/js/versions-proxy.php` in the
repository of this project (`<https://github.com/TYPO3-Documentation/render-guides>`)
can act as a simple proxy. You can copy or symlink that file into your `Documentation-GENERATED-temp`
directory, so that it is callable with a URL like:

..  code::

    https://render-guides.ddev.site/versions-proxy.php?url=https://docs.typo3.org/m/typo3/tutorial-getting-started/12.4/en-us/Concepts/Index.html

The PHP proxy passes the URL parameter `url` on to the actual `docs.typo3.org` API endpoint,
and returns its output locally.

Once the proxy PHP file is in place, the default values of the rendering will take effect
already. See :ref:`AjaxVersions-data-attributes` on how to fine-tune this.

Details on how the version switcher is implemented
==================================================

Local rendering is automatically detected via the absence of an environment variable
`TYPO3AZUREEDGEURIVERSION` (see :ref:`deploy-azure-assets`).

This allows a Twig function `isRenderedForDeployment` (defined in
:file:`packages/typo3-docs-theme/src/Twig/TwigExtension.php` of this repository) to
conditionally generate output. The Twig template file
`packages/typo3-docs-theme/resources/template/structure/navigation/navigationHeader.html.twig`
makes use of that function to define some default HTML data-attributes for out-of-the-box
version witcher simulation, once the PHP proxy URL can be called without a 404 error.

The JavaScript is contained in :file:`packages/typo3-docs-theme/assets/js/versions.js` and
contains the code for parsing the JSON response of the API, and sorting the keys appropriately.
More details can be found inside the code comments of that file.

When changing this JavaScript, the assets must be rebuilt (:bash:`ddev npm-build`) and
documentation must be rendered with the built assets (:bash:`make docs`).

The JavaScript file also contains some logic to set names for known published translations.
If a static name is not available, it is resolved to the ISO-code (like "de-de"). The
TYPO3 Documentation team can not easily access active server-side code to get an automatic
list of languages.

..  _AjaxVersions-data-attributes:
Configuring HTML data-attributes
================================

The following data-attributes on the HTML element :html:`<div id="toc-version">` are available:

*  `data-override-url-self` - This is a full URL starting with `https://docs.typo3.org` which
   is used as the simulated page from which the version switching information is retrieved for.
   For example, `https://docs.typo3.org/m/typo3/tutorial-getting-started/12.4/en-us/Concepts/Index.html`.

*  `data-override-url-proxy` - Points to the full URI of the :ref:`AjaxVersions-proxy` PHP proxy.
   This is called due to CORS reasons. The default of this attribute is set to
   `https://render-guides.ddev.site/versions-proxy.php?url=`. Unless this file is manually
   copied to this URL, the proxy will return a 404 failure and inform about the non-working
   version switcher.

Seeing the simulated output
===========================

Once the PHP proxy is in place, open the rendered documentation HTML file in your DDEV/webserver
environment (not using a `file:///` syntax, because then JavaScript may unavailable), for example

..  code::

    https://render-guides.ddev.site/Developer/AjaxVersions.html

You can then also use the browser's JavaScript console to manipulate the used URLs for
rendering, for example:

..  code::

    document.getElementById('toc-version').setAttribute('data-override-url-self', 'https://docs.typo3.org/other/t3docs/render-guides/main/en-us/Developer/InterlinkInventories.html');

This must be performed before actually clicking the version switcher link, because
the remote AJAX request is only performed once and then never again without reloading the page.
