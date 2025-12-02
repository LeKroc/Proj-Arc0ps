<?php
// Inclusion des fonctions de sécurité
require_once 'functions.php';

// Démarrage sécurisé de la session
secure_session_start();

// --- 1. CONFIGURATION & BDD ---
$paths = ['config/db.php', 'db.php', '../config/db.php'];
$db_found = false;
foreach ($paths as $p) {
    if (file_exists($p)) { require_once $p; $db_found = true; break; }
}
if (!$db_found) die("❌ Erreur : Impossible de trouver db.php.");

// Sécurité : Login requis
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$project_id = isset($_GET['id']) ? secure_int($_GET['id']) : 0;
if ($project_id === 0) die("❌ Erreur : Aucun projet sélectionné.");

// --- SECURITÉ RBAC (Role-Based Access Control) ---
// Vérifier le rôle de l'utilisateur DANS CE PROJET
$userId = secure_int($_SESSION['user_id']);
$stmtRole = $pdo->prepare("SELECT role FROM project_members WHERE project_id = ? AND user_id = ?");
$stmtRole->execute([$project_id, $userId]);
$userRole = $stmtRole->fetchColumn();

// Liste des rôles autorisés à modifier les settings
$allowed_roles = ['owner', 'admin'];

// Si l'utilisateur n'a pas le bon rôle, on affiche une page d'erreur "Accès Refusé"
if (!in_array($userRole, $allowed_roles)) {
    // On inclut le CSS pour que la page d'erreur soit jolie
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Accès Refusé</title>
        <link rel="stylesheet" href="style-dashboard.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            body { display: flex; align-items: center; justify-content: center; height: 100vh; background: #1e1e2f; color: white; }
            .error-card { text-align: center; padding: 40px; background: #27293d; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.5); max-width: 500px; }
            .error-icon { font-size: 4rem; color: #e74c3c; margin-bottom: 20px; }
            .btn-back { background: #ba54f5; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; font-weight: bold; display: inline-block; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class="error-card">
            <div class="error-icon"><i class="fas fa-lock"></i></div>
            <h2>Accès Refusé</h2>
            <p style="color:#aaa; margin: 15px 0;">Vous n'avez pas les droits suffisants pour accéder aux paramètres de ce projet.</p>
            <p style="font-size:0.9rem;">Votre rôle actuel : <span class="status-badge" style="background:#444;"><?= htmlspecialchars($userRole ?: 'Aucun') ?></span></p>
            <a href="avancement.php?id=<?= $project_id ?>" class="btn-back">Retour au projet</a>
        </div>
    </body>
    </html>
    <?php
    exit; // On arrête tout ici, le reste du fichier ne sera pas exécuté
}

$message = "";
$msg_type = ""; 

// Helpers
function recalculateProgressInsideSettings($pdo, $pid) {
    $stmt = $pdo->prepare("SELECT SUM(weight) FROM project_objectives WHERE project_id = ? AND is_done = 1");
    $stmt->execute([$pid]);
    $progress = (int)$stmt->fetchColumn();
    if ($progress > 100) $progress = 100;
    $stmtUpd = $pdo->prepare("UPDATE projects SET progression = ? WHERE id = ?");
    $stmtUpd->execute([$progress, $pid]);
}

function delete_folder_recursive($dir) {
    if (!is_dir($dir)) return;
    $files = array_diff(scandir($dir), array('.','..'));
    foreach ($files as $file) {
        (is_dir("$dir/$file")) ? delete_folder_recursive("$dir/$file") : unlink("$dir/$file");
    }
    return rmdir($dir);
}

// --- 2. TRAITEMENT DES FORMULAIRES (POST) ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Protection CSRF
    csrf_protect();
    
    // A. UPDATE INFOS GENERALES (AVEC UPLOAD IMAGE)
    if (isset($_POST['update_general'])) {
        $title = clean_input($_POST['title']);
        $desc  = clean_input($_POST['description']);
        
        // Gestion Image (Si nouvelle image envoyée)
        $imageUpdateSQL = "";
        $params = ['t' => $title, 'd' => $desc, 'id' => $project_id];

        if (isset($_FILES['banner_file']) && $_FILES['banner_file']['error'] === 0) {
            $validation = validate_file_upload($_FILES['banner_file'], ['jpg', 'jpeg', 'png', 'gif', 'webp'], 5242880);
            
            if ($validation['success']) {
                $uploadDir = 'assets/imageProject/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                
                $destPath = $uploadDir . $validation['safe_name'];
                
                if (move_uploaded_file($_FILES['banner_file']['tmp_name'], $destPath)) {
                    $imageUpdateSQL = ", image_url = :img";
                    $params['img'] = $destPath;
                } else {
                    log_security_event("Échec upload bannière projet {$project_id}");
                }
            } else {
                $message = $validation['message'];
                $msg_type = "error";
                log_security_event("Upload invalide bannière projet {$project_id}: " . $validation['message']);
            }
        }

        if (empty($msg_type)) { // Si pas d'erreur d'image
            $sql = "UPDATE projects SET title = :t, description = :d $imageUpdateSQL WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute($params)) {
                $message = "Informations mises à jour.";
                $msg_type = "success";
            }
        }
    }

    // B. GESTION OBJECTIFS (AJOUT)
    if (isset($_POST['add_objective'])) {
        $text = trim($_POST['obj_text']);
        $weight = (int)$_POST['obj_weight'];

        // Vérification Total Poids
        $stmtSum = $pdo->prepare("SELECT SUM(weight) FROM project_objectives WHERE project_id = ?");
        $stmtSum->execute([$project_id]);
        $currentSum = (int)$stmtSum->fetchColumn();

        if (($currentSum + $weight) > 100) {
            $message = "Erreur : Le total des pourcentages ne peut pas dépasser 100% (Actuel: $currentSum%).";
            $msg_type = "error";
        } else {
            $stmt = $pdo->prepare("INSERT INTO project_objectives (project_id, text, weight) VALUES (?, ?, ?)");
            $stmt->execute([$project_id, $text, $weight]);
            $message = "Objectif ajouté.";
            $msg_type = "success";
            recalculateProgressInsideSettings($pdo, $project_id);
        }
    }

    // C. GESTION OBJECTIFS (SUPPRESSION)
    if (isset($_POST['delete_objective_id'])) {
        $oid = (int)$_POST['delete_objective_id'];
        $stmt = $pdo->prepare("DELETE FROM project_objectives WHERE id = ? AND project_id = ?");
        $stmt->execute([$oid, $project_id]);
        $message = "Objectif supprimé.";
        $msg_type = "success";
        recalculateProgressInsideSettings($pdo, $project_id);
    }

    // D. GESTION NOTES (SUPPRESSION JSON)
    if (isset($_POST['delete_note_id'])) {
        $noteIdToDelete = $_POST['delete_note_id'];
        $noteFile = 'assets/notes/notes_' . $project_id . '.json';
        
        if (file_exists($noteFile)) {
            $currentNotes = json_decode(file_get_contents($noteFile), true) ?? [];
            // On filtre pour garder ceux qui n'ont pas l'ID
            $newNotes = array_filter($currentNotes, function($n) use ($noteIdToDelete) {
                return $n['id'] !== $noteIdToDelete;
            });
            // Réindexation et sauvegarde
            file_put_contents($noteFile, json_encode(array_values($newNotes), JSON_PRETTY_PRINT));
            $message = "Note supprimée.";
            $msg_type = "success";
        }
    }

    // E. MEMBRES (AJOUT/SUPPRESSION - Code existant conservé)
    if (isset($_POST['add_member'])) { /* ... Code identique à avant ... */ 
        $username_to_add = clean_input(trim($_POST['new_member_name']));
        $role_to_assign  = clean_input(trim($_POST['new_member_role']));
        $stmtU = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmtU->execute([$username_to_add]);
        $userToAdd = $stmtU->fetch(PDO::FETCH_ASSOC);
        if ($userToAdd) {
            $stmtCheck = $pdo->prepare("SELECT project_id FROM project_members WHERE project_id = ? AND user_id = ?");
            $stmtCheck->execute([$project_id, $userToAdd['id']]);
            if ($stmtCheck->rowCount() == 0) {
                $stmtInsert = $pdo->prepare("INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, ?)");
                $stmtInsert->execute([$project_id, $userToAdd['id'], $role_to_assign]);
                $message = "Membre ajouté."; $msg_type = "success";
            } else { $message = "Déjà membre."; $msg_type = "error"; }
        } else { $message = "Utilisateur introuvable."; $msg_type = "error"; }
    }
    if (isset($_POST['remove_user_id'])) {
        $userIdToRemove = secure_int($_POST['remove_user_id']);
        $stmtDel = $pdo->prepare("DELETE FROM project_members WHERE user_id = ? AND project_id = ?"); 
        $stmtDel->execute([$userIdToRemove, $project_id]);
        $message = "Membre retiré."; $msg_type = "success";
    }

    // F. SUPPRESSION TOTALE
    if (isset($_POST['delete_total_project'])) {
        // ... (Code de suppression identique à l'étape 5 précédente) ...
        $stmtImg = $pdo->prepare("SELECT image_url FROM projects WHERE id = ?");
        $stmtImg->execute([$project_id]);
        $projData = $stmtImg->fetch(PDO::FETCH_ASSOC);
        $stmtDelProj = $pdo->prepare("DELETE FROM projects WHERE id = ?");
        if ($stmtDelProj->execute([$project_id])) {
            if (!empty($projData['image_url']) && file_exists($projData['image_url'])) unlink($projData['image_url']);
            $noteFile = 'assets/notes/notes_' . $project_id . '.json';
            if (file_exists($noteFile)) unlink($noteFile);
            $projectFilesDir = 'assets/project_files/' . $project_id;
            if (is_dir($projectFilesDir)) delete_folder_recursive($projectFilesDir);
            header("Location: dashboard.php"); exit;
        }
    }
}

// --- 3. RECUPERATION DONNEES ---
$stmt = $pdo->prepare("SELECT * FROM projects WHERE id = :id");
$stmt->execute(['id' => $project_id]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$project) die("Projet introuvable.");

// Membres
$sqlMembres = "SELECT pm.user_id, pm.role, u.username FROM project_members pm JOIN users u ON pm.user_id = u.id WHERE pm.project_id = :pid";
$stmtM = $pdo->prepare($sqlMembres); $stmtM->execute(['pid' => $project_id]);
$members = $stmtM->fetchAll(PDO::FETCH_ASSOC);

// Objectifs
$stmtObj = $pdo->prepare("SELECT * FROM project_objectives WHERE project_id = ? ORDER BY id DESC");
$stmtObj->execute([$project_id]);
$objectives = $stmtObj->fetchAll(PDO::FETCH_ASSOC);

// Notes JSON
$projectNotes = [];
$noteFile = 'assets/notes/notes_' . $project_id . '.json';
if (file_exists($noteFile)) $projectNotes = json_decode(file_get_contents($noteFile), true) ?? [];

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
        :root { --input-bg: #1e1e2f; --border-color: #2b3553; }
        .form-control { width: 100%; padding: 12px; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 6px; color: #fff; }
        .members-table, .obj-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .members-table th, .obj-table th { text-align: left; color: #888; padding: 10px; border-bottom: 1px solid #444; }
        .members-table td, .obj-table td { padding: 12px 10px; border-bottom: 1px solid #333; color: #fff; }
        .btn-remove { background: transparent; border: 1px solid #e74c3c; color: #e74c3c; padding: 5px 10px; border-radius: 4px; cursor: pointer; transition: 0.3s; }
        .btn-remove:hover { background: #e74c3c; color: white; }
        .preview-img { width: 100%; height: 150px; border-radius: 8px; object-fit: cover; margin-top: 10px; border: 1px solid #444; }
        .danger-zone { border: 1px solid #e74c3c; background: rgba(231, 76, 60, 0.05); }
        .danger-zone h3 { color: #e74c3c !important; border-bottom-color: #e74c3c !important; }
        .btn-danger { background-color: #e74c3c; color: white; border: none; width: 100%; padding: 12px; border-radius: 8px; font-weight: bold; cursor: pointer; }
    </style>
</head>
<body>

<div class="dashboard-container">
    
    <aside class="sidebar">
        <div class="logo"><i class="fas fa-cube"></i> Λrc0ps</div>
        <div class="user-profile">
            <?php 
                // 1. Image
                $displayImg = 'assets/PhotoProfile/default_avatar.png';
                $imgClass = 'avatar';
                if (isset($project) && !empty($project['image_url']) && file_exists($project['image_url'])) {
                    $displayImg = $project['image_url'];
                    $imgClass = 'project-avatar-sidebar'; 
                } elseif (isset($_SESSION['user_avatar']) && !empty($_SESSION['user_avatar'])) {
                     $displayImg = $_SESSION['user_avatar'];
                }
            ?>
            <img src="<?= htmlspecialchars($displayImg) ?>" class="<?= $imgClass ?>" alt="Icone">
            
            <div style="margin-top:10px;">
                <?php if(isset($project)): ?>
                    <h3 style="margin-bottom:5px; color:white; font-weight:bold;">
                        <?= htmlspecialchars_decode(substr($project['title'], 0, 20)) ?>
                    </h3>
                    <small style="color:#ba54f5; font-weight:bold;">
                        <?= htmlspecialchars($_SESSION['username']) ?>
                    </small>
                <?php else: ?>
                    <h3><?= htmlspecialchars($_SESSION['username']) ?></h3>
                <?php endif; ?>
            </div>
        </div>
        <nav class="menu">
            <a href="dashboard.php" class="menu-item"><i class="fas fa-home"></i> Dashboard</a>
            <a href="avancement.php?id=<?= $project_id ?>" class="menu-item"><i class="fas fa-folder-open"></i> Projet Actuel</a>
            <a href="#" class="menu-item active"><i class="fas fa-cog"></i> Settings</a>
        </nav>
        <div class="footer">© Corporation</div>
    </aside>
    
    <main class="main-content">
        
        <header class="header">
            <div class="welcome-section">
                <h1>Configuration</h1>
                <p class="time">ID: <?= $project_id ?></p>
            </div>
        </header>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="project-grid-detail"> 
            
            <div class="left-column">
                <div class="section-card">
                    <h3><i class="fas fa-edit"></i> Informations Générales</h3>
                    
                    <form method="POST" action="" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <input type="hidden" name="update_general" value="1">
                        
                        <div class="form-group">
                            <label>Nom du Projet</label>
                            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($project['title']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($project['description']) ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Bannière du projet</label>
                            
                            <?php if(!empty($project['image_url']) && file_exists($project['image_url'])): ?>
                                <div style="margin-bottom: 15px; position: relative;">
                                    <img src="<?= htmlspecialchars($project['image_url']) ?>" class="preview-img" style="width: 100%; height: 180px; object-fit: cover; border-radius: 10px; border: 1px solid #444;">
                                </div>
                            <?php endif; ?>

                            <input type="file" name="banner_file" id="banner_file_input" accept="image/*">
                            
                            <label for="banner_file_input" class="custom-file-upload">
                                <i class="fas fa-cloud-upload-alt"></i> Changer l'image de bannière
                            </label>
                            
                            <span id="file-chosen">Aucun fichier choisi</span>
                        </div>

                        <div style="text-align: right; margin-top: 20px;">
                            <button type="submit" class="btn-save"><i class="fas fa-save"></i> Enregistrer les modifications</button>
                        </div>
                    </form>
                </div>

                <div class="section-card">
                    <h3><i class="fas fa-tasks"></i> Gestion des Objectifs</h3>
                    
                    <form method="POST" action="" style="display: flex; gap: 10px; margin-bottom: 20px;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="add_objective" value="1">
                        <input type="text" name="obj_text" class="form-control" placeholder="Nouvel objectif" required style="flex:2;">
                        <input type="number" name="obj_weight" class="form-control" placeholder="%" min="1" max="100" required style="flex:1;">
                        <button type="submit" class="btn-save" style="width:auto; margin:0;"><i class="fas fa-plus"></i></button>
                    </form>

                    <table class="obj-table">
                        <thead><tr><th>Objectif</th><th>Poids</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php 
                            $totalWeight = 0;
                            if (count($objectives) > 0): 
                                foreach ($objectives as $obj): 
                                    $totalWeight += $obj['weight'];
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($obj['text']) ?></td>
                                    <td><?= $obj['weight'] ?>%</td>
                                    <td style="text-align: right;">
                                        <form method="POST" action="" onsubmit="return confirm('Supprimer ?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="delete_objective_id" value="<?= $obj['id'] ?>">
                                            <button type="submit" class="btn-remove"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; else: ?>
                                <tr><td colspan="3" style="text-align: center; color: #666;">Aucun objectif.</td></tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td style="font-weight:bold; color:var(--purple-main);">TOTAL</td>
                                <td style="font-weight:bold; <?= $totalWeight > 100 ? 'color:#e74c3c;' : 'color:#58d68d;' ?>"><?= $totalWeight ?>%</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="section-card danger-zone">
                    <h3><i class="fas fa-exclamation-triangle"></i> Zone de Danger</h3>
                    <p style="color:#aaa; font-size:0.9rem; margin-bottom:15px;">
                        Suppression définitive du projet, des fichiers et des notes.
                    </p>
                    <form method="POST" onsubmit="return confirm('Êtes-vous ABSOLUMENT SÛR ?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="delete_total_project" value="1">
                        <button type="submit" class="btn-danger"><i class="fas fa-trash-alt"></i> Supprimer définitivement</button>
                    </form>
                </div>
            </div>

            <div class="right-column">
                <div class="section-card">
                    <h3><i class="fas fa-users-cog"></i> Membres</h3>
                    <form method="POST" action="" style="margin-bottom: 20px; display:flex; gap:5px;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="add_member" value="1">
                        <input type="text" name="new_member_name" class="form-control" placeholder="Pseudo" required>
                        <input type="text" name="new_member_role" class="form-control" placeholder="Rôle" required>
                        <button type="submit" class="btn-save" style="width:auto; margin:0;"><i class="fas fa-plus"></i></button>
                    </form>
                    <table class="members-table">
                        <tbody>
                            <?php foreach ($members as $mem): ?>
                                <tr>
                                    <td><i class="fas fa-user-circle"></i> <?= htmlspecialchars($mem['username']) ?></td>
                                    <td><small><?= htmlspecialchars($mem['role']) ?></small></td>
                                    <td style="text-align: right;">
                                        <form method="POST" action="" onsubmit="return confirm('Retirer ?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="remove_user_id" value="<?= $mem['user_id'] ?>">
                                            <button type="submit" class="btn-remove"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="section-card">
                    <h3><i class="fas fa-sticky-note"></i> Gestion des Notes</h3>
                    <?php if(count($projectNotes) > 0): ?>
                        <div style="max-height: 400px; overflow-y: auto;">
                            <?php foreach ($projectNotes as $note): ?>
                                <div style="background: rgba(255,255,255,0.05); padding:10px; margin-bottom:10px; border-radius:5px; position:relative;">
                                    <small style="color:var(--purple-main); font-weight:bold;"><?= htmlspecialchars($note['auteur']) ?> - <?= $note['date'] ?></small>
                                    <p style="margin:5px 0; font-size:0.9rem;"><?= htmlspecialchars_decode(substr($note['contenu'], 0, 50)) ?>...</p>
                                    <form method="POST" style="position:absolute; top:5px; right:5px;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="delete_note_id" value="<?= $note['id'] ?>">
                                        <button type="submit" class="btn-remove" style="padding:2px 6px;"><i class="fas fa-times"></i></button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="color:#666;">Aucune note.</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </main>

</div>
<script>
    // Petit script pour afficher le nom du fichier sélectionné
    const actualBtn = document.getElementById('banner_file_input');
    const fileChosen = document.getElementById('file-chosen');

    if(actualBtn) {
        actualBtn.addEventListener('change', function(){
            fileChosen.textContent = this.files[0].name;
            fileChosen.style.color = '#58d68d'; // Vert pour confirmer
        });
    }
</script>
</body>
</html>