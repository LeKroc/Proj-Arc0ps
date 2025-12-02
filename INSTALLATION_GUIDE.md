# 🚀 GUIDE D'INSTALLATION - APPLICATION ARCOPS SÉCURISÉE

## ⚠️ PRÉREQUIS

- PHP 7.4+ avec extensions : `pdo_mysql`, `fileinfo`, `session`
- MySQL 5.7+ ou MariaDB 10.3+
- Apache 2.4+ avec `mod_rewrite` et `mod_headers` activés
- Certificat SSL (recommandé pour production)

---

## 📦 ÉTAPE 1 : INSTALLATION DES FICHIERS

```bash
# Cloner le projet ou extraire l'archive
cd /var/www/html/
git clone https://github.com/votre-repo/arcops.git
cd arcops/

# Vérifier les permissions
chmod 755 -R .
chmod 644 *.php
chmod 600 config/db.php  # Fichier sensible
```

---

## 🗄️ ÉTAPE 2 : CONFIGURATION DE LA BASE DE DONNÉES

### A. Créer la base de données

```sql
CREATE DATABASE arcops_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'arcops_user'@'localhost' IDENTIFIED BY 'MOT_DE_PASSE_FORT_ICI';
GRANT SELECT, INSERT, UPDATE, DELETE ON arcops_db.* TO 'arcops_user'@'localhost';
FLUSH PRIVILEGES;
```

### B. Importer la structure

```bash
mysql -u arcops_user -p arcops_db < database_structure.sql
```

### C. Configurer la connexion

Éditer `config/db.php` :

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

---

## 🔧 ÉTAPE 3 : CONFIGURATION APACHE

### A. Activer les modules requis

```bash
sudo a2enmod rewrite
sudo a2enmod headers
sudo systemctl restart apache2
```

### B. Configurer le VirtualHost

Éditer `/etc/apache2/sites-available/arcops.conf` :

```apache
<VirtualHost *:80>
    ServerName arcops.votredomaine.com
    DocumentRoot /var/www/html/arcops
    
    <Directory /var/www/html/arcops>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/arcops_error.log
    CustomLog ${APACHE_LOG_DIR}/arcops_access.log combined
</VirtualHost>
```

Activer le site :

```bash
sudo a2ensite arcops.conf
sudo systemctl reload apache2
```

---

## 🔐 ÉTAPE 4 : ACTIVATION HTTPS (PRODUCTION)

### Option 1 : Let's Encrypt (Gratuit)

```bash
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d arcops.votredomaine.com
```

### Option 2 : Certificat commercial

Suivre les instructions de votre fournisseur SSL.

### Après activation SSL :

Éditer `functions.php`, ligne 13 :

```php
ini_set('session.cookie_secure', 1);  // Changer 0 → 1
```

Décommenter dans `.htaccess` :

```apache
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
```

---

## 📁 ÉTAPE 5 : CRÉATION DES DOSSIERS

```bash
# Créer les dossiers pour les uploads et logs
mkdir -p assets/imageProject assets/PhotoProfile assets/project_files assets/notes logs

# Permissions (le serveur web doit pouvoir écrire)
sudo chown -R www-data:www-data assets/ logs/
chmod 755 assets/
chmod 755 logs/
```

---

## 🧪 ÉTAPE 6 : TESTS DE SÉCURITÉ

### A. Vérifier la protection CSRF

1. Se connecter normalement
2. Supprimer le champ `<input name="csrf_token">` d'un formulaire via DevTools
3. Soumettre le formulaire
4. ✅ **Attendu** : Message "Token CSRF invalide"

### B. Vérifier la protection RCE

1. Tenter d'uploader un fichier `test.php` :
   ```php
   <?php system($_GET['cmd']); ?>
   ```
2. ✅ **Attendu** : Upload refusé ("Type de contenu invalide")

### C. Vérifier la protection IDOR

1. Se connecter avec User #1
2. Noter l'URL d'un projet : `avancement.php?id=5`
3. Se connecter avec User #2
4. Accéder à `avancement.php?id=5` (projet de User #1)
5. ✅ **Attendu** : Page "Accès Refusé"

### D. Vérifier les logs

```bash
tail -f logs/security.log
# Doit afficher les événements de sécurité (tentatives d'upload invalides, etc.)
```

---

## 🔍 ÉTAPE 7 : CONFIGURATION FINALE

### A. Désactiver l'affichage des erreurs PHP (PRODUCTION)

Éditer `php.ini` :

```ini
display_errors = Off
display_startup_errors = Off
log_errors = On
error_log = /var/log/php_errors.log
```

### B. Configurer le pare-feu

```bash
# UFW (Ubuntu/Debian)
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable

# Ou Firewalld (CentOS/RHEL)
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
```

### C. Configurer les sauvegardes automatiques

Créer un script de backup quotidien :

```bash
#!/bin/bash
# /opt/backup_arcops.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups/arcops"

# Sauvegarde BDD
mysqldump -u arcops_user -p'PASSWORD' arcops_db | gzip > "$BACKUP_DIR/db_$DATE.sql.gz"

# Sauvegarde fichiers
tar -czf "$BACKUP_DIR/files_$DATE.tar.gz" /var/www/html/arcops/assets/

# Supprimer les backups de plus de 30 jours
find $BACKUP_DIR -type f -mtime +30 -delete
```

Ajouter au cron :

```bash
sudo crontab -e
# Ajouter :
0 3 * * * /opt/backup_arcops.sh
```

---

## ✅ CHECKLIST DE VALIDATION

- [ ] Base de données créée et connectée
- [ ] Module `mod_rewrite` activé
- [ ] Fichier `.htaccess` présent à la racine
- [ ] Fichier `assets/.htaccess` présent
- [ ] Dossiers `assets/` et `logs/` créés avec bonnes permissions
- [ ] HTTPS activé (production)
- [ ] `session.cookie_secure = 1` si HTTPS
- [ ] Tests CSRF, RCE, IDOR validés
- [ ] Logs de sécurité fonctionnels
- [ ] Sauvegardes automatiques configurées

---

## 🆘 DÉPANNAGE

### Erreur "Token CSRF invalide" sur tous les formulaires

**Cause** : Sessions PHP non fonctionnelles  
**Solution** :

```bash
# Vérifier les permissions du dossier de sessions
ls -la /var/lib/php/sessions/
sudo chown -R www-data:www-data /var/lib/php/sessions/
```

### Erreur "Type de contenu invalide" sur uploads d'images valides

**Cause** : Extension `fileinfo` manquante  
**Solution** :

```bash
sudo apt install php-fileinfo
sudo systemctl restart apache2
```

### Page blanche après connexion

**Cause** : Erreur PHP non affichée  
**Solution** :

```bash
# Consulter les logs Apache
sudo tail -f /var/log/apache2/arcops_error.log
```

---

## 📞 SUPPORT

- 📧 Email : support@arcops.com
- 📚 Documentation : https://docs.arcops.com
- 🐛 Signaler un bug : https://github.com/votre-repo/arcops/issues

---

**Version** : 2.0 (Sécurisée)  
**Dernière mise à jour** : 2024

---

*Développé par l'équipe ArcOps - Audité par Expert DevSecOps*
