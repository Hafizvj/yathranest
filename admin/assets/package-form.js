/**
 * Progressive enhancement for the package form.
 * Everything here degrades to plain inputs when JS is unavailable:
 * pickers stay open lists, highlights stay a chip list with a text entry,
 * itinerary days stay editable rows, and dropzones stay file inputs.
 */
(function () {
  'use strict';

  var form = document.querySelector('[data-package-form]');
  if (!form) {
    return;
  }

  function icon(name) {
    var template = document.getElementById('icon-' + name);
    return template ? template.innerHTML : '';
  }

  /* ---------- character counters ---------- */

  form.querySelectorAll('[data-counter-for]').forEach(function (counter) {
    var field = form.querySelector('#' + counter.getAttribute('data-counter-for'));
    if (!field) {
      return;
    }
    var max = field.getAttribute('maxlength') || counter.getAttribute('data-counter-max') || '';
    function update() {
      counter.textContent = field.value.length + (max ? ' / ' + max : '');
    }
    field.addEventListener('input', update);
    update();
  });

  /* ---------- chip pickers over checkbox lists ---------- */

  form.querySelectorAll('[data-picker]').forEach(function (picker) {
    var control = picker.querySelector('[data-picker-control]');
    var toggle = picker.querySelector('[data-picker-toggle]');
    var chips = picker.querySelector('[data-picker-chips]');
    var placeholder = picker.querySelector('[data-picker-empty]');
    var panel = picker.querySelector('[data-picker-panel]');
    if (!control || !toggle || !chips || !panel) {
      return;
    }
    var boxes = Array.prototype.slice.call(panel.querySelectorAll('input[type="checkbox"]'));
    if (!boxes.length) {
      return;
    }

    picker.classList.add('is-enhanced');
    panel.hidden = true;

    function labelFor(box) {
      var label = box.closest('label');
      return label ? label.textContent.trim() : box.value;
    }

    function renderChips() {
      var checked = boxes.filter(function (box) {
        return box.checked;
      });
      chips.innerHTML = '';
      if (placeholder) {
        placeholder.hidden = checked.length > 0;
      }
      checked.forEach(function (box) {
        var chip = document.createElement('span');
        chip.className = 'chip';
        chip.appendChild(document.createTextNode(labelFor(box)));
        var remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'chip__remove';
        remove.setAttribute('aria-label', 'Remove ' + labelFor(box));
        remove.innerHTML = '&times;';
        remove.addEventListener('click', function (event) {
          event.stopPropagation();
          box.checked = false;
          renderChips();
        });
        chip.appendChild(remove);
        chips.appendChild(chip);
      });
    }

    function setOpen(open) {
      panel.hidden = !open;
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    control.addEventListener('click', function () {
      setOpen(panel.hidden);
    });
    boxes.forEach(function (box) {
      box.addEventListener('change', renderChips);
    });
    document.addEventListener('click', function (event) {
      if (!picker.contains(event.target)) {
        setOpen(false);
      }
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        setOpen(false);
      }
    });

    renderChips();
  });

  /* ---------- highlights as chips ---------- */

  (function () {
    var root = form.querySelector('[data-highlights]');
    if (!root) {
      return;
    }
    var list = root.querySelector('[data-highlight-list]');
    var entry = root.querySelector('[data-highlight-entry]');
    var addBtn = root.querySelector('[data-highlight-add]');
    if (!list || !entry) {
      return;
    }

    function addChip(value) {
      value = value.trim();
      if (value === '') {
        return;
      }
      var chip = document.createElement('span');
      chip.className = 'chip';
      chip.appendChild(document.createTextNode(value));

      var input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'highlights[]';
      input.value = value;
      chip.appendChild(input);

      var remove = document.createElement('button');
      remove.type = 'button';
      remove.className = 'chip__remove';
      remove.setAttribute('aria-label', 'Remove ' + value);
      remove.innerHTML = '&times;';
      remove.addEventListener('click', function () {
        chip.remove();
      });
      chip.appendChild(remove);

      list.insertBefore(chip, entry);
    }

    list.querySelectorAll('[data-chip-remove]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var chip = btn.closest('.chip');
        if (chip) {
          chip.remove();
        }
      });
    });

    // Never move focus on blur, or leaving the field would pull focus back.
    function commit(keepFocus) {
      var value = entry.value;
      entry.value = '';
      addChip(value);
      if (keepFocus) {
        entry.focus();
      }
    }

    entry.addEventListener('keydown', function (event) {
      if (event.key === 'Enter' || event.key === ',') {
        event.preventDefault();
        commit(true);
      }
    });
    entry.addEventListener('blur', function () {
      commit(false);
    });
    if (addBtn) {
      addBtn.addEventListener('click', function () {
        commit(true);
      });
    }
  })();

  /* ---------- stays: one select per night ---------- */

  (function () {
    var root = form.querySelector('[data-stays]');
    var nightsInput = form.querySelector('[data-nights]');
    if (!root || !nightsInput) {
      return;
    }
    var grid = root.querySelector('[data-stay-grid]');
    var places = [];
    try {
      places = JSON.parse(root.getAttribute('data-places') || '[]');
    } catch (err) {
      places = [];
    }

    function values() {
      return Array.prototype.map.call(grid.querySelectorAll('select'), function (select) {
        return select.value;
      });
    }

    function render(nights, selected) {
      if (nights < 1) {
        grid.innerHTML = '<p class="field__hint">No overnight stays for this duration.</p>';
        return;
      }
      grid.innerHTML = '';
      for (var i = 0; i < nights; i++) {
        var item = document.createElement('div');
        item.className = 'stay-grid__item';

        var label = document.createElement('label');
        label.setAttribute('for', 'stay-' + i);
        label.textContent = 'Night ' + (i + 1);
        item.appendChild(label);

        var select = document.createElement('select');
        select.className = 'form-control';
        select.id = 'stay-' + i;
        select.name = 'stays[]';
        select.appendChild(new Option('Select a place', ''));
        places.forEach(function (place) {
          select.appendChild(new Option(place.label, place.slug));
        });
        select.value = selected[i] || '';
        item.appendChild(select);

        grid.appendChild(item);
      }
    }

    nightsInput.addEventListener('input', function () {
      render(Math.max(0, parseInt(nightsInput.value, 10) || 0), values());
    });
  })();

  /* ---------- itinerary days ---------- */

  (function () {
    var root = form.querySelector('[data-days]');
    var daysInput = form.querySelector('[data-days-count]');
    if (!root) {
      return;
    }
    var list = root.querySelector('[data-day-list]');
    var addBtn = root.querySelector('[data-day-add]');

    function renumber() {
      Array.prototype.forEach.call(list.children, function (item, index) {
        var no = item.querySelector('.day-item__no');
        var label = item.querySelector('.day-item__label');
        var title = item.querySelector('.day-item__title');
        var text = item.querySelector('textarea');
        if (no) {
          no.textContent = String(index + 1);
        }
        if (label) {
          label.textContent = 'Day ' + (index + 1);
        }
        if (title) {
          title.setAttribute('aria-label', 'Day ' + (index + 1) + ' title');
        }
        if (text) {
          text.id = 'day-text-' + index;
        }
      });
    }

    function bind(item) {
      var toggle = item.querySelector('[data-day-toggle]');
      var remove = item.querySelector('[data-day-remove]');
      var copy = item.querySelector('[data-day-copy]');
      if (toggle) {
        toggle.addEventListener('click', function () {
          item.classList.toggle('is-collapsed');
          toggle.setAttribute('aria-expanded', item.classList.contains('is-collapsed') ? 'false' : 'true');
        });
      }
      if (remove) {
        remove.addEventListener('click', function () {
          if (list.children.length <= 1) {
            item.querySelector('.day-item__title').value = '';
            item.querySelector('textarea').value = '';
            return;
          }
          item.remove();
          renumber();
        });
      }
      if (copy) {
        copy.addEventListener('click', function () {
          var clone = create(
            item.querySelector('.day-item__title').value,
            item.querySelector('textarea').value
          );
          item.after(clone);
          renumber();
        });
      }
    }

    function create(title, text) {
      var item = document.createElement('div');
      item.className = 'day-item is-collapsed';
      item.innerHTML =
        '<div class="day-item__head">' +
        '<span class="day-item__no">0</span>' +
        '<span class="day-item__label">Day</span>' +
        '<input class="day-item__title" type="text" name="itinerary_title[]" placeholder="Day title" />' +
        '<span class="day-item__actions">' +
        '<button class="icon-btn" type="button" data-day-copy aria-label="Duplicate day">' + icon('copy') + '</button>' +
        '<button class="icon-btn icon-btn--danger" type="button" data-day-remove aria-label="Delete day">' + icon('trash') + '</button>' +
        '<button class="icon-btn day-toggle" type="button" data-day-toggle aria-expanded="false" aria-label="Toggle day details">' + icon('chevron-down') + '</button>' +
        '</span>' +
        '</div>' +
        '<div class="day-item__body">' +
        '<label class="field__label">Details (optional)</label>' +
        '<textarea class="form-control" name="itinerary_text[]" rows="3" maxlength="1000" placeholder="Add more details about this day (activities, places, meals, etc.)"></textarea>' +
        '</div>';
      item.querySelector('.day-item__title').value = title || '';
      item.querySelector('textarea').value = text || '';
      bind(item);
      return item;
    }

    Array.prototype.forEach.call(list.children, bind);

    if (addBtn) {
      addBtn.addEventListener('click', function () {
        var item = create('', '');
        item.classList.remove('is-collapsed');
        list.appendChild(item);
        renumber();
        item.querySelector('.day-item__title').focus();
      });
    }

    // Keep one row per day; only trailing empty rows are dropped.
    if (daysInput) {
      daysInput.addEventListener('input', function () {
        var target = Math.max(1, parseInt(daysInput.value, 10) || 1);
        while (list.children.length < target) {
          list.appendChild(create('', ''));
        }
        while (list.children.length > target) {
          var last = list.lastElementChild;
          if (last.querySelector('.day-item__title').value.trim() !== '' || last.querySelector('textarea').value.trim() !== '') {
            break;
          }
          last.remove();
        }
        renumber();
      });
    }

    renumber();
  })();

  /* ---------- dropzones and file pickers ---------- */

  form.querySelectorAll('[data-dropzone]').forEach(function (zone) {
    var input = zone.querySelector('input[type="file"]');
    if (!input) {
      return;
    }
    ['dragenter', 'dragover'].forEach(function (type) {
      zone.addEventListener(type, function (event) {
        event.preventDefault();
        zone.classList.add('is-dragover');
      });
    });
    ['dragleave', 'drop'].forEach(function (type) {
      zone.addEventListener(type, function (event) {
        event.preventDefault();
        zone.classList.remove('is-dragover');
      });
    });
    zone.addEventListener('drop', function (event) {
      if (event.dataTransfer && event.dataTransfer.files && event.dataTransfer.files.length) {
        input.files = event.dataTransfer.files;
        input.dispatchEvent(new Event('change', { bubbles: true }));
      }
    });
  });

  form.querySelectorAll('[data-file-name-for]').forEach(function (output) {
    var input = form.querySelector('#' + output.getAttribute('data-file-name-for'));
    if (!input) {
      return;
    }
    var initial = output.textContent;
    input.addEventListener('change', function () {
      output.textContent = input.files && input.files.length
        ? Array.prototype.map.call(input.files, function (file) { return file.name; }).join(', ')
        : initial;
    });
  });

  /* ---------- cover image check ----------
     The file input is visually hidden, so a native `required` would block the
     submit with a message the browser cannot show anywhere. */

  (function () {
    var input = form.querySelector('[data-cover-required]');
    var message = form.querySelector('[data-cover-error]');
    if (!input || !message) {
      return;
    }
    var zone = input.closest('.dropzone');
    form.addEventListener('submit', function (event) {
      var missing = !input.files || !input.files.length;
      message.hidden = !missing;
      if (zone) {
        zone.classList.toggle('has-error', missing);
      }
      if (missing) {
        event.preventDefault();
        message.scrollIntoView({ block: 'center' });
      }
    });
    input.addEventListener('change', function () {
      message.hidden = true;
      if (zone) {
        zone.classList.remove('has-error');
      }
    });
  })();

  /* ---------- featured toggle label ---------- */

  form.querySelectorAll('[data-switch-state]').forEach(function (output) {
    var input = form.querySelector('#' + output.getAttribute('data-switch-state'));
    if (!input) {
      return;
    }
    function update() {
      output.textContent = input.checked ? 'Yes' : 'No';
    }
    input.addEventListener('change', update);
    update();
  });
})();
