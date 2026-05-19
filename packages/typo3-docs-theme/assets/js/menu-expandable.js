(() => {
  'use strict';

  // Toggle expand-collapse state of menu item
  function toggleCurrent(event) {
    event.preventDefault();

    const button = event.currentTarget;
    const element = button.parentElement;

    element.parentElement.parentElement.querySelectorAll('li.active').forEach(active => {
      if (active !== element) {
        active.classList.remove('active');
        const activeButton = active.querySelector(':scope > .toctree-expand');
        if (activeButton) activeButton.setAttribute('aria-expanded', 'false');
      }
    });

    element.classList.toggle('active');
    button.setAttribute('aria-expanded', element.classList.contains('active'));
  }

  // Add toggle icon to a-tags of menu items in .toc navigations
  function makeMenuExpandable() {
    const mainMenues = document.getElementsByClassName('main_menu');

    Array.from(mainMenues).forEach(tocEntry => {
      const links = tocEntry.getElementsByTagName('a');

      Array.from(links).forEach(link => {
        if (link.nextSibling) {
          const expand = document.createElement('button');
          expand.classList.add('toctree-expand');
          expand.setAttribute('aria-expanded', 'false');
          expand.setAttribute('aria-label', `Toggle ${link.textContent.trim()}`);
          expand.addEventListener('click', toggleCurrent, true);
          link.after(expand);
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
  window.addEventListener('all-documentation-menu-loaded', () => {
    makeMenuExpandable();
  })
})();
