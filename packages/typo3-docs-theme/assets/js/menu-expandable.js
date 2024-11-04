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
          expand.setAttribute('tabindex', '0');
          expand.addEventListener('click', toggleCurrent, true);
          expand.addEventListener('keydown', (e) => {
            if (e.key === "Enter") {
              toggleCurrent(e)
            }
          }, true);
          link.prepend(expand);
        }
      });
    });
  }

  // Adds the EventListener for the toggle Button of the complete menu (mobile)
  function makeTocMenuExpandable() {
    const tocToggle = document.getElementById('toc-toggle');
    tocToggle.addEventListener('click', () => toggleNavigation(tocToggle), true);
  }

  function toggleNavigation(tocToggle) {
    const tocCollapse = document.getElementById('toc-collapse');
    tocCollapse.classList.toggle('show');
    tocToggle.setAttribute('aria-expanded', tocCollapse.classList.contains('show'));
  }

  makeTocMenuExpandable();
  makeMenuExpandable();
})();
