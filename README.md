# Serveur de QCM – README

## Présentation

Ce projet est un prototype complet de **serveur de QCM** permettant :

* aux **professeurs** de créer un référentiel de questions, composer des QCM, les publier, suivre les copies et consulter les résultats ;
* aux **élèves** de passer les QCM en ligne, voir immédiatement leur note et l’historique de leurs tentatives.

Le périmètre fonctionnel reprend le cahier des charges initial : dépôt et partage de questions, génération de QCM, notation automatique et accès réservé aux utilisateurs authentifiés.

## Flux d’utilisation

| Acteur         | Parcours principal                                                                                                                                                                                                                                                                                          |
| -------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Professeur** | 1. Connexion → 2. Tableau de bord → 3. Création ou import de **questions** (thème & sous‑thème, choix simple ou multiple) → 4. Création d’un **QCM** (drag‑and‑drop des questions, définition durée & tentatives) → 5. Publication (visibilité) → 6. Suivi des copies en temps réel & export des résultats. |
| **Élève**      | 1. Connexion → 2. Liste des QCM disponibles → 3. Démarrage d’une tentative → 4. Passage (minuteur, blocage retour navigateur) → 5. Soumission automatique ou manuelle → 6. Affichage de la note et détail question par question.                                                                            |

## Sécurité

* Hachage des mots de passe avec `password_hash` (algo par défaut).
* Vérification systématique du rôle (*prof* / *eleve*) côté serveur avant chaque action sensible.
* L’API AJAX renvoie toujours des codes HTTP appropriés et ne renvoie jamais de données sensibles.
* Clés étrangères avec `ON DELETE CASCADE` pour maintenir l’intégrité (ex. suppression d’une question enlève les références dans les QCM).

## Installation

1. **Pré‑requis** : PHP 8.1+, MySQL/MariaDB, Composer, serveur web local.
2. Cloner le dépôt :

   ```bash
   git clone https://…/qcm-server.git && cd qcm-server
   ```
3. Installer les dépendances PHP (uniquement `vlucas/phpdotenv`) :

   ```bash
   composer install
   ```
4. Créer `.env` et renseigner l’hôte, la base, l’utilisateur et le mot de passe.
5. Créer la base **`qcm_db`** dans phpMyAdmin, puis **importer** le script `qcm_db.sql` (fourni).
6. Placer le dossier `public/` dans votre *DocumentRoot* (ou créer un virtual‑host) et appeler `http://localhost/qcm-server/public/`.
7. Placer le dossier `config/` dans votre *DocumentRoot*

## Structure de la base `qcm_db`

```sql
-- Table des utilisateurs
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(50) UNIQUE NOT NULL,
  mot_de_passe VARCHAR(255) NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  statut ENUM('prof','eleve') NOT NULL
);

-- Thèmes & sous‑thèmes
CREATE TABLE themes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(100) UNIQUE NOT NULL
);

CREATE TABLE subthemes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  theme_id INT NOT NULL,
  nom VARCHAR(100) NOT NULL,
  FOREIGN KEY (theme_id) REFERENCES themes(id) ON DELETE CASCADE
);

-- Questions
CREATE TABLE questions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  auteur_id INT NOT NULL,
  theme_id INT NOT NULL,
  subtheme_id INT NULL,
  is_multiple TINYINT(1) DEFAULT 0,
  texte_question TEXT NOT NULL,
  reponses JSON NOT NULL,
  FOREIGN KEY (auteur_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (theme_id)  REFERENCES themes(id),
  FOREIGN KEY (subtheme_id) REFERENCES subthemes(id) ON DELETE SET NULL
);

-- QCMs
CREATE TABLE qcms (
  id INT AUTO_INCREMENT PRIMARY KEY,
  auteur_id INT NOT NULL,
  titre VARCHAR(150) NOT NULL,
  date_examen DATE NOT NULL,
  duree_min INT NOT NULL,
  tentative_max INT DEFAULT 1,
  visible TINYINT(1) DEFAULT 0,
  FOREIGN KEY (auteur_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Relations QCM ↔ questions (ordre conservé)
CREATE TABLE qcm_questions (
  qcm_id INT NOT NULL,
  question_id INT NOT NULL,
  ordre INT NOT NULL,
  PRIMARY KEY (qcm_id, question_id),
  FOREIGN KEY (qcm_id) REFERENCES qcms(id) ON DELETE CASCADE,
  FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);

-- Tentatives
CREATE TABLE qcm_attempts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  qcm_id INT NOT NULL,
  eleve_id INT NOT NULL,
  start_time DATETIME NOT NULL,
  end_time DATETIME NULL,
  good INT DEFAULT 0,
  total INT DEFAULT 0,
  finished TINYINT(1) DEFAULT 0,
  FOREIGN KEY (qcm_id) REFERENCES qcms(id) ON DELETE CASCADE,
  FOREIGN KEY (eleve_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Réponses d’une tentative
CREATE TABLE qcm_answers (
  attempt_id INT NOT NULL,
  question_id INT NOT NULL,
  selected JSON NOT NULL,
  PRIMARY KEY (attempt_id, question_id),
  FOREIGN KEY (attempt_id) REFERENCES qcm_attempts(id) ON DELETE CASCADE,
  FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);
```

> **Astuce phpMyAdmin** : ouvrez l’onglet *Importer* de la base `qcm_db`, sélectionnez `qcm_db.sql`, laissez les options par défaut puis validez.

## Licence

Projet académique – usage pédagogique libre.