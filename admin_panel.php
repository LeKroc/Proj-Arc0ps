<?php
// ═══════════════════════════════════════════════════════════════════
//  PANEL ADMIN - ARCOPS v2.1
// ═══════════════════════════════════════════════════════════════════
//  Accès : admin / go_admin_1234!!
//  Gestion des utilisateurs et surveillance sécurité
// ═══════════════════════════════════════════════════════════════════

// Inclusion des fonctions de sécurité
require_once 'functions.php';

// Démarrage sécurisé de la session
secure_session_start();

// Connexion DB
$paths = ['config/db.php', 'db.php', '../config/db.php'];
$db_found = false;
foreach ($paths as $p) {
    if (file_exists($p)) { require_once $p; $db_found = true; break; }
}
if (!$db_found) die("❌ Erreur : Impossible de trouver db.php.");

// ═══════════════════════════════════════════════════════════════════
//  AUTHENTIFICATION ADMIN HARDCODÉE
// ═══════════════════════════════════════════════════════════════════

$ADMIN_USERNAME = 'admin';
$ADMIN_PASSWORD = 'go_admin_1234!!';

$loginError = "";

// Traitement de la connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login'])) {
    csrf_protect();
    
    $inputUser = clean_input($_POST['admin_username']);
    $inputPass = $_POST['admin_password'];
    
    if ($inputUser === $ADMIN_USERNAME && $inputPass === $ADMIN_PASSWORD) {
        $_SESSION['is_admin'] = true;
        $_SESSION['admin_logged_at'] = time();
        log_security_event("Connexion admin réussie depuis IP : " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $loginError = "Identifiants admin incorrects.";
        log_security_event("TENTATIVE DE CONNEXION ADMIN ÉCHOUÉE depuis IP : " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    }
}

// Déconnexion
if (isset($_GET['logout'])) {
    unset($_SESSION['is_admin']);
    unset($_SESSION['admin_logged_at']);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// ═══════════════════════════════════════════════════════════════════
//  AFFICHAGE LOGIN SI PAS CONNECTÉ
// ═══════════════════════════════════════════════════════════════════

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login - ArcOps</title>
        <link rel="icon" type="image/x-icon" href="assets/logo_Arc0ps.ico">
        <link rel="stylesheet" href="style-dashboard.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            body { 
                display: flex; align-items: center; justify-content: center; 
                min-height: 100vh; background: linear-gradient(135deg, #1e1e2f 0%, #27293d 100%); 
            }
            .admin-login-box {
                background: rgba(255,255,255,0.03); padding: 40px; border-radius: 15px; 
                box-shadow: 0 8px 32px rgba(0,0,0,0.5); max-width: 400px; width: 100%;
                border: 1px solid rgba(186, 84, 245, 0.3);
            }
            .admin-login-box h2 { 
                color: #ba54f5; text-align: center; margin-bottom: 30px; 
                font-size: 1.8rem; display: flex; align-items: center; justify-content: center; gap: 10px;
            }
            .form-group { margin-bottom: 20px; }
            .form-group label { color: #a9a9b3; display: block; margin-bottom: 8px; font-size: 0.9rem; }
            .form-group input {
                width: 100%; padding: 12px; background: #1e1e2f; border: 1px solid #444; 
                border-radius: 8px; color: white; font-size: 1rem;
            }
            .form-group input:focus { outline: none; border-color: #ba54f5; }
            .btn-admin-login {
                width: 100%; background: linear-gradient(135deg, #ba54f5 0%, #e74c3c 100%); 
                color: white; padding: 14px; border: none; border-radius: 8px; 
                font-weight: bold; cursor: pointer; font-size: 1rem; transition: 0.3s;
            }
            .btn-admin-login:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(186, 84, 245, 0.5); }
            .error-msg { 
                background: rgba(231, 76, 60, 0.1); color: #e74c3c; padding: 10px; 
                border-radius: 5px; margin-bottom: 15px; text-align: center; 
            }
            .back-link { text-align: center; margin-top: 20px; }
            .back-link a { color: #ba54f5; text-decoration: none; }
        </style>
    </head>
    <body>
        <div class="admin-login-box">
            <h2><i class="fas fa-shield-alt"></i> Admin Panel</h2>
            
            <?php if (!empty($loginError)): ?>
                <div class="error-msg"><i class="fas fa-exclamation-triangle"></i> <?= clean_output($loginError) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <?= csrf_field() ?>
                <input type="hidden" name="admin_login" value="1">
                
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Nom d'utilisateur</label>
                    <input type="text" name="admin_username" required autocomplete="off" autofocus>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Mot de passe</label>
                    <input type="password" name="admin_password" required>
                </div>
                
                <button type="submit" class="btn-admin-login">
                    <i class="fas fa-sign-in-alt"></i> Se connecter
                </button>
            </form>
            
            <div class="back-link">
                <a href="dashboard.php"><i class="fas fa-arrow-left"></i> Retour au dashboard</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ═══════════════════════════════════════════════════════════════════
//  DASHBOARD ADMIN (SI CONNECTÉ)
// ═══════════════════════════════════════════════════════════════════

// Récupération de tous les utilisateurs AVEC leurs projets
$allUsers = [];
try {
    $stmt = $pdo->prepare("SELECT id, username, email, bio, avatar_url, has_leaked, created_at FROM users ORDER BY created_at DESC");
    $stmt->execute();
    $allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Pour chaque utilisateur, récupérer ses projets
    foreach ($allUsers as &$user) {
        // Projets où il est OWNER
        $stmtOwned = $pdo->prepare("
            SELECT p.id, p.title, 'owner' as user_role 
            FROM projects p 
            WHERE p.owner_id = ?
        ");
        $stmtOwned->execute([$user['id']]);
        $ownedProjects = $stmtOwned->fetchAll(PDO::FETCH_ASSOC);
        
        // Projets où il est MEMBRE
        $stmtMember = $pdo->prepare("
            SELECT p.id, p.title, pm.role as user_role 
            FROM project_members pm
            JOIN projects p ON pm.project_id = p.id
            WHERE pm.user_id = ? AND pm.role != 'owner'
        ");
        $stmtMember->execute([$user['id']]);
        $memberProjects = $stmtMember->fetchAll(PDO::FETCH_ASSOC);
        
        // Fusion des deux listes
        $user['projects'] = array_merge($ownedProjects, $memberProjects);
    }
    
} catch (PDOException $e) {
    log_security_event("Erreur récupération users admin : " . $e->getMessage());
}

// Statistiques
$totalUsers = count($allUsers);
$leakedUsers = count(array_filter($allUsers, fn($u) => $u['has_leaked'] == 1));
$safeUsers = $totalUsers - $leakedUsers;

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - ArcOps</title>
    <link rel="icon" type="image/x-icon" href="assets/logo_Arc0ps.ico">
    <link rel="stylesheet" href="style-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-header {
            background: linear-gradient(135deg, #ba54f5 0%, #e74c3c 100%);
            padding: 20px 30px; border-radius: 10px; margin-bottom: 30px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .admin-header h1 { color: white; margin: 0; font-size: 1.8rem; }
        .btn-logout-admin {
            background: rgba(255,255,255,0.2); color: white; padding: 10px 20px;
            border-radius: 5px; text-decoration: none; font-weight: bold; transition: 0.3s;
        }
        .btn-logout-admin:hover { background: rgba(255,255,255,0.3); }
        
        .stats-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px; margin-bottom: 30px;
        }
        .stat-card {
            background: rgba(255,255,255,0.03); padding: 20px; border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.1); text-align: center;
        }
        .stat-card .number { font-size: 2.5rem; font-weight: bold; color: #ba54f5; }
        .stat-card .label { color: #a9a9b3; font-size: 0.9rem; margin-top: 5px; }
        
        .users-table {
            width: 100%; border-collapse: collapse; background: rgba(255,255,255,0.03);
            border-radius: 10px; overflow: hidden;
        }
        .users-table thead { background: rgba(186, 84, 245, 0.2); }
        .users-table th {
            padding: 15px; text-align: left; color: #ba54f5; font-weight: bold;
            border-bottom: 2px solid rgba(186, 84, 245, 0.5);
        }
        .users-table td {
            padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); color: #ccc;
        }
        .users-table tr:hover { background: rgba(255,255,255,0.05); }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
        .badge-leaked { 
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); 
            color: white; padding: 5px 10px; border-radius: 5px; font-size: 0.8rem; font-weight: bold;
        }
        .badge-safe { 
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%); 
            color: white; padding: 5px 10px; border-radius: 5px; font-size: 0.8rem; font-weight: bold;
        }
    </style>
</head>
<body style="background: #1e1e2f; color: white; padding: 30px; min-height: 100vh;">

    <div style="max-width: 1400px; margin: 0 auto;">
        
        <div class="admin-header">
            <h1><i class="fas fa-shield-alt"></i> Admin Panel - ArcOps</h1>
            <a href="?logout=1" class="btn-logout-admin"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="number"><?= $totalUsers ?></div>
                <div class="label"><i class="fas fa-users"></i> Utilisateurs Total</div>
            </div>
            <div class="stat-card">
                <div class="number" style="color: #2ecc71;"><?= $safeUsers ?></div>
                <div class="label"><i class="fas fa-check-circle"></i> Comptes Sécurisés</div>
            </div>
            <div class="stat-card">
                <div class="number" style="color: #e74c3c;"><?= $leakedUsers ?></div>
                <div class="label"><i class="fas fa-exclamation-triangle"></i> Comptes Compromis</div>
            </div>
        </div>
        
        <h2 style="color: #ba54f5; margin-bottom: 20px; font-size: 1.5rem;">
            <i class="fas fa-table"></i> Liste des Utilisateurs
        </h2>
        
        <table class="users-table">
            <thead>
                <tr>
                    <th>Avatar</th>
                    <th>Nom d'utilisateur</th>
                    <th>Email</th>
                    <th>Projets Liés</th>
                    <th>Statut Sécurité</th>
                    <th>Inscription</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($allUsers) > 0): ?>
                    <?php foreach ($allUsers as $user): ?>
                        <?php 
                            $avatarUrl = !empty($user['avatar_url']) && file_exists($user['avatar_url']) 
                                        ? $user['avatar_url'] : 'assets/PhotoProfile/default_avatar.png';
                        ?>
                        <tr>
                            <td>
                                <img src="<?= clean_output($avatarUrl) ?>" class="user-avatar" alt="Avatar">
                            </td>
                            <td>
                                <strong><?= clean_output($user['username']) ?></strong>
                            </td>
                            <td>
                                <i class="fas fa-envelope"></i> <?= clean_output($user['email']) ?>
                            </td>
                            <td style="max-width: 350px;">
                                <?php if (count($user['projects']) > 0): ?>
                                    <div style="display: flex; flex-direction: column; gap: 5px;">
                                        <?php foreach ($user['projects'] as $proj): ?>
                                            <div style="display: flex; align-items: center; gap: 8px; padding: 5px 10px; background: rgba(255,255,255,0.05); border-radius: 5px;">
                                                <?php if ($proj['user_role'] === 'owner'): ?>
                                                    <i class="fas fa-crown" style="color: #f1c40f;" title="Propriétaire"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-user" style="color: #3498db;" title="Membre (<?= clean_output($proj['user_role']) ?>)"></i>
                                                <?php endif; ?>
                                                <span style="font-size: 0.85rem;">
                                                    <?= clean_output(strlen($proj['title']) > 30 ? substr($proj['title'], 0, 30) . '...' : $proj['title']) ?>
                                                </span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <small style="color: #666; font-style: italic;">Aucun projet</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['has_leaked'] == 1): ?>
                                    <span class="badge-leaked"><i class="fas fa-skull-crossbones"></i> LEAKED</span>
                                <?php else: ?>
                                    <span class="badge-safe"><i class="fas fa-shield-alt"></i> SAFE</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small style="color: #888;">
                                    <i class="far fa-calendar"></i> <?= date('d/m/Y', strtotime($user['created_at'])) ?>
                                </small>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px; color: #666;">
                            <i class="fas fa-inbox"></i> Aucun utilisateur inscrit.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div style="margin-top: 30px; text-align: center; color: #666;">
            <p><i class="fas fa-info-circle"></i> Session admin ouverte depuis <?= date('H:i', $_SESSION['admin_logged_at']) ?></p>
            <a href="dashboard.php" style="color: #ba54f5; text-decoration: none;">
                <i class="fas fa-arrow-left"></i> Retour au dashboard utilisateur
            </a>
        </div>
        
    </div>

</body>
</html>
