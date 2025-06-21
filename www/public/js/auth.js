/**
 * Gestionnaire d'authentification
 */
class AuthManager {
  constructor() {
    this.api = window.api;
    this.init();
  }

  init() {
    // Vérifier l'état de connexion au chargement
    this.updateUIForAuthState();

    // Écouter les changements de stockage (déconnexion sur un autre onglet)
    window.addEventListener("storage", (e) => {
      if (e.key === "authToken") {
        this.updateUIForAuthState();
      }
    });
  }

  /**
   * Connexion
   */
  async login(email, password) {
    try {
      this.showLoading("Connexion en cours...");

      const response = await this.api.login(email, password);

      if (response.success) {
        this.showMessage("Connexion réussie !", "success");
        this.updateUIForAuthState();

        // Redirection selon le rôle
        const user = this.api.getCurrentUser();
        if (
          user.type_compte === "administrateur" ||
          user.type_compte === "moderateur"
        ) {
          window.location.href = "/moderation";
        } else {
          window.location.href = "/dashboard";
        }
      }

      return response;
    } catch (error) {
      this.showMessage(error.message, "error");
      throw error;
    } finally {
      this.hideLoading();
    }
  }

  /**
   * Inscription
   */
  async register(userData) {
    try {
      this.showLoading("Création du compte...");

      const response = await this.api.register(userData);

      if (response.success) {
        this.showMessage(
          "Compte créé avec succès ! En attente de validation par un administrateur.",
          "success"
        );
        setTimeout(() => {
          window.location.href = "/login";
        }, 2000);
      }

      return response;
    } catch (error) {
      this.showMessage(error.message, "error");
      throw error;
    } finally {
      this.hideLoading();
    }
  }

  /**
   * Déconnexion
   */
  async logout() {
    try {
      await this.api.logout();
      this.showMessage("Déconnexion réussie !", "success");
      this.updateUIForAuthState();
      window.location.href = "/";
    } catch (error) {
      console.warn("Erreur lors de la déconnexion:", error);
      // Forcer la déconnexion côté client même en cas d'erreur
      this.api.logout();
      this.updateUIForAuthState();
      window.location.href = "/";
    }
  }

  /**
   * Met à jour l'interface selon l'état de connexion
   */
  updateUIForAuthState() {
    const isAuth = this.api.isAuthenticated();
    const user = this.api.getCurrentUser();

    // Éléments à afficher/masquer selon l'authentification
    const authElements = document.querySelectorAll('[data-auth="true"]');
    const guestElements = document.querySelectorAll('[data-auth="false"]');

    authElements.forEach((el) => {
      el.style.display = isAuth ? "" : "none";
    });

    guestElements.forEach((el) => {
      el.style.display = isAuth ? "none" : "";
    });

    // Mettre à jour les informations utilisateur
    if (isAuth && user) {
      const userNameElements = document.querySelectorAll('[data-user="name"]');
      const userEmailElements = document.querySelectorAll(
        '[data-user="email"]'
      );
      const userAvatarElements = document.querySelectorAll(
        '[data-user="avatar"]'
      );

      userNameElements.forEach((el) => {
        el.textContent = user.pseudonyme;
      });

      userEmailElements.forEach((el) => {
        el.textContent = user.email;
      });

      userAvatarElements.forEach((el) => {
        el.textContent = user.pseudonyme.charAt(0).toUpperCase();
      });

      // Afficher/masquer selon le rôle
      this.updateUIForRole(user.type_compte);
    }
  }

  /**
   * Met à jour l'interface selon le rôle
   */
  updateUIForRole(role) {
    const adminElements = document.querySelectorAll('[data-role="admin"]');
    const moderatorElements = document.querySelectorAll(
      '[data-role="moderator"]'
    );

    adminElements.forEach((el) => {
      el.style.display = role === "administrateur" ? "" : "none";
    });

    moderatorElements.forEach((el) => {
      el.style.display = ["administrateur", "moderateur"].includes(role)
        ? ""
        : "none";
    });
  }

  /**
   * Protège une page (redirection si non authentifié)
   */
  requireAuth(requiredRole = null) {
    if (!this.api.isAuthenticated()) {
      this.showMessage(
        "Vous devez être connecté pour accéder à cette page.",
        "warning"
      );
      window.location.href = "/login";
      return false;
    }

    if (requiredRole && !this.api.hasRole(requiredRole)) {
      this.showMessage("Permissions insuffisantes.", "error");
      window.location.href = "/";
      return false;
    }

    return true;
  }

  /**
   * Utilitaires UI
   */
  showMessage(message, type = "info") {
    const container = this.getOrCreateFlashContainer();

    const alertDiv = document.createElement("div");
    alertDiv.className = `alert alert-${type} flash-message`;
    alertDiv.textContent = message;

    container.appendChild(alertDiv);

    // Auto-suppression après 5 secondes
    setTimeout(() => {
      if (alertDiv.parentNode) {
        alertDiv.parentNode.removeChild(alertDiv);
      }
    }, 5000);
  }

  showLoading(message = "Chargement...") {
    const loader =
      document.getElementById("global-loader") || this.createLoader();
    loader.querySelector(".loader-message").textContent = message;
    loader.style.display = "flex";
  }

  hideLoading() {
    const loader = document.getElementById("global-loader");
    if (loader) {
      loader.style.display = "none";
    }
  }

  getOrCreateFlashContainer() {
    let container = document.querySelector(".flash-messages");
    if (!container) {
      container = document.createElement("div");
      container.className = "flash-messages";
      document.body.appendChild(container);
    }
    return container;
  }

  createLoader() {
    const loader = document.createElement("div");
    loader.id = "global-loader";
    loader.innerHTML = `
            <div class="modal-overlay show">
                <div class="d-flex flex-column align-center">
                    <div class="spinner spinner-lg"></div>
                    <p class="loader-message mt-md text-white">Chargement...</p>
                </div>
            </div>
        `;
    loader.style.display = "none";
    document.body.appendChild(loader);
    return loader;
  }
}

// Instance globale du gestionnaire d'auth
window.auth = new AuthManager();
