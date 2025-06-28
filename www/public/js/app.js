/**
 * Script principal de OneMediaPiece
 * Gère les fonctionnalités communes à toutes les pages
 */

// Configuration globale
window.OneMediaPiece = {
  version: "1.0.0",
  debug: false,

  // Configuration de l'application
  config: {
    apiTimeout: 30000,
    maxFileSize: 5 * 1024 * 1024, // 5MB
    allowedImageTypes: ["image/jpeg", "image/png", "image/gif"],
    pagination: {
      articlesPerPage: 12,
      commentsPerPage: 20,
    },
  },
};

/**
 * Utilitaires globaux
 */
window.Utils = {
  /**
   * Formater une date de façon relative
   */
  formatRelativeDate: function (dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffTime = Math.abs(now - date);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    const diffHours = Math.ceil(diffTime / (1000 * 60 * 60));
    const diffMinutes = Math.ceil(diffTime / (1000 * 60));

    if (diffMinutes < 60) {
      return `Il y a ${diffMinutes} minute${diffMinutes > 1 ? "s" : ""}`;
    } else if (diffHours < 24) {
      return `Il y a ${diffHours} heure${diffHours > 1 ? "s" : ""}`;
    } else if (diffDays < 7) {
      return `Il y a ${diffDays} jour${diffDays > 1 ? "s" : ""}`;
    } else {
      return date.toLocaleDateString("fr-FR");
    }
  },

  /**
   * Valider un email
   */
  isValidEmail: function (email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  },

  /**
   * Échapper le HTML pour éviter XSS
   */
  escapeHtml: function (unsafe) {
    return unsafe
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  },

  /**
   * Tronquer un texte
   */
  truncateText: function (text, maxLength) {
    if (text.length <= maxLength) {
      return text;
    }
    return text.substring(0, maxLength) + "...";
  },

  /**
   * Débounce pour les événements
   */
  debounce: function (func, wait) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  },

  /**
   * Générer un ID unique
   */
  generateId: function () {
    return "id_" + Math.random().toString(36).substr(2, 9);
  },

  /**
   * Copier du texte dans le presse-papiers
   */
  copyToClipboard: async function (text) {
    try {
      await navigator.clipboard.writeText(text);
      return true;
    } catch (err) {
      // Fallback pour les navigateurs plus anciens
      const textArea = document.createElement("textarea");
      textArea.value = text;
      document.body.appendChild(textArea);
      textArea.select();
      const successful = document.execCommand("copy");
      document.body.removeChild(textArea);
      return successful;
    }
  },
};

/**
 * Gestionnaire des notifications toast
 */
window.Toast = {
  show: function (message, type = "info", duration = 5000) {
    const toast = document.createElement("div");
    toast.className = `alert alert-${type} flash-message`;
    toast.textContent = message;

    // Ajouter au container
    const container = this.getContainer();
    container.appendChild(toast);

    // Animation d'entrée
    setTimeout(() => {
      toast.classList.add("show");
    }, 10);

    // Auto-suppression
    setTimeout(() => {
      this.remove(toast);
    }, duration);

    return toast;
  },

  remove: function (toast) {
    if (toast && toast.parentNode) {
      toast.classList.add("hiding");
      setTimeout(() => {
        if (toast.parentNode) {
          toast.parentNode.removeChild(toast);
        }
      }, 300);
    }
  },

  getContainer: function () {
    let container = document.querySelector(".flash-messages");
    if (!container) {
      container = document.createElement("div");
      container.className = "flash-messages";
      document.body.appendChild(container);
    }
    return container;
  },
};

/**
 * Gestionnaire des modals
 */
window.ModalManager = {
  currentModal: null,

  show: function (modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.classList.add("show");
      this.currentModal = modal;
      document.body.style.overflow = "hidden";

      // Focus sur le premier élément focusable
      const focusable = modal.querySelector("input, button, textarea, select");
      if (focusable) {
        focusable.focus();
      }
    }
  },

  hide: function (modalId = null) {
    const modal = modalId
      ? document.getElementById(modalId)
      : this.currentModal;
    if (modal) {
      modal.classList.remove("show");
      document.body.style.overflow = "";
      this.currentModal = null;
    }
  },

  hideAll: function () {
    const modals = document.querySelectorAll(".modal-overlay.show");
    modals.forEach((modal) => {
      modal.classList.remove("show");
    });
    document.body.style.overflow = "";
    this.currentModal = null;
  },
};

/**
 * Gestionnaire de la recherche globale
 */
window.SearchManager = {
  searchInput: null,
  searchTimeout: null,

  init: function () {
    // Recherche globale avec Ctrl+K
    document.addEventListener("keydown", (e) => {
      if ((e.ctrlKey || e.metaKey) && e.key === "k") {
        e.preventDefault();
        this.focusSearch();
      }

      // Échapper pour fermer les modals
      if (e.key === "Escape") {
        ModalManager.hideAll();
      }
    });
  },

  focusSearch: function () {
    const searchInput = document.querySelector(".search-input, #searchInput");
    if (searchInput) {
      searchInput.focus();
      searchInput.select();
    }
  },
};

/**
 * Gestionnaire des préférences utilisateur
 */
window.PreferencesManager = {
  get: function (key, defaultValue = null) {
    try {
      const preferences = JSON.parse(
        localStorage.getItem("userPreferences") || "{}"
      );
      return preferences[key] !== undefined ? preferences[key] : defaultValue;
    } catch {
      return defaultValue;
    }
  },

  set: function (key, value) {
    try {
      const preferences = JSON.parse(
        localStorage.getItem("userPreferences") || "{}"
      );
      preferences[key] = value;
      localStorage.setItem("userPreferences", JSON.stringify(preferences));
      return true;
    } catch {
      return false;
    }
  },

  applyTheme: function () {
    const theme = this.get("theme", "light");
    document.documentElement.setAttribute("data-theme", theme);
  },
};

/**
 * Performance et analytics
 */
window.Analytics = {
  trackPageView: function (page) {
    if (OneMediaPiece.debug) {
      console.log("Page view:", page);
    }
    // Ici vous pourriez intégrer Google Analytics, Matomo, etc.
  },

  trackEvent: function (category, action, label = null) {
    if (OneMediaPiece.debug) {
      console.log("Event:", category, action, label);
    }
    // Ici vous pourriez intégrer votre solution d'analytics
  },
};

/**
 * Gestionnaire d'erreurs global
 */
window.ErrorHandler = {
  init: function () {
    window.addEventListener("error", (e) => {
      this.logError("JavaScript Error", e.error);
    });

    window.addEventListener("unhandledrejection", (e) => {
      this.logError("Unhandled Promise Rejection", e.reason);
    });
  },

  logError: function (type, error) {
    if (OneMediaPiece.debug) {
      console.error(type, error);
    }

    // En production, vous pourriez envoyer les erreurs à un service de monitoring
    // comme Sentry, LogRocket, etc.
  },
};

/**
 * Initialisation de l'application
 */
document.addEventListener("DOMContentLoaded", function () {
  // Initialiser les gestionnaires
  SearchManager.init();
  ErrorHandler.init();
  PreferencesManager.applyTheme();

  // Suivre la page vue
  Analytics.trackPageView(window.location.pathname);

  // Initialiser les tooltips si présents
  const tooltips = document.querySelectorAll("[data-tooltip]");
  tooltips.forEach((tooltip) => {
    tooltip.addEventListener("mouseenter", showTooltip);
    tooltip.addEventListener("mouseleave", hideTooltip);
  });

  // Gérer les liens externes
  const externalLinks = document.querySelectorAll(
    'a[href^="http"]:not([href*="' + window.location.hostname + '"])'
  );
  externalLinks.forEach((link) => {
    link.setAttribute("target", "_blank");
    link.setAttribute("rel", "noopener noreferrer");
  });

  // Améliorer l'accessibilité des boutons
  const buttons = document.querySelectorAll(
    "button:not([aria-label]):not([title])"
  );
  buttons.forEach((button) => {
    if (button.textContent.trim()) {
      button.setAttribute("aria-label", button.textContent.trim());
    }
  });

  console.log("OneMediaPiece initialized successfully!");
});

/**
 * Fonctions utilitaires pour les tooltips
 */
function showTooltip(e) {
  const tooltip = e.target.getAttribute("data-tooltip");
  if (!tooltip) return;

  const tooltipEl = document.createElement("div");
  tooltipEl.className = "tooltip-popup";
  tooltipEl.textContent = tooltip;
  tooltipEl.style.position = "absolute";
  tooltipEl.style.backgroundColor = "var(--color-gray-800)";
  tooltipEl.style.color = "var(--color-white)";
  tooltipEl.style.padding = "var(--spacing-xs) var(--spacing-sm)";
  tooltipEl.style.borderRadius = "var(--border-radius-sm)";
  tooltipEl.style.fontSize = "var(--font-size-xs)";
  tooltipEl.style.zIndex = "1000";
  tooltipEl.style.pointerEvents = "none";

  document.body.appendChild(tooltipEl);

  const rect = e.target.getBoundingClientRect();
  tooltipEl.style.left =
    rect.left + rect.width / 2 - tooltipEl.offsetWidth / 2 + "px";
  tooltipEl.style.top = rect.top - tooltipEl.offsetHeight - 5 + "px";

  e.target._tooltip = tooltipEl;
}

function hideTooltip(e) {
  if (e.target._tooltip) {
    document.body.removeChild(e.target._tooltip);
    delete e.target._tooltip;
  }
}

// Exposer les utilitaires globalement pour les autres scripts
window.showTooltip = showTooltip;
window.hideTooltip = hideTooltip;
