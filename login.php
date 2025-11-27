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
        $email_or_username = $_POST['email'];
        $pass  = $_POST['password'];

        $stmt = $pdo->prepare("
            SELECT id, username, password 
            FROM users 
            WHERE email = :input OR username = :input
        ");

        $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE email = :e");
        $stmt->execute(['e' => $email_or_username]);
        $user = $stmt->fetch();

        if ($user && password_verify($pass, $user['password'])) {
            // LOGIN SUCCÈS
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: login_true.php');   // login_true pour debug la connection ou dashboard.php pour aller a la bonne destination
            exit;
        } else {
            // ÉCHEC LOGIN
            $message = "❌ Erreur : Adresse e-mail ou mot de passe incorrect.";
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
                <label for="email">Adresse e-mail</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn-primary">S'identifier</button>
        </form>

        <p class="link-switch">
            Pas encore de compte ? <a href="register.html">S'inscrire ici</a>
        </p>
    </div>
</body>
</html>