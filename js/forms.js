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

  document.addEventListener("click", function (e) {
    const btn = e.target.closest("[data-open-modal]");
    if (!btn) return;
    e.preventDefault();
    const title = btn.getAttribute("data-package-title");
    if (title) {
      document.querySelectorAll("[data-prefill='package'], [data-prefill='interest']").forEach(function (el) {
        el.value = title;
      });
      document.querySelectorAll("[data-interest-label]").forEach(function (el) {
        el.textContent = title;
      });
    }
    openModal(btn.getAttribute("data-open-modal"));
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

  const SUCCESS_NOTE = {
    handed_over:
      "Your enquiry is saved. WhatsApp has opened in a new tab — send the message there and we'll reply with pricing.",
    blocked:
      "Your enquiry is saved. Your browser blocked the new tab, so open WhatsApp below to send us the details.",
    saved_only: "Your enquiry has been submitted. Our team will contact you shortly with availability and pricing.",
  };

  function prepareSuccessModal(id, whatsappUrl, blocked) {
    const modal = document.getElementById(id);
    if (!modal) return;

    const note = modal.querySelector("[data-success-note]");
    if (note) {
      note.textContent = whatsappUrl
        ? blocked
          ? SUCCESS_NOTE.blocked
          : SUCCESS_NOTE.handed_over
        : SUCCESS_NOTE.saved_only;
    }

    const link = modal.querySelector("[data-whatsapp-link]");
    if (link) {
      link.hidden = !whatsappUrl;
      if (whatsappUrl) link.href = whatsappUrl;
    }
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
      const action = form.getAttribute("action") || "../handlers/enquiry.php";
      const submitBtn = form.querySelector('[type="submit"]');
      if (submitBtn) submitBtn.disabled = true;

      // Claim the tab while we are still inside the click that submitted the
      // form: window.open is blocked once the fetch promise resolves.
      let waTab = null;
      try {
        waTab = window.open("", "_blank");
      } catch (err) {
        waTab = null;
      }
      if (waTab && waTab.document) {
        waTab.document.write("<title>Opening WhatsApp</title>");
        waTab.document.close();
      }

      const body = new FormData(form);
      body.append("ajax", "1");

      fetch(action, {
        method: "POST",
        body: body,
        headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
        credentials: "same-origin",
      })
        .then(function (res) {
          return res.json().then(function (data) {
            return { ok: res.ok && data && data.ok, data: data };
          });
        })
        .then(function (result) {
          if (!result.ok) {
            if (waTab) waTab.close();
            const msg = (result.data && result.data.error) || "Could not submit enquiry. Please try again.";
            alert(msg);
            return;
          }
          form.reset();
          form.querySelectorAll(".form-group").forEach(clearError);
          const parentModal = form.closest(".modal");
          if (parentModal) closeModal(parentModal);

          // The enquiry is already stored. Send the visitor's pre-written message
          // to the reserved tab and keep this one on the confirmation.
          const whatsapp = (result.data && result.data.whatsapp) || "";
          if (whatsapp && waTab) {
            waTab.location.replace(whatsapp);
          } else if (waTab) {
            waTab.close();
          }

          prepareSuccessModal(successId, whatsapp, !waTab);
          openModal(successId);
        })
        .catch(function () {
          if (waTab) waTab.close();
          alert("Could not submit enquiry. Please try again.");
        })
        .finally(function () {
          if (submitBtn) submitBtn.disabled = false;
        });
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
    const packageSlug = params.get("package") || "";
    const resort = params.get("resort") || "";
    let packageName = packageSlug;
    if (packageSlug && window.YNPackages && typeof window.YNPackages.byId === "function") {
      const pkg = window.YNPackages.byId(packageSlug);
      if (pkg) packageName = pkg.title;
    }

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
