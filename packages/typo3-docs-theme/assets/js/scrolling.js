document.addEventListener("DOMContentLoaded", function () {
  function adjustScrollMargin() {
    const header = document.querySelector("header");
    const headerHeight = header ? header.offsetHeight : 80; // Default fallback

    document.querySelectorAll("[id]").forEach(el => {
      el.style.scrollMarginTop = `${headerHeight + 10}px`;
    });
  }

  function scrollToAnchor() {
    const hash = window.location.hash.substring(1); // Get anchor without #
    if (!hash || hash === "top") {
      window.scrollTo({ top: 0, behavior: "smooth" });
      return;
    }

    const target = document.getElementById(hash);
    if (target) {
      setTimeout(() => {
        target.scrollIntoView({ behavior: "smooth", block: "start" });
      }, 50); // Delay ensures styles apply
    }
  }

  adjustScrollMargin();
  setTimeout(scrollToAnchor, 100); // Slight delay for rendering

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

    if (activeItem && typeof activeItem.scrollIntoView === "function") {
      activeItem.scrollIntoView({
        behavior: "auto",    // or "smooth" if you prefer
        block: "center",     // or "nearest" if you want minimal scrolling
        inline: "nearest"
      });
    }
  }

  scrollActiveMenuItemIntoView();
  window.addEventListener("resize", adjustScrollMargin);
});
