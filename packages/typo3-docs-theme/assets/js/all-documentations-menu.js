// mock up for presentation purposes - should be replaced with real data
const exampleDocumentationsJson = JSON.stringify([
  {
    name: 'References',
    href: '#',
    children: [
      { name: 'Changelog', href: '#', versions: [ { name: 'main', href: '#' }, { name: '12.4', href: '#' }, { name: '11.5', href: '#' } ] },
      { name: 'TYPO3 Explained', href: '#', versions: [ { name: 'main', href: '#' }, { name: '12.4', href: '#' }, { name: '11.5', href: '#' } ] },
      { name: 'TCA Reference', href: '#', versions: [ { name: 'main', href: '#' }, { name: '12.4', href: '#' }, { name: '11.5', href: '#' } ] },
      { name: 'TypoScript Reference', href: '#', versions: [ { name: 'main', href: '#' }, { name: '12.4', href: '#' }, { name: '11.5', href: '#' } ] },
      { name: 'TSConfig Reference', href: '#', versions: [ { name: 'main', href: '#' }, { name: '12.4', href: '#' }, { name: '11.5', href: '#' } ] },
      { name: 'ViewHelper Reference', href: '#', versions: [ { name: 'main', href: '#' }, { name: '12.4', href: '#' }, { name: '11.5', href: '#' } ] },
    ]
  },
  {
    name: 'Guides',
    href: '#',
    children: [
      { name: 'Getting started', href: '#', versions: [ { name: 'main', href: '#' }, { name: '12.4', href: '#' }, { name: '11.5', href: '#' } ] },
      { name: 'Localization', href: '#', versions: [ { name: 'main', href: '#' }, { name: '12.4', href: '#' }, { name: '11.5', href: '#' } ] },
      { name: 'Upgrade Guide', href: '#', versions: [ { name: 'main', href: '#' }, { name: '12.4', href: '#' }, { name: '11.5', href: '#' } ] },
      { name: 'Editors Guide', href: '#', versions: [ { name: 'main', href: '#' }, { name: '12.4', href: '#' }, { name: '11.5', href: '#' } ] },
      { name: 'Exceptions', href: '#', versions: [ { name: 'main', href: '#' }, { name: '12.4', href: '#' }, { name: '11.5', href: '#' } ] },
    ]
  },
  {
    name: 'Tutorials',
    href: '#',
    children: [
      { name: 'Sitepackage Tutorial', href: '#', versions: [ { name: 'main', href: '#' }, { name: '12.4', href: '#' }, { name: '11.5', href: '#' } ] },
      { name: 'TypoScript Tutorial', href: '#', versions: [ { name: 'main', href: '#' }, { name: '12.4', href: '#' }, { name: '11.5', href: '#' } ] },
    ]
  },
  {
    name: 'System Extensions',
    href: '#',
    children: [
      { name: 'Adminpanel', href: '#', versions: [ { name: 'main', href: '#' }, { name: '12.4', href: '#' }, { name: '11.5', href: '#' } ] },
      { name: 'SEO', href: '#', versions: [ { name: 'main', href: '#' }, { name: '12.4', href: '#' }, { name: '11.5', href: '#' } ] },
    ]
  },
  {
    name: 'Third Party Extensions',
    href: '#',
  },
  {
    name: 'Contribution',
    href: '#',
    children: [
      { name: 'Core', href: '#' },
      { name: 'Documentation', href: '#' },
      { name: 'typo3.org', href: '#' },
    ]
  },
])

class AllDocumentationMenu extends HTMLElement {
  constructor() {
    super();
    this.data = this.initializeDocumentationsData();

    this.classList.add('all-documentations-menu')

    this.mainButton = this.createMainButton('All documentations');
    this.tooltip = this.createTooltip();

    this.appendChild(this.mainButton);
    this.appendChild(this.tooltip);

    this.popperInstance = null;

    this.mainButton.addEventListener('click', (event) => {
      event.stopPropagation();
      this.toggleTooltip();
    });

    // hide popup on outside click
    document.addEventListener('click', (event) => {
      if (!this.tooltip.hasAttribute('data-show')) {
        return;
      }

      if (event.target?.closest('.all-documentations-menu-tooltip')) {
        return
      }

      this.hideTooltip();
    })
  }

  initializeDocumentationsData() {
    // replace with real data
    return JSON.parse(exampleDocumentationsJson)
  }

  createClassName(name) {
    return `all-documentations-menu-${name}`;
  }

  /** Button */

  createMainButton(text) {
    const element = document.createElement('button')
    element.classList.add(
      'btn', 'btn-light',
      this.createClassName('button'),
    );
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

    if (!documentation.versions || !documentation.versions.length) {
      return listItemElement;
    }

    const versionsElement = document.createElement('div');
    versionsElement.classList.add(this.createClassName('versions'));

    for (const version of documentation.versions) {
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

  createTooltip() {
    const element = document.createElement('div');
    element.classList.add(this.createClassName('tooltip'));
    element.setAttribute('role', 'topoltip');

    const arrowElement = document.createElement('div');
    arrowElement.classList.add(this.createClassName('tooltip-arrow'));
    arrowElement.setAttribute('data-popper-arrow', '')
    element.appendChild(arrowElement);

    const categoriesElement = document.createElement('div');
    categoriesElement.classList.add(this.createClassName('categories'));

    for (const category of this.data) {
      categoriesElement.appendChild(this.createDocumentationCategory(category));
    }

    element.appendChild(categoriesElement);

    return element;
  }

  toggleTooltip() {
    if (this.tooltip.hasAttribute('data-show')) {
      this.hideTooltip();
    } else {
      this.showTooltip();
    }
  }

  showTooltip() {
    this.tooltip.setAttribute('data-show', '');

    this.popperInstance = Popper.createPopper(this.mainButton, this.tooltip, {
      placement: 'bottom',
      modifiers: [
        { name: 'arrow' },
        { name: 'offset', options: { offset: [0, 10] } }
      ],
    });
  }

  hideTooltip() {
    this.tooltip.removeAttribute('data-show');
    if (this.popperInstance) {
      this.popperInstance.destroy();
      this.popperInstance = null;
    }
  }
}

customElements.define('all-documentations-menu', AllDocumentationMenu)
