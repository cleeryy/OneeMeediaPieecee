CREATE DATABASE IF NOT EXISTS onemediapiece;

USE onemediapiece;

CREATE TABLE IF NOT EXISTS Utilisateur (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    pseudonyme VARCHAR(50) NOT NULL,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    type_compte ENUM('redacteur', 'moderateur', 'administrateur') DEFAULT 'redacteur',
    etat_compte ENUM('en_attente', 'valide', 'refuse') DEFAULT 'en_attente',
    est_banni BOOLEAN DEFAULT FALSE
);

CREATE TABLE IF NOT EXISTS Article (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titre VARCHAR(255) NOT NULL,
    contenu TEXT NOT NULL,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    etat ENUM('en_attente', 'accepte', 'refuse', 'efface') DEFAULT 'en_attente',
    visibilite ENUM('public', 'prive') DEFAULT 'public',
    utilisateur_id INT NOT NULL,
    FOREIGN KEY (utilisateur_id) REFERENCES Utilisateur(id)
);

CREATE TABLE IF NOT EXISTS Commentaire (
    id INT PRIMARY KEY AUTO_INCREMENT,
    contenu TEXT NOT NULL,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    etat ENUM('en_attente', 'accepte', 'refuse', 'efface') DEFAULT 'en_attente',
    utilisateur_id INT NOT NULL,
    article_id INT NOT NULL,
    FOREIGN KEY (utilisateur_id) REFERENCES Utilisateur(id),
    FOREIGN KEY (article_id) REFERENCES Article(id)
);

CREATE TABLE IF NOT EXISTS Moderation (
    id INT PRIMARY KEY AUTO_INCREMENT,
    type_action ENUM('refus_article', 'refus_commentaire', 'signalement', 'suppression_compte', 'validation_compte', 'refus_compte', 'changement_role'),
    description TEXT,
    date_action DATETIME DEFAULT CURRENT_TIMESTAMP,
    moderateur_id INT NOT NULL,
    cible_utilisateur_id INT,
    cible_article_id INT,
    cible_commentaire_id INT,
    FOREIGN KEY (moderateur_id) REFERENCES Utilisateur(id),
    FOREIGN KEY (cible_utilisateur_id) REFERENCES Utilisateur(id),
    FOREIGN KEY (cible_article_id) REFERENCES Article(id),
    FOREIGN KEY (cible_commentaire_id) REFERENCES Commentaire(id)
);

-- Index pour optimiser les requêtes fréquentes
CREATE INDEX idx_utilisateur_etat_compte ON Utilisateur(etat_compte);
CREATE INDEX idx_utilisateur_type_compte ON Utilisateur(type_compte);
CREATE INDEX idx_article_etat ON Article(etat);
CREATE INDEX idx_commentaire_etat ON Commentaire(etat);
CREATE INDEX idx_moderation_type_action ON Moderation(type_action);

-- Insertion des utilisateurs de test
-- Tous les mots de passe sont "password123" (hashés avec password_hash)

-- 1 Administrateur
INSERT INTO Utilisateur (email, mot_de_passe, pseudonyme, type_compte, etat_compte) 
VALUES (
    'admin@onemediapiece.com', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
    'Administrateur', 
    'administrateur', 
    'valide'
);

-- 2 Modérateurs
INSERT INTO Utilisateur (email, mot_de_passe, pseudonyme, type_compte, etat_compte) 
VALUES 
(
    'moderateur1@onemediapiece.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'ModérateurAlice',
    'moderateur',
    'valide'
),
(
    'moderateur2@onemediapiece.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'ModérateurBob',
    'moderateur',
    'valide'
);

-- 3 Rédacteurs (2 validés + 1 en attente pour tester la validation)
INSERT INTO Utilisateur (email, mot_de_passe, pseudonyme, type_compte, etat_compte) 
VALUES 
(
    'redacteur1@onemediapiece.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'RédacteurCharlie',
    'redacteur',
    'valide'
),
(
    'redacteur2@onemediapiece.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'RédacteurDiana',
    'redacteur',
    'valide'
),
(
    'redacteur3@onemediapiece.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'RédacteurEve',
    'redacteur',
    'en_attente'
);

-- Insertion d'articles de test pour avoir du contenu
INSERT INTO Article (titre, contenu, etat, visibilite, utilisateur_id) VALUES
(
    'Bienvenue sur OneMediaPiece !',
    'Ceci est le premier article de notre plateforme collaborative. Nous sommes ravis de vous accueillir dans cette nouvelle aventure du journalisme participatif.\n\nNotre objectif est de créer un espace où chacun peut s\'exprimer librement tout en maintenant un niveau de qualité élevé grâce à notre système de modération.\n\nN\'hésitez pas à créer votre compte et à commencer à publier vos propres articles !',
    'accepte',
    'public',
    1
),
(
    'Les règles de modération sur OneMediaPiece',
    'Pour garantir la qualité des contenus sur notre plateforme, voici les principales règles de modération :\n\n1. **Respect des autres** : Aucun contenu diffamatoire ou haineux ne sera toléré\n2. **Sources fiables** : Vérifiez vos informations avant publication\n3. **Originalité** : Le plagiat est strictement interdit\n4. **Pertinence** : Restez dans le sujet de votre article\n\nTous les articles de rédacteurs passent par une phase de modération avant publication. Les modérateurs et administrateurs peuvent publier directement.',
    'accepte',
    'public',
    2
),
(
    'Comment bien rédiger un article ?',
    'Voici quelques conseils pour rédiger un article de qualité :\n\n**Structure claire :**\n- Utilisez des titres et sous-titres\n- Organisez vos idées de manière logique\n- Faites des paragraphes courts et aérés\n\n**Contenu engageant :**\n- Commencez par une accroche\n- Utilisez des exemples concrets\n- Terminez par une conclusion forte\n\n**Relecture :**\n- Vérifiez l\'orthographe et la grammaire\n- Assurez-vous que vos sources sont crédibles\n- Relisez votre article avant publication',
    'en_attente',
    'public',
    4
);

-- Insertion de commentaires de test
INSERT INTO Commentaire (contenu, etat, utilisateur_id, article_id) VALUES
(
    'Excellente initiative ! J\'ai hâte de voir cette communauté grandir et de participer aux discussions.',
    'accepte',
    4,
    1
),
(
    'Merci pour ces règles claires. Cela va aider tout le monde à mieux comprendre les attentes de la plateforme.',
    'accepte',
    5,
    2
),
(
    'Ces conseils sont très utiles, surtout pour les nouveaux rédacteurs comme moi !',
    'en_attente',
    6,
    3
);
