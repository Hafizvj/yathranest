/**
 * YathraNest — Gallery lightbox & accordions
 */
(function () {
  /* Accordion */
  document.querySelectorAll("[data-accordion]").forEach(function (root) {
    root.querySelectorAll(".accordion__trigger").forEach(function (trigger) {
      trigger.addEventListener("click", function () {
        const item = trigger.closest(".accordion__item");
        const expanded = item.classList.contains("is-open");
        const single = root.getAttribute("data-accordion") === "single";

        if (single) {
          root.querySelectorAll(".accordion__item.is-open").forEach(function (openItem) {
            if (openItem !== item) {
              openItem.classList.remove("is-open");
              const t = openItem.querySelector(".accordion__trigger");
              if (t) t.setAttribute("aria-expanded", "false");
            }
          });
        }

        item.classList.toggle("is-open", !expanded);
        trigger.setAttribute("aria-expanded", String(!expanded));
      });
    });
  });

  /* Lightbox */
  let lightbox = document.getElementById("lightbox");
  if (!lightbox) {
    lightbox = document.createElement("div");
    lightbox.id = "lightbox";
    lightbox.className = "lightbox";
    lightbox.setAttribute("role", "dialog");
    lightbox.setAttribute("aria-modal", "true");
    lightbox.setAttribute("aria-label", "Image gallery");
    lightbox.innerHTML =
      '<button type="button" class="lightbox__close" aria-label="Close gallery">&times;</button>' +
      '<button type="button" class="lightbox__nav lightbox__nav--prev" aria-label="Previous image">‹</button>' +
      '<img src="" alt="" />' +
      '<button type="button" class="lightbox__nav lightbox__nav--next" aria-label="Next image">›</button>';
    document.body.appendChild(lightbox);
  }

  const imgEl = lightbox.querySelector("img");
  const closeBtn = lightbox.querySelector(".lightbox__close");
  const prevBtn = lightbox.querySelector(".lightbox__nav--prev");
  const nextBtn = lightbox.querySelector(".lightbox__nav--next");
  let images = [];
  let index = 0;

  function show(i) {
    if (!images.length) return;
    index = (i + images.length) % images.length;
    const item = images[index];
    imgEl.src = item.src;
    imgEl.alt = item.alt || "Gallery image";
  }

  function open(galleryImages, startIndex) {
    images = galleryImages;
    show(startIndex || 0);
    lightbox.classList.add("is-open");
    document.body.classList.add("modal-open");
    closeBtn.focus();
  }

  function close() {
    lightbox.classList.remove("is-open");
    if (!document.querySelector(".modal.is-open")) {
      document.body.classList.remove("modal-open");
    }
    imgEl.src = "";
  }

  closeBtn.addEventListener("click", close);
  prevBtn.addEventListener("click", function () {
    show(index - 1);
  });
  nextBtn.addEventListener("click", function () {
    show(index + 1);
  });

  lightbox.addEventListener("click", function (e) {
    if (e.target === lightbox) close();
  });

  document.addEventListener("keydown", function (e) {
    if (!lightbox.classList.contains("is-open")) return;
    if (e.key === "Escape") close();
    if (e.key === "ArrowLeft") show(index - 1);
    if (e.key === "ArrowRight") show(index + 1);
  });

  document.querySelectorAll("[data-gallery]").forEach(function (gallery) {
    const buttons = Array.from(gallery.querySelectorAll("[data-gallery-item]"));
    const galleryImages = buttons.map(function (btn) {
      const img = btn.querySelector("img");
      return {
        src: btn.getAttribute("data-full") || (img && img.src) || "",
        alt: (img && img.alt) || "",
      };
    });

    buttons.forEach(function (btn, i) {
      btn.addEventListener("click", function () {
        open(galleryImages, i);
      });
    });
  });
})();
