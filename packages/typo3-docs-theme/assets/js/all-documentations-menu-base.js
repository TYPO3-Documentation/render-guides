
class AllDocumentationsMenuBase extends HTMLElement {
  MAINMENU_JSON_URL = 'https://docs.typo3.org/h/typo3/docs-homepage/main/en-us/mainmenu.json';

  async initializeDocumentationsData() {
    const url = this.getAttribute('data-override-url') || this.MAINMENU_JSON_URL;
    const response = await fetch(url)
    if (!response.ok) {
      this.data = [];
      return
    }

    const json = await response.json();
    this.data = json || [];
  }
}

