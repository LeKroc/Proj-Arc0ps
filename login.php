<?php
$paths = ['config/db.php', 'db.php', '../config/db.php'];
$db_found = false;
foreach ($paths as $p) {
    if (file_exists($p)) { require_once $p; $db_found = true; break; }
}
if (!$db_found) die("❌ Erreur : Impossible de trouver db.php. Vérifie ton dossier.");

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'test_login') {
        
        $identifier = $_POST['identifier']; 
        $pass       = $_POST['password'];

        // CORRECTION ICI : Utilisation de marqueurs distincts
        $sql = "SELECT id, username, password 
                FROM users 
                WHERE email = :email_val OR username = :user_val";
        
        $stmt = $pdo->prepare($sql);
        
        // CORRECTION LIGNE 30 : On lie les deux marqueurs à la même variable $identifier
        $stmt->execute([
            'email_val' => $identifier, 
            'user_val'  => $identifier
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($pass, $user['password'])) {
            session_start();
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: dashboard.php');
            exit;
        } else {
            $message = "❌ Erreur : Identifiant ou mot de passe incorrect.";
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
            <p class="error-message"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <form action="" method="POST">
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
</html>