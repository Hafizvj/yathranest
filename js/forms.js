/**
 * YathraNest — Forms, validation, enquiry modals
 */
(function () {
  function openModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.add("is-open");
    document.body.classList.add("modal-open");
    const focusTarget = modal.querySelector(".modal__close, .btn, h2");
    if (focusTarget) focusTarget.focus();
  }

  function closeModal(modal) {
    modal.classList.remove("is-open");
    if (!document.querySelector(".modal.is-open")) {
      document.body.classList.remove("modal-open");
    }
  }

  window.YN = window.YN || {};
  window.YN.openModal = openModal;
  window.YN.closeModal = closeModal;

  document.querySelectorAll("[data-open-modal]").forEach(function (btn) {
    btn.addEventListener("click", function (e) {
      e.preventDefault();
      openModal(btn.getAttribute("data-open-modal"));
    });
  });

  document.querySelectorAll(".modal").forEach(function (modal) {
    const backdrop = modal.querySelector(".modal__backdrop");
    const closeBtns = modal.querySelectorAll("[data-close-modal]");
    if (backdrop) {
      backdrop.addEventListener("click", function () {
        closeModal(modal);
      });
    }
    closeBtns.forEach(function (btn) {
      btn.addEventListener("click", function () {
        closeModal(modal);
      });
    });
  });

  document.addEventListener("keydown", function (e) {
    if (e.key !== "Escape") return;
    document.querySelectorAll(".modal.is-open").forEach(closeModal);
  });

  function showError(group, message) {
    group.classList.add("has-error");
    const err = group.querySelector(".field-error");
    if (err) err.textContent = message || "This field is required.";
  }

  function clearError(group) {
    group.classList.remove("has-error");
  }

  function validateEmail(value) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
  }

  function validatePhone(value) {
    const digits = value.replace(/\D/g, "");
    return digits.length >= 10;
  }

  function validateForm(form) {
    let valid = true;
    form.querySelectorAll(".form-group").forEach(clearError);

    form.querySelectorAll("[required]").forEach(function (field) {
      const group = field.closest(".form-group");
      if (!group) return;

      if (field.type === "checkbox" || field.type === "radio") {
        const name = field.name;
        const checked = form.querySelector('input[name="' + name + '"]:checked');
        if (!checked) {
          valid = false;
          showError(group, "Please make a selection.");
        }
        return;
      }

      const value = (field.value || "").trim();
      if (!value) {
        valid = false;
        showError(group, "This field is required.");
        return;
      }

      if (field.type === "email" && !validateEmail(value)) {
        valid = false;
        showError(group, "Enter a valid email address.");
      }

      if (field.dataset.validate === "phone" && !validatePhone(value)) {
        valid = false;
        showError(group, "Enter a valid phone number.");
      }
    });

    // Radio groups with required attribute on container
    form.querySelectorAll("[data-required-group]").forEach(function (group) {
      const name = group.getAttribute("data-required-group");
      const checked = form.querySelector('input[name="' + name + '"]:checked');
      if (!checked) {
        valid = false;
        showError(group, "Please select an option.");
      }
    });

    return valid;
  }

  document.querySelectorAll("form[data-enquiry-form]").forEach(function (form) {
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      if (!validateForm(form)) {
        const firstError = form.querySelector(".form-group.has-error .form-control, .form-group.has-error");
        if (firstError) firstError.focus();
        return;
      }

      const successId = form.getAttribute("data-success-modal") || "success-modal";
      form.reset();
      form.querySelectorAll(".form-group").forEach(clearError);

      // Close enquiry modal if form is inside one
      const parentModal = form.closest(".modal");
      if (parentModal) closeModal(parentModal);

      openModal(successId);
    });

    form.querySelectorAll(".form-control").forEach(function (field) {
      field.addEventListener("input", function () {
        const group = field.closest(".form-group");
        if (group) clearError(group);
      });
    });
  });

  // Prefill enquiry context from data attributes / URL
  function prefillEnquiry() {
    const params = new URLSearchParams(window.location.search);
    const interest = params.get("interest") || "";
    const packageName = params.get("package") || "";
    const resort = params.get("resort") || "";

    document.querySelectorAll("[data-prefill='interest']").forEach(function (el) {
      if (interest) el.value = interest;
    });
    document.querySelectorAll("[data-prefill='package']").forEach(function (el) {
      if (packageName) el.value = packageName;
    });
    document.querySelectorAll("[data-prefill='resort']").forEach(function (el) {
      if (resort) el.value = resort;
    });

    document.querySelectorAll("[data-interest-label]").forEach(function (el) {
      const label = packageName || resort || interest;
      if (label) el.textContent = label;
    });
  }

  prefillEnquiry();

  // Choice cards selection styling
  document.querySelectorAll(".choice-card input").forEach(function (input) {
    function sync() {
      document.querySelectorAll('input[name="' + input.name + '"]').forEach(function (el) {
        const card = el.closest(".choice-card");
        if (card) card.classList.toggle("is-selected", el.checked);
      });
    }
    input.addEventListener("change", sync);
    sync();
  });
})();
