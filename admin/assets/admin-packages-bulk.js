/**
 * Packages list: row checkboxes, select-all, the bulk action bar and the
 * bulk edit dialog. Only rows currently shown by search/filters can stay selected.
 */
(function () {
  'use strict';

  var list = document.querySelector('[data-bulk-list]');
  var form = document.querySelector('[data-bulk-form]');
  if (!list || !form) {
    return;
  }

  var items = Array.prototype.slice.call(list.querySelectorAll('[data-bulk-item]'));
  var all = list.querySelector('[data-bulk-all]');
  var bar = form.querySelector('[data-bulk-bar]');
  var modal = form.querySelector('[data-bulk-modal]');
  var typesMode = form.querySelector('[data-bulk-types-mode]');
  var typesBox = form.querySelector('[data-bulk-types]');
  var lastClicked = null;

  function rowOf(item) {
    return item.closest('[data-catalog-row]');
  }

  function visibleItems() {
    return items.filter(function (item) {
      return !rowOf(item).hidden;
    });
  }

  function selectedItems() {
    return items.filter(function (item) {
      return item.checked;
    });
  }

  function sync() {
    items.forEach(function (item) {
      if (item.checked && rowOf(item).hidden) {
        item.checked = false;
      }
      rowOf(item).classList.toggle('is-selected', item.checked);
    });

    var count = selectedItems().length;
    var visible = visibleItems();
    var visibleChecked = visible.filter(function (item) { return item.checked; }).length;

    if (all) {
      all.checked = visible.length > 0 && visibleChecked === visible.length;
      all.indeterminate = visibleChecked > 0 && visibleChecked < visible.length;
      all.disabled = visible.length === 0;
    }
    form.querySelectorAll('[data-bulk-count]').forEach(function (el) {
      el.textContent = String(count);
    });
    form.querySelectorAll('[data-bulk-label]').forEach(function (el) {
      el.textContent = count + (count === 1 ? ' package' : ' packages');
    });
    bar.hidden = count === 0;
    if (count === 0 && !modal.hidden) {
      closeModal();
    }
  }

  items.forEach(function (item) {
    item.addEventListener('click', function (event) {
      if (event.shiftKey && lastClicked && lastClicked !== item) {
        var visible = visibleItems();
        var from = visible.indexOf(lastClicked);
        var to = visible.indexOf(item);
        if (from !== -1 && to !== -1) {
          visible.slice(Math.min(from, to), Math.max(from, to) + 1).forEach(function (other) {
            other.checked = item.checked;
          });
        }
      }
      lastClicked = item;
    });
    item.addEventListener('change', sync);
  });

  if (all) {
    all.addEventListener('change', function () {
      visibleItems().forEach(function (item) {
        item.checked = all.checked;
      });
      sync();
    });
  }

  form.querySelector('[data-bulk-clear]').addEventListener('click', function () {
    items.forEach(function (item) {
      item.checked = false;
    });
    sync();
  });

  // The list script hides rows on search/filter first; re-sync afterwards.
  var search = document.querySelector('[data-catalog-search]');
  if (search) {
    search.addEventListener('input', sync);
  }
  document.querySelectorAll('[data-catalog-filter]').forEach(function (pill) {
    pill.addEventListener('click', sync);
  });

  function openModal() {
    modal.hidden = false;
    document.body.classList.add('bulk-modal-open');
    var first = modal.querySelector('select, input');
    if (first) {
      first.focus();
    }
  }

  function closeModal() {
    modal.hidden = true;
    document.body.classList.remove('bulk-modal-open');
  }

  form.querySelector('[data-bulk-edit-open]').addEventListener('click', openModal);
  modal.querySelectorAll('[data-bulk-edit-close]').forEach(function (el) {
    el.addEventListener('click', closeModal);
  });
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && !modal.hidden) {
      closeModal();
    }
  });

  function syncTypes() {
    typesBox.hidden = typesMode.value === '';
  }
  typesMode.addEventListener('change', syncTypes);
  syncTypes();

  form.addEventListener('submit', function (event) {
    var action = event.submitter ? event.submitter.value : '';
    var count = selectedItems().length;

    if (count === 0) {
      event.preventDefault();
      return;
    }

    if (action === 'delete' &&
        !window.confirm('Delete ' + count + (count === 1 ? ' package' : ' packages') + '? This cannot be undone.')) {
      event.preventDefault();
      return;
    }

    if (action === 'edit') {
      var typesChecked = typesBox.querySelectorAll('input:checked').length;
      if (typesMode.value !== '' && typesChecked === 0) {
        event.preventDefault();
        window.alert('Tick at least one type, or set Type back to "No change".');
        return;
      }
      var changed = typesMode.value !== '' ||
        Array.prototype.some.call(modal.querySelectorAll('select:not([data-bulk-types-mode]), input:not([type="checkbox"])'), function (field) {
          return field.value.trim() !== '';
        });
      if (!changed) {
        event.preventDefault();
        window.alert('Change at least one field before applying.');
      }
    }
  });

  sync();
})();
