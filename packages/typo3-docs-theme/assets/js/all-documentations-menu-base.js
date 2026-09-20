
class AllDocumentationsMenuBase extends HTMLElement {
  MAINMENU_JSON_URL = 'https://docs.typo3.org/h/typo3/docs-homepage/main/en-us/mainmenu.json';

  async initializeDocumentationsData() {
    const proxyUrl = this.getAttribute('data-override-url');
    const url = proxyUrl || this.MAINMENU_JSON_URL;
    this.data = [];

    let response;
    try {
      response = await fetch(url);
    } catch (error) {
      this.reportUnavailable(proxyUrl, error);
      return
    }

    if (!response.ok) {
      this.reportUnavailable(proxyUrl);
      return
    }

    let json;
    try {
      // A server that does not run PHP hands the proxy out as a file: its
      // source arrives with a status of 200 and is no menu.
      json = await response.json();
    } catch (error) {
      this.reportUnavailable(proxyUrl, error);
      return
    }

    this.data = json || [];
  }

  // Only docs.typo3.org may ask itself for the menu of all documentation, so
  // a local page asks its own server to pass the question on. That proxy is
  // PHP: a server that only serves files cannot answer it, and the menu stays
  // empty -- which is worth saying once, rather than leaving a bare 404.
  reportUnavailable(proxyUrl, error) {
    if (proxyUrl) {
      this.unavailableMessage = 'This list is served by docs.typo3.org. A local render reaches it only from a server that runs PHP.';
      console.info('The "All documentation" menu is served by docs.typo3.org. A local render needs a server that runs PHP to reach it through _resources/js/menu-proxy.php.');
      return
    }

    this.unavailableMessage = 'This list could not be loaded from docs.typo3.org.';
    console.warn('The "All documentation" menu could not be loaded.', error || '');
  }
}

