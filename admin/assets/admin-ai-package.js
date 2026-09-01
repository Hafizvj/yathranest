(function () {
  var form = document.querySelector('[data-ai-package-form]');
  if (!form) {
    return;
  }

  var generateUrl = form.getAttribute('data-ai-generate-url') || '';
  var saveDraftUrl = form.getAttribute('data-save-draft-url') || '';
  var startStep = parseInt(form.getAttribute('data-ai-start-step') || '1', 10) || 1;
  var step = startStep;
  var generatedOnce = false;
  var previewUrl = '';

  var panels = {
    1: form.querySelector('[data-ai-panel="1"]'),
    2: form.querySelector('[data-ai-panel="2"]'),
    3: form.querySelector('[data-ai-panel="3"]'),
  };
  var indicators = document.querySelectorAll('[data-ai-step-indicator]');
  var wizard = document.querySelector('[data-ai-wizard]');
  var progress = document.querySelector('[data-ai-progress]');
  var errorBox = form.querySelector('[data-ai-error]');
  var errorText = errorBox ? errorBox.querySelector('span') : null;
  var statusEl = form.querySelector('[data-ai-status]');
  var btnBack = form.querySelector('[data-ai-back]');
  var btnNext = form.querySelector('[data-ai-next]');
  var btnCancel = form.querySelector('[data-ai-cancel]');
  var btnGenerateFooter = form.querySelector('[data-ai-generate-footer]');
  var btnSaveDraft = form.querySelector('[data-ai-save-draft]');
  var btnPreview = form.querySelector('[data-ai-preview]');
  var btnPublish = form.querySelector('[data-ai-publish]');
  var packageIdInput = form.querySelector('[data-package-id]');
  var saveModeInput = form.querySelector('[data-save-mode]');
  var wizardStepInput = form.querySelector('[data-wizard-step]');
  var csrfInput = form.querySelector('input[name="_csrf"]');

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

  function showToast(message) {
    if (!message) {
      return;
    }
    setStatus(message, false);
    window.setTimeout(function () {
      if (statusEl && statusEl.textContent === message) {
        setStatus('');
      }
    }, 3000);
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

  function checkedValues(name) {
    return Array.prototype.map
      .call(form.querySelectorAll('input[name="' + name + '"]:checked'), function (el) {
        return el.value;
      })
      .filter(Boolean);
  }

  function highlightValues() {
    return Array.prototype.map
      .call(form.querySelectorAll('input[name="highlights[]"]'), function (el) {
        return el.value;
      })
      .filter(function (v) {
        return String(v).trim() !== '';
      });
  }

  function stayValues() {
    return Array.prototype.map.call(form.querySelectorAll('select[name="stays[]"]'), function (el) {
      return el.value || '';
    });
  }

  function validateStep1() {
    if (checkedValues('types[]').length === 0) {
      return 'Select at least one type.';
    }
    if (checkedValues('destinations[]').length === 0) {
      return 'Select at least one destination.';
    }
    var pickup = (form.querySelector('#pickup') || {}).value || '';
    if (String(pickup).trim() === '') {
      return 'Pickup / drop is required.';
    }
    var days = Math.max(1, parseInt((form.querySelector('#days') || {}).value || '1', 10) || 1);
    var nights = Math.max(0, parseInt((form.querySelector('#nights') || {}).value || '0', 10) || 0);
    if (nights > days) {
      return 'Nights cannot be more than days.';
    }
    if (nights > 0) {
      var stays = stayValues();
      if (stays.length !== nights) {
        return 'Choose a stay for each night.';
      }
      for (var i = 0; i < stays.length; i++) {
        if (!stays[i]) {
          return 'Choose a stay for night ' + (i + 1) + '.';
        }
      }
    }
    return '';
  }

  function validateStep2() {
    var title = ((form.querySelector('#title') || {}).value || '').trim();
    var overview = ((form.querySelector('#overview') || {}).value || '').trim();
    if (!title) {
      return 'Title is required.';
    }
    if (!overview) {
      return 'Overview is required.';
    }
    return '';
  }

  function syncCounters() {
    ['card_text', 'overview'].forEach(function (id) {
      var field = form.querySelector('#' + id);
      var counter = form.querySelector('[data-counter-for="' + id + '"]');
      if (!field || !counter) {
        return;
      }
      var max = parseInt(field.getAttribute('maxlength') || '0', 10) || 0;
      var len = (field.value || '').length;
      counter.textContent = max ? len + ' / ' + max : String(len);
    });
  }

  function fillItinerary(items) {
    var daysInput = form.querySelector('[data-days-count]');
    var list = form.querySelector('[data-day-list]');
    if (!daysInput || !list || !Array.isArray(items)) {
      return;
    }

    var target = Math.max(1, items.length);
    daysInput.value = String(target);
    daysInput.dispatchEvent(new Event('input', { bubbles: true }));

    var addBtn = form.querySelector('[data-day-add]');
    while (list.children.length < target && addBtn) {
      addBtn.click();
    }
    while (list.children.length > target) {
      var last = list.lastElementChild;
      if (!last) {
        break;
      }
      last.remove();
    }

    Array.prototype.forEach.call(list.children, function (item, index) {
      var day = items[index] || {};
      var title = item.querySelector('.day-item__title');
      var text = item.querySelector('textarea');
      var no = item.querySelector('.day-item__no');
      var label = item.querySelector('.day-item__label');
      if (no) {
        no.textContent = String(index + 1);
      }
      if (label) {
        label.textContent = 'Day ' + (index + 1);
      }
      if (title) {
        title.value = day.title || '';
        title.setAttribute('aria-label', 'Day ' + (index + 1) + ' title');
      }
      if (text) {
        text.value = day.text || '';
        text.id = 'day-text-' + index;
      }
      item.classList.toggle('is-collapsed', index !== 0);
      var toggle = item.querySelector('[data-day-toggle]');
      if (toggle) {
        toggle.setAttribute('aria-expanded', index === 0 ? 'true' : 'false');
      }
    });
  }

  function applyGenerated(data) {
    var title = form.querySelector('#title');
    var card = form.querySelector('#card_text');
    var overview = form.querySelector('#overview');
    if (title) {
      title.value = data.title || '';
    }
    if (card) {
      card.value = data.card_text || '';
    }
    if (overview) {
      overview.value = data.overview || '';
    }
    fillItinerary(data.itinerary || []);
    syncCounters();
    generatedOnce = true;
  }

  function collectPayload() {
    var days = Math.max(1, parseInt((form.querySelector('#days') || {}).value || '1', 10) || 1);
    var nights = Math.max(0, parseInt((form.querySelector('#nights') || {}).value || '0', 10) || 0);
    return {
      _csrf: csrfInput ? csrfInput.value : '',
      days: days,
      nights: nights,
      pickup: ((form.querySelector('#pickup') || {}).value || '').trim(),
      types: checkedValues('types[]'),
      destinations: checkedValues('destinations[]'),
      stays: stayValues(),
      highlights: highlightValues(),
    };
  }

  function setGenerating(busy) {
    [btnGenerateFooter, btnNext, btnBack, btnSaveDraft].forEach(function (btn) {
      if (btn) {
        btn.disabled = !!busy;
      }
    });
  }

  function generate() {
    showError('');
    var step1Error = validateStep1();
    if (step1Error) {
      showError(step1Error);
      goTo(1);
      return Promise.resolve(false);
    }

    setGenerating(true);
    setStatus('Generating SEO copy with Gemini…', true);

    return fetch(generateUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-Token': csrfInput ? csrfInput.value : '',
      },
      body: JSON.stringify(collectPayload()),
      credentials: 'same-origin',
    })
      .then(function (res) {
        return res.json().then(function (data) {
          return { res: res, data: data };
        });
      })
      .then(function (result) {
        if (!result.data || !result.data.ok) {
          var msg = (result.data && result.data.error) || 'Generation failed.';
          showError(msg);
          setStatus('');
          return false;
        }
        applyGenerated(result.data);
        setStatus('Draft ready — edit anything before continuing.');
        return true;
      })
      .catch(function () {
        showError('Could not reach the AI endpoint. Try again.');
        setStatus('');
        return false;
      })
      .finally(function () {
        setGenerating(false);
      });
  }

  function saveDraft() {
    if (!saveDraftUrl) {
      showError('Save endpoint is not configured.');
      return Promise.resolve(false);
    }

    var err = validateStep1();
    if (err) {
      showError(err);
      return Promise.resolve(false);
    }

    if (wizardStepInput) {
      wizardStepInput.value = String(step);
    }

    var fd = new FormData(form);
    fd.set('save_mode', 'draft');
    fd.set('wizard_type', 'ai');
    fd.set('wizard_step', String(step));

    if (btnSaveDraft) {
      btnSaveDraft.disabled = true;
    }
    setStatus('Saving draft…', true);

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
          showError((data && data.error) || 'Could not save draft.');
          setStatus('');
          return false;
        }
        if (packageIdInput && data.id) {
          packageIdInput.value = String(data.id);
        }
        if (data.preview_url) {
          previewUrl = data.preview_url;
        }
        showToast(data.message || 'Draft saved.');
        return true;
      })
      .catch(function () {
        showError('Could not reach the save endpoint. Try again.');
        setStatus('');
        return false;
      })
      .finally(function () {
        if (btnSaveDraft) {
          btnSaveDraft.disabled = false;
        }
      });
  }

  function openPreview() {
    if (previewUrl) {
      window.open(previewUrl, '_blank', 'noopener');
      return;
    }
    saveDraft().then(function (ok) {
      if (ok && previewUrl) {
        window.open(previewUrl, '_blank', 'noopener');
      }
    });
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
    if (btnNext) {
      btnNext.hidden = step === 3;
    }
    if (btnSaveDraft) {
      btnSaveDraft.hidden = false;
    }
    if (btnGenerateFooter) {
      btnGenerateFooter.hidden = step !== 2;
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
    step = Math.max(1, Math.min(3, next));
    showError('');
    updateChrome();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  if (btnBack) {
    btnBack.addEventListener('click', function () {
      if (step === 2) {
        generatedOnce = false;
      }
      goTo(step - 1);
    });
  }

  if (btnNext) {
    btnNext.addEventListener('click', function () {
      if (step === 1) {
        var err = validateStep1();
        if (err) {
          showError(err);
          return;
        }
        goTo(2);
        if (!generatedOnce) {
          generate();
        }
        return;
      }
      if (step === 2) {
        var err2 = validateStep2();
        if (err2) {
          showError(err2);
          return;
        }
        goTo(3);
      }
    });
  }

  if (btnGenerateFooter) {
    btnGenerateFooter.addEventListener('click', function (e) {
      e.preventDefault();
      generate();
    });
  }

  if (btnSaveDraft) {
    btnSaveDraft.addEventListener('click', function (e) {
      e.preventDefault();
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

  goTo(startStep);
  syncCounters();
})();
