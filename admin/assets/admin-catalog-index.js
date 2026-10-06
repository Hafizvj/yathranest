/**
 * Catalog list pages: search, status filter pills, whole-row click to edit,
 * and filter forms that submit as soon as a select changes.
 */
(function () {
  'use strict';

  document.querySelectorAll('form[data-auto-submit]').forEach(function (form) {
    var btn = form.querySelector('[data-auto-submit-btn]');
    if (btn) {
      btn.hidden = true;
    }
    form.querySelectorAll('select').forEach(function (select) {
      select.addEventListener('change', function () {
        form.submit();
      });
    });
  });

  var list = document.querySelector('[data-catalog-list]');
  if (!list) {
    return;
  }

  var rows = Array.prototype.slice.call(list.querySelectorAll('[data-catalog-row]'));
  var search = document.querySelector('[data-catalog-search]');
  var pills = Array.prototype.slice.call(document.querySelectorAll('[data-catalog-filter]'));
  var none = list.querySelector('[data-catalog-none]');
  var status = 'all';

  function apply() {
    var q = search ? search.value.trim().toLowerCase() : '';
    var shown = 0;
    rows.forEach(function (row) {
      var statusMatch = status === 'all' ||
        (status === 'featured' ? row.getAttribute('data-featured') === '1' : row.getAttribute('data-status') === status);
      var match = statusMatch &&
        (q === '' || row.getAttribute('data-search').indexOf(q) !== -1);
      row.hidden = !match;
      if (match) {
        shown++;
      }
    });
    if (none) {
      none.hidden = shown > 0;
    }
  }

  if (search) {
    search.addEventListener('input', apply);
  }
  pills.forEach(function (pill) {
    pill.addEventListener('click', function () {
      status = pill.getAttribute('data-catalog-filter');
      pills.forEach(function (other) {
        var active = other === pill;
        other.classList.toggle('is-active', active);
        other.setAttribute('aria-pressed', active ? 'true' : 'false');
      });
      apply();
    });
  });

  rows.forEach(function (row) {
    row.addEventListener('click', function (event) {
      if (event.target.closest('a, button, form, input, label')) {
        return;
      }
      window.location.href = row.getAttribute('data-href');
    });
  });
})();
