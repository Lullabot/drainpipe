/**
 * @file
 * Sets up show/hide interactions for mobile menu including focus trap.
 */

import { createFocusTrap } from "focus-trap";

export default (Drupal, once) => {
  let mainMenu;
  let mainMenuCaret;
  let mobileNavButton;
  let mobileNavButtonState;
  let header;
  let headerFocusTrap;
  let searchButton;

  /**
   * Functionality of mobile menu.
   */
  function mobileMenuControl(e) {
    e.stopImmediatePropagation();

    // Check if search button is in DOM, if so, make sure it
    // closes before menu opens.
    searchButton = header.querySelector(".utility-menu__link--search button");
    if (searchButton) {
      searchButton.setAttribute("aria-expanded", false);
      header
        .querySelector(".aba-header__search-dropdown")
        .classList.remove("is-visible");
    }

    mobileNavButtonState = mobileNavButton.getAttribute("aria-expanded");
    // If mobile nav btn is closed, open it.
    // Else, close it.
    if (mobileNavButtonState == "false") {
      headerFocusTrap.activate();
      mainMenu.style.setProperty("--header-height", header.clientHeight + "px");
      mobileNavButton.setAttribute("aria-expanded", true);
      mainMenu.classList.add("is-visible");
      mainMenuCaret.classList.add("is-visible");
      document.body.classList.add("is-fixed");
      document.addEventListener("click", clickOutside);
    } else {
      headerFocusTrap.deactivate();
      mobileNavButton.setAttribute("aria-expanded", false);
      mainMenu.classList.remove("is-visible");
      mainMenuCaret.classList.remove("is-visible");
      document.body.classList.remove("is-fixed");
      document.removeEventListener("click", clickOutside);
    }
  }

  /**
   * Ensure mobile menu closes if click outside.
   */
  function clickOutside(event) {
    if (!event.target.closest(".main-menu")) {
      mobileNavButton.setAttribute("aria-expanded", false);
      mainMenu.classList.remove("is-visible");
      mainMenuCaret.classList.remove("is-visible");
      document.body.classList.remove("is-fixed");
      document.removeEventListener("click", clickOutside);
    }
  }

  /**
   * Initialize event listeners and focus trap.
   */
  function init(el) {
    header = el;
    mobileNavButton = header.querySelector(".mobile-nav-button__button");
    mainMenu = header.querySelector(".main-menu");
    mainMenuCaret = header.querySelector(".main-menu__caret");
    headerFocusTrap = createFocusTrap([".mobile-nav-button", ".main-menu"], {
      clickOutsideDeactivates: true,
    });
    mobileNavButton.addEventListener("click", mobileMenuControl);
    document.addEventListener("keyup", (e) => {
      if (e.key === "Escape") {
        headerFocusTrap.deactivate();
        mobileNavButton.setAttribute("aria-expanded", false);
        mainMenu.classList.remove("is-visible");
        mainMenuCaret.classList.remove("is-visible");
        document.body.classList.remove("is-fixed");
        document.removeEventListener("click", clickOutside);
      }
    });
  }

  Drupal.behaviors.mainMenu = {
    attach(context) {
      once("mobileHeader", ".aba-header--wrapper", context).forEach(init);
    },
  };
};
