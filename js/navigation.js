/**
 * YathraNest — Mobile Navigation
 */
(function () {
  const drawer = document.getElementById("nav-drawer");
  const toggle = document.querySelector(".nav-toggle");
  const closeBtn = document.querySelector(".nav-drawer__close");
  const backdrop = document.querySelector(".nav-drawer__backdrop");

  if (!drawer || !toggle) return;

  const focusableSelector =
    'a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])';

  function openNav() {
    drawer.classList.add("is-open");
    toggle.setAttribute("aria-expanded", "true");
    document.body.classList.add("nav-open");
    const panel = drawer.querySelector(".nav-drawer__panel");
    const first = panel && panel.querySelector(focusableSelector);
    if (first) first.focus();
  }

  function closeNav() {
    drawer.classList.remove("is-open");
    toggle.setAttribute("aria-expanded", "false");
    document.body.classList.remove("nav-open");
    toggle.focus();
  }

  toggle.addEventListener("click", function () {
    const open = drawer.classList.contains("is-open");
    if (open) closeNav();
    else openNav();
  });

  if (closeBtn) closeBtn.addEventListener("click", closeNav);
  if (backdrop) backdrop.addEventListener("click", closeNav);

  drawer.querySelectorAll("a").forEach(function (link) {
    link.addEventListener("click", closeNav);
  });

  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape" && drawer.classList.contains("is-open")) {
      closeNav();
    }
  });

  const explore = document.querySelector(".nav-explore");
  if (explore) {
    const exploreBtn = explore.querySelector(".nav-explore__btn");
    const exploreMenu = explore.querySelector(".nav-explore__menu");

    function closeExplore() {
      if (!exploreBtn || !exploreMenu) return;
      exploreBtn.setAttribute("aria-expanded", "false");
      exploreMenu.hidden = true;
    }

    function toggleExplore() {
      const open = exploreBtn.getAttribute("aria-expanded") === "true";
      if (open) {
        closeExplore();
        return;
      }
      exploreBtn.setAttribute("aria-expanded", "true");
      exploreMenu.hidden = false;
    }

    exploreBtn.addEventListener("click", function (e) {
      e.stopPropagation();
      toggleExplore();
    });

    document.addEventListener("click", function (e) {
      if (!explore.contains(e.target)) closeExplore();
    });

    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") closeExplore();
    });
  }
})();
