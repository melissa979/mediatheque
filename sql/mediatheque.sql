-- =========================================================
-- Gestion d'une médiathèque : création de la base (MySQL / MariaDB)
-- Modèle relationnel : étape 6.2 du dossier
-- À importer dans phpMyAdmin (onglet Importer)
-- =========================================================

DROP DATABASE IF EXISTS mediatheque;
CREATE DATABASE mediatheque CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mediatheque;

-- Comptes du personnel (agents et administrateur)
CREATE TABLE utilisateur (
  id INT AUTO_INCREMENT PRIMARY KEY,
  login VARCHAR(50) NOT NULL UNIQUE,
  mot_de_passe_hash VARCHAR(255) NOT NULL,
  nom VARCHAR(100) NOT NULL,
  role ENUM('agent','admin') NOT NULL DEFAULT 'agent',
  actif TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE adherent (
  id INT AUTO_INCREMENT PRIMARY KEY,
  numero_carte VARCHAR(20) NOT NULL UNIQUE,
  nom VARCHAR(100) NOT NULL,
  prenom VARCHAR(100) NOT NULL,
  date_naissance DATE NOT NULL,
  adresse VARCHAR(255) NOT NULL,
  telephone VARCHAR(20),
  email VARCHAR(150),
  date_inscription DATE NOT NULL,
  date_fin_abonnement DATE NOT NULL,
  carte_bloquee TINYINT(1) NOT NULL DEFAULT 0,
  motif_blocage VARCHAR(255),
  UNIQUE KEY uk_adherent_identite (nom, prenom, date_naissance)  -- pas de doublon (F5)
) ENGINE=InnoDB;

CREATE TABLE document (
  id INT AUTO_INCREMENT PRIMARY KEY,
  titre VARCHAR(255) NOT NULL,
  type ENUM('livre','revue','CD','DVD') NOT NULL,
  auteur VARCHAR(150),
  editeur VARCHAR(150),
  annee SMALLINT,
  genre VARCHAR(100),
  cote VARCHAR(50)
) ENGINE=InnoDB;

CREATE TABLE exemplaire (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code_barre VARCHAR(30) NOT NULL UNIQUE,
  etat ENUM('disponible','emprunte','reserve','perdu','abime') NOT NULL DEFAULT 'disponible',
  document_id INT NOT NULL,
  -- composition : supprimer un document supprime ses exemplaires
  CONSTRAINT fk_exemplaire_document FOREIGN KEY (document_id) REFERENCES document(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE emprunt (
  id INT AUTO_INCREMENT PRIMARY KEY,
  adherent_id INT NOT NULL,
  exemplaire_id INT NOT NULL,
  utilisateur_id INT NOT NULL,
  date_emprunt DATE NOT NULL,
  date_retour_prevue DATE NOT NULL,
  date_retour_reelle DATE NULL,          -- NULL = emprunt en cours
  nb_prolongations INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_emprunt_adherent FOREIGN KEY (adherent_id) REFERENCES adherent(id),
  CONSTRAINT fk_emprunt_exemplaire FOREIGN KEY (exemplaire_id) REFERENCES exemplaire(id),
  CONSTRAINT fk_emprunt_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(id)
) ENGINE=InnoDB;

-- Règles de gestion paramétrables (R1 à R5)
CREATE TABLE parametre (
  cle VARCHAR(50) PRIMARY KEY,
  valeur VARCHAR(50) NOT NULL,
  libelle VARCHAR(150) NOT NULL
) ENGINE=InnoDB;

INSERT INTO parametre (cle, valeur, libelle) VALUES
('duree_abonnement_jours', '365', 'Durée de l''abonnement (jours)'),
('quota_total', '25', 'Nombre maximal d''emprunts en cours'),
('quota_dvd', '10', 'Nombre maximal de DVD en cours'),
('duree_pret_jours', '28', 'Durée d''un emprunt (jours)'),
('nb_prolongations_max', '1', 'Nombre maximal de prolongations'),
('duree_prolongation_jours', '28', 'Durée d''une prolongation (jours)');

-- =========================================================
-- Données de test (fictives, RGPD)
-- Les comptes du personnel sont créés par public/installer.php
-- =========================================================
INSERT INTO adherent (numero_carte, nom, prenom, date_naissance, adresse, telephone, email, date_inscription, date_fin_abonnement, carte_bloquee, motif_blocage) VALUES
('C0001', 'Martin', 'Léa', '2001-04-12', '12 rue des Lilas, Nanterre', '0601020304', 'lea.martin@exemple.fr', CURDATE() - INTERVAL 100 DAY, CURDATE() + INTERVAL 265 DAY, 0, NULL),
('C0002', 'Bernard', 'Hugo', '1995-09-30', '3 avenue Jean Jaurès, Nanterre', '0611223344', 'hugo.bernard@exemple.fr', CURDATE() - INTERVAL 400 DAY, CURDATE() - INTERVAL 35 DAY, 0, NULL),
('C0003', 'Petit', 'Inès', '2010-01-05', '8 place de la Mairie, Nanterre', '0622334455', 'ines.petit@exemple.fr', CURDATE() - INTERVAL 50 DAY, CURDATE() + INTERVAL 315 DAY, 1, 'Carte déclarée perdue'),
('C0004', 'Durand', 'Karim', '1988-07-21', '25 rue Voltaire, Nanterre', '0633445566', 'karim.durand@exemple.fr', CURDATE() - INTERVAL 200 DAY, CURDATE() + INTERVAL 165 DAY, 0, NULL),
('C0005', 'Leroy', 'Emma', '1999-11-02', '4 allée des Pins, Nanterre', '0644556677', 'emma.leroy@exemple.fr', CURDATE() - INTERVAL 10 DAY, CURDATE() + INTERVAL 355 DAY, 0, NULL);

INSERT INTO document (titre, type, auteur, editeur, annee, genre, cote) VALUES
('Le Petit Prince', 'livre', 'Antoine de Saint-Exupéry', 'Gallimard', 1943, 'Conte', 'R SAI'),
('L''Étranger', 'livre', 'Albert Camus', 'Gallimard', 1942, 'Roman', 'R CAM'),
('Astérix le Gaulois', 'livre', 'René Goscinny', 'Dargaud', 1961, 'Bande dessinée', 'BD GOS'),
('Science & Vie n°1300', 'revue', NULL, 'Reworld Media', 2026, 'Sciences', 'P SCI'),
('Intouchables', 'DVD', 'Olivier Nakache, Éric Toledano', 'Gaumont', 2011, 'Comédie', 'F NAK'),
('Racine carrée', 'CD', 'Stromae', 'Mosaert', 2013, 'Pop', 'M STR');

INSERT INTO exemplaire (code_barre, etat, document_id) VALUES
('EX0001', 'disponible', 1), ('EX0002', 'emprunte', 1),
('EX0003', 'emprunte', 2),
('EX0004', 'disponible', 3), ('EX0005', 'disponible', 3),
('EX0006', 'disponible', 4),
('EX0007', 'disponible', 5),
('EX0008', 'disponible', 6);
-- Les emprunts de test (dont un en retard) sont ajoutés par installer.php,
-- car ils doivent être liés à un compte du personnel.
