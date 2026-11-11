const revealElements = document.querySelectorAll(".reveal");
const menuToggle = document.querySelector(".menu-toggle");
const navMenu = document.querySelector(".nav-menu");
const contactForm = document.querySelector(".contact-form");
const formStatus = document.querySelector(".form-status");
const trackSelect = document.querySelector("#track");
const availabilitySelect = document.querySelector("#availability");
const trackDialog = document.querySelector(".track-dialog");
const trackDialogContent = document.querySelector(".track-dialog-content");
const trackDialogClose = document.querySelector(".track-dialog-close");
const accountToggle = document.querySelector(".account-toggle");
const accountDialog = document.querySelector(".account-dialog");
const accountDialogClose = document.querySelector(".account-dialog-close");
const accountForm = document.querySelector(".account-form");
const accountTabs = document.querySelectorAll(".account-tab");
const accountName = document.querySelector("#account-name");
const accountPassword = document.querySelector("#account-password");
const accountPasswordConfirm = document.querySelector("#account-password-confirm");
const accountStatus = document.querySelector(".account-status");
const accountSubmitLabel = document.querySelector(".account-submit-label");
const hero = document.querySelector(".hero");

const availabilityOptions = [
  "Hétvégi pályanap — érdeklődés",
  "Hétköznapi pályanap — érdeklődés",
  "Egyéni időpont — egyeztetés szükséges"
];

document.querySelector("#year").textContent = new Date().getFullYear();

if (window.matchMedia("(hover: hover) and (pointer: fine)").matches) {
  hero.addEventListener("pointermove", (event) => {
    const bounds = hero.getBoundingClientRect();
    hero.style.setProperty("--pointer-x", `${event.clientX - bounds.left}px`);
    hero.style.setProperty("--pointer-y", `${event.clientY - bounds.top}px`);
    hero.classList.add("is-revealing");
  });

  hero.addEventListener("pointerleave", () => {
    hero.classList.remove("is-revealing");
  });
}

if ("IntersectionObserver" in window) {
  const revealObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add("is-visible");
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12 });

  revealElements.forEach((element) => revealObserver.observe(element));
} else {
  revealElements.forEach((element) => element.classList.add("is-visible"));
}

menuToggle.addEventListener("click", () => {
  const isExpanded = menuToggle.getAttribute("aria-expanded") === "true";
  menuToggle.setAttribute("aria-expanded", String(!isExpanded));
  menuToggle.setAttribute("aria-label", isExpanded ? "Menü megnyitása" : "Menü bezárása");
  navMenu.classList.toggle("is-open", !isExpanded);
});

navMenu.querySelectorAll("a").forEach((link) => {
  link.addEventListener("click", () => {
    menuToggle.setAttribute("aria-expanded", "false");
    menuToggle.setAttribute("aria-label", "Menü megnyitása");
    navMenu.classList.remove("is-open");
  });
});

const setAccountMode = (mode) => {
  const isRegister = mode === "register";

  accountForm.reset();
  accountPasswordConfirm.setCustomValidity("");
  accountStatus.textContent = "";
  accountName.hidden = !isRegister;
  accountName.previousElementSibling.hidden = !isRegister;
  accountName.required = isRegister;
  accountPasswordConfirm.hidden = !isRegister;
  accountPasswordConfirm.previousElementSibling.hidden = !isRegister;
  accountPasswordConfirm.required = isRegister;
  accountPassword.autocomplete = isRegister ? "new-password" : "current-password";
  accountSubmitLabel.textContent = isRegister ? "Regisztráció" : "Bejelentkezés";

  accountTabs.forEach((tab) => {
    const isActive = tab.dataset.accountMode === mode;
    tab.classList.toggle("is-active", isActive);
    tab.setAttribute("aria-pressed", String(isActive));
  });
};

accountToggle.addEventListener("click", () => {
  navMenu.classList.remove("is-open");
  menuToggle.setAttribute("aria-expanded", "false");
  menuToggle.setAttribute("aria-label", "Menü megnyitása");
  setAccountMode("login");
  accountDialog.showModal();
});

accountTabs.forEach((tab) => {
  tab.addEventListener("click", () => setAccountMode(tab.dataset.accountMode));
});

accountDialogClose.addEventListener("click", () => accountDialog.close());

accountDialog.addEventListener("click", (event) => {
  if (event.target === accountDialog) {
    accountDialog.close();
  }
});

accountPasswordConfirm.addEventListener("input", () => {
  const passwordsMatch = accountPassword.value === accountPasswordConfirm.value;
  accountPasswordConfirm.setCustomValidity(passwordsMatch ? "" : "A két jelszó nem egyezik.");
});

accountPassword.addEventListener("input", () => {
  if (accountPasswordConfirm.value) {
    accountPasswordConfirm.dispatchEvent(new Event("input"));
  }
});

accountForm.addEventListener("submit", (event) => {
  event.preventDefault();
  accountStatus.textContent = "Ez egy bemutató felület: a fiókadatokat nem mentettük el, és bejelentkezés sem történt.";
});

document.querySelectorAll(".event-button").forEach((button) => {
  button.addEventListener("click", () => {
    trackSelect.value = button.dataset.track;
    trackSelect.dispatchEvent(new Event("change"));
  });
});

const closeTrackDialog = () => {
  trackDialog.close();
  trackDialogContent.replaceChildren();
};

document.querySelectorAll(".project-image[data-track]").forEach((image) => {
  image.addEventListener("click", (event) => {
    event.preventDefault();
    const track = image.dataset.track;
    const eventTemplate = [...document.querySelectorAll("template[data-track-event]")].find(
      (template) => template.dataset.trackEvent === track
    );

    trackDialogContent.replaceChildren();

    if (eventTemplate) {
      trackDialogContent.append(eventTemplate.content.cloneNode(true));
    } else {
      const message = document.createElement("p");
      message.className = "track-dialog-message";
      message.textContent = `Ehhez a pályához jelenleg nincs meghirdetett mintaesemény. Kérj értesítést a következő elérhető időpontról.`;

      const button = document.createElement("a");
      button.className = "event-button";
      button.href = "#foglalas";
      button.dataset.track = track;
      button.textContent = "Érdeklődöm";
      trackDialogContent.append(message, button);
    }

    trackDialog.showModal();
  });
});

trackDialogClose.addEventListener("click", closeTrackDialog);

trackDialog.addEventListener("click", (event) => {
  if (event.target === trackDialog) {
    closeTrackDialog();
  }
});

trackDialogContent.addEventListener("click", (event) => {
  const button = event.target.closest(".event-button");

  if (!button) {
    return;
  }

  event.preventDefault();
  trackSelect.value = button.dataset.track;
  trackSelect.dispatchEvent(new Event("change"));
  closeTrackDialog();
  window.location.hash = "foglalas";
  document.querySelector("#foglalas").scrollIntoView({ behavior: "smooth", block: "start" });
});

document.addEventListener("click", (event) => {
  if (!navMenu.contains(event.target) && !menuToggle.contains(event.target)) {
    navMenu.classList.remove("is-open");
    menuToggle.setAttribute("aria-expanded", "false");
    menuToggle.setAttribute("aria-label", "Menü megnyitása");
  }
});

document.addEventListener("keydown", (event) => {
  if (event.key === "Escape") {
    if (trackDialog.open) {
      closeTrackDialog();
    }

    if (accountDialog.open) {
      accountDialog.close();
    }

    navMenu.classList.remove("is-open");
    menuToggle.setAttribute("aria-expanded", "false");
    menuToggle.setAttribute("aria-label", "Menü megnyitása");
  }
});

const updateAvailabilityOptions = () => {
  availabilitySelect.replaceChildren();

  const placeholder = document.createElement("option");
  placeholder.value = "";
  placeholder.textContent = "Válassz érdeklődési időszakot";
  placeholder.disabled = true;
  placeholder.selected = true;
  availabilitySelect.append(placeholder);

  availabilityOptions.forEach((availability) => {
    const option = document.createElement("option");
    option.value = availability;
    option.textContent = availability;
    availabilitySelect.append(option);
  });

  availabilitySelect.disabled = false;
  formStatus.textContent = "";
};

trackSelect.addEventListener("change", updateAvailabilityOptions);

contactForm.addEventListener("input", () => {
  formStatus.textContent = "";
});

contactForm.addEventListener("submit", (event) => {
  event.preventDefault();
  formStatus.textContent = "Ez egy bemutató űrlap, az érdeklődésedet nem rögzítettük. Valódi időpont-egyeztetéshez írj a hello@apextrackdays.hu címre.";
});
