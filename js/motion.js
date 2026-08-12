/**
 * YathraNest — Motion
 * Intro choreography, scroll reveals, magnetic hover, cursor, parallax
 */
(function () {
  const reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const finePointer = window.matchMedia("(hover: hover) and (pointer: fine)").matches;

  if (reduce) return;

  document.documentElement.classList.add("has-motion");

  function ready() {
    document.documentElement.classList.add("is-ready");
    document.body.classList.add("is-ready");
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", function () {
      requestAnimationFrame(ready);
    });
  } else {
    requestAnimationFrame(ready);
  }
  setTimeout(ready, 800);

  /* Scroll reveals */
  const revealEls = document.querySelectorAll(
    ".section-header, .card, .destination-card, .benefit-item, .promo-split, .cta-band, .investment-banner"
  );

  if ("IntersectionObserver" in window && revealEls.length) {
    const io = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) return;
          entry.target.classList.add("is-in");
          io.unobserve(entry.target);
        });
      },
      { threshold: 0.16, rootMargin: "0px 0px -8% 0px" }
    );

    revealEls.forEach(function (el) {
      const parent = el.parentElement;
      const siblings = parent ? Array.prototype.slice.call(parent.children) : [];
      const idx = Math.max(0, siblings.indexOf(el));
      el.style.setProperty("--reveal-delay", idx * 70 + "ms");
      io.observe(el);
    });
  }

  setTimeout(function () {
    document.documentElement.classList.add("is-live");
  }, 1800);

  setTimeout(function () {
    revealEls.forEach(function (el) {
      el.classList.add("is-in");
    });
  }, 4000);

  /* Hero parallax */
  const visualImg = document.querySelector(".hero-v2__visual img");
  const visual = document.querySelector(".hero-v2__visual");
  let scrollY = window.scrollY;
  let ticking = false;

  function onScroll() {
    scrollY = window.scrollY;
    if (!ticking) {
      requestAnimationFrame(updateParallax);
      ticking = true;
    }
  }

  function updateParallax() {
    ticking = false;
    if (!visualImg || !visual) return;
    const y = Math.min(scrollY, 600);
    visual.style.transform = "translate3d(0," + y * 0.18 + "px,0)";
    visualImg.style.transform = "scale(" + (1.05 + y * 0.00015) + ")";
  }

  window.addEventListener("scroll", onScroll, { passive: true });

  if (!finePointer) return;

  /* Custom cursor */
  const cursor = document.createElement("div");
  cursor.className = "cursor";
  cursor.setAttribute("aria-hidden", "true");
  cursor.innerHTML = '<span class="cursor__dot"></span><span class="cursor__ring"></span>';
  document.body.appendChild(cursor);

  const dot = cursor.querySelector(".cursor__dot");
  const ring = cursor.querySelector(".cursor__ring");
  let mouseX = 0;
  let mouseY = 0;
  let ringX = 0;
  let ringY = 0;
  let cursorOn = false;

  document.addEventListener(
    "mousemove",
    function (e) {
      mouseX = e.clientX;
      mouseY = e.clientY;
      if (!cursorOn) {
        cursorOn = true;
        ringX = mouseX;
        ringY = mouseY;
        document.documentElement.classList.add("is-cursor-on");
      }
      dot.style.transform = "translate3d(" + mouseX + "px," + mouseY + "px,0)";
    },
    { passive: true }
  );

  function loopCursor() {
    ringX += (mouseX - ringX) * 0.18;
    ringY += (mouseY - ringY) * 0.18;
    ring.style.transform = "translate3d(" + ringX + "px," + ringY + "px,0)";
    requestAnimationFrame(loopCursor);
  }
  requestAnimationFrame(loopCursor);

  function hoverable(el) {
    return el.closest("a, button, [data-magnetic], .hero-card, .destination-card, .nav-explore__btn");
  }

  document.addEventListener("mouseover", function (e) {
    if (hoverable(e.target)) document.documentElement.classList.add("is-cursor-hover");
  });
  document.addEventListener("mouseout", function (e) {
    if (hoverable(e.target)) document.documentElement.classList.remove("is-cursor-hover");
  });
  document.addEventListener("mousedown", function () {
    document.documentElement.classList.add("is-cursor-down");
  });
  document.addEventListener("mouseup", function () {
    document.documentElement.classList.remove("is-cursor-down");
  });

  /* Magnetic elements */
  function magnetic(el, strength) {
    el.addEventListener("mousemove", function (e) {
      const r = el.getBoundingClientRect();
      const x = e.clientX - (r.left + r.width / 2);
      const y = e.clientY - (r.top + r.height / 2);
      el.style.transform = "translate(" + x * strength + "px," + y * strength + "px)";
    });
    el.addEventListener("mouseleave", function () {
      el.style.transform = "";
    });
  }

  document.querySelectorAll(".nav-toggle--circle, .nav-explore__btn, .logo").forEach(function (el) {
    magnetic(el, 0.28);
  });

  document.querySelectorAll(".btn").forEach(function (el) {
    magnetic(el, 0.18);
  });

  /* 3D tilt on hero cards */
  document.querySelectorAll(".hero-card").forEach(function (card) {
    card.addEventListener("mousemove", function (e) {
      const r = card.getBoundingClientRect();
      const px = (e.clientX - r.left) / r.width;
      const py = (e.clientY - r.top) / r.height;
      const rx = (py - 0.5) * -10;
      const ry = (px - 0.5) * 12;
      card.style.transform =
        "perspective(900px) rotateX(" + rx + "deg) rotateY(" + ry + "deg) translateY(-6px)";
    });
    card.addEventListener("mouseleave", function () {
      card.style.transform = "";
    });
  });
})();
