/**
 * YathraNest — Shared utilities
 */
(function () {
  // Hide broken images gracefully (keeps colored media placeholder visible)
  document.querySelectorAll("img").forEach(function (img) {
    img.addEventListener("error", function () {
      if (!img.getAttribute("src")) return;
      img.setAttribute("data-broken", "true");
      img.removeAttribute("src");
    });
  });

  // Current year in footers
  document.querySelectorAll("[data-year]").forEach(function (el) {
    el.textContent = String(new Date().getFullYear());
  });

  // Smooth scroll for same-page anchors
  document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
    anchor.addEventListener("click", function (e) {
      const id = anchor.getAttribute("href");
      if (!id || id === "#") return;
      const target = document.querySelector(id);
      if (!target) return;
      e.preventDefault();
      target.scrollIntoView({ behavior: "smooth", block: "start" });
    });
  });

  // Mark active nav links based on path
  const path = window.location.pathname.replace(/\\/g, "/");
  const file = path.split("/").pop() || "index.html";

  document.querySelectorAll("[data-nav]").forEach(function (link) {
    const href = (link.getAttribute("href") || "").split("/").pop();
    if (href && href === file) {
      link.setAttribute("aria-current", "page");
    }
  });

  // Package listing pages share "Packages" parent highlight
  const packagePages = [
    "kerala-packages.html",
    "south-indian-packages.html",
    "domestic-packages.html",
    "international-packages.html",
    "package-details.html",
  ];
  if (packagePages.indexOf(file) !== -1) {
    document.querySelectorAll('[data-nav="packages"]').forEach(function (link) {
      link.setAttribute("aria-current", "page");
    });
  }
})();
