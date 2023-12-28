(() => {
  'use strict';

  // Toggle expand-collapse state of menu item
  function toggleCurrent(event) {
    event.preventDefault();

    const link = event.currentTarget.parentElement;
    const element = link.parentElement;
    const siblings = element.parentElement.parentElement.querySelectorAll('li.active');

    Array.from(siblings).forEach(sibling => {
      if (sibling !== element) {
        sibling.classList.remove('active');
      }
    });

    element.classList.toggle('active');
  }

  // Add toggle icon to a-tags of menu items in .toc navigations
  function makeMenuExpandable() {
    const mainMenues = document.getElementsByClassName('main_menu');

    Array.from(mainMenues).forEach(tocEntry => {
      const links = tocEntry.getElementsByTagName('a');

      Array.from(links).forEach(link => {
        if (link.nextSibling) {
          var expand = document.createElement('span');
          expand.classList.add('toctree-expand');
          expand.addEventListener('click', toggleCurrent, true);
          link.prepend(expand);
        }
      });
    });
  }

  makeMenuExpandable();
})();


document.addEventListener('DOMContentLoaded', () => {
  const currentFirstLevelEntries = document.querySelectorAll('li.toctree-l1.active');

  Array.from(currentFirstLevelEntries).filter(entry => {
    if (entry.textContent.trim().startsWith('TYPO3 Exceptions')) {
      entry.classList.remove('active');
    }
  });
});
