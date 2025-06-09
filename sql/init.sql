CREATE DATABASE IF NOT EXISTS onemediapiece;
USE onemediapiece;

CREATE TABLE IF NOT EXISTS Utilisateur (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    pseudonyme VARCHAR(50) NOT NULL,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    type_compte ENUM('redacteur', 'moderateur', 'administrateur') DEFAULT 'redacteur',
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
    type_action ENUM('refus_article', 'refus_commentaire', 'signalement', 'suppression_compte'),
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
