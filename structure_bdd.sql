-- ========================================
-- BASE DE DONNÉES : gestion_notes
-- ========================================

-- ========== TABLE 1 : UTILISATEURS ==========
-- Contient tous les utilisateurs : étudiants, formateurs, administrateurs
CREATE TABLE IF NOT EXISTS utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    sexe ENUM('masculin', 'féminin') DEFAULT 'masculin',
    email VARCHAR(120) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('administrateur', 'formateur', 'etudiant') NOT NULL,
    date_inscription TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    statut_scolarite ENUM('en_cours', 'termine') DEFAULT 'en_cours',
    INDEX idx_role (role),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ========== TABLE 2 : CLASSES ==========
-- Les promotions/classes disponibles
CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) UNIQUE NOT NULL,
    scolarite DECIMAL(10, 2) DEFAULT 0,
    INDEX idx_nom (nom)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ========== TABLE 3 : ETUDIANTS_CLASSES ==========
-- Relation entre étudiants et classes (Many to Many)
CREATE TABLE IF NOT EXISTS etudiants_classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    etudiant_id INT NOT NULL,
    classe_id INT NOT NULL,
    date_inscription TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (etudiant_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (classe_id) REFERENCES classes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_etudiant_classe (etudiant_id, classe_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ========== TABLE 4 : MATIERES ==========
-- Les matières enseignées, associées à un formateur
CREATE TABLE IF NOT EXISTS matieres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    formateur_id INT NOT NULL,
    coefficient INT DEFAULT 1,
    FOREIGN KEY (formateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    INDEX idx_formateur (formateur_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ========== TABLE 5 : NOTES ==========
-- Les notes des étudiants par matière
CREATE TABLE IF NOT EXISTS notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    etudiant_id INT NOT NULL,
    matiere_id INT NOT NULL,
    matiere VARCHAR(100),
    note FLOAT,
    statut ENUM('en_attente', 'validee', 'refusee') DEFAULT 'en_attente',
    date_ajout TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (etudiant_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (matiere_id) REFERENCES matieres(id) ON DELETE CASCADE,
    UNIQUE KEY unique_etudiant_matiere (etudiant_id, matiere_id),
    INDEX idx_etudiant (etudiant_id),
    INDEX idx_matiere (matiere_id),
    INDEX idx_statut (statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ========== TABLE 6 : FORMATEURS_ETUDIANTS ==========
-- Relation entre formateurs et étudiants (qui suit qui)
CREATE TABLE IF NOT EXISTS formateurs_etudiants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formateur_id INT NOT NULL,
    etudiant_id INT NOT NULL,
    date_affectation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (formateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (etudiant_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    UNIQUE KEY unique_formateur_etudiant (formateur_id, etudiant_id),
    INDEX idx_formateur (formateur_id),
    INDEX idx_etudiant (etudiant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ========== TABLE 7 : PAIEMENTS ==========
-- Historique des paiements des étudiants
CREATE TABLE IF NOT EXISTS paiements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    etudiant_id INT NOT NULL,
    montant DECIMAL(10, 2) NOT NULL,
    date_paiement TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (etudiant_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    INDEX idx_etudiant (etudiant_id),
    INDEX idx_date (date_paiement)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ========================================
-- INSERTION DE DONNÉES D'EXEMPLE
-- ========================================

-- Ajouter un administrateur
INSERT INTO utilisateurs (nom, sexe, email, mot_de_passe, role, date_inscription) 
VALUES ('Admin Principal', 'masculin', 'admin@example.com', '$2y$10$...', 'administrateur', NOW());

-- Ajouter des formateurs
INSERT INTO utilisateurs (nom, sexe, email, mot_de_passe, role, date_inscription)
VALUES 
('Formateur 1', 'masculin', 'formateur1@example.com', '$2y$10$...', 'formateur', NOW()),
('Formateur 2', 'féminin', 'formateur2@example.com', '$2y$10$...', 'formateur', NOW());

-- Ajouter des étudiants
INSERT INTO utilisateurs (nom, sexe, email, mot_de_passe, role, date_inscription)
VALUES 
('Etudiant 1', 'masculin', 'etudiant1@example.com', '$2y$10$...', 'etudiant', NOW()),
('Etudiant 2', 'féminin', 'etudiant2@example.com', '$2y$10$...', 'etudiant', NOW());

-- Ajouter des classes
INSERT INTO classes (nom, scolarite) 
VALUES 
('L1 Informatique', 150000),
('M1 Réseaux', 200000);
