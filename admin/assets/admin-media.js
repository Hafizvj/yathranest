/**
 * Media library picker — open via:
 *   window.YNMedia.open({ mode: 'single'|'multiple', max: 10, onSelect: function(items){} })
 * Or data attributes:
 *   data-open-media-picker data-media-mode="single" data-media-target="#path-input" data-media-preview="#img"
 *   data-media-mode="multiple" data-media-max="10" data-media-on="gallery" (custom events)
 */
(function () {
  'use strict';

  var root = document.querySelector('[data-media-picker]');
  if (!root) {
    return;
  }

  var api = root.getAttribute('data-api') || '';
  var grid = root.querySelector('[data-media-picker-grid]');
  var empty = root.querySelector('[data-media-picker-empty]');
  var search = root.querySelector('[data-media-picker-search]');
  var status = root.querySelector('[data-media-picker-status]');
  var hint = root.querySelector('[data-media-picker-hint]');
  var confirmBtn = root.querySelector('[data-media-picker-confirm]');
  var loadMoreBtn = root.querySelector('[data-media-picker-more]');
  var uploadInput = root.querySelector('[data-media-picker-upload]');
  var csrfEl = document.getElementById('media-picker-csrf');
  var csrf = '';
  try {
    csrf = csrfEl ? (JSON.parse(csrfEl.textContent || '{}').token || '') : '';
  } catch (e) {
    csrf = '';
  }

  var state = {
    mode: 'single',
    max: 1,
    selected: {},
    items: [],
    onSelect: null,
    query: '',
    loading: false,
    page: 1,
    total: 0
  };

  function itemKey(item) {
    if (!item) {
      return '';
    }
    if (item.key) {
      return String(item.key);
    }
    if (item.path) {
      return 'path:' + item.path;
    }
    return 'id:' + String(item.id || 0);
  }

  function selectedList() {
    return Object.keys(state.selected).map(function (key) {
      return state.selected[key];
    });
  }

  function setOpen(open) {
    root.hidden = !open;
    document.body.classList.toggle('media-picker-open', open);
    if (open && search) {
      search.focus();
    }
  }

  function updateStatusText() {
    if (!status) {
      return;
    }
    var count = selectedList().length;
    if (state.loading) {
      status.textContent = 'Loading…';
      return;
    }
    var loaded = state.items.length;
    var summary = state.total > 0 ? 'Showing ' + loaded + ' of ' + state.total : '';
    if (state.mode === 'multiple') {
      status.textContent = summary
        ? count + ' / ' + state.max + ' selected · ' + summary
        : count + ' / ' + state.max + ' selected';
      return;
    }
    status.textContent = summary || (count ? '1 selected' : '');
  }

  function updateConfirm() {
    var count = selectedList().length;
    if (confirmBtn) {
      confirmBtn.disabled = count === 0;
      confirmBtn.textContent = state.mode === 'multiple'
        ? 'Use selected (' + count + ')'
        : 'Use selected';
    }
    updateStatusText();
  }

  function updateLoadMore() {
    if (!loadMoreBtn) {
      return;
    }
    var hasMore = state.items.length < state.total;
    loadMoreBtn.hidden = !hasMore || state.loading;
    loadMoreBtn.disabled = state.loading;
    loadMoreBtn.textContent = state.loading ? 'Loading…' : 'Load more images';
  }

  function render() {
    if (!grid) {
      return;
    }
    grid.innerHTML = '';
    if (!state.items.length) {
      if (empty) {
        empty.hidden = false;
      }
      updateConfirm();
      updateLoadMore();
      return;
    }
    if (empty) {
      empty.hidden = true;
    }
    state.items.forEach(function (item) {
      var key = itemKey(item);
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'media-picker__item' + (state.selected[key] ? ' is-selected' : '');
      btn.setAttribute('data-key', key);
      btn.innerHTML =
        '<img src="' + item.url + '" alt="" loading="lazy" />' +
        '<span class="media-picker__item-name"></span>';
      var nameEl = btn.querySelector('.media-picker__item-name');
      if (nameEl) {
        nameEl.textContent = item.name || '';
      }
      btn.addEventListener('click', function () {
        toggleSelect(item);
      });
      grid.appendChild(btn);
    });
    updateConfirm();
    updateLoadMore();
  }

  function toggleSelect(item) {
    var key = itemKey(item);
    if (state.mode === 'single') {
      state.selected = {};
      state.selected[key] = item;
      render();
      return;
    }
    if (state.selected[key]) {
      delete state.selected[key];
    } else {
      if (selectedList().length >= state.max) {
        return;
      }
      state.selected[key] = item;
    }
    render();
  }

  function mergeItems(incoming) {
    var seen = {};
    state.items.forEach(function (item) {
      seen[item.path] = true;
    });
    incoming.forEach(function (item) {
      if (!seen[item.path]) {
        state.items.push(item);
        seen[item.path] = true;
      }
    });
  }

  function load(page, append) {
    if (!api || state.loading) {
      return Promise.resolve();
    }
    state.loading = true;
    updateStatusText();
    updateLoadMore();
    var url = api + '?page=' + (page || 1) + '&q=' + encodeURIComponent(state.query || '');
    return fetch(url, { credentials: 'same-origin' })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        state.loading = false;
        if (!data || !data.ok) {
          if (!append) {
            state.items = [];
            state.total = 0;
            state.page = 1;
          }
          render();
          if (status) {
            status.textContent = (data && data.error) || 'Could not load library.';
          }
          updateLoadMore();
          return;
        }
        state.page = data.page || page || 1;
        state.total = data.total || 0;
        var incoming = data.items || [];
        if (append) {
          mergeItems(incoming);
        } else {
          state.items = incoming;
        }
        render();
      })
      .catch(function () {
        state.loading = false;
        if (!append) {
          state.items = [];
          state.total = 0;
          state.page = 1;
        }
        render();
        if (status) {
          status.textContent = 'Could not load library.';
        }
        updateLoadMore();
      });
  }

  function loadAllRemaining() {
    if (state.items.length >= state.total || state.loading) {
      return Promise.resolve();
    }
    return load(state.page + 1, true).then(function () {
      if (state.items.length < state.total) {
        return loadAllRemaining();
      }
    });
  }

  function openPicker(options) {
    options = options || {};
    state.mode = options.mode === 'multiple' ? 'multiple' : 'single';
    state.max = Math.max(1, parseInt(options.max || (state.mode === 'multiple' ? 10 : 1), 10) || 1);
    state.onSelect = typeof options.onSelect === 'function' ? options.onSelect : null;
    state.selected = {};
    state.query = '';
    state.page = 1;
    state.total = 0;
    state.items = [];
    if (search) {
      search.value = '';
    }
    if (hint) {
      hint.textContent = state.mode === 'multiple'
        ? 'Select up to ' + state.max + ' images.'
        : 'Select an image to use.';
    }
    setOpen(true);
    updateConfirm();
    load(1, false).then(function () {
      return loadAllRemaining();
    });
  }

  function closePicker() {
    setOpen(false);
    state.onSelect = null;
  }

  function confirmSelection() {
    var items = selectedList();
    if (!items.length) {
      return;
    }
    var cb = state.onSelect;
    closePicker();
    if (cb) {
      cb(state.mode === 'single' ? items.slice(0, 1) : items);
    }
  }

  root.querySelectorAll('[data-media-picker-close]').forEach(function (el) {
    el.addEventListener('click', closePicker);
  });
  if (confirmBtn) {
    confirmBtn.addEventListener('click', confirmSelection);
  }
  if (loadMoreBtn) {
    loadMoreBtn.addEventListener('click', function () {
      if (state.items.length >= state.total) {
        return;
      }
      load(state.page + 1, true);
    });
  }
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && !root.hidden) {
      closePicker();
    }
  });

  var searchTimer = null;
  if (search) {
    search.addEventListener('input', function () {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        state.query = search.value.trim();
        load(1, false).then(function () {
          return loadAllRemaining();
        });
      }, 250);
    });
  }

  if (uploadInput) {
    uploadInput.addEventListener('change', function () {
      var files = uploadInput.files ? Array.prototype.slice.call(uploadInput.files) : [];
      uploadInput.value = '';
      if (!files.length || !api) {
        return;
      }
      var chain = Promise.resolve();
      files.forEach(function (file) {
        chain = chain.then(function () {
          var body = new FormData();
          body.append('action', 'upload');
          body.append('_csrf', csrf);
          body.append('file', file);
          return fetch(api, { method: 'POST', body: body, credentials: 'same-origin' })
            .then(function (res) { return res.json(); })
            .then(function (data) {
              if (data && data.ok && data.item) {
                state.items.unshift(data.item);
                state.total += 1;
                var key = itemKey(data.item);
                if (state.mode === 'single') {
                  state.selected = {};
                  state.selected[key] = data.item;
                } else if (selectedList().length < state.max) {
                  state.selected[key] = data.item;
                }
              }
            });
        });
      });
      chain.then(function () {
        render();
      });
    });
  }

  document.addEventListener('click', function (event) {
    var trigger = event.target.closest('[data-open-media-picker]');
    if (!trigger) {
      return;
    }
    event.preventDefault();
    var mode = trigger.getAttribute('data-media-mode') || 'single';
    var max = parseInt(trigger.getAttribute('data-media-max') || '10', 10) || 10;
    var targetSel = trigger.getAttribute('data-media-target');
    var previewSel = trigger.getAttribute('data-media-preview');
    var nameSel = trigger.getAttribute('data-media-name');
    var eventName = trigger.getAttribute('data-media-event');

    openPicker({
      mode: mode,
      max: max,
      onSelect: function (items) {
        if (eventName) {
          document.dispatchEvent(new CustomEvent(eventName, { detail: { items: items, trigger: trigger } }));
        }
        if (mode === 'single' && items[0]) {
          var item = items[0];
          if (targetSel) {
            var target = document.querySelector(targetSel);
            if (target) {
              target.value = item.path;
              target.dispatchEvent(new Event('change', { bubbles: true }));
            }
          }
          if (previewSel) {
            var img = document.querySelector(previewSel);
            if (img) {
              img.src = item.url;
              img.hidden = false;
              var wrap = img.closest('[hidden]');
              if (wrap && wrap !== img) {
                wrap.hidden = false;
              }
            }
          }
          if (nameSel) {
            var nameEl = document.querySelector(nameSel);
            if (nameEl) {
              nameEl.textContent = item.name;
            }
          }
        }
      }
    });
  });

  window.YNMedia = {
    open: openPicker,
    close: closePicker
  };
})();
