document.addEventListener("DOMContentLoaded", function () {
  function adjustScrollMargin() {
    const header = document.querySelector("header");
    const headerHeight = header ? header.offsetHeight : 80; // Default fallback

    document.querySelectorAll(".section").forEach(section => {
      section.style.scrollMarginTop = `${headerHeight + 10}px`; // Extra space for visibility
    });
  }

  function scrollToAnchor() {
    const hash = window.location.hash.substring(1); // Get anchor without #
    if (!hash) return;

    const target = document.getElementById(hash);
    if (target) {
      // Check if the target is the first main section (h1)
      const isFirstSection = target.closest(".section")?.querySelector("h1") !== null;

      if (isFirstSection) {
        window.scrollTo({
          top: 0,
          behavior: "smooth"
        });
      } else {
        setTimeout(() => {
          target.scrollIntoView({ behavior: "smooth", block: "start" });
        }, 50); // Delay ensures styles apply
      }
    }
  }

  // Adjust scroll margin on load
  adjustScrollMargin();

  // Handle initial anchor scrolling
  setTimeout(scrollToAnchor, 100); // Slight delay for rendering

  // Handle clicks on internal links
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener("click", function (e) {
      e.preventDefault();
      const targetId = this.getAttribute("href").substring(1);
      history.pushState(null, null, `#${targetId}`); // Update URL without reload
      scrollToAnchor();
    });
  });

  // Adjust when resizing (e.g., switching between desktop & mobile)
  window.addEventListener("resize", adjustScrollMargin);
});
