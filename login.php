
</html><?php
// Inclusion des fonctions de sécurité
require_once 'functions.php';

// Démarrage sécurisé de la session
secure_session_start();

// On inclut la config (qui contient $pdo et SECRET_KEY)
$paths = ['config/db.php', 'db.php', '../config/db.php'];
$db_found = false;
foreach ($paths as $p) {
    if (file_exists($p)) { require_once $p; $db_found = true; break; }
}
if (!$db_found) die("❌ Erreur : Impossible de trouver db.php. Vérifie ton dossier.");

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Protection CSRF
    csrf_protect();
    
    if (isset($_POST['action']) && $_POST['action'] === 'test_login') {
        
        $identifier = clean_input($_POST['identifier']); 
        $pass       = $_POST['password'];

        $sql = "SELECT id, username, password 
                FROM users 
                WHERE email = :email_val OR username = :user_val";
        
        $stmt = $pdo->prepare($sql);
        
        $stmt->execute([
            'email_val' => $identifier, 
            'user_val'  => $identifier
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($pass, $user['password'])) {

            // On force le serveur à créer un nouvel ID de session vierge
            session_regenerate_id(true); 

            $_SESSION['user_id']  = (int)$user['id'];
            $_SESSION['username'] = $user['username'];

           
            $infoBrute = $user['username'] . '|' . date('Y-m-d H:i:s');
            
            $base64 = base64_encode($infoBrute);
            
            $randomSuffix = bin2hex(random_bytes(2));
            
            $payload = $base64 . $randomSuffix;
            
            $signature = hash_hmac('sha256', $payload, SECRET_KEY);
        
            setcookie(
                'mon_site_auth', 
                $payload . '.' . $signature, 
                time() + 3600, // Expire dans 1h
                '/',           // Valide sur tout le site
                '',            // Domaine (laisser vide par défaut)
                false,         // Secure (mettre true si tu as HTTPS)
                true           // HttpOnly (Javascript ne peut pas le lire, sécurité XSS)
            );

            // 3. Redirection
            header('Location: dashboard.php');
            exit;

        } 
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Λrc0ps</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/x-icon" href="assets/logo_Arc0ps.ico"> 
</head>

<body>
    <div class="auth-container">
        <h2>Se Connecter</h2>
        
        <?php if (!empty($message)): ?>
            <p class="error-message"><?= clean_output($message) ?></p>
        <?php endif; ?>

        <form action="" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="test_login">
            
            <div class="form-group">
                <label for="identifier">Adresse e-mail ou nom d'utilisateur</label>
                <input type="text" id="identifier" name="identifier" required>
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn-primary">S'identifier</button>
        </form>

        <p class="link-switch">
            Pas encore de compte ? <a href="register.php">S'inscrire ici</a>
        </p>
    </div>
</body>