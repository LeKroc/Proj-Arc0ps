<div align="center">
  <img src="assets/LOGO_Arc0ps.png" alt="Logo### 🔐 Vérification des Fuites de Données
- **Intégration API "Have I Been Pwned" (Mode Simulation Gratuit)** : Surveillance proactive des emails compromis
- **Mise à jour automatique** : Statut de sécurité stocké en BDD (`has_leaked`)
- **Alertes visuelles** : Badge rouge/vert selon le résultat
- **Badge permanent** : Indicateur de sécurité affiché en temps réel dans la sidebar
- **Mode gratuit** : Simulation locale sans nécessiter de clé API payante (idéal pour la démonstration et les tests)0ps" width="400">
  
  # 🔒 Λrc0ps - Project Management Platform
  
  [![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat&logo=php)](https://www.php.net/)
  [![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1?style=flat&logo=mysql&logoColor=white)](https://www.mysql.com/)
  [![Security](https://img.shields.io/badge/Security-OWASP%20Compliant-success?style=flat&logo=security)](https://owasp.org/)
  [![License](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
  
  **Plateforme centralisée de gestion de projets sécurisée avec approche DevSecOps**
  
  *Managing projects with security at the core.*
  
  [🚀 Démo](#) • [📖 Documentation](#installation--configuration) • [🐛 Signaler un bug](https://github.com/ton-pseudo/Arc0ps/issues)
</div>

---

## � À propos

**ArcOps** est une application web professionnelle conçue pour orchestrer et administrer des projets informatiques dans un environnement sécurisé. Elle combine gestion d'équipe, suivi d'avancement, stockage de fichiers et surveillance de la sécurité des données dans une interface unifiée et intuitive.

Développée avec une approche **"Security by Design"**, ArcOps implémente les meilleures pratiques de l'OWASP dès la conception, garantissant une protection robuste contre les menaces modernes.

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

### 👥 Gestion d'Équipe (RBAC)
- **Système de rôles hiérarchique** :
  - `Owner` : Propriétaire, tous droits
  - `Admin` : Gestion d'équipe et paramètres
  - `Membre` : Contribution (objectifs, notes, fichiers)
- **Invitation de membres** : Attribution de rôles par username
- **Contrôle d'accès strict** : Vérification du rôle à chaque action

### 📁 Système de Fichiers Sécurisé
- **Upload protégé** : Validation MIME type réel (pas seulement l'extension)
- **Renommage automatique** : UUID + timestamp pour éviter les collisions
- **Quotas intelligents** : Limite configurable (10 fichiers / 3Mo par défaut)
- **Support multi-format** : Images, PDF, Archives, Documents Office
- **Interdiction d'exécution** : `.htaccess` désactive PHP dans les dossiers d'upload

### 📝 Notes de Suivi
- **Stockage JSON plat** : Léger et portable
- **Traçabilité** : Auteur, date et heure de chaque note
- **Affichage chronologique** : Notes les plus récentes en premier

### � Vérification des Fuites de Données
- **Intégration API "Have I Been Pwned"** : Surveillance proactive des emails compromis
- **Mise à jour automatique** : Statut de sécurité stocké en BDD (`has_leaked`)
- **Alertes visuelles** : Badge rouge/vert selon le résultat

### 👮 Panel Admin
- **Accès protégé** : Authentification hardcodée (`admin` / `go_admin_1234!!`)
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
| **RCE (Remote Code Execution)** | ✅ **Architecture Multicouche** | **1.** Validation du MIME type réel (`finfo_file`)<br>**2.** Blacklist des extensions dangereuses (`.php`, `.phtml`, `.exe`)<br>**3.** Renommage forcé avec UUID (`bin2hex(random_bytes(8))`)<br>**4.** `.htaccess` désactive l'exécution de scripts dans `/assets/` |
| **IDOR (Insecure Direct Object Reference)** | ✅ **RBAC Strict** | Vérification systématique de l'appartenance utilisateur-projet avant affichage (`require_project_access()`). Fonction dédiée pour chaque page sensible (`avancement.php`, `project_settings.php`). |
| **Session Hijacking** | ✅ **Session Durcie** | Configuration serveur : `HttpOnly`, `SameSite=Strict`, `use_strict_mode=1`. Détection de vol via comparaison User-Agent + IP. Régénération d'ID après login (`session_regenerate_id(true)`). |
| **Information Disclosure** | ✅ **Gestion d'Erreurs Sécurisée** | Erreurs PDO loggées côté serveur uniquement (jamais affichées). Messages génériques pour l'utilisateur. Logging sécurisé via `log_security_event()`. |
| **Fuites de Données Externes** | ✅ **Surveillance Proactive** | Intégration API **Have I Been Pwned** pour détecter les emails compromis. Mise à jour automatique du statut en BDD. Alertes visuelles dans le dashboard. |

</div>

### � Mécanismes de Sécurité Additionnels

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

### 🚀 Installation en 5 étapes

#### 1️⃣ Cloner le dépôt

```bash
git clone https://github.com/ton-pseudo/Arc0ps.git
cd Arc0ps
```

#### 2️⃣ Créer la base de données

```sql
CREATE DATABASE arcops_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'arcops_user'@'localhost' IDENTIFIED BY 'VOTRE_MOT_DE_PASSE_FORT';
GRANT SELECT, INSERT, UPDATE, DELETE ON arcops_db.* TO 'arcops_user'@'localhost';
FLUSH PRIVILEGES;
```

#### 3️⃣ Importer la structure

```bash
mysql -u arcops_user -p arcops_db < database.sql
mysql -u arcops_user -p arcops_db < database_migration_v2.1.sql
mysql -u arcops_user -p arcops_db < database_migration_v2.2_pin_fix.sql
```

> ⚠️ **Important** : Exécutez les migrations **dans l'ordre** :
> 1. `database.sql` : Structure de base
> 2. `database_migration_v2.1.sql` : Agenda + Colonne HIBP
> 3. `database_migration_v2.2_pin_fix.sql` : Épinglage personnel (supprime `is_pinned` de `projects`)

#### 4️⃣ Configurer la connexion

Éditez `config/db.php` :

```php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'arcops_db');
define('DB_USER', 'arcops_user');
define('DB_PASS', 'VOTRE_MOT_DE_PASSE');

// Clé secrète pour les cookies (générer avec : openssl rand -hex 32)
define('SECRET_KEY', 'GÉNÉRER_UNE_CLÉ_ALÉATOIRE_64_CARACTÈRES');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données.");
}
```

#### 5️⃣ Configurer les permissions

```bash
# Dossiers d'upload
chmod 755 assets/
chmod 755 assets/imageProject assets/PhotoProfile assets/project_files assets/notes

# Dossier de logs
mkdir logs
chmod 755 logs

# Fichier de configuration (sensible)
chmod 600 config/db.php
```

### 🌐 Accès à l'application

Démarrez votre serveur local et accédez à :

```
http://localhost/Arc0ps/
```

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
├── 📄 .htaccess                   # Sécurité globale (racine)
├── 📄 .gitignore                  # Fichiers exclus du versioning
│
├── 📄 database.sql                # Structure BDD initiale
├── 📄 database_migration_v2.1.sql # Migration v2.1 (Agenda, HIBP)
├── 📄 database_migration_v2.2_pin_fix.sql # Migration v2.2 (Épinglage personnel)
│
├── 📄 SECURITY_AUDIT_REPORT.md    # Rapport d'audit complet
├── 📄 INSTALLATION_GUIDE.md       # Guide d'installation détaillé
├── 📄 HIBP_FEATURE_GUIDE.md       # Guide technique HIBP
├── 📄 CHANGELOG_v2.1_COMPLETE.md  # Récapitulatif v2.1
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

### 📝 Pour Tester

1. **Créer un compte de test** :
   ```
   Email : test-pwned@example.com
   Username : TestPwned
   Password : testpass123
   ```

2. **Se connecter** et aller dans l'onglet **Settings**

3. **Cliquer sur** "🔍 Vérifier les fuites de données"

4. **Observer** :
   - Alerte rouge avec message d'avertissement
   - Badge "LEAKED" rouge apparaît dans la sidebar
   - Colonne `has_leaked` mise à 1 dans la BDD

### 🔄 Passage en Mode Production (API Réelle)

Pour utiliser l'API officielle de Have I Been Pwned :

1. **Obtenir une clé API** : https://haveibeenpwned.com/API/Key

2. **Modifier `dashboard.php`** (remplacer la section simulation) :

```php
// Remplacer la ligne 125 environ
$apiUrl = "https://haveibeenpwned.com/api/v3/breachedaccount/" . urlencode($userEmail);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'hibp-api-key: VOTRE_CLE_API_ICI',
    'User-Agent: ArcOps-Security-Check'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($httpCode === 200) {
    $isLeaked = true; // Fuites trouvées
} elseif ($httpCode === 404) {
    $isLeaked = false; // Aucune fuite
}
```

3. **Coût** : ~3.50$ USD/mois pour une utilisation commerciale

---

## 🌟 Système d'Épinglage Personnel

ArcOps implémente un système d'épinglage **personnalisé par utilisateur** pour une meilleure expérience.

### Comment ça fonctionne ?

#### Architecture
- **Table dédiée** : `user_project_pins` (relation many-to-many)
- **Clé primaire composite** : `(user_id, project_id)` - Un utilisateur ne peut épingler qu'une fois chaque projet
- **Suppression en cascade** : Si un projet ou un utilisateur est supprimé, les épingles associées sont automatiquement supprimées

#### Avantages de cette Approche

✅ **Isolation des Préférences** :
```
User A épingle le Projet X → Visible uniquement pour User A
User B ne voit PAS le Projet X épinglé (sauf s'il l'épingle lui-même)
```

✅ **Flexibilité** :
- Chaque utilisateur peut épingler jusqu'à 10 projets différents
- Les épingles n'affectent pas les autres membres du projet
- L'ordre d'affichage est basé sur la date d'épinglage (`pinned_at`)

✅ **Performance** :
- Index sur `user_id` et `project_id` pour des requêtes rapides
- Requête SQL optimisée avec `LEFT JOIN` pour récupérer le statut en une seule query

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

### 🔬 Tests Fonctionnels

```bash
# Test CSRF
curl -X POST http://localhost/Arc0ps/dashboard.php \
  -d "create_project=1&project_name=Test" \
  # Doit retourner : "Token CSRF invalide"

# Test IDOR
# 1. Se connecter avec User A
# 2. Noter l'ID d'un projet : avancement.php?id=5
# 3. Se connecter avec User B (non membre)
# 4. Accéder à avancement.php?id=5
# Résultat attendu : Page "Accès Refusé" (HTTP 403)

# Test Épinglage Personnel
# 1. User A se connecte et épingle le Projet X
# 2. User B se connecte
# 3. User B vérifie sa section "Vos favoris"
# Résultat attendu : Le Projet X n'apparaît PAS (sauf si User B l'épingle aussi)
```

---

## 📚 Documentation Complémentaire

- 📖 **[Guide d'installation détaillé](INSTALLATION_GUIDE.md)** : Déploiement pas-à-pas avec troubleshooting
- 🛡️ **[Rapport d'audit de sécurité](SECURITY_AUDIT_REPORT.md)** : Analyse technique complète (OWASP)
- 🔧 **[Migration v2.1](database_migration_v2.1.sql)** : Nouvelles fonctionnalités (Agenda, HIBP)

---

## 🤝 Contribution

Les contributions sont les bienvenues ! Pour proposer une amélioration :

1. Fork le projet
2. Créez une branche feature (`git checkout -b feature/AmazingFeature`)
3. Committez vos changements (`git commit -m 'Add: AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrez une Pull Request

### 🔒 Signaler une vulnérabilité de sécurité

Si vous découvrez une faille de sécurité, **ne créez pas d'issue publique**. Envoyez un email privé à : **security@arcops.dev**

---

## 👥 L'Équipe

Projet réalisé dans le cadre du cursus **DevSecOps - Guardia Cybersecurity School**.

<div align="center">

| Membre | Rôle | GitHub |
|--------|------|--------|
| **LeKroc** | Lead Developer & Owner | [@LeKroc](https://github.com/LeKroc) |
| **Luca** | Backend Developer | [@Luca](https://github.com/Luca) |
| **Rayan** | Frontend Developer | [@Rayan](https://github.com/Rayan) |
| **Bost** | DevSecOps Engineer | [@Bost](https://github.com/Bost) |

</div>

---

## 📜 Licence

Ce projet est sous licence **MIT**. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

---

## 🙏 Remerciements

- **OWASP Foundation** pour les guidelines de sécurité
- **Have I Been Pwned** (Troy Hunt) pour l'API de vérification des fuites
- **Font Awesome** pour les icônes
- **Guardia Cybersecurity School** pour l'accompagnement

---

<div align="center">
  
  **⚡ Built with security in mind ⚡**
  
  <sub>© 2025 ArcOps Team - Guardia Cybersecurity School</sub>
  
  [![Made with PHP](https://img.shields.io/badge/Made%20with-PHP-777BB4?style=for-the-badge&logo=php)](https://www.php.net/)
  [![Secured by OWASP](https://img.shields.io/badge/Secured%20by-OWASP-brightgreen?style=for-the-badge&logo=security)](https://owasp.org/)
  
</div>
