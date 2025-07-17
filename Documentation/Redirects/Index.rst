..  _redirects:

=========
Redirects
=========

You can create redirects by calling:

..  code-block:: bash

    docker run --rm --pull always -v $(pwd):/project -it ghcr.io/typo3-documentation/render-guides:latest create-redirects-from-git -b 8faf68f9b0d2a6037c86300536eca52287150b43

Changes are saved to :file:`redirects.nginx.conf` and you need to make a manual PR at 
https://github.com/TYPO3GmbH/site-intercept/blob/develop/config/nginx/redirects.conf

To include them there
