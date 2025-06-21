/**
 * Interface de modération pour OneMediaPiece
 */
class ModerationInterface {
  constructor() {
    this.currentTab = "articles";
    this.pendingArticles = [];
    this.pendingComments = [];
    this.reports = [];
    this.history = [];

    // Éléments DOM
    this.tabs = document.querySelectorAll(".tab-content");
    this.tabButtons = document.querySelectorAll(".tab-button");
    this.moderationModal = document.getElementById("moderationModal");

    // Callback pour la modération
    this.moderationCallback = null;
  }

  init() {
    this.setupEventListeners();
    this.loadModerationData();
  }

  setupEventListeners() {
    // Navigation entre onglets
    this.tabButtons.forEach((button) => {
      button.addEventListener("click", () => {
        const tab = button.dataset.tab;
        this.switchTab(tab);
      });
    });

    // Boutons d'actualisation
    document
      .getElementById("refreshArticles")
      ?.addEventListener("click", () => {
        this.loadPendingArticles();
      });

    document
      .getElementById("refreshComments")
      ?.addEventListener("click", () => {
        this.loadPendingComments();
      });

    document.getElementById("refreshReports")?.addEventListener("click", () => {
      this.loadReports();
    });

    document.getElementById("refreshHistory")?.addEventListener("click", () => {
      this.loadHistory();
    });

    // Filtre historique
    document
      .getElementById("historyFilter")
      ?.addEventListener("change", (e) => {
        this.filterHistory(e.target.value);
      });

    // Menu utilisateur
    this.setupUserMenu();

    // Modal de modération
    this.setupModerationModal();
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

  setupModerationModal() {
    document
      .getElementById("closeModerationModal")
      ?.addEventListener("click", () => {
        this.hideModerationModal();
      });

    document
      .getElementById("cancelModeration")
      ?.addEventListener("click", () => {
        this.hideModerationModal();
      });

    document
      .getElementById("confirmModeration")
      ?.addEventListener("click", () => {
        this.executeModerationAction();
      });

    this.moderationModal?.addEventListener("click", (e) => {
      if (e.target === this.moderationModal) {
        this.hideModerationModal();
      }
    });
  }

  switchTab(tabName) {
    // Masquer tous les onglets
    this.tabs.forEach((tab) => {
      tab.classList.remove("active");
    });

    // Désactiver tous les boutons
    this.tabButtons.forEach((button) => {
      button.classList.remove("active");
    });

    // Afficher l'onglet sélectionné
    const targetTab = document.getElementById(`tab-${tabName}`);
    if (targetTab) {
      targetTab.classList.add("active");
      this.currentTab = tabName;
    }

    // Activer le bouton correspondant
    const targetButton = document.querySelector(`[data-tab="${tabName}"]`);
    if (targetButton) {
      targetButton.classList.add("active");
    }

    // Charger les données selon l'onglet
    switch (tabName) {
      case "articles":
        this.loadPendingArticles();
        break;
      case "comments":
        this.loadPendingComments();
        break;
      case "reports":
        this.loadReports();
        break;
      case "history":
        this.loadHistory();
        break;
    }
  }

  async loadModerationData() {
    // Charger les statistiques
    await this.loadStats();
    // Charger les données de l'onglet actuel
    await this.loadPendingArticles();
  }

  async loadStats() {
    try {
      // Pour l'instant, utiliser des appels séparés
      // Dans un vrai projet, vous pourriez avoir un endpoint de stats
      const [articlesResponse, commentsResponse] = await Promise.all([
        api.getArticlesEnAttente(),
        api.getCommentairesEnAttente(),
      ]);

      const articlesCount = articlesResponse.success
        ? articlesResponse.data.length
        : 0;
      const commentsCount = commentsResponse.success
        ? commentsResponse.data.length
        : 0;

      document.getElementById("statsPendingArticles").textContent =
        articlesCount;
      document.getElementById("statsPendingComments").textContent =
        commentsCount;
      document.getElementById("statsReports").textContent = "0"; // À implémenter
      document.getElementById("statsModerated").textContent = "0"; // À implémenter
    } catch (error) {
      console.error("Erreur lors du chargement des statistiques:", error);
    }
  }

  async loadPendingArticles() {
    const loading = document.getElementById("articlesLoading");
    const container = document.getElementById("articlesList");
    const noArticles = document.getElementById("noArticles");

    try {
      loading.style.display = "";
      container.innerHTML = "";
      noArticles.style.display = "none";

      const response = await api.getArticlesEnAttente();

      if (response.success && response.data) {
        this.pendingArticles = response.data;
        this.displayPendingArticles();
      } else {
        this.showNoArticles();
      }
    } catch (error) {
      console.error("Erreur lors du chargement des articles:", error);
      auth.showMessage("Erreur lors du chargement des articles", "error");
    } finally {
      loading.style.display = "none";
    }
  }

  displayPendingArticles() {
    const container = document.getElementById("articlesList");
    const noArticles = document.getElementById("noArticles");

    if (this.pendingArticles.length === 0) {
      this.showNoArticles();
      return;
    }

    container.innerHTML = this.pendingArticles
      .map(
        (article) => `
            <div class="card moderation-card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">${article.titre}</h3>
                        <p class="card-subtitle">
                            Par Auteur #${article.utilisateur_id} • 
                            ${this.formatDate(article.date_creation)}
                        </p>
                    </div>
                    <span class="badge badge-pending">En attente</span>
                </div>
                <div class="card-body">
                    <p class="mb-md">${this.truncateText(
                      article.contenu,
                      200
                    )}</p>
                    
                    <div class="moderation-actions">
                        <button class="btn btn-sm btn-accent" onclick="moderation.viewArticle(${
                          article.id
                        })">
                            👁️ Voir l'article
                        </button>
                        <button class="btn btn-sm btn-success" onclick="moderation.moderateArticle(${
                          article.id
                        }, 'accepter')">
                            ✅ Accepter
                        </button>
                        <button class="btn btn-sm btn-warning" onclick="moderation.showModerationModal('article', ${
                          article.id
                        }, 'refuser')">
                            ❌ Refuser
                        </button>
                        <button class="btn btn-sm btn-error" onclick="moderation.showModerationModal('article', ${
                          article.id
                        }, 'effacer')">
                            🗑️ Effacer
                        </button>
                    </div>
                </div>
            </div>
        `
      )
      .join("");
  }

  async loadPendingComments() {
    const loading = document.getElementById("commentsLoading");
    const container = document.getElementById("commentsList");
    const noComments = document.getElementById("noComments");

    try {
      loading.style.display = "";
      container.innerHTML = "";
      noComments.style.display = "none";

      const response = await api.getCommentairesEnAttente();

      if (response.success && response.data) {
        this.pendingComments = response.data;
        this.displayPendingComments();
      } else {
        this.showNoComments();
      }
    } catch (error) {
      console.error("Erreur lors du chargement des commentaires:", error);
      auth.showMessage("Erreur lors du chargement des commentaires", "error");
    } finally {
      loading.style.display = "none";
    }
  }

  displayPendingComments() {
    const container = document.getElementById("commentsList");
    const noComments = document.getElementById("noComments");

    if (this.pendingComments.length === 0) {
      this.showNoComments();
      return;
    }

    container.innerHTML = this.pendingComments
      .map(
        (comment) => `
            <div class="card moderation-card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">Commentaire sur l'article #${
                          comment.article_id
                        }</h3>
                        <p class="card-subtitle">
                            Par Auteur #${comment.utilisateur_id} • 
                            ${this.formatDate(comment.date_creation)}
                        </p>
                    </div>
                    <span class="badge badge-pending">En attente</span>
                </div>
                <div class="card-body">
                    <p class="mb-md">${this.formatContent(comment.contenu)}</p>
                    
                    <div class="moderation-actions">
                        <button class="btn btn-sm btn-accent" onclick="moderation.viewArticle(${
                          comment.article_id
                        })">
                            👁️ Voir l'article
                        </button>
                        <button class="btn btn-sm btn-success" onclick="moderation.moderateComment(${
                          comment.id
                        }, 'accepter')">
                            ✅ Accepter
                        </button>
                        <button class="btn btn-sm btn-warning" onclick="moderation.showModerationModal('comment', ${
                          comment.id
                        }, 'refuser')">
                            ❌ Refuser
                        </button>
                        <button class="btn btn-sm btn-error" onclick="moderation.showModerationModal('comment', ${
                          comment.id
                        }, 'effacer')">
                            🗑️ Effacer
                        </button>
                    </div>
                </div>
            </div>
        `
      )
      .join("");
  }

  async loadReports() {
    // Implémenter le chargement des signalements
    const container = document.getElementById("reportsList");
    const noReports = document.getElementById("noReports");

    // Pour l'instant, afficher un message
    container.innerHTML = "";
    noReports.style.display = "";
  }

  async loadHistory() {
    // Implémenter le chargement de l'historique
    const container = document.getElementById("historyList");

    // Pour l'instant, afficher un message
    container.innerHTML =
      '<p class="text-center text-muted p-lg">Fonctionnalité en développement</p>';
  }

  filterHistory(filter) {
    // Implémenter le filtrage de l'historique
    console.log("Filtrer l'historique par:", filter);
  }

  async moderateArticle(articleId, action, reason = "") {
    try {
      const response = await api.modererArticle(articleId, action, reason);

      if (response.success) {
        auth.showMessage(
          `Article ${
            action === "accepter"
              ? "accepté"
              : action === "refuser"
              ? "refusé"
              : "effacé"
          } avec succès !`,
          "success"
        );
        await this.loadPendingArticles();
        await this.loadStats();
      }
    } catch (error) {
      console.error("Erreur lors de la modération de l'article:", error);
      auth.showMessage(error.message, "error");
    }
  }

  async moderateComment(commentId, action, reason = "") {
    try {
      const response = await api.modererCommentaire(commentId, action, reason);

      if (response.success) {
        auth.showMessage(
          `Commentaire ${
            action === "accepter"
              ? "accepté"
              : action === "refuser"
              ? "refusé"
              : "effacé"
          } avec succès !`,
          "success"
        );
        await this.loadPendingComments();
        await this.loadStats();
      }
    } catch (error) {
      console.error("Erreur lors de la modération du commentaire:", error);
      auth.showMessage(error.message, "error");
    }
  }

  viewArticle(articleId) {
    window.open(`/article-detail?id=${articleId}`, "_blank");
  }

  showModerationModal(type, id, action) {
    const title = `${action === "refuser" ? "Refuser" : "Effacer"} ${
      type === "article" ? "l'article" : "le commentaire"
    }`;

    document.getElementById("moderationModalTitle").textContent = title;
    document.getElementById("moderationModalReason").value = "";

    this.moderationCallback = (reason) => {
      if (type === "article") {
        this.moderateArticle(id, action, reason);
      } else {
        this.moderateComment(id, action, reason);
      }
    };

    this.showModerationModalElement();
  }

  showModerationModalElement() {
    this.moderationModal.classList.add("show");
  }

  hideModerationModal() {
    this.moderationModal.classList.remove("show");
    this.moderationCallback = null;
  }

  executeModerationAction() {
    const reason = document
      .getElementById("moderationModalReason")
      .value.trim();

    if (!reason) {
      auth.showMessage("La raison est obligatoire", "error");
      return;
    }

    if (this.moderationCallback) {
      this.moderationCallback(reason);
      this.hideModerationModal();
    }
  }

  showNoArticles() {
    document.getElementById("articlesList").innerHTML = "";
    document.getElementById("noArticles").style.display = "";
  }

  showNoComments() {
    document.getElementById("commentsList").innerHTML = "";
    document.getElementById("noComments").style.display = "";
  }

  // Méthodes utilitaires
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

  formatContent(content) {
    return content.replace(/\n/g, "<br>");
  }

  truncateText(text, maxLength) {
    if (text.length <= maxLength) {
      return text;
    }
    return text.substring(0, maxLength) + "...";
  }
}

// Instance globale
window.moderation = new ModerationInterface();
