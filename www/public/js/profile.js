/**
 * Gestionnaire de la page de profil
 */
class ProfilePage {
  constructor() {
    this.currentTab = "personal";
    this.user = null;

    // Éléments DOM
    this.tabs = document.querySelectorAll(".tab-content");
    this.tabLinks = document.querySelectorAll("[data-tab]");

    // Formulaires
    this.personalInfoForm = document.getElementById("personalInfoForm");
    this.passwordForm = document.getElementById("passwordForm");
    this.preferencesForm = document.getElementById("preferencesForm");
  }

  init() {
    this.setupEventListeners();
    this.loadUserData();
  }

  setupEventListeners() {
    // Navigation entre onglets
    this.tabLinks.forEach((link) => {
      link.addEventListener("click", (e) => {
        e.preventDefault();
        const tab = link.dataset.tab;
        this.switchTab(tab);
        window.location.hash = tab;
      });
    });

    // Menu utilisateur
    this.setupUserMenu();

    // Formulaires
    this.personalInfoForm?.addEventListener("submit", (e) => {
      e.preventDefault();
      this.handlePersonalInfoUpdate();
    });

    this.passwordForm?.addEventListener("submit", (e) => {
      e.preventDefault();
      this.handlePasswordChange();
    });

    this.preferencesForm?.addEventListener("submit", (e) => {
      e.preventDefault();
      this.handlePreferencesUpdate();
    });

    // Actions
    document.getElementById("exportDataBtn")?.addEventListener("click", () => {
      this.exportUserData();
    });

    document
      .getElementById("deleteAccountBtn")
      ?.addEventListener("click", () => {
        this.showDeleteAccountConfirmation();
      });

    // Gérer l'URL hash pour les onglets
    const hash = window.location.hash.substring(1);
    if (
      hash &&
      ["personal", "security", "activity", "preferences"].includes(hash)
    ) {
      this.switchTab(hash);
    }
  }

  setupUserMenu() {
    const userMenuToggle = document.getElementById("userMenuToggle");
    const userMenuDropdown = document.getElementById("userMenuDropdown");

    userMenuToggle?.addEventListener("click", () => {
      userMenuDropdown.classList.toggle("show");
    });

    document.addEventListener("click", (e) => {
      if (!e.target.closest(".user-menu")) {
        userMenuDropdown?.classList.remove("show");
      }
    });

    document.getElementById("logoutBtn")?.addEventListener("click", (e) => {
      e.preventDefault();
      auth.logout();
    });
  }

  switchTab(tabName) {
    // Masquer tous les onglets
    this.tabs.forEach((tab) => {
      tab.classList.remove("active");
    });

    // Désactiver tous les liens
    this.tabLinks.forEach((link) => {
      link.classList.remove("active");
    });

    // Afficher l'onglet sélectionné
    const targetTab = document.getElementById(`tab-${tabName}`);
    if (targetTab) {
      targetTab.classList.add("active");
      this.currentTab = tabName;
    }

    // Activer le lien correspondant
    const targetLink = document.querySelector(`[data-tab="${tabName}"]`);
    if (targetLink) {
      targetLink.classList.add("active");
    }

    // Charger les données selon l'onglet
    switch (tabName) {
      case "activity":
        this.loadActivity();
        break;
    }
  }

  async loadUserData() {
    this.user = api.getCurrentUser();

    if (this.user) {
      // Mettre à jour les informations utilisateur dans l'interface
      this.updateUserInterface();

      // Charger les données détaillées
      try {
        const response = await api.getUser(this.user.id);
        if (response.success && response.data) {
          this.user = { ...this.user, ...response.data };
          this.updateUserInterface();
        }
      } catch (error) {
        console.error(
          "Erreur lors du chargement des données utilisateur:",
          error
        );
      }

      // Charger les statistiques
      await this.loadUserStats();
    }
  }

  updateUserInterface() {
    if (!this.user) return;

    // Informations générales
    document.getElementById("email").value = this.user.email || "";
    document.getElementById("pseudonyme").value = this.user.pseudonyme || "";

    // Type de compte
    const accountTypeElement = document.getElementById("accountType");
    if (accountTypeElement) {
      accountTypeElement.textContent = this.getUserTypeLabel(
        this.user.type_compte
      );
      accountTypeElement.className = `badge badge-${this.getUserTypeBadgeClass(
        this.user.type_compte
      )}`;
    }

    // Date d'inscription
    if (this.user.date_creation) {
      const memberSince = document.getElementById("memberSince");
      if (memberSince) {
        memberSince.textContent = this.formatDate(this.user.date_creation);
      }

      // Calcul des jours depuis l'inscription
      const joinedDays = Math.floor(
        (new Date() - new Date(this.user.date_creation)) / (1000 * 60 * 60 * 24)
      );
      const userJoinedDaysElement = document.getElementById("userJoinedDays");
      if (userJoinedDaysElement) {
        userJoinedDaysElement.textContent = joinedDays;
      }
    }
  }

  async loadUserStats() {
    try {
      // Pour l'instant, utiliser des valeurs par défaut
      // Dans un vrai projet, vous feriez des appels API pour récupérer ces stats
      document.getElementById("userArticlesCount").textContent = "0";
      document.getElementById("userCommentsCount").textContent = "0";
      document.getElementById("totalArticles").textContent = "0";
      document.getElementById("totalComments").textContent = "0";
      document.getElementById("totalViews").textContent = "0";
    } catch (error) {
      console.error("Erreur lors du chargement des statistiques:", error);
    }
  }

  async loadActivity() {
    const activityList = document.getElementById("activityList");
    if (!activityList) return;

    // Simuler le chargement d'activité
    activityList.innerHTML = `
            <div class="text-center text-muted p-lg">
                <p>Aucune activité récente à afficher.</p>
                <p class="text-small">Votre activité apparaîtra ici au fur et à mesure que vous utilisez la plateforme.</p>
            </div>
        `;
  }

  async handlePersonalInfoUpdate() {
    try {
      const formData = new FormData(this.personalInfoForm);
      const updateData = {
        email: formData.get("email"),
        pseudonyme: formData.get("pseudonyme"),
        bio: formData.get("bio") || "",
      };

      // Validation
      if (!this.validatePersonalInfo(updateData)) {
        return;
      }

      const response = await api.updateUser(this.user.id, updateData);

      if (response.success) {
        auth.showMessage("Profil mis à jour avec succès !", "success");

        // Mettre à jour les données utilisateur stockées
        this.user = { ...this.user, ...updateData };
        localStorage.setItem("currentUser", JSON.stringify(this.user));
        auth.updateUIForAuthState();
      }
    } catch (error) {
      console.error("Erreur lors de la mise à jour du profil:", error);
      auth.showMessage(error.message, "error");
    }
  }

  validatePersonalInfo(data) {
    let isValid = true;

    // Validation email
    if (!data.email || !this.isValidEmail(data.email)) {
      this.showFieldError("email-error", "Email invalide");
      isValid = false;
    } else {
      this.clearFieldError("email-error");
    }

    // Validation pseudonyme
    if (!data.pseudonyme || data.pseudonyme.length < 3) {
      this.showFieldError(
        "pseudonyme-error",
        "Le pseudonyme doit contenir au moins 3 caractères"
      );
      isValid = false;
    } else if (data.pseudonyme.length > 50) {
      this.showFieldError(
        "pseudonyme-error",
        "Le pseudonyme ne peut pas dépasser 50 caractères"
      );
      isValid = false;
    } else {
      this.clearFieldError("pseudonyme-error");
    }

    return isValid;
  }

  async handlePasswordChange() {
    try {
      const formData = new FormData(this.passwordForm);
      const passwordData = {
        currentPassword: formData.get("currentPassword"),
        newPassword: formData.get("newPassword"),
        confirmPassword: formData.get("confirmPassword"),
      };

      // Validation
      if (!this.validatePasswordChange(passwordData)) {
        return;
      }

      // Pour l'instant, simuler la réussite
      // Dans un vrai projet, vous feriez un appel API spécifique pour changer le mot de passe
      auth.showMessage("Mot de passe modifié avec succès !", "success");
      this.passwordForm.reset();
    } catch (error) {
      console.error("Erreur lors du changement de mot de passe:", error);
      auth.showMessage(error.message, "error");
    }
  }

  validatePasswordChange(data) {
    let isValid = true;

    // Validation mot de passe actuel
    if (!data.currentPassword) {
      this.showFieldError(
        "currentPassword-error",
        "Le mot de passe actuel est requis"
      );
      isValid = false;
    } else {
      this.clearFieldError("currentPassword-error");
    }

    // Validation nouveau mot de passe
    if (!data.newPassword || data.newPassword.length < 6) {
      this.showFieldError(
        "newPassword-error",
        "Le nouveau mot de passe doit contenir au moins 6 caractères"
      );
      isValid = false;
    } else {
      this.clearFieldError("newPassword-error");
    }

    // Validation confirmation
    if (data.newPassword !== data.confirmPassword) {
      this.showFieldError(
        "confirmPassword-error",
        "Les mots de passe ne correspondent pas"
      );
      isValid = false;
    } else {
      this.clearFieldError("confirmPassword-error");
    }

    return isValid;
  }

  async handlePreferencesUpdate() {
    try {
      const formData = new FormData(this.preferencesForm);
      const preferences = {
        emailNotifications: formData.get("emailNotifications") === "on",
        commentNotifications: formData.get("commentNotifications") === "on",
        moderationNotifications:
          formData.get("moderationNotifications") === "on",
        language: formData.get("language"),
        theme: formData.get("theme"),
      };

      // Sauvegarder les préférences dans le localStorage
      localStorage.setItem("userPreferences", JSON.stringify(preferences));

      auth.showMessage("Préférences sauvegardées avec succès !", "success");
    } catch (error) {
      console.error("Erreur lors de la sauvegarde des préférences:", error);
      auth.showMessage("Erreur lors de la sauvegarde", "error");
    }
  }

  exportUserData() {
    // Créer un objet avec les données utilisateur
    const userData = {
      profil: this.user,
      preferences: JSON.parse(localStorage.getItem("userPreferences") || "{}"),
      exportDate: new Date().toISOString(),
    };

    // Créer et télécharger le fichier JSON
    const dataStr = JSON.stringify(userData, null, 2);
    const dataUri =
      "data:application/json;charset=utf-8," + encodeURIComponent(dataStr);

    const exportFileDefaultName = `oneMediaPiece-${this.user.pseudonyme}-${
      new Date().toISOString().split("T")[0]
    }.json`;

    const linkElement = document.createElement("a");
    linkElement.setAttribute("href", dataUri);
    linkElement.setAttribute("download", exportFileDefaultName);
    linkElement.click();

    auth.showMessage("Données exportées avec succès !", "success");
  }

  showDeleteAccountConfirmation() {
    if (
      confirm(
        "Êtes-vous vraiment sûr de vouloir supprimer votre compte ?\n\nCette action est irréversible et supprimera toutes vos données."
      )
    ) {
      window.location.href = "/dashboard#profile";
      // Le modal de suppression est géré dans le dashboard
    }
  }

  // Méthodes utilitaires
  getUserTypeLabel(type) {
    const labels = {
      redacteur: "Rédacteur",
      moderateur: "Modérateur",
      administrateur: "Administrateur",
    };
    return labels[type] || type;
  }

  getUserTypeBadgeClass(type) {
    const classes = {
      redacteur: "info",
      moderateur: "warning",
      administrateur: "error",
    };
    return classes[type] || "info";
  }

  formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString("fr-FR", {
      year: "numeric",
      month: "long",
      day: "numeric",
    });
  }

  isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  }

  showFieldError(elementId, message) {
    const errorElement = document.getElementById(elementId);
    if (errorElement) {
      errorElement.textContent = message;
      errorElement.style.display = "block";
    }
  }

  clearFieldError(elementId) {
    const errorElement = document.getElementById(elementId);
    if (errorElement) {
      errorElement.textContent = "";
      errorElement.style.display = "none";
    }
  }
}

// Instance globale
window.profile = new ProfilePage();
