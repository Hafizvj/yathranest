(function () {
  'use strict';

  var body = document.body;
  if (!body || !body.classList.contains('admin')) {
    return;
  }

  var menuBtn = document.querySelector('[data-admin-menu]');
  var backdrop = document.querySelector('[data-admin-backdrop]');
  var sidebar = document.querySelector('.admin-sidebar');

  function setNavOpen(open) {
    body.classList.toggle('nav-open', open);
    if (menuBtn) {
      menuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    if (backdrop) {
      backdrop.classList.toggle('is-visible', open);
      backdrop.hidden = !open;
    }
  }

  if (menuBtn) {
    menuBtn.addEventListener('click', function () {
      setNavOpen(!body.classList.contains('nav-open'));
    });
  }

  if (backdrop) {
    backdrop.addEventListener('click', function () {
      setNavOpen(false);
    });
  }

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      setNavOpen(false);
    }
  });

  if (sidebar) {
    sidebar.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () {
        if (window.matchMedia('(max-width: 900px)').matches) {
          setNavOpen(false);
        }
      });
    });
  }

  document.querySelectorAll('[data-alert-dismiss]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var alert = btn.closest('.admin-alert');
      if (alert) {
        alert.remove();
      }
    });
  });

  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      var message = form.getAttribute('data-confirm') || 'Are you sure?';
      if (!window.confirm(message)) {
        event.preventDefault();
      }
    });
  });

  function readPreview(file, target) {
    if (!file || !target || !file.type || file.type.indexOf('image/') !== 0) {
      return;
    }
    var reader = new FileReader();
    reader.onload = function () {
      target.src = reader.result;
      target.hidden = false;
      var wrap = target.closest('.media-live-preview');
      if (wrap) {
        wrap.hidden = false;
      }
    };
    reader.readAsDataURL(file);
  }

  document.querySelectorAll('input[type="file"][data-preview-target]').forEach(function (input) {
    input.addEventListener('change', function () {
      var selector = input.getAttribute('data-preview-target');
      var target = selector ? document.querySelector(selector) : null;
      if (!target) {
        return;
      }
      if (input.files && input.files[0]) {
        readPreview(input.files[0], target);
      }
    });
  });

  document.querySelectorAll('input[type="file"][data-preview-list]').forEach(function (input) {
    input.addEventListener('change', function () {
      var selector = input.getAttribute('data-preview-list');
      var list = selector ? document.querySelector(selector) : null;
      if (!list || !input.files) {
        return;
      }
      list.innerHTML = '';
      Array.prototype.forEach.call(input.files, function (file) {
        if (!file.type || file.type.indexOf('image/') !== 0) {
          return;
        }
        var item = document.createElement('div');
        item.className = 'media-preview__item';
        var img = document.createElement('img');
        img.alt = file.name;
        item.appendChild(img);
        list.appendChild(item);
        readPreview(file, img);
      });
    });
  });
})();
