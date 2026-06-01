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

  // Mobile TOC menu toggle
  function makeTocMenuExpandable() {
    const tocToggle = document.getElementById('toc-toggle');
    tocToggle.addEventListener('click', () => toggleNavigation(tocToggle), true);
  }

  function toggleNavigation(tocToggle) {
    const tocCollapse = document.getElementById('toc-collapse');
    tocCollapse.classList.toggle('show');
    tocToggle.setAttribute('aria-expanded', tocCollapse.classList.contains('show'));
    // Close search when opening nav
    if (tocCollapse.classList.contains('show')) {
      closeSearch();
    }
  }

  // Mobile search toggle
  function makeSearchToggle() {
    const searchToggle = document.getElementById('search-toggle');
    if (!searchToggle) return;
    searchToggle.addEventListener('click', () => toggleSearch(searchToggle), true);
  }

  function toggleSearch(searchToggle) {
    const headerSearch = document.getElementById('header-search-expand');
    if (!headerSearch) return;
    headerSearch.classList.toggle('show');
    const isExpanded = headerSearch.classList.contains('show');
    searchToggle.setAttribute('aria-expanded', isExpanded);
    if (isExpanded) {
      // Close nav when opening search
      const tocCollapse = document.getElementById('toc-collapse');
      const tocToggle = document.getElementById('toc-toggle');
      if (tocCollapse && tocCollapse.classList.contains('show')) {
        tocCollapse.classList.remove('show');
        if (tocToggle) tocToggle.setAttribute('aria-expanded', 'false');
      }
      headerSearch.querySelector('input[type="text"]')?.focus();
    }
  }

  function closeSearch() {
    const headerSearch = document.getElementById('header-search-expand');
    const searchToggle = document.getElementById('search-toggle');
    if (!headerSearch) return;
    headerSearch.classList.remove('show');
    if (searchToggle) searchToggle.setAttribute('aria-expanded', 'false');
  }

  // Close mobile search on Escape or outside click
  function bindSearchDismiss() {
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeSearch();
    });
    document.addEventListener('click', (e) => {
      const headerSearch = document.getElementById('header-search-expand');
      const searchToggle = document.getElementById('search-toggle');
      if (!headerSearch || !headerSearch.classList.contains('show')) return;
      if (!headerSearch.contains(e.target) && e.target !== searchToggle) {
        closeSearch();
      }
    });
  }

  makeTocMenuExpandable();
  makeSearchToggle();
  bindSearchDismiss();

  window.addEventListener('all-documentation-menu-loaded', () => {
    makeMenuExpandable();
  })
})();
