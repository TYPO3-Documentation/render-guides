document.addEventListener("DOMContentLoaded", function () {
  const pageHeader = document.querySelector('.page-header');
  let isScrolled = false;
  function updateHeaderShadow() {
    const scrolled = window.scrollY > 0;
    if (scrolled !== isScrolled) {
      isScrolled = scrolled;
      pageHeader?.classList.toggle('scrolled', scrolled);
    }
  }
  updateHeaderShadow();
  window.addEventListener('scroll', updateHeaderShadow, { passive: true });

  const header = document.querySelector("header");
  const scrollTargets = document.querySelectorAll("[id]");
  function adjustScrollMargin() {
    const headerHeight = header ? header.offsetHeight : 80;
    const value = `${headerHeight + 10}px`;
    scrollTargets.forEach(el => {
      el.style.scrollMarginTop = value;
    });
  }

  function scrollToAnchor() {
    const hash = window.location.hash.substring(1);
    if (!hash || hash === "top") {
      window.scrollTo({ top: 0, behavior: "smooth" });
      return;
    }
    const target = document.getElementById(hash);
    if (target) {
      setTimeout(() => {
        target.scrollIntoView({ behavior: "smooth", block: "start" });
      }, 50);
    }
  }

  adjustScrollMargin();
  setTimeout(scrollToAnchor, 100);

  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener("click", function (e) {
      e.preventDefault();
      const targetId = this.getAttribute("href").substring(1);
      history.pushState(null, null, `#${targetId}`);
      scrollToAnchor();
    });
  });

  function scrollActiveMenuItemIntoView() {
    const menuContainer = document.querySelector(".page-main-navigation nav");
    const activeItem = menuContainer?.querySelector(".main_menu .active");
    if (activeItem && typeof activeItem.scrollIntoView === "function") {
      activeItem.scrollIntoView({ behavior: "auto", block: "center", inline: "nearest" });
    }
  }

  scrollActiveMenuItemIntoView();

  let resizeTimer;
  window.addEventListener("resize", () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(adjustScrollMargin, 100);
  }, { passive: true });
});
