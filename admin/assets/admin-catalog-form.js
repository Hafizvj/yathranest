/**
 * Catalog edit (resorts, getaways, gift cards, investment plans):
 * live card preview, a slug placeholder that follows the title, and the
 * gift card resort picker (search, select all / clear, count).
 */
(function () {
  'use strict';

  var form = document.querySelector('[data-catalog-form]');
  if (!form) {
    return;
  }

  function slugify(text) {
    return String(text || '')
      .toLowerCase()
      .normalize('NFKD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '');
  }

  function sources(name) {
    return Array.prototype.slice.call(form.querySelectorAll('[data-preview-source="' + name + '"]'));
  }

  var titleOut = form.querySelector('[data-preview-title]');
  var metaOut = form.querySelector('[data-preview-meta]');
  var textOut = form.querySelector('[data-preview-text]');
  var titleFallback = titleOut ? titleOut.textContent : '';
  var slugInput = form.querySelector('[data-slug-input]');
  var slugFallback = slugInput ? slugInput.getAttribute('placeholder') : '';

  function firstValue(list) {
    for (var i = 0; i < list.length; i++) {
      var value = list[i].value.trim();
      if (value) {
        return value;
      }
    }
    return '';
  }

  function render() {
    var title = firstValue(sources('title'));
    if (titleOut) {
      titleOut.textContent = title || titleFallback;
      titleOut.classList.toggle('is-placeholder', !title);
    }
    if (metaOut) {
      var meta = [firstValue(sources('location')), firstValue(sources('category'))].filter(Boolean);
      metaOut.textContent = meta.length ? meta.join(' · ') : 'Location';
      metaOut.parentElement.classList.toggle('is-placeholder', !meta.length);
    }
    if (textOut) {
      var text = firstValue(sources('text'));
      textOut.textContent = text || 'Your summary will appear here.';
      textOut.classList.toggle('is-placeholder', !text);
    }
    if (slugInput) {
      slugInput.setAttribute('placeholder', slugify(title) || slugFallback);
    }
  }

  form.addEventListener('input', function (event) {
    if (event.target && event.target.hasAttribute('data-preview-source')) {
      render();
    }
  });
  // Suggestion picks set the value without firing input.
  form.addEventListener('mouseup', function () {
    window.setTimeout(render, 0);
  });
  render();

  var picker = form.querySelector('[data-resort-picker]');
  var resortsOut = form.querySelector('[data-preview-resorts] span');
  if (picker) {
    var options = Array.prototype.slice.call(picker.querySelectorAll('[data-resort-option]'));
    var countOut = picker.querySelector('[data-resort-count]');
    var search = picker.querySelector('[data-resort-search]');
    var noMatch = picker.querySelector('[data-resort-nomatch]');

    var boxOf = function (option) {
      return option.querySelector('input[type="checkbox"]');
    };
    var updateCount = function () {
      var n = options.filter(function (option) { return boxOf(option).checked; }).length;
      if (countOut) {
        countOut.textContent = n + ' selected';
        countOut.classList.toggle('is-empty', n === 0);
      }
      if (resortsOut) {
        resortsOut.textContent = n ? 'Valid at ' + n + ' resort' + (n === 1 ? '' : 's') : 'No resorts linked';
        resortsOut.parentElement.classList.toggle('is-placeholder', n === 0);
      }
    };
    var visible = function () {
      return options.filter(function (option) { return !option.hidden; });
    };

    picker.addEventListener('change', updateCount);
    if (search) {
      search.addEventListener('input', function () {
        var q = search.value.trim().toLowerCase();
        options.forEach(function (option) {
          option.hidden = q !== '' && option.getAttribute('data-search').indexOf(q) === -1;
        });
        if (noMatch) {
          noMatch.hidden = visible().length > 0;
        }
      });
      search.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
          event.preventDefault();
        }
      });
    }
    [['[data-resort-all]', true], ['[data-resort-none]', false]].forEach(function (pair) {
      var btn = picker.querySelector(pair[0]);
      if (btn) {
        btn.addEventListener('click', function () {
          visible().forEach(function (option) { boxOf(option).checked = pair[1]; });
          updateCount();
        });
      }
    });
    updateCount();
  } else if (resortsOut) {
    resortsOut.textContent = 'No resorts linked';
  }

  var coverImg = form.querySelector('[data-cover-img]');
  var previewImg = form.querySelector('[data-preview-img]');
  var previewEmpty = form.querySelector('[data-preview-img-empty]');
  if (coverImg && previewImg) {
    var syncCover = function () {
      var src = coverImg.getAttribute('src') || '';
      var visible = src !== '' && !coverImg.hidden;
      if (visible) {
        previewImg.src = src;
      }
      previewImg.hidden = !visible;
      if (previewEmpty) {
        previewEmpty.hidden = visible;
      }
    };
    new MutationObserver(syncCover).observe(coverImg, { attributes: true, attributeFilter: ['src', 'hidden'] });
    syncCover();
  }
})();
