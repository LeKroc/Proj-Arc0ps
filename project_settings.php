<?php
session_start();

// --- 1. CONFIGURATION & BDD ---
$paths = ['config/db.php', 'db.php', '../config/db.php'];
$db_found = false;
foreach ($paths as $p) {
    if (file_exists($p)) { require_once $p; $db_found = true; break; }
}
if (!$db_found) die("❌ Erreur : Impossible de trouver db.php.");

// Sécurité : Vérifier si connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = "";
$msg_type = ""; // success ou error

// --- 2. TRAITEMENT DES FORMULAIRES (POST) ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // A. MISE A JOUR DES INFOS GENERALES
    if (isset($_POST['update_general'])) {
        $title = $_POST['title'];
        $desc  = $_POST['description'];
        $img   = $_POST['image_url'];

        $sql = "UPDATE projects SET title = :t, description = :d, image_url = :i WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute(['t' => $title, 'd' => $desc, 'i' => $img, 'id' => $project_id])) {
            $message = "Informations mises à jour avec succès.";
            $msg_type = "success";
        } else {
            $message = "Erreur lors de la mise à jour.";
            $msg_type = "error";
        }
    }

    // B. AJOUT D'UN MEMBRE
    if (isset($_POST['add_member'])) {
        $username_to_add = trim($_POST['new_member_name']);
        $role_to_assign  = trim($_POST['new_member_role']);

        // 1. Trouver l'ID du user
        $stmtU = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmtU->execute([$username_to_add]);
        $userToAdd = $stmtU->fetch(PDO::FETCH_ASSOC);

        if ($userToAdd) {
            // 2. Vérifier s'il n'est pas déjà dans le projet
            $stmtCheck = $pdo->prepare("SELECT id FROM project_members WHERE project_id = ? AND user_id = ?");
            $stmtCheck->execute([$project_id, $userToAdd['id']]);
            
            if ($stmtCheck->rowCount() == 0) {
                // 3. Insérer
                $stmtInsert = $pdo->prepare("INSERT INTO project_members (project_id, user_id, role_project) VALUES (?, ?, ?)");
                $stmtInsert->execute([$project_id, $userToAdd['id'], $role_to_assign]);
                $message = "Membre ajouté : " . htmlspecialchars($username_to_add);
                $msg_type = "success";
            } else {
                $message = "Ce membre fait déjà partie du projet.";
                $msg_type = "error";
            }
        } else {
            $message = "Utilisateur introuvable.";
            $msg_type = "error";
        }
    }

    // C. SUPPRESSION D'UN MEMBRE
    if (isset($_POST['remove_member_id'])) {
        $memId = $_POST['remove_member_id'];
        $stmtDel = $pdo->prepare("DELETE FROM project_members WHERE id = ? AND project_id = ?"); // Sécurité: on vérifie le project_id
        $stmtDel->execute([$memId, $project_id]);
        $message = "Membre retiré du projet.";
        $msg_type = "success";
    }
}

// --- 3. RECUPERATION DES DONNEES ---

// Infos Projet
$stmt = $pdo->prepare("SELECT * FROM projects WHERE id = :id");
$stmt->execute(['id' => $project_id]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) die("Projet introuvable.");

// Liste des Membres
$sqlMembres = "SELECT pm.id as link_id, pm.role_project, u.username, u.email 
               FROM project_members pm 
               JOIN users u ON pm.user_id = u.id 
               WHERE pm.project_id = :pid";
$stmtM = $pdo->prepare($sqlMembres);
$stmtM->execute(['pid' => $project_id]);
$members = $stmtM->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings : <?= htmlspecialchars($project['title']) ?></title>
    
    <link rel="icon" type="image/x-icon" href="assets/logo_Arc0ps.ico">
    <link rel="stylesheet" href="style-dashboard.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Styles spécifiques pour le formulaire (Dark Theme) */
        :root {
            --input-bg: #1e1e2f;
            --border-color: #2b3553;
        }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; color: #ccc; margin-bottom: 8px; font-size: 0.9em; }
        
        .form-control {
            width: 100%;
            padding: 12px;
            background-color: var(--input-bg);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            color: #fff;
            outline: none;
            transition: border-color 0.3s;
        }
        .form-control:focus { border-color: var(--primary-color); }
        
        textarea.form-control { resize: vertical; min-height: 100px; }

        /* Table des membres */
        .members-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .members-table th { text-align: left; color: #888; padding: 10px; border-bottom: 1px solid #444; }
        .members-table td { padding: 12px 10px; border-bottom: 1px solid #333; color: #fff; }
        .members-table tr:last-child td { border-bottom: none; }
        
        .btn-remove { 
            background: transparent; border: 1px solid #e74c3c; color: #e74c3c; 
            padding: 5px 10px; border-radius: 4px; cursor: pointer; transition: 0.3s; 
        }
        .btn-remove:hover { background: #e74c3c; color: white; }

        .btn-save {
            background-color: var(--primary-color); color: white; border: none;
            padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold;
        }
        .btn-save:hover { background-color: #a450e0; }

        .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .alert-success { background-color: rgba(88, 214, 141, 0.2); border: 1px solid #58d68d; color: #58d68d; }
        .alert-error { background-color: rgba(231, 76, 60, 0.2); border: 1px solid #e74c3c; color: #e74c3c; }

        .preview-img { max-width: 100px; max-height: 60px; border-radius: 4px; object-fit: cover; margin-top: 10px; border: 1px solid #444; }
    </style>
</head>
<body>

<div class="dashboard-container">
    
    <aside class="sidebar">
        <div class="logo"><i class="fas fa-cube"></i> Λrc0ps</div>
        <div class="user-profile">
            <div class="avatar"></div>
            <h3><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></h3>
        </div>
        <nav class="menu">
            <a href="dashboard.php" class="menu-item"><i class="fas fa-home"></i> Dashboard</a>
            <a href="project_details.php?id=<?= $project_id ?>" class="menu-item"><i class="fas fa-folder-open"></i> Projet Actuel</a>
            <a href="#" class="menu-item active"><i class="fas fa-cog"></i> Settings</a>
        </nav>
        <div class="footer">© Corporation</div>
    </aside>
    
    <main class="main-content">
        
        <header class="header">
            <div class="welcome-section">
                <h1>Configuration du Projet</h1>
                <p class="time">ID: <?= $project_id ?></p>
            </div>
        </header>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $msg_type ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="project-grid">
            
            <div class="section-card">
                <h3><i class="fas fa-edit"></i> Informations Générales</h3>
                
                <form method="POST" action="">
                    <input type="hidden" name="update_general" value="1">
                    
                    <div class="form-group">
                        <label>Nom du Projet</label>
                        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($project['title']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control"><?= htmlspecialchars($project['description']) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>URL de l'image (Bannière)</label>
                        <input type="text" name="image_url" class="form-control" value="<?= htmlspecialchars($project['image_url'] ?? '') ?>" placeholder="https://...">
                        <?php if(!empty($project['image_url'])): ?>
                            <img src="<?= htmlspecialchars($project['image_url']) ?>" class="preview-img" alt="Aperçu">
                        <?php endif; ?>
                    </div>

                    <div style="text-align: right;">
                        <button type="submit" class="btn-save"><i class="fas fa-save"></i> Enregistrer</button>
                    </div>
                </form>
            </div>

            <div class="section-card">
                <h3><i class="fas fa-users-cog"></i> Gestion des Membres</h3>
                
                <form method="POST" action="" style="background: rgba(255,255,255,0.05); padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                    <input type="hidden" name="add_member" value="1">
                    <p style="margin-top:0; font-weight:bold; font-size:0.9em;">Ajouter un collaborateur</p>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" name="new_member_name" class="form-control" placeholder="Nom d'utilisateur exact" required>
                        <input type="text" name="new_member_role" class="form-control" placeholder="Rôle (ex: Dev)" required>
                        <button type="submit" class="btn-save" style="white-space: nowrap;"><i class="fas fa-plus"></i> Ajouter</button>
                    </div>
                </form>

                <table class="members-table">
                    <thead>
                        <tr>
                            <th>Utilisateur</th>
                            <th>Rôle</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($members) > 0): ?>
                            <?php foreach ($members as $mem): ?>
                                <tr>
                                    <td>
                                        <i class="fas fa-user-circle"></i> <?= htmlspecialchars($mem['username']) ?>
                                    </td>
                                    <td>
                                        <span style="background: #444; padding: 2px 8px; border-radius: 10px; font-size: 0.8em;">
                                            <?= htmlspecialchars($mem['role_project']) ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right;">
                                        <form method="POST" action="" onsubmit="return confirm('Retirer ce membre ?');">
                                            <input type="hidden" name="remove_member_id" value="<?= $mem['link_id'] ?>">
                                            <button type="submit" class="btn-remove"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" style="text-align: center; color: #666;">Aucun membre dans l'équipe.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

            </div>

        </div>

    </main>

</div>

</body>
</html>
