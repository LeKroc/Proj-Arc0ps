<?php
/**
 * ═══════════════════════════════════════════════════════════════════
 *  FONCTIONS DE SÉCURITÉ - OWASP Top 10 Compliance
 * ═══════════════════════════════════════════════════════════════════
 *  Créé par : Expert DevSecOps
 *  Protection contre : CSRF, XSS, Session Hijacking, RCE
 * ═══════════════════════════════════════════════════════════════════
 */

// ─────────────────────────────────────────────────────────────────────
//  1. CONFIGURATION SÉCURISÉE DES SESSIONS
// ─────────────────────────────────────────────────────────────────────

function secure_session_start() {
    // Si session déjà active, on sort
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    // Configuration des paramètres de sécurité du cookie de session
    ini_set('session.cookie_httponly', 1);  // Pas d'accès JS au cookie
    ini_set('session.cookie_secure', 0);    // Mettre à 1 si HTTPS activé
    ini_set('session.cookie_samesite', 'Strict'); // Protection CSRF
    ini_set('session.use_strict_mode', 1);  // Rejette les ID non générés par le serveur
    
    session_start();
    
    // Protection contre le Session Fixation
    if (!isset($_SESSION['initiated'])) {
        session_regenerate_id(true);
        $_SESSION['initiated'] = true;
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    // Vérification de l'intégrité de la session
    if (isset($_SESSION['user_id'])) {
        $current_ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $current_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // Si le User-Agent ou l'IP change -> Session compromise
        if ($_SESSION['user_agent'] !== $current_ua || $_SESSION['ip_address'] !== $current_ip) {
            session_unset();
            session_destroy();
            header('Location: login.php?error=session_invalid');
            exit;
        }
    }
}

// ─────────────────────────────────────────────────────────────────────
//  2. PROTECTION CSRF (Cross-Site Request Forgery)
// ─────────────────────────────────────────────────────────────────────

/**
 * Génère un token CSRF unique pour la session
 * @return string Token CSRF
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie la validité du token CSRF envoyé
 * @param string $token Token à vérifier
 * @return bool True si valide, False sinon
 */
function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Protège un formulaire POST contre les attaques CSRF
 * À appeler au début de chaque traitement POST
 */
function csrf_protect() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
            die("🛑 ERREUR DE SÉCURITÉ : Token CSRF invalide ou manquant. Action bloquée.");
        }
    }
}

/**
 * Retourne le HTML d'un champ hidden avec le token CSRF
 * @return string HTML du champ input
 */
function csrf_field() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

// ─────────────────────────────────────────────────────────────────────
//  3. SANITIZATION & VALIDATION
// ─────────────────────────────────────────────────────────────────────

/**
 * Nettoie une chaîne pour l'affichage (Protection XSS)
 * @param string $string Chaîne à nettoyer
 * @return string Chaîne sécurisée
 */
function clean_output($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Nettoie une entrée utilisateur (Sanitization)
 * @param string $input Entrée à nettoyer
 * @return string Entrée nettoyée
 */
function clean_input($input) {
    $input = trim($input);
    $input = stripslashes($input);
    return $input;
}

/**
 * Valide et sécurise un ID numérique
 * @param mixed $id ID à valider
 * @return int ID sécurisé
 */
function secure_int($id) {
    return (int) filter_var($id, FILTER_VALIDATE_INT, ['options' => ['default' => 0, 'min_range' => 1]]);
}

// ─────────────────────────────────────────────────────────────────────
//  4. VALIDATION D'UPLOAD DE FICHIERS (Protection RCE)
// ─────────────────────────────────────────────────────────────────────

/**
 * Valide un fichier uploadé de manière sécurisée
 * @param array $file Tableau $_FILES['nom_champ']
 * @param array $allowed_extensions Extensions autorisées (ex: ['jpg', 'png'])
 * @param int $max_size Taille max en octets (défaut: 5Mo)
 * @return array ['success' => bool, 'message' => string, 'safe_name' => string|null]
 */
function validate_file_upload($file, $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'], $max_size = 5242880) {
    
    // 1. Vérification qu'un fichier a bien été uploadé
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Erreur lors de l\'upload du fichier.'];
    }
    
    // 2. Vérification de la taille
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'Fichier trop volumineux (Max: ' . ($max_size / 1048576) . ' Mo).'];
    }
    
    // 3. Vérification de l'extension (Basique)
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, $allowed_extensions)) {
        return ['success' => false, 'message' => 'Type de fichier non autorisé.'];
    }
    
    // 4. VÉRIFICATION DU MIME TYPE RÉEL (Protection RCE)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    // Whitelist des MIME autorisés
    $allowed_mimes = [
        'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf', 'application/zip', 'application/x-rar-compressed'
    ];
    
    if (!in_array($mime_type, $allowed_mimes)) {
        return ['success' => false, 'message' => 'Type de contenu invalide (MIME: ' . $mime_type . ').'];
    }
    
    // 5. BLACKLIST EXTENSIONS DANGEREUSES (Double protection)
    $dangerous_extensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'pht', 'phps', 'exe', 'sh', 'bat', 'cmd'];
    if (in_array($file_extension, $dangerous_extensions)) {
        return ['success' => false, 'message' => 'Type de fichier dangereux détecté.'];
    }
    
    // 6. Génération d'un nom de fichier sécurisé et unique
    $safe_name = 'upload_' . bin2hex(random_bytes(8)) . '_' . time() . '.' . $file_extension;
    
    return [
        'success' => true, 
        'message' => 'Validation réussie.',
        'safe_name' => $safe_name,
        'mime_type' => $mime_type
    ];
}

// ─────────────────────────────────────────────────────────────────────
//  5. CONTRÔLE D'ACCÈS (IDOR Protection)
// ─────────────────────────────────────────────────────────────────────

/**
 * Vérifie qu'un utilisateur est membre d'un projet
 * @param PDO $pdo Connexion BDD
 * @param int $user_id ID de l'utilisateur
 * @param int $project_id ID du projet
 * @return bool True si membre, False sinon
 */
function user_is_project_member($pdo, $user_id, $project_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM project_members WHERE user_id = ? AND project_id = ?");
    $stmt->execute([$user_id, $project_id]);
    return ($stmt->fetchColumn() > 0);
}

/**
 * Vérifie qu'un utilisateur a un rôle spécifique dans un projet
 * @param PDO $pdo Connexion BDD
 * @param int $user_id ID de l'utilisateur
 * @param int $project_id ID du projet
 * @param array $allowed_roles Rôles autorisés (ex: ['owner', 'admin'])
 * @return bool True si autorisé, False sinon
 */
function user_has_project_role($pdo, $user_id, $project_id, $allowed_roles = ['owner']) {
    $stmt = $pdo->prepare("SELECT role FROM project_members WHERE user_id = ? AND project_id = ?");
    $stmt->execute([$user_id, $project_id]);
    $role = $stmt->fetchColumn();
    return $role && in_array($role, $allowed_roles);
}

/**
 * Bloque l'accès et affiche une page d'erreur si l'utilisateur n'est pas membre
 * @param PDO $pdo Connexion BDD
 * @param int $user_id ID de l'utilisateur
 * @param int $project_id ID du projet
 */
function require_project_access($pdo, $user_id, $project_id) {
    if (!user_is_project_member($pdo, $user_id, $project_id)) {
        http_response_code(403);
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>Accès Refusé</title>
            <style>
                body { display: flex; align-items: center; justify-content: center; height: 100vh; 
                       background: linear-gradient(135deg, #1e1e2f 0%, #27293d 100%); color: white; font-family: Arial, sans-serif; }
                .error-box { text-align: center; padding: 50px; background: rgba(255,255,255,0.05); 
                             border-radius: 15px; box-shadow: 0 8px 32px rgba(0,0,0,0.5); }
                .error-icon { font-size: 5rem; margin-bottom: 20px; }
                h1 { color: #e74c3c; margin-bottom: 15px; }
                a { color: #ba54f5; text-decoration: none; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="error-box">
                <div class="error-icon">🚫</div>
                <h1>Accès Refusé</h1>
                <p>Vous n'êtes pas autorisé à accéder à ce projet.</p>
                <p><a href="dashboard.php">← Retour au Dashboard</a></p>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// ─────────────────────────────────────────────────────────────────────
//  6. LOGGING & AUDIT
// ─────────────────────────────────────────────────────────────────────

/**
 * Enregistre une tentative d'action suspecte
 * @param string $message Message à logger
 */
function log_security_event($message) {
    $log_file = __DIR__ . '/logs/security.log';
    $log_dir = dirname($log_file);
    
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $user_id = $_SESSION['user_id'] ?? 'guest';
    
    $log_entry = "[{$timestamp}] [IP: {$ip}] [User: {$user_id}] {$message}\n";
    
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

// ═══════════════════════════════════════════════════════════════════
//  FIN DU FICHIER - Toutes les fonctions sont maintenant disponibles
// ═══════════════════════════════════════════════════════════════════
