# 🔐 GUIDE DE SÉCURISATION - CLÉ API & CONFIGURATION

## 🎯 Objectif

Ce guide explique comment la clé API BreachDirectory et les informations sensibles sont stockées de manière sécurisée dans l'application ArcOps.

---

## 📂 Architecture de Sécurité

### Fichiers Sensibles

```
Proj-Arc0ps_to_devops/
├── config/
│   ├── db.php               ← ⚠️ FICHIER SENSIBLE (Clés API, DB credentials)
│   ├── db.php.example       ← ✅ Template public (sans secrets)
│   └── .htaccess            ← 🔒 Protection Apache
├── .gitignore               ← 🚫 Liste des fichiers à ne pas commiter
└── logs/
    └── security.log         ← 📝 Logs (exclus de Git)
```

---

## 🔑 Stockage de la Clé API

### Fichier : `config/db.php`

La clé API est stockée sous forme de **constante PHP** :

```php
// ═══════════════════════════════════════════════════════════════════
//  CLÉ API BREACHDIRECTORY (RapidAPI) - SÉCURISÉE
// ═══════════════════════════════════════════════════════════════════
define('RAPIDAPI_KEY', '9da75d2638msha156ca537944969p1d1543jsn5cab76c76c80');
define('RAPIDAPI_HOST', 'breachdirectory.p.rapidapi.com');
```

### Utilisation dans `dashboard.php`

```php
// Headers obligatoires RapidAPI
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-RapidAPI-Key: ' . RAPIDAPI_KEY,     // ← Utilisation de la constante
    'X-RapidAPI-Host: ' . RAPIDAPI_HOST
]);
```

**Avantages** :
- ✅ **Centralisation** : Une seule modification pour tout le site
- ✅ **Sécurité** : Pas de clé en dur dans les fichiers PHP publics
- ✅ **Évolutivité** : Facile de passer aux variables d'environnement plus tard

---

## 🛡️ Protections Mises en Place

### 1️⃣ `.gitignore` (Protection Git)

**Fichier** : `.gitignore` (racine du projet)

```gitignore
# ─────────────────────────────────────────────────────────────────
#  CONFIGURATION & SECRETS
# ─────────────────────────────────────────────────────────────────
config/db.php         # ← Fichier sensible JAMAIS commité
db.php
.env
.env.local
secrets.php
```

**Test de validation** :
```bash
# Vérifier que db.php n'est PAS dans Git
git status

# Si db.php apparaît, l'ajouter au .gitignore et faire :
git rm --cached config/db.php
git commit -m "Retrait fichier sensible du dépôt"
```

---

### 2️⃣ `.htaccess` (Protection Apache)

**Fichier** : `config/.htaccess`

```apache
# Refuser l'accès à tous les fichiers du dossier /config/
Order Deny,Allow
Deny from all

# Désactiver l'exécution de scripts PHP
<FilesMatch "\.(php|php3|php4|php5|phtml)$">
    Order Deny,Allow
    Deny from all
</FilesMatch>
```

**Test de validation** :
```bash
# Essayer d'accéder directement au fichier via HTTP
curl http://localhost/Arc0ps/config/db.php
# Résultat attendu : HTTP 403 Forbidden
```

---

### 3️⃣ Template Public (`db.php.example`)

**Fichier** : `config/db.php.example`

```php
// Template sans secrets
define('RAPIDAPI_KEY', 'YOUR_RAPIDAPI_KEY_HERE');
$host = 'YOUR_DB_HOST';
$user = 'YOUR_DB_USER';
$pass = 'YOUR_DB_PASSWORD';
```

**Utilisation** :
```bash
# Nouveau développeur clone le projet
git clone https://github.com/user/arcops.git
cd arcops/config

# Créer son fichier de configuration local
cp db.php.example db.php

# Éditer avec ses vraies valeurs
nano db.php
```

---

### 4️⃣ Permissions Fichier (Linux/Mac)

**Commande** :
```bash
# Rendre db.php lisible UNIQUEMENT par le propriétaire
chmod 600 config/db.php

# Vérification
ls -la config/db.php
# Résultat attendu : -rw------- (600)
```

**Explication** :
- `6` (owner) : Lecture + Écriture
- `0` (group) : Aucun accès
- `0` (others) : Aucun accès

---

## 🚀 Migration vers Variables d'Environnement (Production)

### Méthode Recommandée pour la Production

#### 1. Créer un fichier `.env`

**Fichier** : `.env` (racine du projet)

```env
# Base de données
DB_HOST=us.mysql.db.bot-hosting.net:3306
DB_NAME=s410232_myDB
DB_USER=u410232_qA8QsiPr4f
DB_PASS=XXVg7vISK@s9.6lpBDnkHmCC

# API RapidAPI
RAPIDAPI_KEY=9da75d2638msha156ca537944969p1d1543jsn5cab76c76c80
RAPIDAPI_HOST=breachdirectory.p.rapidapi.com

# Sécurité
SECRET_KEY=jaidhdjskd!j_uzjffjgkfidi_aisi462jdjfj_!
```

#### 2. Charger les variables avec `vlucas/phpdotenv`

**Installation** :
```bash
composer require vlucas/phpdotenv
```

**Fichier** : `config/db.php`

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Charger le .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Utilisation des variables d'environnement
define('SECRET_KEY', $_ENV['SECRET_KEY']);
define('RAPIDAPI_KEY', $_ENV['RAPIDAPI_KEY']);
define('RAPIDAPI_HOST', $_ENV['RAPIDAPI_HOST']);

$host = $_ENV['DB_HOST'];
$db   = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];

// ...reste du code PDO
?>
```

#### 3. Mettre à jour `.gitignore`

```gitignore
.env
.env.local
.env.production
vendor/
```

---

## 🧪 Tests de Sécurité

### Test 1 : Accès HTTP Direct (doit échouer)

```bash
# Test 1 : Accès à db.php via HTTP
curl -I http://localhost/Arc0ps/config/db.php
# Résultat attendu : HTTP/1.1 403 Forbidden

# Test 2 : Accès au dossier config/
curl -I http://localhost/Arc0ps/config/
# Résultat attendu : HTTP/1.1 403 Forbidden

# Test 3 : Listing du dossier (désactivé)
curl http://localhost/Arc0ps/config/
# Résultat attendu : Forbidden ou page d'erreur
```

### Test 2 : Vérification Git (doit être exclu)

```bash
# Vérifier que db.php n'est PAS tracké par Git
git ls-files | grep "config/db.php"
# Résultat attendu : (aucune ligne retournée)

# Vérifier le contenu du .gitignore
cat .gitignore | grep "db.php"
# Résultat attendu : config/db.php
```

### Test 3 : Appel API Fonctionnel

```bash
# Se connecter à l'application
# Aller dans Settings → Vérifier les fuites
# Résultat attendu : 
# - Appel API réussi (HTTP 200)
# - Log enregistré dans logs/security.log
```

---

## 🔒 Checklist de Sécurité

Avant déploiement en production, vérifier :

### Configuration
- [ ] `config/db.php` n'est PAS dans le dépôt Git
- [ ] `.gitignore` contient `config/db.php`
- [ ] `config/.htaccess` existe et fonctionne (HTTP 403)
- [ ] `config/db.php.example` est à jour (template sans secrets)
- [ ] Permissions `600` sur `config/db.php` (Linux/Mac)

### Clés & Credentials
- [ ] `SECRET_KEY` est unique (64+ caractères aléatoires)
- [ ] `RAPIDAPI_KEY` est valide (testée avec un appel API)
- [ ] Mot de passe BDD complexe (12+ caractères, symboles)
- [ ] Clés stockées dans `db.php` ou variables d'environnement

### Tests
- [ ] Accès HTTP direct à `config/db.php` → 403 Forbidden
- [ ] Appel API BreachDirectory fonctionne
- [ ] Logs écrits dans `logs/security.log`
- [ ] Git status propre (pas de fichiers sensibles)

### Documentation
- [ ] README.md mentionne `db.php.example`
- [ ] Instructions d'installation pour nouveaux développeurs
- [ ] Politique de rotation des clés documentée

---

## 📋 Bonnes Pratiques

### ✅ À FAIRE

1. **Rotation des Clés** :
   - Changer `SECRET_KEY` tous les 6 mois
   - Changer mot de passe BDD tous les 3 mois
   - Regénérer clé RapidAPI si suspicion de fuite

2. **Monitoring** :
   - Surveiller les logs (`logs/security.log`)
   - Configurer des alertes sur les erreurs API (HTTP 429, 401)
   - Vérifier l'usage RapidAPI (quota de 100 requêtes/mois)

3. **Sauvegarde** :
   - Sauvegarder `config/db.php` de manière chiffrée
   - Utiliser un gestionnaire de mots de passe (1Password, Bitwarden)

### ❌ À ÉVITER

1. **NE JAMAIS** :
   - Commiter `db.php` sur Git/GitHub
   - Partager des clés API par email/Slack
   - Logger les clés API dans `security.log`
   - Utiliser la même clé sur dev/staging/prod

2. **Éviter** :
   - Clés API en dur dans les fichiers PHP
   - Permissions trop permissives (777, 666)
   - Réutiliser les mêmes credentials partout

---

## 🆘 Que Faire en Cas de Fuite ?

### Si une clé API est compromise :

**1. Révoquer immédiatement** :
```
1. Se connecter à RapidAPI Dashboard
2. Aller dans "My Apps" → "Security"
3. Cliquer "Regenerate Key"
4. Copier la nouvelle clé
```

**2. Mettre à jour le code** :
```bash
# Éditer config/db.php
nano config/db.php

# Remplacer RAPIDAPI_KEY par la nouvelle valeur
define('RAPIDAPI_KEY', 'NOUVELLE_CLE_ICI');
```

**3. Logging** :
```php
log_security_event("⚠️ CLEF API REGÉNÉRÉE - Ancienne clé révoquée");
```

**4. Notification** :
- Prévenir l'équipe
- Vérifier les logs pour détecter une utilisation abusive
- Analyser l'origine de la fuite (Git, logs, partage email)

---

## 📚 Ressources

- **RapidAPI Dashboard** : https://rapidapi.com/developer/dashboard
- **vlucas/phpdotenv** : https://github.com/vlucas/phpdotenv
- **OWASP Secrets Management** : https://cheatsheetseries.owasp.org/cheatsheets/Secrets_Management_Cheat_Sheet.html
- **Apache .htaccess Guide** : https://httpd.apache.org/docs/2.4/howto/htaccess.html

---

## ✅ Conclusion

La clé API BreachDirectory est maintenant stockée de manière **sécurisée** avec :

✅ Centralisation dans `config/db.php` (constantes PHP)  
✅ Protection Git via `.gitignore`  
✅ Protection HTTP via `.htaccess` (Apache)  
✅ Template public `db.php.example` pour nouveaux devs  
✅ Permissions restrictives (600)  
✅ Guide de migration vers variables d'environnement  

**Prêt pour production** ! 🚀

**Dernière mise à jour** : 2025-01-15  
**Version** : 2.3 (API Key Security)
