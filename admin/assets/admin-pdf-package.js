(function () {
  'use strict';

  var form = document.querySelector('[data-pdf-package-form]');
  if (!form) {
    return;
  }

  var parseUrl = form.getAttribute('data-pdf-parse-url') || '';
  var saveDraftUrl = form.getAttribute('data-save-draft-url') || '';
  var quickAddPlaceUrl = form.getAttribute('data-quick-add-place-url') || '';
  var catalogScopes = {};
  var startStep = parseInt(form.getAttribute('data-pdf-start-step') || '1', 10) || 1;
  var places = [];
  var types = {};
  var highlightSuggestions = [];
  var pickupSuggestions = [];
  var plans = [];
  var step = startStep;

  try {
    places = JSON.parse(form.getAttribute('data-places') || '[]');
  } catch (e) {
    places = [];
  }
  try {
    types = JSON.parse(form.getAttribute('data-types') || '{}');
  } catch (e2) {
    types = {};
  }
  try {
    highlightSuggestions = JSON.parse(form.getAttribute('data-highlights') || '[]');
  } catch (e3) {
    highlightSuggestions = [];
  }
  try {
    pickupSuggestions = JSON.parse(form.getAttribute('data-pickups') || '[]');
  } catch (e4) {
    pickupSuggestions = [];
  }
  try {
    catalogScopes = JSON.parse(form.getAttribute('data-catalog-scopes') || '{}');
  } catch (e5) {
    catalogScopes = { kerala: 'Kerala' };
  }

  var panels = {
    1: form.querySelector('[data-pdf-panel="1"]'),
    2: form.querySelector('[data-pdf-panel="2"]'),
    3: form.querySelector('[data-pdf-panel="3"]'),
  };
  var indicators = document.querySelectorAll('[data-ai-step-indicator]');
  var wizard = document.querySelector('[data-ai-wizard]');
  var progress = document.querySelector('[data-ai-progress]');
  var errorBox = form.querySelector('[data-pdf-error]');
  var errorText = errorBox ? errorBox.querySelector('span') : null;
  var warnBox = form.querySelector('[data-pdf-warnings]');
  var warnText = warnBox ? warnBox.querySelector('span') : null;
  var statusEl = form.querySelector('[data-pdf-status]');
  var summaryEl = form.querySelector('[data-pdf-summary]');
  var plansList = form.querySelector('[data-pdf-plans-list]');
  var plansSidebar = form.querySelector('[data-pdf-plans-sidebar]');
  var mediaSidebar = form.querySelector('[data-pdf-media-sidebar]');
  var mediaEditor = form.querySelector('[data-pdf-media-editor]');
  var toastEl = form.querySelector('[data-pdf-toast]');
  var pdfFileInput = form.querySelector('[data-pdf-file]');
  var pdfFileName = form.querySelector('[data-pdf-file-name]');
  var pdfTokenInput = form.querySelector('[data-pdf-token]');
  var pdfFilenameInput = form.querySelector('[data-pdf-filename]');
  var pdfAttachedHint = form.querySelector('[data-pdf-attached-hint]');
  var btnBack = form.querySelector('[data-pdf-back]');
  var btnNext = form.querySelector('[data-pdf-next]');
  var btnParse = form.querySelector('[data-pdf-parse]');
  var btnSaveDraft = form.querySelector('[data-pdf-save-draft]');
  var btnPreview = form.querySelector('[data-pdf-preview]');
  var btnPublish = form.querySelector('[data-pdf-publish]');
  var btnCancel = form.querySelector('[data-pdf-cancel]');
  var saveModeInput = form.querySelector('[data-save-mode]');
  var wizardStepInput = form.querySelector('[data-wizard-step]');
  var csrfInput = form.querySelector('input[name="_csrf"]');
  var activePlanIndex = 0;
  var planPreviewUrls = {};
  var placeModal = document.querySelector('[data-pdf-place-modal]');
  var placeModalHint = placeModal ? placeModal.querySelector('[data-pdf-place-modal-hint]') : null;
  var placeModalLabel = placeModal ? placeModal.querySelector('[data-pdf-place-modal-label]') : null;
  var placeModalScope = placeModal ? placeModal.querySelector('[data-pdf-place-modal-scope]') : null;
  var placeModalError = placeModal ? placeModal.querySelector('[data-pdf-place-modal-error]') : null;
  var placeModalAdd = placeModal ? placeModal.querySelector('[data-pdf-place-modal-add]') : null;
  var placeModalSkip = placeModal ? placeModal.querySelector('[data-pdf-place-modal-skip]') : null;
  var placeModalBackdrop = placeModal ? placeModal.querySelector('[data-pdf-place-modal-backdrop]') : null;
  var placeModalResolver = null;
  var placeModalBusy = false;

  function pickerCaretHtml() {
    var tpl = document.getElementById('icon-chevron-down');
    return tpl ? tpl.innerHTML : '';
  }

  function collectUnknownPlaces(planList) {
    var seen = {};
    var out = [];
    (planList || []).forEach(function (plan) {
      (plan.unmatched_destinations || []).concat(plan.unmatched_stays || []).forEach(function (name) {
        name = String(name || '').trim();
        if (name && !seen[name]) {
          seen[name] = true;
          out.push(name);
        }
      });
    });
    return out;
  }

  function addPlaceToCatalog(slug, label) {
    var exists = places.some(function (p) {
      return p.slug === slug;
    });
    if (!exists) {
      places.push({ slug: slug, label: label });
      places.sort(function (a, b) {
        return String(a.label).localeCompare(String(b.label));
      });
      form.setAttribute('data-places', JSON.stringify(places));
    }
  }

  function applyPlaceToPlans(rawName, slug) {
    plans.forEach(function (plan) {
      var destUnmatched = plan.unmatched_destinations || [];
      var stayUnmatched = plan.unmatched_stays || [];
      if (destUnmatched.indexOf(rawName) !== -1) {
        plan.destinations = plan.destinations || [];
        if (plan.destinations.indexOf(slug) === -1) {
          plan.destinations.push(slug);
        }
        plan.unmatched_destinations = destUnmatched.filter(function (n) {
          return n !== rawName;
        });
      }
      if (stayUnmatched.indexOf(rawName) !== -1) {
        plan.stays = plan.stays || [];
        var nights = Math.max(0, parseInt(plan.nights || '0', 10) || 0);
        while (plan.stays.length < nights) {
          plan.stays.push(slug);
        }
        for (var i = 0; i < plan.stays.length; i++) {
          if (!plan.stays[i]) {
            plan.stays[i] = slug;
          }
        }
        plan.unmatched_stays = stayUnmatched.filter(function (n) {
          return n !== rawName;
        });
      }
    });
  }

  function skipPlaceOnPlans(rawName) {
    plans.forEach(function (plan) {
      plan.unmatched_destinations = (plan.unmatched_destinations || []).filter(function (n) {
        return n !== rawName;
      });
      plan.unmatched_stays = (plan.unmatched_stays || []).filter(function (n) {
        return n !== rawName;
      });
    });
  }

  function closePlaceModal() {
    if (!placeModal) {
      return;
    }
    placeModal.hidden = true;
    document.body.classList.remove('pdf-place-modal-open');
    if (placeModalError) {
      placeModalError.hidden = true;
      placeModalError.textContent = '';
    }
  }

  function showPlaceModal(rawName, done) {
    if (!placeModal || !placeModalLabel || !placeModalHint) {
      done('skip');
      return;
    }
    placeModalResolver = done;
    placeModalLabel.value = rawName;
    placeModalHint.textContent =
      '"' + rawName + '" was not found in your places catalog. Add it now or skip to continue without mapping it.';
    if (placeModalError) {
      placeModalError.hidden = true;
      placeModalError.textContent = '';
    }
    placeModal.hidden = false;
    document.body.classList.add('pdf-place-modal-open');
    placeModalLabel.focus();
    placeModalLabel.select();
  }

  function resolveUnknownPlaces(queue) {
    if (!queue.length) {
      return Promise.resolve();
    }
    var rawName = queue[0];
    return new Promise(function (resolve) {
      showPlaceModal(rawName, function (action, slug, label) {
        if (action === 'add' && slug) {
          addPlaceToCatalog(slug, label);
          applyPlaceToPlans(rawName, slug);
        } else {
          skipPlaceOnPlans(rawName);
        }
        resolveUnknownPlaces(queue.slice(1)).then(resolve);
      });
    });
  }

  function finishPlaceModal(action, slug, label) {
    if (!placeModalResolver) {
      closePlaceModal();
      return;
    }
    var resolver = placeModalResolver;
    placeModalResolver = null;
    closePlaceModal();
    resolver(action, slug, label);
  }

  function quickAddPlace(label, scope) {
    if (!quickAddPlaceUrl) {
      return Promise.resolve({ ok: false, error: 'Add-place endpoint is not configured.' });
    }
    var fd = new FormData();
    fd.append('_csrf', csrfInput ? csrfInput.value : '');
    fd.append('label', label);
    fd.append('catalog_scope', scope || 'kerala');
    return fetch(quickAddPlaceUrl, {
      method: 'POST',
      body: fd,
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-Token': csrfInput ? csrfInput.value : '',
      },
    }).then(function (res) {
      return res.json();
    });
  }

  if (placeModalAdd) {
    placeModalAdd.addEventListener('click', function () {
      if (placeModalBusy || !placeModalLabel) {
        return;
      }
      var label = String(placeModalLabel.value || '').trim();
      var scope = placeModalScope ? placeModalScope.value : 'kerala';
      if (!label) {
        if (placeModalError) {
          placeModalError.textContent = 'Enter a place name.';
          placeModalError.hidden = false;
        }
        return;
      }
      placeModalBusy = true;
      placeModalAdd.disabled = true;
      quickAddPlace(label, scope)
        .then(function (data) {
          if (!data || !data.ok) {
            if (placeModalError) {
              placeModalError.textContent = (data && data.error) || 'Could not add place.';
              placeModalError.hidden = false;
            }
            return;
          }
          finishPlaceModal('add', data.slug, data.label);
        })
        .catch(function () {
          if (placeModalError) {
            placeModalError.textContent = 'Could not reach the server. Try again.';
            placeModalError.hidden = false;
          }
        })
        .finally(function () {
          placeModalBusy = false;
          placeModalAdd.disabled = false;
        });
    });
  }

  if (placeModalSkip) {
    placeModalSkip.addEventListener('click', function () {
      if (placeModalBusy) {
        return;
      }
      finishPlaceModal('skip');
    });
  }

  if (placeModalBackdrop) {
    placeModalBackdrop.addEventListener('click', function () {
      if (!placeModalBusy) {
        finishPlaceModal('skip');
      }
    });
  }

  function escHtml(s) {
    return String(s || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function showToast(message) {
    if (!toastEl) {
      return;
    }
    if (!message) {
      toastEl.hidden = true;
      toastEl.textContent = '';
      return;
    }
    toastEl.hidden = false;
    toastEl.textContent = message;
    window.setTimeout(function () {
      if (toastEl.textContent === message) {
        showToast('');
      }
    }, 3000);
  }

  function planLabelAt(index) {
    var panel = plansList ? plansList.querySelector('[data-plan-panel="' + index + '"]') : null;
    if (panel) {
      var title = (panel.querySelector('.form-card__title') || {}).textContent || '';
      if (title) {
        return title;
      }
    }
    return (plans[index] && plans[index].plan_label) || 'Plan ' + (index + 1);
  }

  function planIsComplete(index) {
    var panel = plansList ? plansList.querySelector('[data-plan-panel="' + index + '"]') : null;
    if (!panel) {
      return false;
    }
    var prefix = panel.getAttribute('data-plan-prefix') || '';
    var titleEl = panel.querySelector('[name="' + prefix + '[title]"]');
    var overviewEl = panel.querySelector('[name="' + prefix + '[overview]"]');
    return !!(titleEl && String(titleEl.value || '').trim() && overviewEl && String(overviewEl.value || '').trim());
  }

  function renderReviewSidebar() {
    if (!plansSidebar) {
      return;
    }
    plansSidebar.innerHTML = '';
    plans.forEach(function (plan, index) {
      var item = document.createElement('li');
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'pdf-plans-sidebar__item' + (index === activePlanIndex ? ' is-active' : '') + (planIsComplete(index) ? ' is-done' : '');
      btn.setAttribute('data-plan-sidebar', String(index));
      btn.textContent = plan.plan_label || 'Plan ' + (index + 1);
      btn.addEventListener('click', function () {
        selectReviewPlan(index);
      });
      item.appendChild(btn);
      plansSidebar.appendChild(item);
    });
  }

  function renderMediaSidebar() {
    if (!mediaSidebar) {
      return;
    }
    mediaSidebar.innerHTML = '';
    plans.forEach(function (plan, index) {
      var item = document.createElement('li');
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'pdf-plans-sidebar__item' + (index === activePlanIndex ? ' is-active' : '');
      btn.setAttribute('data-media-sidebar', String(index));
      btn.textContent = plan.plan_label || 'Plan ' + (index + 1);
      btn.addEventListener('click', function () {
        selectMediaPlan(index);
      });
      item.appendChild(btn);
      mediaSidebar.appendChild(item);
    });
  }

  function selectReviewPlan(index) {
    activePlanIndex = index;
    if (!plansList) {
      return;
    }
    plansList.querySelectorAll('[data-plan-panel]').forEach(function (panel) {
      var n = parseInt(panel.getAttribute('data-plan-panel') || '0', 10);
      panel.hidden = n !== index;
    });
    renderReviewSidebar();
  }

  function selectMediaPlan(index) {
    activePlanIndex = index;
    if (!mediaEditor) {
      return;
    }
    mediaEditor.querySelectorAll('[data-plan-media-panel]').forEach(function (panel) {
      var n = parseInt(panel.getAttribute('data-plan-media-panel') || '0', 10);
      panel.hidden = n !== index;
    });
    renderMediaSidebar();
  }

  function buildMediaPanel(plan, index) {
    var prefix = 'plans[' + index + ']';
    var label = plan.plan_label || 'Plan ' + (index + 1);
    var section = document.createElement('section');
    section.className = 'form-card ai-panel-card pdf-plan-media-panel';
    section.setAttribute('data-plan-media-panel', String(index));
    section.hidden = index !== activePlanIndex;
    section.innerHTML =
      '<div class="form-card__head"><div class="form-card__titles"><h2 class="form-card__title">' + escHtml(label) + ' — Media</h2>' +
      '<p class="form-card__hint">Cover and gallery for this package.</p></div></div>' +
      '<div class="form-card__body form-card__body--media">' +
      '<input type="hidden" name="' + escHtml(prefix) + '[package_id]" value="' + escHtml(plan.package_id || '') + '" data-plan-package-id />' +
      '<input type="hidden" name="' + escHtml(prefix) + '[plan_key]" value="' + escHtml(plan.plan_key || '') + '" />' +
      '<input type="hidden" name="' + escHtml(prefix) + '[plan_label]" value="' + escHtml(label) + '" />' +
      '<div class="media-split" data-package-media data-gallery-max="10">' +
      '<div class="media-col media-col--cover">' +
      '<span class="field__label">Cover <span class="field__req">*</span></span>' +
      '<input type="hidden" name="' + escHtml(prefix) + '[remove_image]" value="0" data-cover-remove />' +
      '<input type="hidden" name="' + escHtml(prefix) + '[library_image]" value="" data-cover-library />' +
      '<input class="media-file-input" type="file" name="' + escHtml(prefix) + '[image_file]" accept="image/jpeg,image/png,image/webp,image/gif" data-cover-input />' +
      '<div class="media-drop media-drop--cover" data-dropzone data-cover-empty>' +
      '<span class="media-drop__title">Upload cover image</span></div>' +
      '<div class="media-cover-card" data-cover-filled hidden><div class="media-cover-card__preview">' +
      '<img alt="Cover preview" data-cover-img hidden /></div></div></div>' +
      '<div class="media-col media-col--gallery">' +
      '<span class="field__label">Gallery</span>' +
      '<input class="media-file-input" type="file" name="' + escHtml(prefix) + '[gallery_files][]" accept="image/jpeg,image/png,image/webp,image/gif" multiple data-gallery-input />' +
      '<div class="media-drop media-drop--gallery" data-dropzone data-gallery-empty><span class="media-drop__title">Upload gallery images</span></div>' +
      '<div class="media-gallery-panel" data-gallery-panel hidden><div class="media-gallery-grid" data-gallery-grid></div></div>' +
      '</div></div>' +
      '<div class="field" style="margin-top:1rem"><span class="field__label">Price chart PDF</span>' +
      '<input class="form-control" type="file" name="' + escHtml(prefix) + '[price_chart_pdf_file]" accept="application/pdf,.pdf" /></div>' +
      '</div>';
    return section;
  }

  function buildMediaPanels() {
    if (!mediaEditor) {
      return;
    }
    syncPlansFromDom();
    mediaEditor.innerHTML = '';
    plans.forEach(function (plan, index) {
      mediaEditor.appendChild(buildMediaPanel(plan, index));
    });
    if (typeof window.ynInitPackageMediaPanels === 'function') {
      window.ynInitPackageMediaPanels(mediaEditor);
    }
    renderMediaSidebar();
    selectMediaPlan(activePlanIndex);
  }

  function syncPlansFromDom() {
    if (!plansList) {
      return;
    }
    var panels = Array.prototype.slice.call(plansList.querySelectorAll('[data-plan-panel]'));
    plans = panels.map(function (panel, index) {
      var existing = plans[index] || {};
      var collected = collectPlanFromPanel(panel, index);
      collected.unmatched_destinations = existing.unmatched_destinations || [];
      collected.unmatched_stays = existing.unmatched_stays || [];
      return collected;
    });
  }

  function syncPackageIdsFromResponse(packages) {
    if (!packages || !packages.length || !plansList) {
      return;
    }
    packages.forEach(function (pkg, index) {
      if (pkg.id) {
        var panel = plansList.querySelector('[data-plan-panel="' + index + '"]');
        if (panel) {
          var input = panel.querySelector('[data-plan-package-id]');
          if (input) {
            input.value = String(pkg.id);
          }
        }
        var mediaPanel = mediaEditor ? mediaEditor.querySelector('[data-plan-media-panel="' + index + '"]') : null;
        if (mediaPanel) {
          var mediaInput = mediaPanel.querySelector('[data-plan-package-id]');
          if (mediaInput) {
            mediaInput.value = String(pkg.id);
          }
        }
        if (pkg.preview_url) {
          planPreviewUrls[index] = pkg.preview_url;
        }
      }
      if (plans[index]) {
        plans[index].package_id = pkg.id;
      }
    });
  }

  function saveDraft() {
    if (!saveDraftUrl) {
      showError('Save endpoint is not configured.');
      return Promise.resolve(false);
    }
    syncPlansFromDom();
    if (wizardStepInput) {
      wizardStepInput.value = String(step);
    }
    var fd = new FormData(form);
    fd.set('save_mode', 'draft');
    fd.set('wizard_type', 'pdf');
    fd.set('wizard_step', String(step));

    if (btnSaveDraft) {
      btnSaveDraft.disabled = true;
    }
    showToast('Saving drafts…');

    return fetch(saveDraftUrl, {
      method: 'POST',
      body: fd,
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-Token': csrfInput ? csrfInput.value : '',
      },
    })
      .then(function (res) {
        return res.json();
      })
      .then(function (data) {
        if (!data || !data.ok) {
          showError((data && data.error) || 'Could not save drafts.');
          return false;
        }
        syncPackageIdsFromResponse(data.packages || []);
        showToast(data.message || 'Drafts saved.');
        return true;
      })
      .catch(function () {
        showError('Could not reach the save endpoint. Try again.');
        return false;
      })
      .finally(function () {
        if (btnSaveDraft) {
          btnSaveDraft.disabled = false;
        }
      });
  }

  function openPreview() {
    var url = planPreviewUrls[activePlanIndex];
    if (url) {
      window.open(url, '_blank', 'noopener');
      return;
    }
    saveDraft().then(function (ok) {
      var nextUrl = planPreviewUrls[activePlanIndex];
      if (ok && nextUrl) {
        window.open(nextUrl, '_blank', 'noopener');
      }
    });
  }

  function showError(message) {
    if (!errorBox || !errorText) {
      return;
    }
    if (!message) {
      errorBox.hidden = true;
      errorText.textContent = '';
      return;
    }
    errorText.textContent = message;
    errorBox.hidden = false;
  }

  function showWarnings(messages) {
    if (!warnBox || !warnText) {
      return;
    }
    if (!messages || !messages.length) {
      warnBox.hidden = true;
      warnText.textContent = '';
      return;
    }
    warnText.textContent = messages.join(' ');
    warnBox.hidden = false;
  }

  function setStatus(message, isBusy) {
    if (!statusEl) {
      return;
    }
    if (!message) {
      statusEl.hidden = true;
      statusEl.textContent = '';
      statusEl.classList.remove('is-busy');
      return;
    }
    statusEl.hidden = false;
    statusEl.textContent = message;
    statusEl.classList.toggle('is-busy', !!isBusy);
  }

  function initPicker(picker) {
    if (!picker || picker.classList.contains('is-enhanced')) {
      return;
    }
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
          box.dispatchEvent(new Event('change', { bubbles: true }));
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
      box.addEventListener('change', function () {
        renderChips();
        picker.dispatchEvent(new CustomEvent('picker-change', { bubbles: true }));
      });
    });
    document.addEventListener('click', function (event) {
      if (!picker.contains(event.target)) {
        setOpen(false);
      }
    });

    renderChips();
  }

  function initChips(root) {
    if (!root || root.classList.contains('is-enhanced')) {
      return;
    }
    var list = root.querySelector('[data-chips-list]');
    var entry = root.querySelector('[data-chips-entry]');
    var addBtn = root.querySelector('[data-chips-add]');
    var fieldName = root.getAttribute('data-chips');
    if (!list || !entry || !fieldName) {
      return;
    }
    root.classList.add('is-enhanced');

    function existingValues() {
      return Array.prototype.map
        .call(list.querySelectorAll('input[type="hidden"]'), function (input) {
          return String(input.value || '').trim().toLowerCase();
        })
        .filter(Boolean);
    }

    function addChip(value) {
      value = String(value || '').trim();
      if (!value || existingValues().indexOf(value.toLowerCase()) !== -1) {
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
      remove.innerHTML = '&times;';
      remove.addEventListener('click', function () {
        chip.remove();
      });
      chip.appendChild(remove);
      list.insertBefore(chip, entry);
    }

    function commitEntry() {
      addChip(entry.value);
      entry.value = '';
    }

    entry.addEventListener('keydown', function (event) {
      if (event.key === 'Enter') {
        event.preventDefault();
        commitEntry();
      }
    });
    if (addBtn) {
      addBtn.addEventListener('click', function () {
        commitEntry();
      });
    }
  }

  function checkedInPanel(panel, name) {
    return Array.prototype.map
      .call(panel.querySelectorAll('input[name="' + name + '"]:checked'), function (el) {
        return el.value;
      })
      .filter(Boolean);
  }

  function rebuildStays(panel) {
    var staysRoot = panel.querySelector('[data-plan-stays]');
    var grid = panel.querySelector('[data-stay-grid]');
    if (!staysRoot || !grid) {
      return;
    }
    var days = Math.max(1, parseInt((panel.querySelector('[data-plan-days]') || {}).value || '1', 10) || 1);
    var nights = Math.max(0, parseInt((panel.querySelector('[data-plan-nights]') || {}).value || '0', 10) || 0);
    if (nights > days) {
      nights = days;
    }
    var destSlugs = checkedInPanel(panel, panel.getAttribute('data-dest-name'));
    var stayPlaces = places.filter(function (p) {
      return destSlugs.indexOf(p.slug) !== -1;
    });
    var prefix = panel.getAttribute('data-plan-prefix') || '';
    var current = Array.prototype.map.call(grid.querySelectorAll('select'), function (sel) {
      return sel.value;
    });

    grid.innerHTML = '';
    if (nights < 1) {
      grid.innerHTML = '<p class="field__hint">No overnight stays.</p>';
      return;
    }
    if (!stayPlaces.length) {
      grid.innerHTML = '<p class="field__hint">Select destinations first.</p>';
      return;
    }

    for (var i = 0; i < nights; i++) {
      var item = document.createElement('div');
      item.className = 'stay-grid__item';
      var label = document.createElement('label');
      label.setAttribute('for', prefix + '-stay-' + i);
      label.textContent = 'Night ' + (i + 1);
      var select = document.createElement('select');
      select.className = 'form-control';
      select.id = prefix + '-stay-' + i;
      select.name = prefix + '[stays][]';
      var empty = document.createElement('option');
      empty.value = '';
      empty.textContent = 'Select a place';
      select.appendChild(empty);
      stayPlaces.forEach(function (place) {
        var opt = document.createElement('option');
        opt.value = place.slug;
        opt.textContent = place.label;
        if ((current[i] || '') === place.slug) {
          opt.selected = true;
        }
        select.appendChild(opt);
      });
      if (!select.value && stayPlaces[0]) {
        select.value = current[i] || stayPlaces[Math.min(i, stayPlaces.length - 1)].slug;
      }
      item.appendChild(label);
      item.appendChild(select);
      grid.appendChild(item);
    }
  }

  function rebuildItinerary(panel, dayCount) {
    var list = panel.querySelector('[data-day-list]');
    if (!list) {
      return;
    }
    dayCount = Math.max(1, dayCount || 1);
    var prefix = panel.getAttribute('data-plan-prefix') || '';
    var existing = Array.prototype.map.call(list.querySelectorAll('.day-item'), function (item) {
      return {
        title: (item.querySelector('.day-item__title') || {}).value || '',
        text: (item.querySelector('textarea') || {}).value || '',
      };
    });

    list.innerHTML = '';
    for (var i = 0; i < dayCount; i++) {
      var day = existing[i] || { title: '', text: '' };
      var wrap = document.createElement('div');
      wrap.className = 'day-item' + (i === 0 ? '' : ' is-collapsed');
      wrap.innerHTML =
        '<div class="day-item__head">' +
        '<span class="day-item__no">' + (i + 1) + '</span>' +
        '<span class="day-item__label">Day ' + (i + 1) + '</span>' +
        '<input class="day-item__title" type="text" name="' + escHtml(prefix) + '[itinerary_title][]" value="' + escHtml(day.title) + '" placeholder="Day title" aria-label="Day ' + (i + 1) + ' title" />' +
        '<span class="day-item__actions">' +
        '<button class="icon-btn day-toggle" type="button" data-day-toggle aria-expanded="' + (i === 0 ? 'true' : 'false') + '" aria-label="Toggle day details">' +
        (document.getElementById('icon-chevron-down') ? document.getElementById('icon-chevron-down').innerHTML : '') +
        '</button></span></div>' +
        '<div class="day-item__body">' +
        '<label class="field__label">Details</label>' +
        '<textarea class="form-control" name="' + escHtml(prefix) + '[itinerary_text][]" rows="3" maxlength="1000" placeholder="Activities, places, meals…">' + escHtml(day.text) + '</textarea>' +
        '</div>';
      list.appendChild(wrap);
      var toggle = wrap.querySelector('[data-day-toggle]');
      if (toggle) {
        toggle.addEventListener('click', function () {
          var open = wrap.classList.toggle('is-collapsed') === false;
          toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
      }
    }
  }

  function enhancePlanPanel(panel) {
    panel.querySelectorAll('[data-picker]').forEach(initPicker);
    panel.querySelectorAll('[data-chips]').forEach(initChips);
    rebuildStays(panel);

    var daysInput = panel.querySelector('[data-plan-days]');
    var nightsInput = panel.querySelector('[data-plan-nights]');
    function onDurationChange() {
      var days = Math.max(1, parseInt((daysInput || {}).value || '1', 10) || 1);
      rebuildItinerary(panel, days);
      rebuildStays(panel);
    }
    if (daysInput) {
      daysInput.addEventListener('input', onDurationChange);
    }
    if (nightsInput) {
      nightsInput.addEventListener('input', onDurationChange);
    }
    panel.addEventListener('picker-change', function () {
      rebuildStays(panel);
    });
  }

  function buildPlanPanel(plan, index) {
    var prefix = 'plans[' + index + ']';
    var destName = prefix + '[destinations][]';
    var typeName = prefix + '[types][]';
    var label = plan.plan_label || 'Plan ' + (index + 1);

    var unmatchedDestHtml = (plan.unmatched_destinations || [])
      .map(function (name) {
        return '<span class="chip chip--warn">' + escHtml(name) + ' <em>(not in catalog)</em></span>';
      })
      .join('');

    var destHtml = places
      .map(function (p) {
        var checked = (plan.destinations || []).indexOf(p.slug) !== -1 ? ' checked' : '';
        return '<label><input type="checkbox" name="' + escHtml(destName) + '" value="' + escHtml(p.slug) + '"' + checked + ' /> ' + escHtml(p.label) + '</label>';
      })
      .join('');

    var typeHtml = Object.keys(types)
      .map(function (key) {
        var checked = (plan.types || []).indexOf(key) !== -1 ? ' checked' : '';
        return '<label><input type="checkbox" name="' + escHtml(typeName) + '" value="' + escHtml(key) + '"' + checked + ' /> ' + escHtml(types[key]) + '</label>';
      })
      .join('');

    var highlightsHtml = (plan.highlights || [])
      .map(function (h) {
        return (
          '<span class="chip">' +
          escHtml(h) +
          '<input type="hidden" name="' + escHtml(prefix) + '[highlights][]" value="' + escHtml(h) + '" />' +
          '<button class="chip__remove" type="button" data-chip-remove aria-label="Remove">&times;</button></span>'
        );
      })
      .join('');

    var section = document.createElement('section');
    section.className = 'form-card ai-panel-card pdf-plan-panel';
    section.setAttribute('data-plan-panel', String(index));
    section.setAttribute('data-plan-prefix', prefix);
    section.setAttribute('data-dest-name', destName);
    section.hidden = index !== activePlanIndex;
    section.innerHTML =
      '<div class="form-card__head">' +
      '<div class="form-card__titles"><h2 class="form-card__title">' + escHtml(label) + '</h2></div>' +
      '<button class="btn btn--ghost btn--sm" type="button" data-plan-remove>Remove plan</button>' +
      '</div>' +
      '<div class="form-card__body">' +
      '<input type="hidden" name="' + escHtml(prefix) + '[plan_key]" value="' + escHtml(plan.plan_key || '') + '" />' +
      '<input type="hidden" name="' + escHtml(prefix) + '[plan_label]" value="' + escHtml(label) + '" />' +
      '<input type="hidden" name="' + escHtml(prefix) + '[package_id]" value="' + escHtml(plan.package_id || '') + '" data-plan-package-id />' +
      '<div class="field"><span class="field__label">Destinations <span class="field__req">*</span></span>' +
      '<p class="field__hint">Select one or more destinations. Chips show your current selection.</p>' +
      (unmatchedDestHtml ? '<div class="pdf-unmatched-dests">' + unmatchedDestHtml + '</div>' : '') +
      '<div class="picker" data-picker><div class="picker__control" data-picker-control">' +
      '<span class="picker__chips" data-picker-chips></span>' +
      '<button class="picker__toggle" type="button" data-picker-toggle aria-expanded="false">' +
      '<span class="picker__placeholder" data-picker-empty>Select destinations</span>' +
      '<span class="picker__caret">' + pickerCaretHtml() + '</span></button></div>' +
      '<div class="picker__panel" data-picker-panel">' + destHtml + '</div></div></div>' +
      '<div class="field"><span class="field__label">Type <span class="field__req">*</span></span>' +
      '<div class="picker" data-picker><div class="picker__control" data-picker-control">' +
      '<span class="picker__chips" data-picker-chips></span>' +
      '<button class="picker__toggle" type="button" data-picker-toggle aria-expanded="false">' +
      '<span class="picker__placeholder" data-picker-empty>Select types</span>' +
      '<span class="picker__caret">' + pickerCaretHtml() + '</span></button></div>' +
      '<div class="picker__panel" data-picker-panel">' + typeHtml + '</div></div></div>' +
      '<div class="field"><label>Pickup / Drop <span class="field__req">*</span></label>' +
      '<input class="form-control" name="' + escHtml(prefix) + '[pickup]" value="' + escHtml(plan.pickup || '') + '" /></div>' +
      '<div class="field"><label>Duration <span class="field__req">*</span></label>' +
      '<div class="duration-grid">' +
      '<input class="form-control" type="number" name="' + escHtml(prefix) + '[days]" min="1" max="60" value="' + escHtml(plan.days || 1) + '" data-plan-days aria-label="Days" />' +
      '<input class="form-control" type="number" name="' + escHtml(prefix) + '[nights]" min="0" max="60" value="' + escHtml(plan.nights || 0) + '" data-plan-nights aria-label="Nights" />' +
      '</div></div>' +
      '<div class="field full" data-plan-stays><span class="field__label">Stays <span class="field__req">*</span></span>' +
      '<div class="stay-grid" data-stay-grid></div></div>' +
      '<div class="field full" data-chips="' + escHtml(prefix) + '[highlights]" data-suggest="' + escHtml(JSON.stringify(highlightSuggestions)) + '">' +
      '<label>Highlights</label><div class="chips-input" data-chips-list>' + highlightsHtml +
      '<input class="chips-input__entry" type="text" data-chips-entry placeholder="Add highlight + Enter" /></div>' +
      '<button class="chips-add" type="button" data-chips-add>Add</button></div>' +
      '<div class="field full"><label>Title <span class="field__req">*</span></label>' +
      '<input class="form-control" name="' + escHtml(prefix) + '[title]" value="' + escHtml(plan.title || '') + '" /></div>' +
      '<div class="field full"><label>Card text</label>' +
      '<input class="form-control" name="' + escHtml(prefix) + '[card_text]" maxlength="90" value="' + escHtml(plan.card_text || '') + '" /></div>' +
      '<div class="field full"><label>Overview <span class="field__req">*</span></label>' +
      '<textarea class="form-control" name="' + escHtml(prefix) + '[overview]" rows="4" maxlength="500">' + escHtml(plan.overview || '') + '</textarea></div>' +
      '<div class="field full" data-days><span class="field__label">Itinerary</span>' +
      '<div class="day-list full" data-day-list></div></div>' +
      '</div>';

    section.querySelector('[data-plan-remove]').addEventListener('click', function () {
      section.remove();
      reindexPlans();
    });

    section.querySelectorAll('[data-chip-remove]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var chip = btn.closest('.chip');
        if (chip) {
          chip.remove();
        }
      });
    });

    enhancePlanPanel(section);
    rebuildItinerary(section, plan.days || 1);

    var itinerary = plan.itinerary || [];
    var list = section.querySelector('[data-day-list]');
    if (list && itinerary.length) {
      rebuildItinerary(section, Math.max(plan.days || 1, itinerary.length));
      Array.prototype.forEach.call(list.querySelectorAll('.day-item'), function (item, i) {
        var row = itinerary[i] || {};
        var title = item.querySelector('.day-item__title');
        var text = item.querySelector('textarea');
        if (title) {
          title.value = row.title || '';
        }
        if (text) {
          text.value = row.text || '';
        }
      });
    }

    var stays = plan.stays || [];
    if (stays.length) {
      rebuildStays(section);
      Array.prototype.forEach.call(section.querySelectorAll('[data-stay-grid] select'), function (sel, i) {
        if (stays[i]) {
          sel.value = stays[i];
        }
      });
    }

    return section;
  }

  function collectPlanFromPanel(panel, index) {
    var oldPrefix = panel.getAttribute('data-plan-prefix') || 'plans[0]';
    var prefix = 'plans[' + index + ']';
    panel.setAttribute('data-plan-prefix', prefix);
    panel.setAttribute('data-dest-name', prefix + '[destinations][]');

    panel.querySelectorAll('[name]').forEach(function (el) {
      if (el.name && el.name.indexOf(oldPrefix) === 0) {
        el.name = prefix + el.name.slice(oldPrefix.length);
      }
    });

    return {
      package_id: (panel.querySelector('[data-plan-package-id]') || {}).value || '',
      unmatched_destinations: (plans[index] && plans[index].unmatched_destinations) || [],
      unmatched_stays: (plans[index] && plans[index].unmatched_stays) || [],
      plan_key: (panel.querySelector('[name="' + prefix + '[plan_key]"]') || {}).value || '',
      plan_label: (panel.querySelector('[name="' + prefix + '[plan_label]"]') || {}).value || '',
      days: parseInt((panel.querySelector('[data-plan-days]') || {}).value || '1', 10) || 1,
      nights: parseInt((panel.querySelector('[data-plan-nights]') || {}).value || '0', 10) || 0,
      destinations: checkedInPanel(panel, prefix + '[destinations][]'),
      types: checkedInPanel(panel, prefix + '[types][]'),
      pickup: (panel.querySelector('[name="' + prefix + '[pickup]"]') || {}).value || '',
      stays: Array.prototype.map.call(panel.querySelectorAll('[data-stay-grid] select'), function (sel) {
        return sel.value;
      }),
      highlights: Array.prototype.map
        .call(panel.querySelectorAll('[name="' + prefix + '[highlights][]"]'), function (el) {
          return el.value;
        })
        .filter(Boolean),
      title: (panel.querySelector('[name="' + prefix + '[title]"]') || {}).value || '',
      card_text: (panel.querySelector('[name="' + prefix + '[card_text]"]') || {}).value || '',
      overview: (panel.querySelector('[name="' + prefix + '[overview]"]') || {}).value || '',
      itinerary: Array.prototype.map.call(panel.querySelectorAll('.day-item'), function (item, i) {
        return {
          day: i + 1,
          title: (item.querySelector('.day-item__title') || {}).value || '',
          text: (item.querySelector('textarea') || {}).value || '',
        };
      }),
    };
  }

  function reindexPlans() {
    if (!plansList) {
      return;
    }
    var panels = Array.prototype.slice.call(plansList.querySelectorAll('[data-plan-panel]'));
    var collected = panels.map(function (panel, index) {
      return collectPlanFromPanel(panel, index);
    });
    renderPlans(collected);
    plans = collected;
  }

  function renderPlans(planData) {
    if (!plansList) {
      return;
    }
    plansList.innerHTML = '';
    planData.forEach(function (plan, index) {
      plansList.appendChild(buildPlanPanel(plan, index));
    });
    renderReviewSidebar();
    selectReviewPlan(activePlanIndex < planData.length ? activePlanIndex : 0);
  }

  function validateStep2Structural() {
    if (!plansList) {
      return 'No plans to review.';
    }
    var panels = plansList.querySelectorAll('[data-plan-panel]');
    if (!panels.length) {
      return 'Keep at least one plan to create packages.';
    }
    for (var i = 0; i < panels.length; i++) {
      var panel = panels[i];
      var label = (panel.querySelector('.form-card__title') || {}).textContent || 'Plan ' + (i + 1);
      var prefix = panel.getAttribute('data-plan-prefix') || '';
      if (checkedInPanel(panel, prefix + '[destinations][]').length === 0) {
        return label + ': select at least one destination.';
      }
      if (checkedInPanel(panel, prefix + '[types][]').length === 0) {
        return label + ': select at least one type.';
      }
      var pickupEl = panel.querySelector('[name="' + prefix + '[pickup]"]');
      if (!pickupEl || !String(pickupEl.value || '').trim()) {
        return label + ': pickup / drop is required.';
      }
      var nights = Math.max(0, parseInt((panel.querySelector('[data-plan-nights]') || {}).value || '0', 10) || 0);
      if (nights > 0) {
        var stays = Array.prototype.map.call(panel.querySelectorAll('[data-stay-grid] select'), function (sel) {
          return sel.value;
        });
        if (stays.length !== nights || stays.some(function (s) { return !s; })) {
          return label + ': choose a stay for each night.';
        }
      }
    }
    return '';
  }

  function validateStep1() {
    if (!pdfTokenInput || !pdfTokenInput.value) {
      return 'Upload and parse a PDF first.';
    }
    return '';
  }

  function validateStep2() {
    if (!plansList) {
      return 'No plans to review.';
    }
    var panels = plansList.querySelectorAll('[data-plan-panel]');
    if (!panels.length) {
      return 'Keep at least one plan to create packages.';
    }
    for (var i = 0; i < panels.length; i++) {
      var panel = panels[i];
      var label = (panel.querySelector('.form-card__title') || {}).textContent || 'Plan ' + (i + 1);
      var prefix = panel.getAttribute('data-plan-prefix') || '';
      if (checkedInPanel(panel, prefix + '[destinations][]').length === 0) {
        return label + ': select at least one destination.';
      }
      if (checkedInPanel(panel, prefix + '[types][]').length === 0) {
        return label + ': select at least one type.';
      }
      var pickupEl = panel.querySelector('[name="' + prefix + '[pickup]"]');
      if (!pickupEl || !String(pickupEl.value || '').trim()) {
        return label + ': pickup / drop is required.';
      }
      var titleEl = panel.querySelector('[name="' + prefix + '[title]"]');
      if (!titleEl || !String(titleEl.value || '').trim()) {
        return label + ': title is required.';
      }
      var overviewEl = panel.querySelector('[name="' + prefix + '[overview]"]');
      if (!overviewEl || !String(overviewEl.value || '').trim()) {
        return label + ': overview is required.';
      }
      var nights = Math.max(0, parseInt((panel.querySelector('[data-plan-nights]') || {}).value || '0', 10) || 0);
      if (nights > 0) {
        var stays = Array.prototype.map.call(panel.querySelectorAll('[data-stay-grid] select'), function (sel) {
          return sel.value;
        });
        if (stays.length !== nights || stays.some(function (s) { return !s; })) {
          return label + ': choose a stay for each night.';
        }
      }
    }
    return '';
  }

  function updateChrome() {
    Object.keys(panels).forEach(function (key) {
      var panel = panels[key];
      if (panel) {
        panel.hidden = parseInt(key, 10) !== step;
      }
    });

    Array.prototype.forEach.call(indicators, function (el) {
      var n = parseInt(el.getAttribute('data-ai-step-indicator') || '0', 10);
      el.classList.toggle('is-active', n === step);
      el.classList.toggle('is-done', n < step);
    });

    if (wizard) {
      wizard.setAttribute('data-step', String(step));
    }
    if (progress) {
      var fill = step <= 1 ? 0 : step === 2 ? 50 : 100;
      progress.style.width = fill + '%';
    }

    if (btnBack) {
      btnBack.hidden = step === 1;
    }
    if (btnCancel) {
      btnCancel.hidden = step !== 1;
    }
    if (btnParse) {
      btnParse.hidden = step !== 1;
    }
    if (btnSaveDraft) {
      btnSaveDraft.hidden = step === 1;
    }
    if (btnNext) {
      btnNext.hidden = step === 1 || step === 3;
    }
    if (btnPreview) {
      btnPreview.hidden = step !== 3;
    }
    if (btnPublish) {
      btnPublish.hidden = step !== 3;
    }
    if (wizardStepInput) {
      wizardStepInput.value = String(step);
    }
  }

  function goTo(next) {
    var target = Math.max(1, Math.min(3, next));
    if (target === 3 && step !== 3) {
      syncPlansFromDom();
      buildMediaPanels();
    }
    step = target;
    showError('');
    updateChrome();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function updateSummary() {
    if (!summaryEl) {
      return;
    }
    if (!plans.length) {
      summaryEl.hidden = true;
      summaryEl.textContent = '';
      return;
    }
    var names = plans.map(function (p) {
      return p.plan_label || 'Plan';
    });
    summaryEl.hidden = false;
    summaryEl.textContent =
      'Found ' + plans.length + ' plan' + (plans.length === 1 ? '' : 's') + ': ' + names.join(', ') + '.';
    if (pdfAttachedHint && pdfFilenameInput && pdfFilenameInput.value) {
      pdfAttachedHint.textContent = 'Will attach "' + pdfFilenameInput.value + '" as itinerary PDF on each package.';
    }
  }

  function parsePdf() {
    showError('');
    showWarnings([]);
    if (!pdfFileInput || !pdfFileInput.files || !pdfFileInput.files[0]) {
      showError('Choose a PDF file first.');
      return;
    }
    if (!parseUrl) {
      showError('Parse endpoint is not configured.');
      return;
    }

    var fd = new FormData();
    fd.append('pdf_file', pdfFileInput.files[0]);
    fd.append('_csrf', csrfInput ? csrfInput.value : '');

    setStatus('Parsing PDF with Gemini…', true);
    if (btnParse) {
      btnParse.disabled = true;
    }

    fetch(parseUrl, {
      method: 'POST',
      body: fd,
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-Token': csrfInput ? csrfInput.value : '',
      },
    })
      .then(function (res) {
        return res.json().then(function (data) {
          return { res: res, data: data };
        });
      })
      .then(function (result) {
        if (!result.data || !result.data.ok) {
          showError((result.data && result.data.error) || 'PDF parsing failed.');
          setStatus('');
          return;
        }
        plans = result.data.plans || [];
        if (pdfTokenInput) {
          pdfTokenInput.value = result.data.token || '';
        }
        if (pdfFilenameInput) {
          pdfFilenameInput.value = result.data.pdf_filename || '';
        }
        showWarnings(result.data.warnings || []);
        var unknownQueue = result.data.unknown_places || collectUnknownPlaces(plans);
        setStatus(
          unknownQueue.length
            ? 'Checking ' + unknownQueue.length + ' unknown location' + (unknownQueue.length === 1 ? '' : 's') + '…'
            : 'Preparing plans…',
          true
        );
        resolveUnknownPlaces(unknownQueue).then(function () {
          renderPlans(plans);
          updateSummary();
          setStatus('Parsed ' + plans.length + ' plan' + (plans.length === 1 ? '' : 's') + '.');
          goTo(2);
        });
      })
      .catch(function () {
        showError('Could not reach the parse endpoint. Try again.');
        setStatus('');
      })
      .finally(function () {
        if (btnParse) {
          btnParse.disabled = false;
        }
      });
  }

  if (pdfFileInput && pdfFileName) {
    pdfFileInput.addEventListener('change', function () {
      var name = pdfFileInput.files && pdfFileInput.files[0] ? pdfFileInput.files[0].name : 'No file chosen';
      pdfFileName.textContent = name;
    });
  }

  if (btnParse) {
    btnParse.addEventListener('click', parsePdf);
  }

  if (btnBack) {
    btnBack.addEventListener('click', function () {
      goTo(step - 1);
    });
  }

  if (btnNext) {
    btnNext.addEventListener('click', function () {
      if (step === 2) {
        var err = validateStep2();
        if (err) {
          showError(err);
          return;
        }
        goTo(3);
      }
    });
  }

  if (btnSaveDraft) {
    btnSaveDraft.addEventListener('click', function (e) {
      e.preventDefault();
      var err = validateStep1();
      if (err) {
        showError(err);
        return;
      }
      if (step === 2) {
        var err2 = validateStep2Structural();
        if (err2) {
          showError(err2);
          return;
        }
      }
      saveDraft();
    });
  }

  if (btnPreview) {
    btnPreview.addEventListener('click', function (e) {
      e.preventDefault();
      openPreview();
    });
  }

  form.addEventListener('submit', function (e) {
    if (step !== 3) {
      e.preventDefault();
      return;
    }
    if (saveModeInput) {
      saveModeInput.value = 'publish';
    }
    var err1 = validateStep1();
    if (err1) {
      e.preventDefault();
      showError(err1);
      goTo(1);
      return;
    }
    var err2 = validateStep2();
    if (err2) {
      e.preventDefault();
      showError(err2);
      goTo(2);
    }
  });

  var repostPlans = [];
  try {
    repostPlans = JSON.parse(form.getAttribute('data-repost-plans') || '[]');
  } catch (e5) {
    repostPlans = [];
  }
  if (repostPlans.length) {
    plans = repostPlans;
    renderPlans(plans);
    updateSummary();
  }

  goTo(startStep);
})();
