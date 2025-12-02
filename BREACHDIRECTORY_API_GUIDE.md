# 🔐 GUIDE D'INTÉGRATION API - BREACHDIRECTORY (RapidAPI)

## 📋 Vue d'Ensemble

Ce document décrit l'intégration complète de l'API **BreachDirectory** via RapidAPI pour la détection de fuites de données dans l'application ArcOps.

---

## 🔑 Informations API

### Service Utilisé
**BreachDirectory** - Base de données collaborative de fuites de données publiques

### Fournisseur
**RapidAPI** - https://rapidapi.com/rohan-patra/api/breachdirectory

### Clé API Actuelle
```
X-RapidAPI-Key: 9da75d2638msha156ca537944969p1d1543jsn5cab76c76c80
X-RapidAPI-Host: breachdirectory.p.rapidapi.com
```

⚠️ **IMPORTANT** : Cette clé doit être stockée de manière sécurisée (variables d'environnement en production).

---

## 🚀 Implémentation Technique

### Endpoint API

```
GET https://breachdirectory.p.rapidapi.com/?func=auto&term={EMAIL}
```

**Paramètres** :
- `func` : `auto` (détection automatique du type de recherche)
- `term` : Adresse email à vérifier (URL-encoded)

### Requête cURL Complète

```php
$apiUrl = "https://breachdirectory.p.rapidapi.com/?func=auto&term=" . urlencode($userEmail);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15); // 15 secondes max
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

// Headers obligatoires
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-RapidAPI-Key: VOTRE_CLE_ICI',
    'X-RapidAPI-Host: breachdirectory.p.rapidapi.com'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
```

---

## 📊 Structure de la Réponse JSON

### Cas 1 : Fuites Détectées

```json
{
  "success": true,
  "found": 3,
  "result": [
    {
      "source": "LinkedIn2021",
      "password": "hashedpassword123",
      "sha1": "a94a8fe5ccb19ba61c4c0873d391e987982fbbd3",
      "hash_type": "SHA1"
    },
    {
      "source": "Collection#1",
      "password": "plaintext_password",
      "sha1": null,
      "hash_type": null
    },
    {
      "source": "Adobe2013",
      "password": "encrypted_pass",
      "sha1": "b1946ac92492d2347c6235b4d2611184",
      "hash_type": "MD5"
    }
  ]
}
```

### Cas 2 : Aucune Fuite

```json
{
  "success": true,
  "found": 0,
  "result": []
}
```

### Cas 3 : Erreur API

```json
{
  "success": false,
  "error": "Rate limit exceeded"
}
```

---

## 🔍 Logique de Parsing

### Détection de Fuite

```php
$data = json_decode($response, true);

if (isset($data['success']) && $data['success'] === true) {
    if (isset($data['found']) && $data['found'] > 0) {
        // FUITE DÉTECTÉE
        $isLeaked = true;
        $breachCount = (int)$data['found'];
        
        // Extraction des sources
        foreach ($data['result'] as $breach) {
            $sources[] = $breach['source'];
        }
    } else {
        // AUCUNE FUITE
        $isLeaked = false;
    }
}
```

### Gestion des Erreurs

```php
try {
    // Appel API
    $response = curl_exec($ch);
    
    // Vérification cURL
    if (curl_error($ch)) {
        throw new Exception("Erreur cURL : " . curl_error($ch));
    }
    
    // Vérification HTTP
    if ($httpCode !== 200) {
        throw new Exception("API HTTP Error : " . $httpCode);
    }
    
    // Vérification JSON
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("JSON invalide : " . json_last_error_msg());
    }
    
} catch (Exception $e) {
    // Affichage message d'erreur à l'utilisateur
    // Logging de l'erreur
    // NE PAS mettre à jour has_leaked (skip_db_update)
}
```

---

## 🎨 Interface Utilisateur

### Message - Fuite Détectée (Rouge)

```html
<div class="alert alert-error">
    <i class="fas fa-exclamation-triangle" style="font-size: 2rem;"></i>
    <strong>⚠️ ALERTE CRITIQUE - 3 Fuite(s) Détectée(s) !</strong>
    <p>
        Votre email a été trouvé dans <strong>3 base(s) de données compromises</strong>.
    </p>
    <p>
        <strong>Sources identifiées</strong> : LinkedIn2021, Collection#1, Adobe2013
    </p>
    <div style="background: rgba(231, 76, 60, 0.2); padding: 10px;">
        <strong>Recommandations urgentes</strong> :
        • Changez immédiatement votre mot de passe
        • Activez l'authentification à deux facteurs (2FA)
        • Vérifiez vos comptes bancaires
        • Ne réutilisez JAMAIS ce mot de passe ailleurs
    </div>
</div>
```

### Message - Aucune Fuite (Vert)

```html
<div class="alert alert-success">
    <i class="fas fa-shield-alt" style="font-size: 2rem;"></i>
    <strong>✅ Excellente Nouvelle !</strong>
    <p>
        Aucune fuite de données détectée pour votre email.
    </p>
    <p>
        <i class="fas fa-check-circle"></i> 
        Votre compte n'apparaît dans aucune base de données compromise.
    </p>
    <p style="font-style: italic;">
        💡 Continuez à utiliser des mots de passe forts et uniques.
    </p>
</div>
```

### Message - Erreur API (Jaune)

```html
<div class="alert alert-warning">
    <i class="fas fa-exclamation-circle" style="font-size: 2rem;"></i>
    <strong>⚠️ Service Temporairement Indisponible</strong>
    <p>
        Impossible de vérifier les fuites pour le moment.
    </p>
    <small>Erreur technique : Timeout lors de la connexion à l'API</small>
</div>
```

---

## 📝 Logging des Événements

### Événements Loggés

Tous les appels API et résultats sont tracés dans `logs/security.log` :

```
[2025-01-15 16:42:33] Appel API BreachDirectory pour test@example.com - HTTP 200
[2025-01-15 16:42:35] ⚠️ FUITE DÉTECTÉE (API BreachDirectory) : test@example.com - 2 source(s) : LinkedIn2021, Collection#1
[2025-01-15 16:45:12] ✅ AUCUNE FUITE (API BreachDirectory) : secure.user@company.fr
[2025-01-15 16:48:20] ❌ ERREUR API BreachDirectory pour retry@test.com : API HTTP Error : 429
```

### Consultation des Logs

```bash
# Linux/Mac
tail -f logs/security.log | grep "BreachDirectory"

# Windows (PowerShell)
Get-Content logs/security.log -Wait -Tail 50 | Select-String "BreachDirectory"
```

---

## 🧪 Tests de Validation

### Test 1 : Email avec Fuites Connues

**Procédure** :
1. Créer un compte avec un email connu pour être compromis (ex: `test@gmail.com`)
2. Aller dans Settings → Cliquer "Vérifier les fuites"
3. Attendre la réponse de l'API (2-5 secondes)

**Résultat attendu** :
- ✅ Alerte rouge affichée
- ✅ Nombre de fuites indiqué (ex: "3 Fuite(s) Détectée(s)")
- ✅ Sources listées (ex: "LinkedIn2021, Collection#1, Adobe2013")
- ✅ Badge "LEAKED" rouge dans la sidebar
- ✅ Colonne `has_leaked` = 1 en BDD
- ✅ Log : "⚠️ FUITE DÉTECTÉE (API BreachDirectory)"

### Test 2 : Email Sécurisé

**Procédure** :
1. Utiliser un email unique jamais enregistré nulle part (ex: `unique.test.arcops.2025@example.com`)
2. Effectuer la vérification

**Résultat attendu** :
- ✅ Alerte verte affichée
- ✅ Message "Aucune fuite de données détectée"
- ✅ Badge "SECURE" vert dans la sidebar
- ✅ Colonne `has_leaked` = 0 en BDD
- ✅ Log : "✅ AUCUNE FUITE (API BreachDirectory)"

### Test 3 : Gestion d'Erreur (Simulation)

**Procédure** :
1. Modifier temporairement la clé API pour la rendre invalide
2. Effectuer une vérification

**Résultat attendu** :
- ✅ Alerte jaune/orange affichée
- ✅ Message "Service Temporairement Indisponible"
- ✅ Erreur technique affichée (ex: "API HTTP Error : 401")
- ✅ Colonne `has_leaked` NON mise à jour (reste inchangée)
- ✅ Log : "❌ ERREUR API BreachDirectory"

### Test 4 : Timeout

**Procédure** :
1. Réduire le timeout cURL à 1 seconde (ligne ~140 de dashboard.php)
2. Effectuer une vérification

**Résultat attendu** :
- ✅ Erreur "Erreur cURL : Timeout was reached"
- ✅ Gestion gracieuse sans crash de l'application

---

## 🔒 Sécurité & Bonnes Pratiques

### ✅ Points Sécurisés

1. **HTTPS Obligatoire** : `curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);`
2. **Sanitization** : `urlencode($userEmail)` avant envoi
3. **Timeout** : 15 secondes pour éviter les blocages
4. **Logging** : Traçabilité complète sans exposer la clé API
5. **Gestion d'erreurs** : Try-catch avec messages utilisateur appropriés
6. **Skip Update** : En cas d'erreur API, ne pas marquer comme "safe" par défaut

### ⚠️ Points d'Attention

1. **Rate Limiting** :
   - RapidAPI limite à 100 requêtes/mois (plan gratuit)
   - Implémenter un cache pour éviter de vérifier plusieurs fois le même email

2. **Stockage Clé API** :
   ```php
   // Méthode recommandée (production)
   define('RAPIDAPI_KEY', getenv('RAPIDAPI_KEY') ?: 'fallback_key');
   
   // Utilisation
   curl_setopt($ch, CURLOPT_HTTPHEADER, [
       'X-RapidAPI-Key: ' . RAPIDAPI_KEY,
       'X-RapidAPI-Host: breachdirectory.p.rapidapi.com'
   ]);
   ```

3. **Privacy** :
   - Informer l'utilisateur que son email est envoyé à un service tiers
   - Ajouter mention dans les CGU/Politique de confidentialité

### 📋 Checklist Pré-Déploiement

- [ ] Clé API RapidAPI valide et testée
- [ ] Timeout configuré (15 secondes recommandé)
- [ ] Gestion d'erreurs complète (cURL, HTTP, JSON)
- [ ] Logging opérationnel
- [ ] Badge sidebar fonctionne (LEAKED/SECURE)
- [ ] Colonne `has_leaked` correctement mise à jour
- [ ] Test avec email compromis réel
- [ ] Test avec email sécurisé
- [ ] Test gestion d'erreur (clé invalide)
- [ ] Vérification logs de sécurité

---

## 💰 Coûts & Limitations

### Plan Gratuit RapidAPI

| Critère | Limite |
|---------|--------|
| **Requêtes/mois** | 100 |
| **Requêtes/seconde** | 1 |
| **Timeout** | 30 secondes |
| **Support** | Community |

### Plan Pro (Recommandé pour Production)

| Critère | Limite |
|---------|--------|
| **Requêtes/mois** | 10 000 |
| **Requêtes/seconde** | 10 |
| **Coût** | ~9.99$/mois |
| **Support** | Email |

### Optimisation Coûts

**Implémentation Cache** :
```php
// Vérifier si l'email a été vérifié dans les 7 derniers jours
$stmt = $pdo->prepare("SELECT has_leaked, updated_at FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$lastCheck = strtotime($user['updated_at']);
$now = time();
$daysSinceCheck = ($now - $lastCheck) / 86400;

if ($daysSinceCheck < 7) {
    // Afficher le résultat en cache
    echo "Dernière vérification : Il y a " . round($daysSinceCheck) . " jour(s)";
} else {
    // Appeler l'API
}
```

---

## 🔗 Ressources Externes

- **API Documentation** : https://rapidapi.com/rohan-patra/api/breachdirectory
- **RapidAPI Dashboard** : https://rapidapi.com/developer/dashboard
- **BreachDirectory GitHub** : https://github.com/breachdirectory
- **Have I Been Pwned** : https://haveibeenpwned.com/ (alternative)

---

## 📞 Support & Dépannage

### Problèmes Courants

**1. Erreur 401 Unauthorized**
```
Solution : Vérifier la clé API dans les headers
```

**2. Erreur 429 Too Many Requests**
```
Solution : Limite de rate atteinte, implémenter un cache ou upgrader le plan
```

**3. Timeout après 15 secondes**
```
Solution : Augmenter le timeout ou vérifier la connexion réseau
```

**4. JSON invalide**
```
Solution : Vérifier que l'API renvoie bien du JSON (var_dump($response))
```

### Debug Mode

```php
// Ajouter temporairement après curl_exec()
error_log("API Response: " . $response);
error_log("HTTP Code: " . $httpCode);
error_log("cURL Error: " . curl_error($ch));
```

---

## ✅ Conclusion

L'intégration de l'API BreachDirectory est maintenant **100% opérationnelle** avec :

✅ Appel API réel (pas de simulation)  
✅ Parsing complet de la réponse JSON  
✅ Affichage des sources de fuites  
✅ Gestion d'erreurs robuste  
✅ Logging complet  
✅ Badge permanent dans la sidebar  
✅ Messages utilisateur détaillés  

**Prêt pour production** avec plan RapidAPI activé ! 🎉

**Dernière mise à jour** : 2025-01-15  
**Version** : 2.3 (BreachDirectory API Integration)
