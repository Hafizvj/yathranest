/**
 * YathraNest — Render package listings and detail pages from the catalog
 */
(function () {
  function assetPrefix() {
    return document.body.getAttribute("data-asset-prefix") || "../assets/images/";
  }

  function detailsHref(id) {
    var prefix = document.body.getAttribute("data-details-prefix") || "package-details.html";
    return prefix + "?package=" + encodeURIComponent(id);
  }

  function escapeHtml(str) {
    return String(str || "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function cardHtml(pkg) {
    var img = assetPrefix() + pkg.image;
    var href = detailsHref(pkg.id);
    var destAttr = pkg.destinations.join(" ");
    var highlights = pkg.highlights
      .slice(0, 3)
      .map(function (h) {
        return "<li>" + escapeHtml(h) + "</li>";
      })
      .join("");
    return (
      '<article class="card" data-filter-item data-name="' +
      escapeHtml(pkg.title) +
      '" data-destination="' +
      escapeHtml(destAttr) +
      '" data-state="' +
      escapeHtml(pkg.state) +
      '" data-duration="' +
      escapeHtml(pkg.duration) +
      '" data-type="' +
      escapeHtml(pkg.type) +
      '" data-pickup="' +
      escapeHtml(pkg.pickupSlug) +
      '">' +
      '<div class="card__media"><img src="' +
      escapeHtml(img) +
      '" alt="' +
      escapeHtml(pkg.title) +
      '" width="800" height="500" loading="lazy" /></div>' +
      '<div class="card__body">' +
      '<p class="card__meta">' +
      escapeHtml(pkg.destLine) +
      "</p>" +
      '<h3 class="card__title"><a href="' +
      href +
      '">' +
      escapeHtml(pkg.title) +
      "</a></h3>" +
      '<p class="meta-row"><span><strong>' +
      pkg.days +
      " Days / " +
      pkg.nights +
      " Nights</strong></span></p>" +
      '<p class="card__text">' +
      escapeHtml(pkg.cardText) +
      "</p>" +
      '<ul class="highlight-list">' +
      highlights +
      "</ul>" +
      '<div class="card__actions">' +
      '<a class="btn btn--secondary btn--sm" href="' +
      href +
      '">View Details</a>' +
      '<a class="btn btn--primary btn--sm" href="#enquiry" data-open-modal="enquiry-modal" data-package-title="' +
      escapeHtml(pkg.title) +
      '">Request Pricing</a>' +
      "</div></div></article>"
    );
  }

  function uniqueSorted(values) {
    var seen = {};
    var out = [];
    values.forEach(function (v) {
      if (!v || seen[v]) return;
      seen[v] = true;
      out.push(v);
    });
    out.sort(function (a, b) {
      return a.localeCompare(b);
    });
    return out;
  }

  function fillSelect(select, items, allLabel) {
    if (!select) return;
    var current = select.value || "all";
    var html = '<option value="all">' + (allLabel || "All") + "</option>";
    items.forEach(function (item) {
      html += '<option value="' + escapeHtml(item.value) + '">' + escapeHtml(item.label) + "</option>";
    });
    select.innerHTML = html;
    if (Array.prototype.some.call(select.options, function (o) { return o.value === current; })) {
      select.value = current;
    }
  }

  function renderList(page) {
    if (!window.YNPackages) return;
    var list = document.querySelector("[data-filter-list]");
    if (!list) return;
    var packages = window.YNPackages.forPage(page);
    var html = packages.map(cardHtml).join("");
    if (page === "domestic") {
      list.insertAdjacentHTML("afterbegin", html);
    } else {
      list.innerHTML = html;
    }

    if (page !== "domestic") {
      fillSelect(
        document.querySelector('[data-filter="destination"]'),
        uniqueSorted(
          packages.reduce(function (acc, p) {
            return acc.concat(p.destinations);
          }, [])
        ).map(function (d) {
          var place = window.YNPackages.places[d];
          return { value: d, label: (place && place.label) || d };
        }),
        "All destinations"
      );

      var pickupSelect = document.querySelector('[data-filter="pickup"]');
      if (pickupSelect) {
        var pickups = uniqueSorted(
          packages.map(function (p) {
            return p.pickupSlug;
          })
        ).map(function (slug) {
          var labels = { calicut: "Calicut", kochi: "Kochi", coimbatore: "Coimbatore", mysore: "Mysore", trivandrum: "Trivandrum" };
          return { value: slug, label: labels[slug] || slug };
        });
        fillSelect(pickupSelect, pickups, "Any pickup");
      }
    }
  }

  function setText(selector, text) {
    var el = document.querySelector(selector);
    if (el) el.textContent = text;
  }

  function renderDetails() {
    if (!window.YNPackages) return;
    var params = new URLSearchParams(window.location.search);
    var id = params.get("package") || "";
    var pkg = window.YNPackages.byId(id);
    var missing = document.querySelector("[data-package-missing]");
    var content = document.querySelector("[data-package-content]");

    if (!pkg) {
      if (content) content.hidden = true;
      if (missing) missing.hidden = false;
      document.title = "Package not found | YathraNest";
      return;
    }

    if (missing) missing.hidden = true;
    if (content) content.hidden = false;

    document.title = pkg.title + " — Package Details | YathraNest";
    var desc = document.querySelector('meta[name="description"]');
    if (desc) desc.setAttribute("content", pkg.overview);

    setText("[data-bind='breadcrumb-page']", pkg.pages[0] === "south" ? "South Indian Packages" : pkg.pages[0] === "domestic" ? "Domestic Packages" : "Kerala Packages");
    var crumbLink = document.querySelector("[data-bind='breadcrumb-href']");
    if (crumbLink) {
      crumbLink.href =
        pkg.pages[0] === "south" ? "south-indian-packages.html" : pkg.pages[0] === "domestic" ? "domestic-packages.html" : "kerala-packages.html";
    }
    setText("[data-bind='breadcrumb-title']", pkg.title);
    setText("[data-bind='eyebrow']", pkg.sheet === "TN & KA PLANS" ? "South Indian Package" : pkg.sheet === "Domestic" ? "Domestic Package" : "Kerala Package");
    setText("[data-bind='title']", pkg.title);
    setText("[data-bind='dest']", pkg.destLine);
    setText("[data-bind='duration']", pkg.days + " Days / " + pkg.nights + " Nights");
    setText("[data-bind='route']", "Pickup " + pkg.pickup + " · Drop " + pkg.drop);
    setText("[data-bind='summary']", pkg.overview);
    setText("[data-bind='overview']", pkg.overview);
    setText("[data-bind='accommodation']", pkg.accommodation);
    setText("[data-bind='interest-label']", pkg.title);

    var heroImg = document.querySelector("[data-bind='hero-image']");
    if (heroImg) {
      heroImg.src = assetPrefix() + pkg.image;
      heroImg.alt = pkg.title;
    }

    var hl = document.querySelector("[data-bind='highlights']");
    if (hl) {
      hl.innerHTML = pkg.highlights.map(function (h) {
        return "<li>" + escapeHtml(h) + "</li>";
      }).join("");
    }

    var acc = document.querySelector("[data-bind='itinerary']");
    if (acc) {
      acc.innerHTML = pkg.itinerary
        .map(function (day, i) {
          var open = i === 0 ? " is-open" : "";
          var expanded = i === 0 ? "true" : "false";
          return (
            '<div class="accordion__item' +
            open +
            '">' +
            '<button class="accordion__trigger" type="button" aria-expanded="' +
            expanded +
            '"><span>Day ' +
            day.day +
            " — " +
            escapeHtml(day.title) +
            '</span><span class="accordion__icon" aria-hidden="true">+</span></button>' +
            '<div class="accordion__panel"><p>' +
            escapeHtml(day.text) +
            "</p></div></div>"
          );
        })
        .join("");
    }

    var inc = document.querySelector("[data-bind='inclusions']");
    if (inc) {
      var items = [
        "Accommodation as per itinerary (" + pkg.staySummary + ")",
        pkg.hasHouseboat ? "Daily breakfast and houseboat meals as applicable" : "Daily breakfast",
        "Private transfers for sightseeing segments",
        "Assistance at pickup and drop",
      ];
      inc.innerHTML = items.map(function (t) {
        return "<li>" + escapeHtml(t) + "</li>";
      }).join("");
    }

    var gallery = document.querySelector("[data-bind='gallery']");
    if (gallery) {
      gallery.innerHTML = pkg.gallery
        .map(function (file) {
          var src = assetPrefix() + file;
          return (
            '<button type="button" data-gallery-item data-full="' +
            escapeHtml(src) +
            '"><img src="' +
            escapeHtml(src) +
            '" alt="' +
            escapeHtml(pkg.title) +
            '" /></button>'
          );
        })
        .join("");
    }

    var related = document.querySelector("[data-bind='related']");
    if (related) {
      related.innerHTML = window.YNPackages.related(pkg, 3)
        .map(function (p) {
          var href = detailsHref(p.id);
          return (
            '<article class="card">' +
            '<div class="card__media"><img src="' +
            escapeHtml(assetPrefix() + p.image) +
            '" alt="' +
            escapeHtml(p.title) +
            '" loading="lazy" /></div>' +
            '<div class="card__body">' +
            '<h3 class="card__title"><a href="' +
            href +
            '">' +
            escapeHtml(p.title) +
            "</a></h3>" +
            '<p class="card__text">' +
            escapeHtml(p.days + " Days · " + p.destLine) +
            "</p>" +
            '<a class="link-arrow" href="' +
            href +
            '">View Package</a>' +
            "</div></article>"
          );
        })
        .join("");
    }

    document.querySelectorAll("[data-prefill='package']").forEach(function (el) {
      el.value = pkg.title;
    });
    document.querySelectorAll("[data-interest-label]").forEach(function (el) {
      el.textContent = pkg.title;
    });
  }

  var page = document.body.getAttribute("data-package-page");
  if (page) renderList(page);
  if (document.body.getAttribute("data-package-details") === "true") renderDetails();
})();
