<?php
// admin_debug.php
// OUTIL DE TEST COMPLET : USERS + PROJETS + MEMBRES + LOGIN CHECK

// 1. DÉTECTION AUTOMATIQUE DU FICHIER DB
$paths = ['config/db.php', 'db.php', '../config/db.php'];
$db_found = false;
foreach ($paths as $p) {
    if (file_exists($p)) { require_once $p; $db_found = true; break; }
}
if (!$db_found) die("❌ Erreur : Impossible de trouver db.php. Vérifie ton dossier.");

$message = "";

// --- TRAITEMENT DES FORMULAIRES ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ACTION 1 : CRÉER UN USER
    if ($_POST['action'] === 'create_user') {
        try {
            $sql = "INSERT INTO users (username, email, password, theme) VALUES (:u, :e, :p, 'dark')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'u' => $_POST['username'],
                'e' => $_POST['email'],
                'p' => password_hash($_POST['password'], PASSWORD_DEFAULT)
            ]);
            $message = "<div class='alert success'>👤 User <b>{$_POST['username']}</b> créé !</div>";
        } catch (PDOException $e) {
            $message = "<div class='alert error'>Erreur User : " . $e->getMessage() . "</div>";
        }
    }

    // ACTION 2 : CRÉER UN PROJET
    if ($_POST['action'] === 'create_project') {
        try {
            $sql = "INSERT INTO projects (title, description, owner_id, status) VALUES (:t, :d, :o, 'active')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                't' => $_POST['title'],
                'd' => $_POST['description'],
                'o' => $_POST['owner_id']
            ]);
            $message = "<div class='alert success'>📂 Projet <b>{$_POST['title']}</b> créé !</div>";
        } catch (PDOException $e) {
            $message = "<div class='alert error'>Erreur Projet : " . $e->getMessage() . "</div>";
        }
    }

    // ACTION 3 : AJOUTER UN MEMBRE (LIAISON)
    if ($_POST['action'] === 'add_member') {
        try {
            $sql = "INSERT INTO project_members (project_id, user_id, role) VALUES (:pid, :uid, :r)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'pid' => $_POST['project_id'],
                'uid' => $_POST['user_id'],
                'r'   => $_POST['role']
            ]);
            $message = "<div class='alert success'>🔗 Membre ajouté au projet avec succès !</div>";
        } catch (PDOException $e) {
            $message = "<div class='alert error'>Erreur Liaison : " . $e->getMessage() . "</div>";
        }
    }

    // ACTION 4 : TEST LOGIN (Authentification)
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

// --- RECUP DATA ---
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
    <title>Λrc0ps - Master Debug</title>
    <style>
        body { background-color: #333333; color: #eee; font-family: sans-serif; padding: 20px; }
        h1, h2 { color: #00ffcc; border-bottom: 1px solid #555; padding-bottom: 10px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .card { background: #222; padding: 20px; border-radius: 8px; border: 1px solid #444; box-shadow: 0 4px 6px rgba(0,0,0,0.3); }
        
        input, select, textarea, button { width: 100%; padding: 10px; margin-bottom: 10px; background: #444; border: 1px solid #555; color: white; border-radius: 4px; box-sizing: border-box;}
        button { font-weight: bold; cursor: pointer; border: none; transition: 0.3s; }
        
        .btn-user { background: #2ecc71; color: #000; }
        .btn-project { background: #00ffcc; color: #000; }
        .btn-member { background: #9b59b6; color: white; }
        .btn-login { background: #e67e22; color: white; } /* Orange pour le login */

        button:hover { opacity: 0.8; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 0.9em; }
        th, td { border: 1px solid #555; padding: 8px; text-align: left; }
        th { background: #111; color: #aaa; }
        tr:nth-child(even) { background: #2a2a2a; }

        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .success { background: #2ecc71; color: #000; }
        .error { background: #e74c3c; color: white; }
    </style>
</head>
<body>

    <h1>Λrc0ps <span style="font-size:0.5em; color:#777">// Admin Dashboard Test</span></h1>
    
    <?php echo $message; ?>

    <div class="grid">
        
        <div class="card" style="border-top: 3px solid #2ecc71;">
            <h3>👤 1. Créer Utilisateur</h3>
            <form method="POST">
                <input type="hidden" name="action" value="create_user">
                <input type="text" name="username" placeholder="Pseudo" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="text" name="password" placeholder="Mot de passe" required>
                <button type="submit" class="btn-user">Ajouter User</button>
            </form>
        </div>

        <div class="card" style="border-top: 3px solid #00ffcc;">
            <h3>📂 2. Créer Projet</h3>
            <form method="POST">
                <input type="hidden" name="action" value="create_project">
                <input type="text" name="title" placeholder="Nom du projet" required>
                <input type="text" name="description" placeholder="Description courte">
                
                <label style="font-size:0.8em; color:#aaa">Propriétaire (Owner) :</label>
                <select name="owner_id" required>
                    <?php foreach($users as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['username']) ?> (ID: <?= $u['id'] ?>)</option>
                    <?php endforeach; ?>
                </select>
                
                <button type="submit" class="btn-project">Créer Projet</button>
            </form>
        </div>

        <div class="card" style="border-top: 3px solid #9b59b6;">
            <h3>🔗 3. Lier un Membre</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_member">
                
                <label style="font-size:0.8em; color:#aaa">Qui ?</label>
                <select name="user_id">
                    <?php foreach($users as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['username']) ?></option>
                    <?php endforeach; ?>
                </select>

                <label style="font-size:0.8em; color:#aaa">Sur quel projet ?</label>
                <select name="project_id">
                    <?php foreach($projects as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['title']) ?></option>
                    <?php endforeach; ?>
                </select>

                <label style="font-size:0.8em; color:#aaa">Quel rôle ?</label>
                <select name="role">
                    <option value="viewer">Viewer</option>
                    <option value="dev">Dev</option>
                    <option value="admin">Admin</option>
                </select>

                <button type="submit" class="btn-member">Ajouter au projet</button>
            </form>
        </div>

        <div class="card" style="border-top: 3px solid #e67e22;">
            <h3>🔐 4. Tester Login</h3>
            <p style="font-size:0.8em; color:#aaa; margin-bottom:10px;">Vérifie l'email et le hash du mot de passe.</p>
            <form method="POST">
                <input type="hidden" name="action" value="test_login">
                <input type="email" name="email" placeholder="Email à tester" required>
                <input type="text" name="password" placeholder="Mot de passe" required>
                <button type="submit" class="btn-login">Vérifier Connexion</button>
            </form>
        </div>

    </div>

    <h2>📊 État de la Base de Données</h2>
    
    <div class="grid">
        <div class="card">
            <h4>Liste Users (<?= count($users) ?>)</h4>
            <table>
                <tr><th>ID</th><th>User</th><th>Email</th></tr>
                <?php foreach($users as $u): ?>
                <tr><td><?= $u['id'] ?></td><td><?= htmlspecialchars($u['username']) ?></td><td><?= htmlspecialchars($u['email']) ?></td></tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="card">
            <h4>Liste Projets (<?= count($projects) ?>)</h4>
            <table>
                <tr><th>ID</th><th>Titre</th><th>Status</th><th>Owner</th></tr>
                <?php foreach($projects as $p): ?>
                <tr>
                    <td><?= $p['id'] ?></td>
                    <td><?= htmlspecialchars($p['title']) ?></td>
                    <td><?= $p['status'] ?></td>
                    <td style="color:#00ffcc"><?= htmlspecialchars($p['owner']) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        
        <div class="card">
            <h4>Liste Membres (<?= count($members) ?>)</h4>
            <table>
                <tr><th>Projet</th><th>Membre</th><th>Role</th></tr>
                <?php foreach($members as $m): ?>
                <tr>
                    <td><?= htmlspecialchars($m['title']) ?></td>
                    <td><?= htmlspecialchars($m['username']) ?></td>
                    <td style="color:#9b59b6"><?= $m['role'] ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

</body>
</html>