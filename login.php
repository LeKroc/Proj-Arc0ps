<?php

$paths = ['config/db.php', 'db.php', '../config/db.php'];
$db_found = false;
foreach ($paths as $p) {
    if (file_exists($p)) { require_once $p; $db_found = true; break; }
}
if (!$db_found) die("❌ Erreur : Impossible de trouver db.php. Vérifie ton dossier.");

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] === 'test_login') {
        $email = $_POST['email'];
        $pass  = $_POST['password'];

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :e");
        $stmt->execute(['e' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($pass, $user['password'])) {
            $message = "<div class='alert success'>✅ LOGIN SUCCÈS !<br>Bienvenue <b>{$user['username']}</b> (ID: {$user['id']})</div>";
        } else {
            $message = "<div class='alert error'>❌ ÉCHEC LOGIN : Email ou mot de passe incorrect.</div>";
        }
    }
}

$users = $pdo->query("SELECT id, username, email FROM users ORDER BY id DESC")->fetchAll();

$projects = $pdo->query("
    SELECT p.id, p.title, p.status, u.username as owner 
    FROM projects p
    JOIN users u ON p.owner_id = u.id
    ORDER BY p.id DESC
")->fetchAll();

$members = $pdo->query("
    SELECT pm.role, pm.joined_at, u.username, p.title
    FROM project_members pm
    JOIN users u ON pm.user_id = u.id
    JOIN projects p ON pm.project_id = p.id
    ORDER BY pm.joined_at DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Λrc0ps</title>
    <link rel="stylesheet" href="style.css"> </head>

<body>
    <div class="auth-container">
        <h2>Se Connecter</h2>
        <form action="/submit-login" method="POST">
            
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