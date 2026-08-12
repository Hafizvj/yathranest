/**
 * YathraNest — Filters, search, pagination (frontend mock)
 */
(function () {
  function normalize(str) {
    return (str || "").toString().toLowerCase().trim();
  }

  function initFilters(root) {
    const form = root.querySelector("[data-filter-form]");
    const list = root.querySelector("[data-filter-list]");
    const countEl = root.querySelector("[data-filter-count]");
    const emptyEl = root.querySelector("[data-filter-empty]");
    const pagination = root.querySelector("[data-pagination]");

    if (!form || !list) return;

    const items = Array.from(list.querySelectorAll("[data-filter-item]"));
    const perPage = parseInt(list.dataset.perPage || "6", 10);
    let currentPage = 1;
    let filtered = items.slice();

    function getFilters() {
      const data = {};
      form.querySelectorAll("[data-filter]").forEach(function (el) {
        data[el.dataset.filter] = normalize(el.value);
      });
      const search = form.querySelector("[data-search]");
      if (search) data.q = normalize(search.value);
      return data;
    }

    function matches(item, filters) {
      const dataset = item.dataset;
      if (filters.q) {
        const hay = normalize(
          [
            dataset.name,
            dataset.destination,
            dataset.location,
            dataset.country,
            dataset.type,
            dataset.duration,
            item.textContent,
          ].join(" ")
        );
        if (!hay.includes(filters.q)) return false;
      }

      const keys = ["destination", "state", "country", "duration", "type", "location", "amenities"];
      for (let i = 0; i < keys.length; i++) {
        const key = keys[i];
        if (filters[key] && filters[key] !== "all") {
          const val = normalize(dataset[key] || "");
          if (!val.includes(filters[key]) && filters[key] !== val) return false;
        }
      }
      return true;
    }

    function render() {
      filtered = items.filter(function (item) {
        return matches(item, getFilters());
      });

      const totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
      if (currentPage > totalPages) currentPage = totalPages;

      items.forEach(function (item) {
        item.hidden = true;
      });

      const start = (currentPage - 1) * perPage;
      const pageItems = filtered.slice(start, start + perPage);
      pageItems.forEach(function (item) {
        item.hidden = false;
      });

      if (countEl) {
        countEl.textContent =
          filtered.length === 0
            ? "No results"
            : filtered.length + " result" + (filtered.length === 1 ? "" : "s");
      }

      if (emptyEl) {
        emptyEl.hidden = filtered.length > 0;
      }

      renderPagination(totalPages);
    }

    function renderPagination(totalPages) {
      if (!pagination) return;
      pagination.innerHTML = "";

      if (filtered.length === 0) return;

      const prev = document.createElement("button");
      prev.type = "button";
      prev.textContent = "Prev";
      prev.disabled = currentPage === 1;
      prev.addEventListener("click", function () {
        currentPage -= 1;
        render();
        list.scrollIntoView({ behavior: "smooth", block: "start" });
      });
      pagination.appendChild(prev);

      for (let i = 1; i <= totalPages; i++) {
        const btn = document.createElement("button");
        btn.type = "button";
        btn.textContent = String(i);
        if (i === currentPage) btn.setAttribute("aria-current", "page");
        btn.addEventListener("click", function () {
          currentPage = i;
          render();
          list.scrollIntoView({ behavior: "smooth", block: "start" });
        });
        pagination.appendChild(btn);
      }

      const next = document.createElement("button");
      next.type = "button";
      next.textContent = "Next";
      next.disabled = currentPage === totalPages;
      next.addEventListener("click", function () {
        currentPage += 1;
        render();
        list.scrollIntoView({ behavior: "smooth", block: "start" });
      });
      pagination.appendChild(next);
    }

    form.addEventListener("submit", function (e) {
      e.preventDefault();
      currentPage = 1;
      render();
    });

    form.querySelectorAll("[data-filter], [data-search]").forEach(function (el) {
      el.addEventListener("change", function () {
        currentPage = 1;
        render();
      });
      if (el.matches("[data-search]")) {
        let t;
        el.addEventListener("input", function () {
          clearTimeout(t);
          t = setTimeout(function () {
            currentPage = 1;
            render();
          }, 220);
        });
      }
    });

    const resetBtn = form.querySelector("[data-filter-reset]");
    if (resetBtn) {
      resetBtn.addEventListener("click", function () {
        form.reset();
        currentPage = 1;
        render();
      });
    }

    render();
  }

  document.querySelectorAll("[data-filter-root]").forEach(initFilters);
})();
