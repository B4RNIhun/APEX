const revealElements = document.querySelectorAll(".reveal");
const menuToggle = document.querySelector(".menu-toggle");
const navMenu = document.querySelector(".nav-menu");
const navDropdown = document.querySelector(".nav-dropdown");
const dropdownToggle = document.querySelector(".dropdown-toggle");
const contactForm = document.querySelector(".contact-form");
const formStatus = document.querySelector(".form-status");
const trackSelect = document.querySelector("#track");
const availabilitySelect = document.querySelector("#availability");

const availabilityOptions = [
  "Hétvégi pályanap — érdeklődés",
  "Hétköznapi pályanap — érdeklődés",
  "Egyéni időpont — egyeztetés szükséges"
];

document.querySelector("#year").textContent = new Date().getFullYear();

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
    navDropdown.classList.remove("is-open");
    dropdownToggle.setAttribute("aria-expanded", "false");
  });
});

dropdownToggle.addEventListener("click", () => {
  const isExpanded = dropdownToggle.getAttribute("aria-expanded") === "true";
  dropdownToggle.setAttribute("aria-expanded", String(!isExpanded));
  navDropdown.classList.toggle("is-open", !isExpanded);
});

document.addEventListener("click", (event) => {
  if (!navDropdown.contains(event.target)) {
    navDropdown.classList.remove("is-open");
    dropdownToggle.setAttribute("aria-expanded", "false");
  }

  if (!navMenu.contains(event.target) && !menuToggle.contains(event.target)) {
    navMenu.classList.remove("is-open");
    menuToggle.setAttribute("aria-expanded", "false");
    menuToggle.setAttribute("aria-label", "Menü megnyitása");
  }
});

document.addEventListener("keydown", (event) => {
  if (event.key === "Escape") {
    navDropdown.classList.remove("is-open");
    dropdownToggle.setAttribute("aria-expanded", "false");
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
