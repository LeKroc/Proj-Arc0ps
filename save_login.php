<?php
$paths = ['config/db.php', 'db.php', '../config/db.php'];
$db_found = false;
foreach ($paths as $p) {
    if (file_exists($p)) { require_once $p; $db_found = true; break; }
}
if (!$db_found) die("❌ Erreur : Impossible de trouver db.php. Vérifie ton dossier.");

$message = "";
// Assurez-vous que $pdo est initialisé et que l'environnement est configuré.

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'test_login') {
        
        // 1. Récupération des données POST
        $email_or_username = $_POST['email']; // Suppose que le champ d'entrée s'appelle 'email'
        $pass              = $_POST['password'];

        // 2. Correction de la Requête SQL : Ajouter les guillemets manquants et lier correctement
        $sql = "
            SELECT id, username, password 
            FROM users 
            WHERE email = :input OR username = :input 
        ";
        
        $stmt = $pdo->prepare($sql);
        
        // 3. Correction de l'exécution : Utiliser le paramètre correct
        $stmt->execute(['input' => $email_or_username]);
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC); // Optionnel : Spécifier le mode de récupération pour plus de clarté

        if ($user && password_verify($pass, $user['password'])) {
            // ✅ LOGIN SUCCÈS
            // Démarrez la session seulement après une vérification réussie
            session_start();
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            
            header('Location: dashboard.php');
            exit;
        } else {
            // ❌ ÉCHEC LOGIN
            $message = "❌ Erreur : Adresse e-mail/Username ou mot de passe incorrect.";
        }
    }
}
// ⚠️ SUPPRESSION des requêtes $users, $projects, et $members
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
            Pas encore de compte ? <a href="register.html">S'inscrire ici</a>
        </p>
    </div>
</body>
</html>