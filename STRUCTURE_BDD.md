# 📋 STRUCTURE DE LA BASE DE DONNÉES - Gestion Notes

**Base de données :** `gestion_notes`  
**Utilisateur MySQL :** `root`  
**Mot de passe :** (vide)

---

## 📊 TABLES ET LEURS COLONNES

### 1️⃣ TABLE : `utilisateurs`
**Description :** Gestionnaire des utilisateurs (admin, formateurs, étudiants)

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | INT | PRIMARY KEY AUTO_INCREMENT | Identifiant unique |
| `nom` | VARCHAR(100) | NOT NULL | Nom complet |
| `sexe` | ENUM | Default: 'masculin' | Genre (masculin/féminin) |
| `email` | VARCHAR(120) | UNIQUE, NOT NULL | Email unique |
| `mot_de_passe` | VARCHAR(255) | NOT NULL | Hash du mot de passe (bcrypt) |
| `role` | ENUM | NOT NULL | administrateur / formateur / etudiant |
| `date_inscription` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Date inscription |
| `statut_scolarite` | ENUM | Default: 'en_cours' | en_cours / termine |

---

### 2️⃣ TABLE : `classes`
**Description :** Les classes/promotions disponibles

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | INT | PRIMARY KEY AUTO_INCREMENT | Identifiant unique |
| `nom` | VARCHAR(100) | UNIQUE, NOT NULL | Nom de la classe |
| `scolarite` | DECIMAL(10,2) | Default: 0 | Montant de scolarité (FCFA) |

---

### 3️⃣ TABLE : `etudiants_classes`
**Description :** Relation Many-to-Many entre étudiants et classes

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | INT | PRIMARY KEY AUTO_INCREMENT | Identifiant unique |
| `etudiant_id` | INT | FOREIGN KEY → utilisateurs(id) | Étudiant |
| `classe_id` | INT | FOREIGN KEY → classes(id) | Classe |
| `date_inscription` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Date d'inscription |

**Note :** Un étudiant peut être dans plusieurs classes (unique par paire)

---

### 4️⃣ TABLE : `matieres`
**Description :** Les matières enseignées

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | INT | PRIMARY KEY AUTO_INCREMENT | Identifiant unique |
| `nom` | VARCHAR(100) | NOT NULL | Nom de la matière |
| `formateur_id` | INT | FOREIGN KEY → utilisateurs(id) | Formateur responsable |
| `coefficient` | INT | Default: 1 | Coefficient pour le calcul de moyenne |

---

### 5️⃣ TABLE : `notes`
**Description :** Les notes des étudiants

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | INT | PRIMARY KEY AUTO_INCREMENT | Identifiant unique |
| `etudiant_id` | INT | FOREIGN KEY → utilisateurs(id) | Étudiant |
| `matiere_id` | INT | FOREIGN KEY → matieres(id) | Matière |
| `matiere` | VARCHAR(100) | - | Nom matière (redondant) |
| `note` | FLOAT | - | La note (0-20) |
| `statut` | ENUM | Default: 'en_attente' | en_attente / validee / refusee |
| `date_ajout` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Date validation |

**Note :** Chaque étudiant a une note unique par matière

---

### 6️⃣ TABLE : `formateurs_etudiants`
**Description :** Qui suit qui (relation formateur → étudiants)

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | INT | PRIMARY KEY AUTO_INCREMENT | Identifiant unique |
| `formateur_id` | INT | FOREIGN KEY → utilisateurs(id) | Formateur |
| `etudiant_id` | INT | FOREIGN KEY → utilisateurs(id) | Étudiant |
| `date_affectation` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Date d'affectation |

---

### 7️⃣ TABLE : `paiements`
**Description :** Historique des paiements de scolarité

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | INT | PRIMARY KEY AUTO_INCREMENT | Identifiant unique |
| `etudiant_id` | INT | FOREIGN KEY → utilisateurs(id) | Étudiant qui a payé |
| `montant` | DECIMAL(10,2) | NOT NULL | Montant payé (FCFA) |
| `date_paiement` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Date du paiement |

---

## 🔗 RELATIONS ENTRE TABLES

```
utilisateurs (1) ──────── (n) classes      [via etudiants_classes]
utilisateurs (1) ──────── (n) matieres     [formateur_id]
utilisateurs (1) ──────── (n) notes        [etudiant_id]
utilisateurs (1) ──────── (n) paiements    [etudiant_id]
utilisateurs (1) ──────── (n) formateurs_etudiants [both sides]
matieres    (1) ──────── (n) notes         [matiere_id]
```

---

## ⚙️ PROBLÈMES IDENTIFIÉS DANS LE CODE

### 🔴 Incohérence critique :
- **connexion.php** utilise `utilisateur_id`
- **register.php** utilise `id`
- **admin_classes.php** utilise `id`

**Action recommandée :** Standardiser sur `id` partout

### ⚠️ Autres remarques :
1. La colonne `matiere` dans `notes` est redondante (déjà dans `matieres.nom`)
2. Le fichier `admin_gestion_scolarite.php` crée la table `paiements` dynamiquement
3. Pas de gestion des permissions granulaires (tout admin peut tout faire)

---

## 📝 SCRIPT SQL COMPLET

Voir le fichier **`structure_bdd.sql`** pour exécuter toutes les tables d'un coup.
