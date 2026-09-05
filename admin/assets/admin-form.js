/**
 * Progressive enhancement for the card-based admin forms (packages, places).
 * Each widget only runs when its markup is present, and everything degrades to
 * plain inputs when JS is unavailable: pickers stay open checkbox lists, chip
 * inputs stay a text field, itinerary days stay editable rows, and dropzones
 * stay file inputs.
 */
(function () {
  'use strict';

  var form = document.querySelector('[data-rich-form]');
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

  /* ---------- free text values as chips (highlights, tags) ---------- */

  form.querySelectorAll('[data-chips]').forEach(function (root) {
    var list = root.querySelector('[data-chips-list]');
    var entry = root.querySelector('[data-chips-entry]');
    var addBtn = root.querySelector('[data-chips-add]');
    var fieldName = root.getAttribute('data-chips');
    var suggestList = root.querySelector('[data-suggest-list]');
    var suggestions = [];
    var activeIndex = -1;
    var suppressBlurCommit = false;
    if (!list || !entry || !fieldName) {
      return;
    }
    try {
      suggestions = JSON.parse(root.getAttribute('data-suggest') || '[]');
      if (!Array.isArray(suggestions)) {
        suggestions = [];
      }
    } catch (err) {
      suggestions = [];
    }

    function existingValues() {
      return Array.prototype.map
        .call(list.querySelectorAll('input[type="hidden"]'), function (input) {
          return String(input.value || '').trim().toLowerCase();
        })
        .filter(Boolean);
    }

    function addChip(value) {
      value = value.trim();
      if (value === '') {
        return;
      }
      if (existingValues().indexOf(value.toLowerCase()) !== -1) {
        return;
      }
      var chip = document.createElement('span');
      chip.className = 'chip';
      chip.appendChild(document.createTextNode(value));

      var input = document.createElement('input');
      input.type = 'hidden';
      input.name = fieldName + '[]';
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

    function hideSuggest() {
      if (!suggestList) {
        return;
      }
      suggestList.hidden = true;
      suggestList.innerHTML = '';
      activeIndex = -1;
    }

    function filteredSuggestions(query) {
      var q = String(query || '').trim().toLowerCase();
      var taken = existingValues();
      return suggestions.filter(function (item) {
        var text = String(item || '').trim();
        if (!text || taken.indexOf(text.toLowerCase()) !== -1) {
          return false;
        }
        return !q || text.toLowerCase().indexOf(q) !== -1;
      }).slice(0, 8);
    }

    function renderSuggest(query, forceOpen) {
      if (!suggestList || !suggestions.length) {
        return;
      }
      var matches = filteredSuggestions(query);
      if (!matches.length && !forceOpen) {
        hideSuggest();
        return;
      }
      suggestList.innerHTML = '';
      if (!matches.length) {
        var empty = document.createElement('li');
        empty.className = 'suggest__empty';
        empty.textContent = 'No matches';
        suggestList.appendChild(empty);
      } else {
        matches.forEach(function (item, index) {
          var li = document.createElement('li');
          var btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'suggest__option';
          btn.textContent = item;
          if (index === activeIndex) {
            btn.classList.add('is-active');
          }
          btn.addEventListener('mousedown', function (event) {
            event.preventDefault();
            suppressBlurCommit = true;
            entry.value = '';
            addChip(item);
            hideSuggest();
            entry.focus();
            suppressBlurCommit = false;
          });
          li.appendChild(btn);
          suggestList.appendChild(li);
        });
      }
      suggestList.hidden = false;
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
      hideSuggest();
      if (keepFocus) {
        entry.focus();
      }
    }

    entry.addEventListener('input', function () {
      activeIndex = -1;
      renderSuggest(entry.value, false);
    });
    entry.addEventListener('focus', function () {
      renderSuggest(entry.value, true);
    });
    entry.addEventListener('keydown', function (event) {
      var options = suggestList ? suggestList.querySelectorAll('.suggest__option') : [];
      if (event.key === 'ArrowDown' && options.length) {
        event.preventDefault();
        activeIndex = Math.min(options.length - 1, activeIndex + 1);
        renderSuggest(entry.value, true);
        return;
      }
      if (event.key === 'ArrowUp' && options.length) {
        event.preventDefault();
        activeIndex = Math.max(0, activeIndex - 1);
        renderSuggest(entry.value, true);
        return;
      }
      if (event.key === 'Escape') {
        hideSuggest();
        return;
      }
      if (event.key === 'Enter' || event.key === ',') {
        event.preventDefault();
        if (event.key === 'Enter' && activeIndex >= 0 && options[activeIndex]) {
          options[activeIndex].dispatchEvent(new Event('mousedown'));
          return;
        }
        commit(true);
      }
    });
    entry.addEventListener('blur', function () {
      window.setTimeout(function () {
        if (suppressBlurCommit) {
          return;
        }
        commit(false);
      }, 120);
    });
    if (addBtn) {
      addBtn.addEventListener('click', function () {
        commit(true);
      });
    }
    document.addEventListener('click', function (event) {
      if (!root.contains(event.target)) {
        hideSuggest();
      }
    });
  });

  /* ---------- reusable text suggestions (pickup) ---------- */

  form.querySelectorAll('[data-suggest-input]').forEach(function (root) {
    var field = root.querySelector('[data-suggest-field]');
    var toggle = root.querySelector('[data-suggest-toggle]');
    var suggestList = root.querySelector('[data-suggest-list]');
    var suggestions = [];
    var activeIndex = -1;
    if (!field || !suggestList) {
      return;
    }
    try {
      suggestions = JSON.parse(root.getAttribute('data-suggest') || '[]');
      if (!Array.isArray(suggestions)) {
        suggestions = [];
      }
    } catch (err) {
      suggestions = [];
    }

    function hideSuggest() {
      suggestList.hidden = true;
      suggestList.innerHTML = '';
      activeIndex = -1;
      if (toggle) {
        toggle.setAttribute('aria-expanded', 'false');
      }
    }

    function filtered(query, showAll) {
      var q = String(query || '').trim().toLowerCase();
      return suggestions.filter(function (item) {
        var text = String(item || '').trim();
        if (!text) {
          return false;
        }
        return showAll || !q || text.toLowerCase().indexOf(q) !== -1;
      }).slice(0, 12);
    }

    function render(query, showAll) {
      if (!suggestions.length) {
        hideSuggest();
        return;
      }
      var matches = filtered(query, !!showAll);
      suggestList.innerHTML = '';
      if (!matches.length) {
        var empty = document.createElement('li');
        empty.className = 'suggest__empty';
        empty.textContent = 'No matches';
        suggestList.appendChild(empty);
      } else {
        matches.forEach(function (item, index) {
          var li = document.createElement('li');
          var btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'suggest__option';
          btn.textContent = item;
          if (index === activeIndex) {
            btn.classList.add('is-active');
          }
          btn.addEventListener('mousedown', function (event) {
            event.preventDefault();
            field.value = item;
            hideSuggest();
            field.focus();
          });
          li.appendChild(btn);
          suggestList.appendChild(li);
        });
      }
      suggestList.hidden = false;
      if (toggle) {
        toggle.setAttribute('aria-expanded', 'true');
      }
    }

    field.addEventListener('input', function () {
      activeIndex = -1;
      render(field.value, false);
    });
    field.addEventListener('focus', function () {
      render(field.value, !String(field.value || '').trim());
    });
    field.addEventListener('keydown', function (event) {
      var options = suggestList.querySelectorAll('.suggest__option');
      if (event.key === 'ArrowDown' && options.length) {
        event.preventDefault();
        activeIndex = Math.min(options.length - 1, activeIndex + 1);
        render(field.value, !String(field.value || '').trim());
        return;
      }
      if (event.key === 'ArrowUp' && options.length) {
        event.preventDefault();
        activeIndex = Math.max(0, activeIndex - 1);
        render(field.value, !String(field.value || '').trim());
        return;
      }
      if (event.key === 'Enter' && activeIndex >= 0 && options[activeIndex]) {
        event.preventDefault();
        options[activeIndex].dispatchEvent(new Event('mousedown'));
        return;
      }
      if (event.key === 'Escape') {
        hideSuggest();
      }
    });
    if (toggle) {
      toggle.addEventListener('click', function () {
        if (!suggestList.hidden) {
          hideSuggest();
          return;
        }
        activeIndex = -1;
        render(field.value, true);
        field.focus();
      });
    }
    document.addEventListener('click', function (event) {
      if (!root.contains(event.target)) {
        hideSuggest();
      }
    });
  });

  /* ---------- stays: one select per night, filtered by destinations ---------- */

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

    function selectedDestinations() {
      return Array.prototype.map
        .call(form.querySelectorAll('input[name="destinations[]"]:checked'), function (el) {
          return el.value;
        })
        .filter(Boolean);
    }

    function allowedPlaces() {
      var dests = selectedDestinations();
      if (!dests.length) {
        return [];
      }
      var allowed = [];
      dests.forEach(function (slug) {
        places.forEach(function (place) {
          if (place.slug === slug) {
            allowed.push(place);
          }
        });
      });
      return allowed;
    }

    function render(nights, selected) {
      if (nights < 1) {
        grid.innerHTML = '<p class="field__hint">No overnight stays for this duration.</p>';
        return;
      }
      var options = allowedPlaces();
      if (!options.length) {
        grid.innerHTML = '<p class="field__hint">Select destinations first.</p>';
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
        options.forEach(function (place) {
          select.appendChild(new Option(place.label, place.slug));
        });
        var keep = selected[i] || '';
        if (keep && options.some(function (place) { return place.slug === keep; })) {
          select.value = keep;
        } else {
          select.value = '';
        }
        item.appendChild(select);

        grid.appendChild(item);
      }
    }

    function refresh() {
      render(Math.max(0, parseInt(nightsInput.value, 10) || 0), values());
    }

    nightsInput.addEventListener('input', refresh);
    form.addEventListener('change', function (event) {
      var target = event.target;
      if (target && target.name === 'destinations[]') {
        refresh();
      }
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
    var input = form.querySelector('[data-cover-input]');
    var message = form.querySelector('[data-cover-error]');
    var library = form.querySelector('input[data-cover-library]');
    if (!input || !message) {
      return;
    }
    form.addEventListener('submit', function (event) {
      var required = input.hasAttribute('data-cover-required');
      var hasFile = input.files && input.files.length;
      var hasLibrary = library && library.value.trim() !== '';
      var missing = required && !hasFile && !hasLibrary;
      var empty = form.querySelector('[data-cover-empty]');
      message.hidden = !missing;
      if (empty) {
        empty.classList.toggle('has-error', missing);
      }
      if (missing) {
        event.preventDefault();
        message.scrollIntoView({ block: 'center' });
      }
    });
    input.addEventListener('change', function () {
      message.hidden = true;
      var empty = form.querySelector('[data-cover-empty]');
      if (empty) {
        empty.classList.remove('has-error');
      }
    });
  })();

  /* ---------- package media panel (cover + gallery) ---------- */

  (function () {
    window.ynInitPackageMediaPanels = function (scope) {
      var host = scope || form;
      host.querySelectorAll('[data-package-media]:not([data-media-ready])').forEach(initPackageMediaPanel);
    };

    form.querySelectorAll('[data-package-media]').forEach(initPackageMediaPanel);

    function initPackageMediaPanel(root) {
    if (!root || root.getAttribute('data-media-ready') === '1') {
      return;
    }
    root.setAttribute('data-media-ready', '1');

    var maxGallery = parseInt(root.getAttribute('data-gallery-max') || '10', 10) || 10;
    var galleryKeepName = root.getAttribute('data-gallery-keep-name') || 'gallery_keep[]';
    var coverInput = root.querySelector('[data-cover-input]');
    var coverRemove = root.querySelector('[data-cover-remove]');
    var coverLibrary = root.querySelector('input[data-cover-library]');
    var coverLibraryBtn = root.querySelector('button[data-cover-library]');
    var galleryLibraryBtn = root.querySelector('[data-gallery-library]');
    var coverEmpty = root.querySelector('[data-cover-empty]');
    var coverFilled = root.querySelector('[data-cover-filled]');
    var coverImg = root.querySelector('[data-cover-img]');
    var coverName = root.querySelector('[data-cover-name]');
    var coverSize = root.querySelector('[data-cover-size]');
    var galleryInput = root.querySelector('[data-gallery-input]');
    var galleryEmpty = root.querySelector('[data-gallery-empty]');
    var galleryPanel = root.querySelector('[data-gallery-panel]');
    var galleryGrid = root.querySelector('[data-gallery-grid]');
    var galleryAdd = root.querySelector('[data-gallery-add]');
    var galleryCount = root.querySelector('[data-gallery-count]');
    var galleryStatus = root.querySelector('[data-gallery-status]');
    var pendingGallery = [];
    var originalCover = {
      src: coverImg && coverImg.getAttribute('src') ? coverImg.getAttribute('src') : '',
      name: coverName ? coverName.textContent : '',
      hadImage: !!(coverFilled && !coverFilled.hidden)
    };

    function formatBytes(bytes) {
      if (!bytes || bytes < 0) {
        return '';
      }
      if (bytes < 1024 * 1024) {
        return (bytes / 1024).toFixed(0) + ' KB';
      }
      return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    function setCoverRequired(on) {
      if (!coverInput) {
        return;
      }
      if (on) {
        coverInput.setAttribute('data-cover-required', '');
      } else {
        coverInput.removeAttribute('data-cover-required');
      }
    }

    function showCoverFilled(src, name, sizeLabel) {
      if (coverEmpty) {
        coverEmpty.hidden = true;
        coverEmpty.classList.remove('has-error');
      }
      if (coverFilled) {
        coverFilled.hidden = false;
      }
      if (coverImg) {
        coverImg.src = src || '';
        coverImg.hidden = !src;
      }
      if (coverName) {
        coverName.textContent = name || 'cover-image.jpg';
      }
      if (coverSize) {
        coverSize.textContent = sizeLabel || '';
      }
      setCoverRequired(false);
    }

    function showCoverEmpty(markRemoved) {
      if (coverFilled) {
        coverFilled.hidden = true;
      }
      if (coverEmpty) {
        coverEmpty.hidden = false;
      }
      if (coverImg) {
        coverImg.removeAttribute('src');
        coverImg.hidden = true;
      }
      if (coverInput) {
        coverInput.value = '';
      }
      if (coverLibrary) {
        coverLibrary.value = '';
      }
      if (coverRemove) {
        coverRemove.value = markRemoved && originalCover.hadImage ? '1' : '0';
      }
      setCoverRequired(true);
    }

    function applyLibraryCover(item) {
      if (!item) {
        return;
      }
      if (coverInput) {
        coverInput.value = '';
      }
      if (coverLibrary) {
        coverLibrary.value = item.path || '';
      }
      if (coverRemove) {
        coverRemove.value = '0';
      }
      showCoverFilled(item.url, item.name, item.bytes_label || 'Library');
    }

    function appendLibraryGalleryItems(items) {
      if (!galleryGrid || !items || !items.length) {
        return;
      }
      var room = maxGallery - existingCount() - pendingGallery.length;
      items.forEach(function (item) {
        if (room <= 0 || !item || !item.path) {
          return;
        }
        var exists = false;
        galleryGrid.querySelectorAll('input[name="' + galleryKeepName + '"]').forEach(function (input) {
          if (input.value === item.path) {
            exists = true;
          }
        });
        if (exists) {
          return;
        }
        var node = document.createElement('div');
        node.className = 'media-thumb';
        node.setAttribute('data-gallery-item', '');
        node.setAttribute('data-existing', '1');
        node.innerHTML =
          '<div class="media-thumb__frame">' +
          '<img alt="" />' +
          '<button class="media-thumb__remove" type="button" data-gallery-remove aria-label="Remove image"></button>' +
          '</div>' +
          '<p class="media-thumb__name"><span class="media-file-meta__check" aria-hidden="true"></span><span></span></p>' +
          '<input type="hidden" name="' + galleryKeepName + '" value="" />';
        var img = node.querySelector('img');
        var nameEl = node.querySelector('.media-thumb__name span:last-child');
        var removeBtn = node.querySelector('[data-gallery-remove]');
        var checkHost = node.querySelector('.media-file-meta__check');
        var keep = node.querySelector('input[name="' + galleryKeepName + '"]');
        var iconTrash = document.querySelector('#icon-trash');
        var sampleCheck = root.querySelector('.media-file-meta__check');
        if (iconTrash && removeBtn) {
          removeBtn.innerHTML = iconTrash.innerHTML;
        }
        if (sampleCheck && checkHost) {
          checkHost.innerHTML = sampleCheck.innerHTML;
        }
        if (img) {
          img.src = item.url;
        }
        if (nameEl) {
          nameEl.textContent = item.name || '';
        }
        if (keep) {
          keep.value = item.path;
        }
        if (galleryAdd) {
          galleryGrid.insertBefore(node, galleryAdd);
        } else {
          galleryGrid.appendChild(node);
        }
        room -= 1;
      });
      updateGalleryMeta();
    }

    function existingCount() {
      return galleryGrid ? galleryGrid.querySelectorAll('[data-gallery-item][data-existing="1"]').length : 0;
    }

    function totalGalleryCount() {
      return existingCount() + pendingGallery.length;
    }

    function syncGalleryInput() {
      if (!galleryInput || typeof DataTransfer === 'undefined') {
        return;
      }
      var transfer = new DataTransfer();
      pendingGallery.forEach(function (file) {
        transfer.items.add(file);
      });
      galleryInput.files = transfer.files;
    }

    function updateGalleryMeta() {
      var total = totalGalleryCount();
      var bytes = pendingGallery.reduce(function (sum, file) {
        return sum + (file.size || 0);
      }, 0);
      if (galleryCount) {
        galleryCount.textContent = 'Selected Images (' + total + '/' + maxGallery + ')';
      }
      if (galleryStatus) {
        if (total === 0) {
          galleryStatus.textContent = 'No images selected';
        } else if (pendingGallery.length && bytes) {
          galleryStatus.textContent = formatBytes(bytes) + ' new';
        } else {
          galleryStatus.textContent = 'Saved images';
        }
      }
      if (galleryEmpty) {
        galleryEmpty.hidden = total > 0;
      }
      if (galleryPanel) {
        galleryPanel.hidden = total === 0;
      }
      if (galleryAdd) {
        galleryAdd.hidden = total >= maxGallery;
      }
    }

    function renderPendingThumbs() {
      if (!galleryGrid) {
        return;
      }
      galleryGrid.querySelectorAll('[data-gallery-item][data-pending]').forEach(function (node) {
        node.remove();
      });
      pendingGallery.forEach(function (file, index) {
        var item = document.createElement('div');
        item.className = 'media-thumb';
        item.setAttribute('data-gallery-item', '');
        item.setAttribute('data-pending', String(index));
        item.innerHTML =
          '<div class="media-thumb__frame">' +
          '<img alt="" />' +
          '<button class="media-thumb__remove" type="button" data-gallery-remove aria-label="Remove image"></button>' +
          '</div>' +
          '<p class="media-thumb__name"><span class="media-file-meta__check" aria-hidden="true"></span><span></span></p>';
        var img = item.querySelector('img');
        var nameEl = item.querySelector('.media-thumb__name span:last-child');
        var removeBtn = item.querySelector('[data-gallery-remove]');
        var checkHost = item.querySelector('.media-file-meta__check');
        var iconTrash = document.querySelector('#icon-trash');
        var iconCheck = null;
        if (iconTrash && removeBtn) {
          removeBtn.innerHTML = iconTrash.innerHTML;
        }
        // Reuse check icon from an existing meta check if present.
        var sampleCheck = root.querySelector('.media-file-meta__check');
        if (sampleCheck && checkHost) {
          checkHost.innerHTML = sampleCheck.innerHTML;
        }
        if (nameEl) {
          nameEl.textContent = file.name;
        }
        if (img && file.type && file.type.indexOf('image/') === 0) {
          var reader = new FileReader();
          reader.onload = function () {
            img.src = reader.result;
          };
          reader.readAsDataURL(file);
        }
        if (galleryAdd) {
          galleryGrid.insertBefore(item, galleryAdd);
        } else {
          galleryGrid.appendChild(item);
        }
      });
      updateGalleryMeta();
    }

    function openCoverPicker() {
      if (coverInput) {
        coverInput.click();
      }
    }

    function openGalleryPicker() {
      if (galleryInput) {
        galleryInput.click();
      }
    }

    function bindDropTarget(zone, onFiles) {
      if (!zone) {
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
          onFiles(Array.prototype.slice.call(event.dataTransfer.files));
        }
      });
    }

    bindDropTarget(coverEmpty, function (files) {
      if (!coverInput || !files[0]) {
        return;
      }
      var transfer = new DataTransfer();
      transfer.items.add(files[0]);
      coverInput.files = transfer.files;
      coverInput.dispatchEvent(new Event('change', { bubbles: true }));
    });

    if (coverEmpty) {
      coverEmpty.addEventListener('click', function (event) {
        if (event.target.closest('[data-cover-browse], [data-cover-library]')) {
          return;
        }
        openCoverPicker();
      });
    }
    root.querySelectorAll('[data-cover-browse], [data-cover-replace]').forEach(function (btn) {
      btn.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        openCoverPicker();
      });
    });
    if (coverLibraryBtn) {
      coverLibraryBtn.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        if (window.YNMedia && typeof window.YNMedia.open === 'function') {
          window.YNMedia.open({
            mode: 'single',
            onSelect: function (items) {
              if (items && items[0]) {
                applyLibraryCover(items[0]);
              }
            }
          });
        }
      });
    }
    if (galleryLibraryBtn) {
      galleryLibraryBtn.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        var room = maxGallery - existingCount() - pendingGallery.length;
        if (room <= 0 || !window.YNMedia) {
          return;
        }
        window.YNMedia.open({
          mode: 'multiple',
          max: room,
          onSelect: function (items) {
            appendLibraryGalleryItems(items || []);
          }
        });
      });
    }
    var clearBtn = root.querySelector('[data-cover-clear]');
    if (clearBtn) {
      clearBtn.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        showCoverEmpty(true);
      });
    }
    if (coverInput) {
      coverInput.addEventListener('change', function () {
        var file = coverInput.files && coverInput.files[0];
        if (!file) {
          return;
        }
        if (coverRemove) {
          coverRemove.value = '0';
        }
        if (coverLibrary) {
          coverLibrary.value = '';
        }
        var reader = new FileReader();
        reader.onload = function () {
          showCoverFilled(reader.result, file.name, formatBytes(file.size));
        };
        reader.readAsDataURL(file);
      });
    }

    if (galleryEmpty) {
      galleryEmpty.addEventListener('click', function (event) {
        if (event.target.closest('[data-gallery-browse], [data-gallery-library]')) {
          return;
        }
        openGalleryPicker();
      });
    }
    bindDropTarget(galleryEmpty, function (files) {
      mergeGalleryFiles(files);
    });
    root.querySelectorAll('[data-gallery-browse], [data-gallery-add]').forEach(function (btn) {
      btn.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        openGalleryPicker();
      });
    });

    function mergeGalleryFiles(files) {
      var room = maxGallery - existingCount() - pendingGallery.length;
      if (room <= 0) {
        updateGalleryMeta();
        return;
      }
      files.forEach(function (file) {
        if (room <= 0) {
          return;
        }
        if (!file.type || file.type.indexOf('image/') !== 0) {
          return;
        }
        pendingGallery.push(file);
        room -= 1;
      });
      syncGalleryInput();
      renderPendingThumbs();
    }

    if (galleryInput) {
      galleryInput.addEventListener('change', function () {
        if (!galleryInput.files || !galleryInput.files.length) {
          return;
        }
        mergeGalleryFiles(Array.prototype.slice.call(galleryInput.files));
      });
    }

    if (galleryGrid) {
      galleryGrid.addEventListener('click', function (event) {
        var removeBtn = event.target.closest('[data-gallery-remove]');
        if (!removeBtn) {
          return;
        }
        event.preventDefault();
        var item = removeBtn.closest('[data-gallery-item]');
        if (!item) {
          return;
        }
        if (item.getAttribute('data-existing') === '1') {
          item.remove();
          updateGalleryMeta();
          return;
        }
        var pendingIndex = parseInt(item.getAttribute('data-pending') || '-1', 10);
        if (pendingIndex >= 0) {
          pendingGallery.splice(pendingIndex, 1);
          syncGalleryInput();
          renderPendingThumbs();
        }
      });
    }

    updateGalleryMeta();
    }
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
