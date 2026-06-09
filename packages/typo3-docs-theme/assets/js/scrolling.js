document.addEventListener("DOMContentLoaded", function () {
  const pageHeader = document.querySelector('.page-header');
  const compactHeaderThreshold = 80;
  let hasShadow = false;
  let isCompact = false;
  function updateHeaderShadow() {
    const scrolled = window.scrollY > 0;
    const compact = isCompact ? window.scrollY > 0 : window.scrollY > compactHeaderThreshold;

    // Only write to the DOM when the state actually changes to avoid
    // triggering unnecessary style recalculations on every scroll tick
    if (scrolled !== hasShadow) {
      hasShadow = scrolled;
      pageHeader?.classList.toggle('scrolled', scrolled);
    }
    if (compact !== isCompact) {
      isCompact = compact;
      pageHeader?.classList.toggle('compact', compact);
    }
  }
  updateHeaderShadow();
  window.addEventListener('scroll', updateHeaderShadow, { passive: true });

  // Cache header and scroll targets once — querying the DOM on every
  // resize event would be expensive on pages with many elements
  const header = document.querySelector("header");
  const scrollTargets = document.querySelectorAll("[id]");
  function adjustScrollMargin() {
    const headerHeight = header ? header.offsetHeight : 80; // Default fallback
    const value = `${headerHeight + 10}px`;
    scrollTargets.forEach(el => {
      el.style.scrollMarginTop = value;
    });
  }

  function scrollToAnchor(behavior = "smooth") {
    const hash = window.location.hash.substring(1); // Get anchor without #
    if (!hash) {
      return;
    }
    if (hash === "top") {
      window.scrollTo({ top: 0, behavior });
      return;
    }
    const target = document.getElementById(hash);
    if (target) {
      setTimeout(() => {
        target.scrollIntoView({ behavior, block: "start" });
      }, 50); // Slight delay ensures styles apply
    }
  }

  adjustScrollMargin();
  setTimeout(() => scrollToAnchor("auto"), 100); // Slight delay for rendering

  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener("click", function (e) {
      e.preventDefault();
      const targetId = this.getAttribute("href").substring(1);
      history.pushState(null, null, `#${targetId}`); // Update URL without reload
      scrollToAnchor();
    });
  });

  function scrollActiveMenuItemIntoView() {
    const menuContainer = document.querySelector(".page-main-navigation nav");
    const activeItem = menuContainer?.querySelector(".main_menu .active");
    if (menuContainer && activeItem) {
      menuContainer.scrollTop = activeItem.offsetTop - menuContainer.clientHeight / 2 + activeItem.clientHeight / 2;
    }
  }

  scrollActiveMenuItemIntoView();

  // Debounce resize to avoid running adjustScrollMargin on every
  // pixel change while the user drags the window
  let resizeTimer;
  window.addEventListener("resize", () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(adjustScrollMargin, 100);
  }, { passive: true });
});
