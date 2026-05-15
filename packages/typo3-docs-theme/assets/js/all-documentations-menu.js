
class AllDocumentationsMenu extends AllDocumentationsMenuBase {

  constructor() {
    super();
    this.mainButton = this.createMainButton('All documentation');
    this.appendChild(this.mainButton);

    this.initializeDocumentationsData()
      .then(() => {
        this.setupComponent()
      });
  }

  setupComponent() {
    this.classList.add('all-documentations-menu')
    this.panel = this.createPanel();
    this.appendChild(this.panel);
  }

  createClassName(name) {
    return `all-documentations-menu-${name}`;
  }

  /** Button */

  createMainButton(text) {
    const element = document.createElement('button')
    element.classList.add(
      'btn', 'btn-light', 'd-lg-flex', 'd-none',
      this.createClassName('button'),
    );
    element.setAttribute('popovertarget', 'all-docs-panel');
    element.setAttribute('aria-controls', 'all-docs-panel');
    element.setAttribute('aria-expanded', 'false');
    element.innerHTML = text;

    const icon = document.createElement('i');
    icon.classList.add('fa-solid', 'fa-bars');
    element.prepend(icon);

    return element;
  }

  /** Documentations popup */

  createDocumentationCategoryHeader(text, href) {
    let headerElement;
    if (href) {
      headerElement = document.createElement('a');
      headerElement.setAttribute('href', href)
    } else {
      headerElement = document.createElement('div')
    }

    headerElement.classList.add(this.createClassName('category-header'))
    headerElement.innerHTML = text;

    return headerElement;
  }

  createDocumentationVersionBadge(version) {
    const element = document.createElement('a');
    element.setAttribute('href', version.href)
    element.innerHTML = version.name;
    return element;
  }

  createDocumentationLink(documentation) {
    const listItemElement = document.createElement('li');
    const anchorElement = document.createElement('a');
    anchorElement.setAttribute('href', documentation.href);
    anchorElement.innerHTML = documentation.name;

    listItemElement.appendChild(anchorElement);

    if (!documentation.children || !documentation.children.length) {
      return listItemElement;
    }

    const versionsElement = document.createElement('div');
    versionsElement.classList.add(this.createClassName('versions'));

    for (const version of documentation.children) {
      versionsElement.appendChild(this.createDocumentationVersionBadge(version))
    }

    listItemElement.appendChild(versionsElement);

    return listItemElement;
  }

  createDocumentationCategory(category) {
    const section = document.createElement('div');
    section.classList.add('category');

    const header = this.createDocumentationCategoryHeader(category.name, category.href);
    section.appendChild(header);

    if (!category.children || !category.children.length) {
      return section;
    }

    const docsListElement = document.createElement('ul');
    docsListElement.classList.add(this.createClassName('documentations'))

    for (const child of category.children) {
      docsListElement.appendChild(this.createDocumentationLink(child));
    }

    section.appendChild(docsListElement);

    return section;
  }

  createPanel() {
    const element = document.createElement('div');
    element.id = 'all-docs-panel';
    element.setAttribute('popover', '');
    element.classList.add(this.createClassName('panel'));

    const categoriesElement = document.createElement('div');
    categoriesElement.classList.add(this.createClassName('categories'));

    for (const category of this.data) {
      categoriesElement.appendChild(this.createDocumentationCategory(category));
    }

    element.appendChild(categoriesElement);

    element.addEventListener('toggle', (e) => {
      this.mainButton.setAttribute('aria-expanded', e.newState === 'open');
    });

    element.addEventListener('focusout', (e) => {
      if (!element.contains(e.relatedTarget)) {
        element.hidePopover();
      }
    });

    return element;
  }
}

customElements.define('all-documentations-menu', AllDocumentationsMenu)
