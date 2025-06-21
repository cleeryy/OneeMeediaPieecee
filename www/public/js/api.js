/**
 * API Client pour OneMediaPiece
 * Gère tous les appels vers l'API REST
 */
class ApiClient {
  constructor() {
    this.baseUrl = "/api";
    this.token = localStorage.getItem("authToken");
  }

  /**
   * Effectue une requête HTTP vers l'API
   */
  async request(endpoint, options = {}) {
    const url = `${this.baseUrl}${endpoint}`;

    const config = {
      headers: {
        "Content-Type": "application/json",
        ...options.headers,
      },
      ...options,
    };

    // Ajouter le token d'authentification si disponible
    if (this.token) {
      config.headers["Authorization"] = `Bearer ${this.token}`;
    }

    try {
      const response = await fetch(url, config);
      const data = await response.json();

      if (!data.success) {
        throw new Error(data.message || "Erreur API");
      }

      return data;
    } catch (error) {
      console.error("Erreur API:", error);
      throw error;
    }
  }

  /**
   * Authentification
   */
  async login(email, password) {
    const response = await this.request("/auth/login", {
      method: "POST",
      body: JSON.stringify({ email, password }),
    });

    if (response.success && response.data.token) {
      this.token = response.data.token;
      localStorage.setItem("authToken", this.token);
      localStorage.setItem("currentUser", JSON.stringify(response.data.user));
    }

    return response;
  }

  async logout() {
    try {
      await this.request("/auth/logout", { method: "POST" });
    } catch (error) {
      console.warn("Erreur lors de la déconnexion:", error);
    } finally {
      this.token = null;
      localStorage.removeItem("authToken");
      localStorage.removeItem("currentUser");
    }
  }

  async register(userData) {
    return await this.request("/utilisateur", {
      method: "POST",
      body: JSON.stringify(userData),
    });
  }

  /**
   * Utilisateurs
   */
  async getUser(id) {
    return await this.request(`/utilisateur/${id}`);
  }

  async updateUser(id, userData) {
    return await this.request(`/utilisateur/${id}`, {
      method: "PUT",
      body: JSON.stringify(userData),
    });
  }

  /**
   * Articles
   */
  async getArticles(filters = {}) {
    const params = new URLSearchParams(filters);
    return await this.request(`/article?${params}`);
  }

  async getArticle(id) {
    return await this.request(`/article/${id}`);
  }

  async createArticle(articleData) {
    return await this.request("/article", {
      method: "POST",
      body: JSON.stringify(articleData),
    });
  }

  async updateArticle(id, articleData) {
    return await this.request(`/article/${id}`, {
      method: "PUT",
      body: JSON.stringify(articleData),
    });
  }

  async deleteArticle(id) {
    return await this.request(`/article/${id}`, {
      method: "DELETE",
    });
  }

  /**
   * Commentaires
   */
  async getComments(articleId) {
    return await this.request(`/article/${articleId}/commentaire`);
  }

  async createComment(articleId, content) {
    return await this.request(`/article/${articleId}/commentaire`, {
      method: "POST",
      body: JSON.stringify({ contenu: content }),
    });
  }

  async updateComment(id, content) {
    return await this.request(`/commentaire/${id}`, {
      method: "PUT",
      body: JSON.stringify({ contenu: content }),
    });
  }

  async deleteComment(id) {
    return await this.request(`/commentaire/${id}`, {
      method: "DELETE",
    });
  }

  /**
   * Modération
   */
  async getArticlesEnAttente() {
    return await this.request("/moderation/articles");
  }

  async getCommentairesEnAttente() {
    return await this.request("/moderation/commentaires");
  }

  async modererArticle(id, action, description = "") {
    return await this.request(`/moderation/article/${id}`, {
      method: "PUT",
      body: JSON.stringify({ action, description }),
    });
  }

  async modererCommentaire(id, action, description = "") {
    return await this.request(`/moderation/commentaire/${id}`, {
      method: "PUT",
      body: JSON.stringify({ action, description }),
    });
  }

  /**
   * Utilitaires
   */
  isAuthenticated() {
    return !!this.token;
  }

  getCurrentUser() {
    const userStr = localStorage.getItem("currentUser");
    return userStr ? JSON.parse(userStr) : null;
  }

  hasRole(role) {
    const user = this.getCurrentUser();
    if (!user) return false;

    switch (role) {
      case "admin":
        return user.type_compte === "administrateur";
      case "moderator":
        return ["administrateur", "moderateur"].includes(user.type_compte);
      case "user":
        return true;
      default:
        return false;
    }
  }
}

// Instance globale de l'API
window.api = new ApiClient();
