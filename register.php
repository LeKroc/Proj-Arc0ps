<?php
// Inclusion des fonctions de sécurité
require_once 'functions.php';

// Démarrage sécurisé de la session
secure_session_start();

// Inclure le fichier de connexion qui initialise $pdo
$paths = ['config/db.php', 'db.php', '../config/db.php'];
$db_found = false;
foreach ($paths as $p) {
    if (file_exists($p)) { require_once $p; $db_found = true; break; }
}
if (!$db_found) die("❌ Erreur : Impossible de trouver db.php. Vérifie ton dossier.");

$message = "";

$errors = []; // Initialisation du tableau d'erreurs

// 1. Vérification que le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Protection CSRF
    csrf_protect();

    // --- 2. Récupération des données ---
    $username = clean_input(trim($_POST['username']));
    $email = clean_input(trim($_POST['email']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm-password'];

    // --- 3. Validation des données ---
    
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $errors[] = "Tous les champs doivent être remplis.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }
    if (strlen($password) < 8) {
        $errors[] = "Le mot de passe doit contenir au moins 8 caractères.";
    }

    // --- 4. Si aucune erreur n'est trouvée (Inscription) ---
    if (empty($errors)) {
        
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            // CORRECTION 1 : Le nom de la colonne dans la requête est maintenant 'password' (comme dans la DB)
            $sql = "INSERT INTO users (username, email, password) VALUES (:username, :email, :password_hash)";
            
            $stmt = $pdo->prepare($sql);
            
            $stmt->bindParam(":username", $username);
            $stmt->bindParam(":email", $email);
            
            // CORRECTION 2 : La liaison du paramètre utilise maintenant le nom de colonne 'password'
            $stmt->bindParam(":password_hash", $hashed_password); // Nous gardons le placeholder pour la sécurité
            
            if ($stmt->execute()) {
                // Succès : Redirection
                header("location: login.php");
                exit;
            }

        } catch (PDOException $e) {
            // REMPLACER CE BLOC PAR LA VERSION SÉCURISÉE UNE FOIS LE DÉBOGAGE TERMINÉ
            if ($e->getCode() == 23000) { 
                $errors[] = "Ce nom d'utilisateur ou e-mail est déjà utilisé.";
            } else {
                // Version sécurisée (Production) :
                $errors[] = "Erreur lors de l'inscription. Veuillez réessayer.";
                log_security_event("Erreur d'inscription PDO : " . $e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Λrc0ps</title>
    <link rel="stylesheet" href="style.css"> 
    <link rel="icon" type="image/x-icon" href="assets/logo_Arc0ps.ico">
</head>
<body>
    <div class="auth-container">
        <h2>Créer un Compte</h2>
        
        <?php 
        // Affichage des erreurs
        if (!empty($errors)): ?>
            <div style='color: red; padding: 10px; border: 1px solid red; margin-bottom: 15px; background-color: #ffe6e6;'>
                <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
            <?= csrf_field() ?>
            
            <div class="form-group">
                <label for="username">Nom d'utilisateur</label>
                <input type="text" id="username" name="username" required value="<?= isset($username) ? clean_output($username) : '' ?>">
            </div>

            <div class="form-group">
                <label for="email">Adresse e-mail</label>
                <input type="email" id="email" name="email" required value="<?= isset($email) ? clean_output($email) : '' ?>">
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label for="confirm-password">Confirmer le mot de passe</label>
                <input type="password" id="confirm-password" name="confirm-password" required>
            </div>

            <button type="submit" class="btn-primary">S'inscrire</button>
        </form>

        <p class="link-switch">
            Déjà inscrit ? <a href="login.php">Se connecter ici</a>
        </p>
    </div>
</body>
</html>