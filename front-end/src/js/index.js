/**
 *   1. Hamburger menu (tablet + mobile).
 *   2. tablet / mobile class is kept in sync on every button.
 *
 * The breakpoints mirror Tailwind's `md` (768px) and `lg` (1024px)
 */

// Matches Tailwind: mobile < 768 <= tablet < 1024 <= desktop.
const MOBILE_QUERY = window.matchMedia("(max-width: 767px)");
const TABLET_QUERY = window.matchMedia("(min-width: 768px) and (max-width: 1023px)");

document.addEventListener("DOMContentLoaded", () => {
  initViewportButtonClasses();
  initMobileMenu();
});

/* ------------------------------------------------------------------------ *
 * Viewport button classes
 *
 * Use matchMedia to add mobile and tablet classes to buttons.
 * ------------------------------------------------------------------------ */
function initViewportButtonClasses() {
  applyViewportButtonClasses();

  MOBILE_QUERY.addEventListener("change", applyViewportButtonClasses);
  TABLET_QUERY.addEventListener("change", applyViewportButtonClasses);
}

function applyViewportButtonClasses() {
  const buttons = document.querySelectorAll("button, .btn");

  buttons.forEach((button) => {
    // Always clear both first, so shrinking and growing the window are both correct.
    button.classList.remove("mobile", "tablet");

    if (MOBILE_QUERY.matches) {
      button.classList.add("mobile");
    } else if (TABLET_QUERY.matches) {
      button.classList.add("tablet");
    }
  });
}

/* ------------------------------------------------------------------------ *
 * Hamburger menu
 *
 * Hide/show hamburger menu.
 * ------------------------------------------------------------------------ */
function initMobileMenu() {
  const toggle = document.getElementById("menu-toggle");
  const menu = document.getElementById("mobile-menu");
  const iconOpen = document.getElementById("menu-icon-open");
  const iconClose = document.getElementById("menu-icon-close");

  if (!toggle || !menu) return;

  const isOpen = () => toggle.getAttribute("aria-expanded") === "true";

  const openMenu = () => {
    menu.hidden = false;

    // Next frame, so the transition has a starting state to animate from.
    requestAnimationFrame(() => {
      menu.classList.remove("invisible", "-translate-y-2", "opacity-0");
    });

    toggle.setAttribute("aria-expanded", "true");
    toggle.setAttribute("aria-label", "Close menu");
    iconOpen.classList.add("hidden");
    iconClose.classList.remove("hidden");

    // Stop the page behind the menu from scrolling while it is open.
    document.body.classList.add("overflow-hidden");
  };

  const closeMenu = () => {
    menu.classList.add("invisible", "-translate-y-2", "opacity-0");

    toggle.setAttribute("aria-expanded", "false");
    toggle.setAttribute("aria-label", "Open menu");
    iconOpen.classList.remove("hidden");
    iconClose.classList.add("hidden");

    document.body.classList.remove("overflow-hidden");

    // Only take it out of the accessibility tree once it has finished fading,
    // otherwise the transition is cut short.
    menu.addEventListener(
      "transitionend",
      () => {
        if (!isOpen()) menu.hidden = true;
      },
      { once: true }
    );
  };

  toggle.addEventListener("click", (event) => {
    event.stopPropagation();
    isOpen() ? closeMenu() : openMenu();
  });

  // Close on a link inside the menu, a click outside it, or Escape.
  menu.querySelectorAll("a").forEach((link) => {
    link.addEventListener("click", closeMenu);
  });

  document.addEventListener("click", (event) => {
    if (isOpen() && !menu.contains(event.target)) closeMenu();
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && isOpen()) {
      closeMenu();
      toggle.focus();
    }
  });

  // Close menu if viewport is expanded to desktop size while menu is open.
  const closeIfDesktop = () => {
    if (isOpen() && !MOBILE_QUERY.matches && !TABLET_QUERY.matches) closeMenu();
  };

  MOBILE_QUERY.addEventListener("change", closeIfDesktop);
  TABLET_QUERY.addEventListener("change", closeIfDesktop);
}
