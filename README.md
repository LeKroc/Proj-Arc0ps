<div align="center">
  <img src="assets/LOGO_Arc0ps.png" alt="Logo### 🔐 Vérification des Fuites de Données
- **Intégration API "BreachDirectory" (RapidAPI)** : Surveillance proactive des emails compromis avec données réelles
- **Mise à jour automatique** : Statut de sécurité stocké en BDD (`has_leaked`)
- **Alertes visuelles détaillées** : Affichage des sources de fuites et nombre de bases compromises
- **Badge permanent** : Indicateur de sécurité affiché en temps réel dans la sidebar (LEAKED rouge / SECURE vert)
- **Gestion d'erreurs robuste** : Messages informatifs en cas d'indisponibilité de l'API
- **Logging complet** : Traçabilité de toutes les vérifications et résultats0ps" width="400">
  
  # 🔒 Λrc0ps - Project Management Platform
  
  [![PHP Version](https://img.shields.io/badge/PHP-777BB4?style=flat&logo=php)](https://www.php.net/)
  [![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=flat&logo=mysql&logoColor=white)](https://www.mysql.com/)
  [![Devscops](https://img.shields.io/badge/Security-Devops%20Compliant-success?style=flat&logo=security)](https://owasp.org/)
  
  **Plateforme centralisée de gestion de projets sécurisée avec approche DevSecOps**
  
  *Managing projects with security at the core.*
  
  [🚀 Démo](#) • [📖 Documentation](#installation--configuration) • [🐛 Signaler un bug](https://github.com/ton-pseudo/Arc0ps/issues)
</div>

---

## À propos

**ArcOps** un projet conçue pour orchestrer et administrer des projets informatiques dans un environnement sécurisé. Elle combine gestion d'équipe, suivi d'avancement, stockage de fichiers et surveillance de la sécurité des données dans une interface unifiée et intuitive.

Développée avec une approche **"Security by Design"**, ArcOps implémente les meilleures pratiques du DevOps

### 🎯 Cas d'usage
- Gestion de projets techniques (DevOps, Cybersécurité, Développement)
- Suivi d'avancement avec objectifs pondérés
- Collaboration d'équipe avec contrôle d'accès granulaire
- Surveillance proactive de la sécurité des comptes

---

## ✨ Fonctionnalités Principales

### 🖥️ Dashboard Interactif
- **Vue d'ensemble personnalisée** : Statistiques en temps réel (projets actifs, membres, progression globale)
- **Système d'épinglage personnel** : Chaque utilisateur peut marquer ses projets favoris (préférence individuelle, pas globale)
- **Interface adaptative** : Design Glassmorphism optimisé pour le travail prolongé (Dark Mode natif)

### 📊 Gestion de Projets
- **Création et édition** : Titre, description enrichie, bannière personnalisée
- **Progression intelligente** : Calcul automatique basé sur des objectifs pondérés (pourcentages)
- **Statuts dynamiques** : En Cours, Terminé, En Attente, Bloqué
- **Historique d'activité** : Traçabilité des modifications

### 📁 Système de Fichiers Sécurisé
- **Upload protégé** : Validation MIME type réel (pas seulement l'extension)
- **Renommage automatique** : UUID + timestamp pour éviter les collisions
- **Quotas intelligents** : Limite configurable (10 fichiers / 3Mo par défaut)
- **Support multi-format** : Images, PDF, Archives, Documents Office

### 📝 Notes de Suivi
- **Stockage JSON plat** : Léger et portable
- **Traçabilité** : Auteur, date et heure de chaque note
- **Affichage chronologique** : Notes les plus récentes en premier

### 🚰 Vérification des Fuites de Données
- **Intégration API "Have I Been Pwned"** : Surveillance proactive des emails compromis
- **Mise à jour automatique** : Statut de sécurité stocké en BDD (`has_leaked`)
- **Alertes visuelles** : Badge rouge/vert selon le résultat

### 👮 Panel Admin
- **Accès protégé** : Authentification hardcodée
- **Vue d'ensemble** : Tous les utilisateurs inscrits avec leur statut de sécurité
- **Gestion des projets par utilisateur** : 
  - Affichage des projets où l'utilisateur est **Owner** (icône couronne 👑)
  - Affichage des projets où l'utilisateur est **Membre** (icône user 👤 avec son rôle)
  - Distinction visuelle claire pour faciliter la supervision
- **Statistiques** : Nombre total d'utilisateurs, comptes sécurisés vs compromis
- **Logging** : Traçabilité des connexions admin

---

## 🛡️ Statut de Sécurité - Architecture Défensive

**ArcOps** a été développé selon les principes du **"Secure Coding"** et respecte les recommandations de l'**OWASP Top 10 (2021)**. Voici comment chaque vulnérabilité critique est neutralisée par l'architecture :

<div align="center">

| 🎯 Menace | 🛡️ Protection Intégrée | 📌 Détails Techniques |
|-----------|-------------------------|------------------------|
| **SQL Injection** | ✅ **Verrouillé** | Utilisation exclusive de `PDO Prepared Statements` avec binding de paramètres. Typage strict des IDs via `secure_int()`. Aucune concaténation SQL directe. |
| **XSS (Cross-Site Scripting)** | ✅ **Sécurisé par Design** | Sanitization systématique de toutes les sorties via `htmlspecialchars(ENT_QUOTES, 'UTF-8')`. Fonction `clean_output()` appliquée à chaque affichage de données utilisateur. |
| **CSRF (Cross-Site Request Forgery)** | ✅ **Protégé** | Tous les formulaires POST intègrent un jeton CSRF unique par session (256-bit). Vérification serveur via `hash_equals()` (résistant aux attaques temporelles). |
| **IDOR (Insecure Direct Object Reference)** | ✅ **RBAC Strict** | Vérification systématique de l'appartenance utilisateur-projet avant affichage (`require_project_access()`). Fonction dédiée pour chaque page sensible (`avancement.php`, `project_settings.php`). |
| **Session Hijacking** | ✅ **Session Durcie** | Configuration serveur : `HttpOnly`, `SameSite=Strict`, `use_strict_mode=1`. Détection de vol via comparaison User-Agent + IP. Régénération d'ID après login (`session_regenerate_id(true)`). |
| **Information Disclosure** | ✅ **Gestion d'Erreurs Sécurisée** | Erreurs PDO loggées côté serveur uniquement (jamais affichées). Messages génériques pour l'utilisateur. Logging sécurisé via `log_security_event()`. |
| **Fuites de Données Externes** | ✅ **Surveillance Proactive** | Intégration API **Have I Been Pwned** pour détecter les emails compromis. Mise à jour automatique du statut en BDD. Alertes visuelles dans le dashboard. |

</div>

### ⚙️ Mécanismes de Sécurité Additionnels

#### 📝 Logging de Sécurité
Tous les événements critiques sont tracés dans `logs/security.log` :
- Tentatives de connexion échouées (avec IP)
- Uploads de fichiers invalides
- Accès refusés (IDOR)
- Connexions admin

#### 🚫 Protection .htaccess
Fichier racine sécurisant l'application :
- Blocage des méthodes HTTP non autorisées (seuls GET/POST)
- Headers de sécurité HTTP (X-Frame-Options, CSP, X-XSS-Protection)
- Désactivation de l'indexation des dossiers
- Blocage des User-Agents de scanners (Nikto, SQLMap, etc.)

#### 🔐 Fonctions de Sécurité Centralisées (`functions.php`)
- `csrf_protect()` : Validation automatique des tokens
- `validate_file_upload()` : Vérification multi-niveaux des fichiers
- `secure_int()` : Typage fort des identifiants
- `clean_input()` / `clean_output()` : Sanitization bidirectionnelle
- `user_has_project_role()` : Contrôle d'accès RBAC

---

## 🛠️ Stack Technique

<div align="center">

| Couche | Technologie | Version |
|--------|-------------|---------|
| **Backend** | PHP (Natif) | 8.2+ |
| **Base de Données** | MySQL / MariaDB | 8.0+ / 10.3+ |
| **Frontend** | HTML5 / CSS3 (Custom) | - |
| **JavaScript** | Vanilla JS | ES6+ |
| **Sécurité** | OWASP Guidelines | 2021 |
| **API Externe** | Have I Been Pwned | v3 |

</div>

### 📦 Extensions PHP Requises
- `pdo_mysql` : Connexion base de données
- `fileinfo` : Détection MIME types
- `session` : Gestion des sessions
- `curl` : Appels API HIBP

---

## ⚙️ Installation & Configuration

### 📥 Prérequis
- Serveur web (Apache 2.4+ recommandé)
- PHP 8.2 ou supérieur
- MySQL 8.0 ou MariaDB 10.3+
- Modules Apache : `mod_rewrite`, `mod_headers`

**Comptes par défaut** :
- **Utilisateur** : Créez-en un via le formulaire d'inscription (`register.php`)
- **Admin Panel** : `admin` / `go_admin_1234!!` (accès via `admin_panel.php`)

---

## 📁 Arborescence du Projet

```
Arc0ps/
├── 📄 index.html                  # Page d'accueil
├── 📄 login.php                   # Authentification
├── 📄 register.php                # Inscription
├── 📄 logout.php                  # Déconnexion
├── 📄 dashboard.php               # Dashboard principal ⭐
├── 📄 avancement.php              # Détails d'un projet
├── 📄 project_settings.php        # Configuration projet (RBAC)
├── 📄 admin_panel.php             # Panel admin 👮
├── 📄 functions.php               # Fonctions de sécurité centralisées 🔒
│
├── 📂 config/
│   └── 📄 db.php                  # Configuration base de données
│
├── 📂 assets/
│   ├── 📂 imageProject/           # Bannières de projets
│   ├── 📂 PhotoProfile/           # Avatars utilisateurs
│   ├── 📂 project_files/          # Fichiers uploadés par projet
│   ├── 📂 notes/                  # Notes JSON par projet
│   ├── 📄 .htaccess               # Sécurité uploads (PHP désactivé)
│   └── 📄 logo_Arc0ps.ico         # Favicon
│
├── 📂 logs/
│   └── 📄 security.log            # Logs de sécurité
│
├── 📄 style.css                   # Styles login/register
├── 📄 style-dashboard.css         # Styles dashboard/projet
├── 📄 .gitignore                  # Fichiers exclus du versioning
│
└── 📄 README.md                   # Ce fichier
```

---

## 🧪 Mode Simulation : Vérification des Fuites de Données

L'application inclut une **fonctionnalité de simulation gratuite** pour tester la détection de fuites de données sans nécessiter de clé API payante "Have I Been Pwned".

### 🎯 Comment ça fonctionne ?

#### Règle de Simulation
Le système vérifie si l'adresse email de l'utilisateur contient le mot-clé **`pwned`** (insensible à la casse) :

| Email Testé | Résultat | Badge Affiché |
|-------------|----------|---------------|
| `test-pwned@gmail.com` | 🔴 **Fuite détectée** | LEAKED (Rouge) |
| `pwned.user@arcops.com` | 🔴 **Fuite détectée** | LEAKED (Rouge) |
| `john.doe@company.fr` | 🟢 **Compte sécurisé** | SECURE (Vert) |
| `admin@arcops.dev` | 🟢 **Compte sécurisé** | SECURE (Vert) |


## 🌟 Système d'Épinglage Personnel

ArcOps implémente un système d'épinglage **personnalisé par utilisateur** pour une meilleure expérience.

### Comment ça fonctionne ?

#### Architecture
- **Table dédiée** : `user_project_pins` (relation many-to-many)
- **Clé primaire composite** : `(user_id, project_id)` - Un utilisateur ne peut épingler qu'une fois chaque projet
- **Suppression en cascade** : Si un projet ou un utilisateur est supprimé, les épingles associées sont automatiquement supprimées

#### Utilisation

1. **Épingler un projet** :
   - Cliquez sur l'icône étoile (☆) dans la liste des projets
   - L'étoile devient pleine (★) et passe en jaune
   
2. **Désépingler un projet** :
   - Re-cliquez sur l'étoile pleine (★)
   - Le projet disparaît de la section "Vos favoris"

3. **Affichage** :
   - Section "Vos favoris" en haut du dashboard
   - Ordre chronologique (derniers épinglés en premier)

---

## 🧪 Tests & Validation

### ✅ Checklist de Sécurité

Avant déploiement en production, vérifiez :

- [ ] `.htaccess` présent dans `/assets/` (désactivation PHP)
- [ ] Permissions 600 sur `config/db.php`
- [ ] Dossier `logs/` créé et accessible en écriture
- [ ] SECRET_KEY générée (64 caractères aléatoires minimum)
- [ ] Tous les formulaires affichent le champ `csrf_token`
- [ ] Test d'upload d'un fichier `.php` → Doit être refusé
- [ ] Test d'accès à un projet non-membre → Doit afficher "Accès Refusé"
- [ ] Test d'épinglage : User A épingle projet X → User B ne le voit pas épinglé

## 👥 L'Équipe

Projet réalisé dans le cadre du cursus **DevSecOps - Guardia Cybersecurity School**.

<div align="center">

| Membre | Rôle | GitHub |
|--------|------|--------|
| **LeKroc** | Lead Developer & Owner | [@LeKroc](https://github.com/LeKroc) |
| **Luca** | Backend Developer | [@Luca](https://github.com/LeKroc/Proj-Arc0ps) |
| **Rayan** | Frontend Developer | [@Rayan](https://github.com/LeKroc/Proj-Arc0ps) |
| **Bost** | DevSecOps Engineer | [@Bost](https://github.com/theBost-Guardia) |

</div>

---

<div align="center">
  
  **⚡ Built with security in mind ⚡**
  
  <sub>© 2025 ArcOps Team - Guardia Cybersecurity School</sub>
  
  [![Made with PHP](https://img.shields.io/badge/Made%20with-PHP-777BB4?style=for-the-badge&logo=php)](https://www.php.net/)
  [![Secured by OWASP](https://img.shields.io/badge/Secured%20by-OWASP-brightgreen?style=for-the-badge&logo=security)](https://owasp.org/)
  
</div>
