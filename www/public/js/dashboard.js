/**
 * Gestionnaire du tableau de bord utilisateur
 */
class Dashboard {
  constructor() {
    this.currentTab = "dashboard";
    this.userArticles = [];
    this.userComments = [];

    // Éléments DOM
    this.tabs = document.querySelectorAll(".tab-content");
    this.tabLinks = document.querySelectorAll("[data-tab]");
    this.tabTriggers = document.querySelectorAll("[data-tab-trigger]");

    // Formulaires
    this.createArticleForm = document.getElementById("createArticleForm");
    this.profileForm = document.getElementById("profileForm");

    // Modals
    this.deleteModal = document.getElementById("deleteModal");
  }

  init() {
    this.setupEventListeners();
    this.loadUserData();
    this.loadDashboardData();

    // Gérer l'URL hash pour les onglets
    const hash = window.location.hash.substring(1);
    if (hash) {
      this.switchTab(hash);
    }
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

    // Déclencheurs d'onglets
    this.tabTriggers.forEach((trigger) => {
      trigger.addEventListener("click", (e) => {
        e.preventDefault();
        const tab = trigger.dataset.tabTrigger;
        this.switchTab(tab);
        window.location.hash = tab;
      });
    });

    // Menu utilisateur
    this.setupUserMenu();

    // Formulaire de création d'article
    this.createArticleForm?.addEventListener("submit", (e) => {
      e.preventDefault();
      this.handleCreateArticle();
    });

    // Bouton reset formulaire
    document.getElementById("resetForm")?.addEventListener("click", () => {
      this.createArticleForm.reset();
    });

    // Formulaire de profil
    this.profileForm?.addEventListener("submit", (e) => {
      e.preventDefault();
      this.handleUpdateProfile();
    });

    // Filtres articles
    document
      .getElementById("articleFilter")
      ?.addEventListener("change", (e) => {
        this.filterUserArticles(e.target.value);
      });

    document.getElementById("articleSearch")?.addEventListener("input", (e) => {
      this.searchUserArticles(e.target.value);
    });

    // Suppression de compte
    document
      .getElementById("deleteAccountBtn")
      ?.addEventListener("click", () => {
        this.showDeleteModal();
      });

    document
      .getElementById("closeDeleteModal")
      ?.addEventListener("click", () => {
        this.hideDeleteModal();
      });

    document.getElementById("cancelDelete")?.addEventListener("click", () => {
      this.hideDeleteModal();
    });

    document.getElementById("confirmDelete")?.addEventListener("click", () => {
      this.handleDeleteAccount();
    });

    // Fermer modal en cliquant sur l'overlay
    this.deleteModal?.addEventListener("click", (e) => {
      if (e.target === this.deleteModal) {
        this.hideDeleteModal();
      }
    });
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
      case "articles":
        this.loadUserArticles();
        break;
      case "comments":
        this.loadUserComments();
        break;
      case "profile":
        this.loadProfileData();
        break;
    }
  }

  async loadUserData() {
    const user = api.getCurrentUser();
    if (user) {
      // Mettre à jour les informations utilisateur dans l'interface
      const userTypeElement = document.getElementById("userType");
      if (userTypeElement) {
        userTypeElement.textContent = this.getUserTypeLabel(user.type_compte);
      }
    }
  }

  async loadDashboardData() {
    try {
      // Charger les statistiques
      await this.loadUserStats();
      await this.loadRecentArticles();
    } catch (error) {
      console.error("Erreur lors du chargement du tableau de bord:", error);
    }
  }

  async loadUserStats() {
    // Pour l'instant, utiliser des données simulées
    // Dans un vrai projet, vous feriez un appel API pour récupérer les stats
    const user = api.getCurrentUser();
    if (user) {
      document.getElementById("statsArticles").textContent = "0";
      document.getElementById("statsComments").textContent = "0";
      document.getElementById("statsPending").textContent = "0";
    }
  }

  async loadRecentArticles() {
    try {
      const response = await api.getArticles({
        utilisateur_id: api.getCurrentUser().id,
      });
      if (response.success && response.data) {
        const recent = response.data.slice(0, 5);
        this.displayRecentArticles(recent);
      }
    } catch (error) {
      console.error("Erreur lors du chargement des articles récents:", error);
    }
  }

  displayRecentArticles(articles) {
    const container = document.getElementById("recentArticles");
    if (!container) return;

    if (articles.length === 0) {
      container.innerHTML = '<p class="text-muted">Aucun article publié</p>';
      return;
    }

    container.innerHTML = articles
      .map(
        (article) => `
            <div class="d-flex justify-between align-center p-sm" style="border-bottom: 1px solid var(--color-gray-200);">
                <div>
                    <h4 class="mb-xs">${article.titre}</h4>
                    <span class="badge badge-${
                      article.etat
                    }">${this.getEtatLabel(article.etat)}</span>
                </div>
                <div class="text-small text-muted">
                    ${this.formatDate(article.date_creation)}
                </div>
            </div>
        `
      )
      .join("");
  }

  async loadUserArticles() {
    try {
      const response = await api.getArticles({
        utilisateur_id: api.getCurrentUser().id,
      });
      if (response.success && response.data) {
        this.userArticles = response.data;
        this.displayUserArticles(this.userArticles);
      }
    } catch (error) {
      console.error("Erreur lors du chargement des articles:", error);
      auth.showMessage("Erreur lors du chargement des articles", "error");
    }
  }

  displayUserArticles(articles) {
    const container = document.getElementById("userArticles");
    if (!container) return;

    if (articles.length === 0) {
      container.innerHTML = `
                <div class="text-center p-xl">
                    <h3>Aucun article</h3>
                    <p class="text-muted">Vous n'avez pas encore publié d'articles</p>
                    <button class="btn btn-primary" data-tab-trigger="create">
                        ➕ Écrire mon premier article
                    </button>
                </div>
            `;
      return;
    }

    container.innerHTML = articles
      .map(
        (article) => `
            <div class="card article-card ${article.etat}">
                <div class="card-body">
                    <div class="d-flex justify-between align-start mb-md">
                        <div>
                            <h3 class="card-title mb-xs">${article.titre}</h3>
                            <div class="article-badges">
                                <span class="badge badge-${
                                  article.etat
                                }">${this.getEtatLabel(article.etat)}</span>
                                <span class="badge badge-${
                                  article.visibilite
                                }">${this.getVisibiliteLabel(
          article.visibilite
        )}</span>
                            </div>
                        </div>
                        <div class="text-small text-muted">
                            ${this.formatDate(article.date_creation)}
                        </div>
                    </div>
                    
                    <p class="article-excerpt mb-md">
                        ${article.contenu.substring(0, 150)}...
                    </p>
                    
                    <div class="d-flex justify-between align-center">
                        <div class="text-small text-muted">
                            Modifié le ${this.formatDate(
                              article.date_modification
                            )}
                        </div>
                        <div class="btn-group">
                            <a href="/article-detail?id=${
                              article.id
                            }" class="btn btn-sm btn-outline-primary">
                                👁️ Voir
                            </a>
                            <button class="btn btn-sm btn-outline-secondary" onclick="dashboard.editArticle(${
                              article.id
                            })">
                                ✏️ Modifier
                            </button>
                            <button class="btn btn-sm btn-outline-error" onclick="dashboard.deleteArticle(${
                              article.id
                            })">
                                🗑️ Supprimer
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `
      )
      .join("");

    // Réattacher les event listeners pour les nouveaux boutons
    this.setupArticleActions();
  }

  setupArticleActions() {
    // Les actions sont gérées via les attributs onclick dans le HTML
    // pour simplifier, mais dans un vrai projet, vous devriez utiliser
    // addEventListener pour une meilleure séparation des préoccupations
  }

  filterUserArticles(etat) {
    let filtered = this.userArticles;
    if (etat) {
      filtered = this.userArticles.filter((article) => article.etat === etat);
    }
    this.displayUserArticles(filtered);
  }

  searchUserArticles(query) {
    let filtered = this.userArticles;
    if (query) {
      filtered = this.userArticles.filter((article) =>
        article.titre.toLowerCase().includes(query.toLowerCase())
      );
    }
    this.displayUserArticles(filtered);
  }

  async handleCreateArticle() {
    try {
      const formData = new FormData(this.createArticleForm);
      const articleData = {
        titre: formData.get("titre"),
        contenu: formData.get("contenu"),
        visibilite: formData.get("visibilite"),
      };

      // Validation
      if (!this.validateArticleData(articleData)) {
        return;
      }

      const response = await api.createArticle(articleData);

      if (response.success) {
        auth.showMessage("Article créé avec succès !", "success");
        this.createArticleForm.reset();
        this.switchTab("articles");
        await this.loadUserArticles();
      }
    } catch (error) {
      console.error("Erreur lors de la création de l'article:", error);
      auth.showMessage(error.message, "error");
    }
  }

  validateArticleData(data) {
    let isValid = true;

    // Validation titre
    if (!data.titre || data.titre.trim().length === 0) {
      this.showFieldError("title-error", "Le titre est requis");
      isValid = false;
    } else if (data.titre.length > 255) {
      this.showFieldError(
        "title-error",
        "Le titre ne peut pas dépasser 255 caractères"
      );
      isValid = false;
    } else {
      this.clearFieldError("title-error");
    }

    // Validation contenu
    if (!data.contenu || data.contenu.trim().length === 0) {
      this.showFieldError("content-error", "Le contenu est requis");
      isValid = false;
    } else {
      this.clearFieldError("content-error");
    }

    return isValid;
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

  async loadUserComments() {
    // Implémenter le chargement des commentaires utilisateur
    const container = document.getElementById("userComments");
    if (container) {
      container.innerHTML =
        '<p class="text-muted">Fonctionnalité en développement</p>';
    }
  }

  async loadProfileData() {
    const user = api.getCurrentUser();
    if (user) {
      document.getElementById("profileEmail").value = user.email;
      document.getElementById("profilePseudonyme").value = user.pseudonyme;
    }
  }

  async handleUpdateProfile() {
    try {
      const formData = new FormData(this.profileForm);
      const profileData = {
        email: formData.get("email"),
        pseudonyme: formData.get("pseudonyme"),
      };

      const password = formData.get("password");
      if (password) {
        profileData.password = password;
      }

      const user = api.getCurrentUser();
      const response = await api.updateUser(user.id, profileData);

      if (response.success) {
        auth.showMessage("Profil mis à jour avec succès !", "success");
        // Mettre à jour les données utilisateur stockées
        localStorage.setItem(
          "currentUser",
          JSON.stringify({
            ...user,
            email: profileData.email,
            pseudonyme: profileData.pseudonyme,
          })
        );
        auth.updateUIForAuthState();
      }
    } catch (error) {
      console.error("Erreur lors de la mise à jour du profil:", error);
      auth.showMessage(error.message, "error");
    }
  }

  showDeleteModal() {
    this.deleteModal.classList.add("show");
  }

  hideDeleteModal() {
    this.deleteModal.classList.remove("show");
  }

  async handleDeleteAccount() {
    try {
      const deleteContent = document.getElementById("deleteContent").checked;
      const user = api.getCurrentUser();

      const response = await api.request(`/utilisateur/${user.id}`, {
        method: "DELETE",
        body: JSON.stringify({ supprimer_contenus: deleteContent }),
      });

      if (response.success) {
        auth.showMessage("Compte supprimé avec succès", "success");
        setTimeout(() => {
          auth.logout();
        }, 2000);
      }
    } catch (error) {
      console.error("Erreur lors de la suppression du compte:", error);
      auth.showMessage(error.message, "error");
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

  getEtatLabel(etat) {
    const labels = {
      en_attente: "En attente",
      accepte: "Accepté",
      refuse: "Refusé",
      efface: "Effacé",
    };
    return labels[etat] || etat;
  }

  getVisibiliteLabel(visibilite) {
    const labels = {
      public: "Public",
      prive: "Privé",
    };
    return labels[visibilite] || visibilite;
  }

  formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString("fr-FR", {
      year: "numeric",
      month: "long",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });
  }

  // Méthodes pour les actions sur les articles
  editArticle(id) {
    // Redirection vers la page d'édition ou ouvrir un modal d'édition
    window.location.href = `/edit-article?id=${id}`;
  }

  async deleteArticle(id) {
    if (confirm("Êtes-vous sûr de vouloir supprimer cet article ?")) {
      try {
        const response = await api.deleteArticle(id);
        if (response.success) {
          auth.showMessage("Article supprimé avec succès", "success");
          await this.loadUserArticles();
        }
      } catch (error) {
        console.error("Erreur lors de la suppression:", error);
        auth.showMessage(error.message, "error");
      }
    }
  }
}

// Instance globale du dashboard
window.dashboard = new Dashboard();
