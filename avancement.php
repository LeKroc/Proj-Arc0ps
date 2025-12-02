<?php
session_start();

// 1. Inclusion DB
$paths = ['config/db.php', 'db.php', '../config/db.php'];
$db_found = false;
foreach ($paths as $p) {
    if (file_exists($p)) { require_once $p; $db_found = true; break; }
}
if (!$db_found) die("❌ Erreur : Impossible de trouver db.php.");

// Sécurité
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// --- CONFIGURATION FICHIERS & NOTES ---
$uploadDir = 'assets/project_files/' . $project_id . '/';
$notesDir  = 'assets/notes/';
$notesFile = $notesDir . 'notes_' . $project_id . '.json';

$maxFileSize = 3 * 1024 * 1024; // 3 Mo
$maxFilesCount = 10;

// Création des dossiers s'ils n'existent pas
if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }
if (!is_dir($notesDir))  { mkdir($notesDir, 0777, true); }

// --- FONCTION DE RECALCUL DE LA PROGRESSION ---
function recalculateProgress($pdo, $pid) {
    $stmt = $pdo->prepare("SELECT SUM(weight) FROM project_objectives WHERE project_id = ? AND is_done = 1");
    $stmt->execute([$pid]);
    $progress = (int)$stmt->fetchColumn();

    if ($progress > 100) $progress = 100;
    if ($progress < 0) $progress = 0;

    $stmtUpd = $pdo->prepare("UPDATE projects SET progression = ? WHERE id = ?");
    $stmtUpd->execute([$progress, $pid]);
}

// --- TRAITEMENT DES FORMULAIRES ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. OBJECTIFS (AJOUT)
    if (isset($_POST['new_objective_text'])) {
        $text = trim($_POST['new_objective_text']);
        if (!empty($text)) {
            $stmt = $pdo->prepare("INSERT INTO project_objectives (project_id, text) VALUES (?, ?)");
            $stmt->execute([$project_id, $text]);
            recalculateProgress($pdo, $project_id);
        }
        header("Location: avancement.php?id=" . $project_id);
        exit;
    }

    // 2. OBJECTIFS (TOGGLE)
    if (isset($_POST['toggle_objective_id'])) {
        $oid = (int)$_POST['toggle_objective_id'];
        $stmt = $pdo->prepare("UPDATE project_objectives SET is_done = NOT is_done WHERE id = ? AND project_id = ?");
        $stmt->execute([$oid, $project_id]);
        recalculateProgress($pdo, $project_id);
        header("Location: avancement.php?id=" . $project_id);
        exit;
    }

    // 3. FICHIERS (UPLOAD)
    if (isset($_FILES['new_file']) && $_FILES['new_file']['error'] === 0) {
        $existingFiles = array_diff(scandir($uploadDir), array('.', '..'));
        
        if (count($existingFiles) >= $maxFilesCount) {
            $error_msg = "Limite de 10 fichiers atteinte.";
        } elseif ($_FILES['new_file']['size'] > $maxFileSize) {
            $error_msg = "Fichier trop lourd (Max 3Mo).";
        } else {
            $fileName = $_FILES['new_file']['name'];
            $newFileName = time() . '_' . preg_replace('/[^a-zA-Z0-9\._-]/', '', $fileName);
            
            if (move_uploaded_file($_FILES['new_file']['tmp_name'], $uploadDir . $newFileName)) {
                header("Location: avancement.php?id=" . $project_id);
                exit;
            }
        }
    }

    // 4. FICHIERS (SUPPRESSION)
    if (isset($_POST['delete_file_name'])) {
        $fileToDelete = basename($_POST['delete_file_name']);
        $filePath = $uploadDir . $fileToDelete;
        if (file_exists($filePath)) { unlink($filePath); }
        header("Location: avancement.php?id=" . $project_id);
        exit;
    }

    // 5. NOTES (AJOUT JSON)
    if (isset($_POST['new_note_content'])) {
        $content = htmlspecialchars(trim($_POST['new_note_content']));
        
        if (!empty($content)) {
            $currentNotes = [];
            if (file_exists($notesFile)) {
                $jsonContent = file_get_contents($notesFile);
                $currentNotes = json_decode($jsonContent, true) ?? [];
            }

            $newNote = [
                'id' => uniqid(),
                'date' => date('d/m/Y H:i'),
                'auteur' => $_SESSION['username'], 
                'contenu' => $content
            ];

            array_unshift($currentNotes, $newNote);
            file_put_contents($notesFile, json_encode($currentNotes, JSON_PRETTY_PRINT));
        }
        header("Location: avancement.php?id=" . $project_id);
        exit;
    }
}

// --- RECUPERATION DES DONNEES ---

// 1. Projet
$sqlProject = "SELECT * FROM projects WHERE id = :id";
$stmt = $pdo->prepare($sqlProject);
$stmt->execute(['id' => $project_id]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$project) die("❌ Projet introuvable.");

// 2. Membres
$sqlMembers = "SELECT u.username, u.avatar_url, u.bio, pm.role 
               FROM project_members pm 
               JOIN users u ON pm.user_id = u.id 
               WHERE pm.project_id = ?";
$stmtM = $pdo->prepare($sqlMembers);
$stmtM->execute([$project_id]);
$real_members = $stmtM->fetchAll(PDO::FETCH_ASSOC);

// 3. Objectifs
$sqlObj = "SELECT * FROM project_objectives WHERE project_id = ? ORDER BY is_done ASC, id DESC";
$stmtObj = $pdo->prepare($sqlObj);
$stmtObj->execute([$project_id]);
$objectives = $stmtObj->fetchAll(PDO::FETCH_ASSOC);

// 4. Fichiers
$projectFiles = [];
if (is_dir($uploadDir)) {
    $scanned_files = array_diff(scandir($uploadDir), array('.', '..'));
    foreach($scanned_files as $file) { $projectFiles[] = $file; }
}

// 5. Notes
$projectNotes = [];
if (file_exists($notesFile)) {
    $jsonContent = file_get_contents($notesFile);
    $projectNotes = json_decode($jsonContent, true) ?? [];
}

// Helpers
function get_status_badge($status) {
    $s = strtolower($status);
    $cls = 'status-primary'; 
    if (strpos($s, 'termin') !== false) $cls = 'status-success';
    if (strpos($s, 'attent') !== false) $cls = 'status-warning';
    if (strpos($s, 'bloq') !== false)   $cls = 'status-error';
    return '<span class="status-badge '.$cls.'">'.htmlspecialchars($status).'</span>';
}

function get_file_icon($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) return '<i class="far fa-file-image" style="color:#ba54f5;"></i>';
    if (in_array($ext, ['pdf'])) return '<i class="far fa-file-pdf" style="color:#e74c3c;"></i>';
    if (in_array($ext, ['zip', 'rar', '7z'])) return '<i class="far fa-file-archive" style="color:#f1c40f;"></i>';
    if (in_array($ext, ['php', 'html', 'css', 'js', 'py'])) return '<i class="far fa-file-code" style="color:#58d68d;"></i>';
    return '<i class="far fa-file" style="color:#999;"></i>';
}

$bgImage = 'assets/default_banner.jpg';
if (!empty($project['image_url']) && file_exists($project['image_url'])) {
    $bgImage = htmlspecialchars($project['image_url']);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projet : <?= htmlspecialchars_decode($project['title']) ?></title>
    <link rel="icon" type="image/x-icon" href="assets/logo_Arc0ps.ico">
    <link rel="stylesheet" href="style-dashboard.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .objective-item {
            display: flex; align-items: center; padding: 10px;
            background: rgba(255,255,255,0.03); border-radius: 8px;
            margin-bottom: 8px; border: 1px solid transparent; transition: 0.3s;
        }
        .objective-item:hover { background: rgba(255,255,255,0.06); }
        .objective-item.done { opacity: 0.5; text-decoration: line-through; }
        .check-form { margin-right: 12px; display: flex; align-items: center; }
        .custom-checkbox { width: 20px; height: 20px; cursor: pointer; accent-color: #58d68d; }
        .obj-input-group { display: flex; gap: 10px; margin-top: 15px; }
        .obj-input { flex: 1; background: #1e1e2f; border: 1px solid #444; color: white; padding: 8px; border-radius: 5px; }
        
        .upload-area {
            border: 2px dashed #444; padding: 15px; text-align: center;
            border-radius: 8px; margin-top: 15px; cursor: pointer; transition: 0.3s;
        }
        .upload-area:hover { border-color: #ba54f5; background: rgba(186, 84, 245, 0.05); }
        .file-delete-btn { background: none; border: none; color: #e74c3c; cursor: pointer; opacity: 0.7; }
        .file-delete-btn:hover { opacity: 1; }

        .note-textarea {
            width: 100%; background: #1e1e2f; border: 1px solid #444; 
            color: white; padding: 10px; border-radius: 8px; resize: vertical; min-height: 80px;
            font-family: inherit; margin-bottom: 10px;
        }
        .note-textarea:focus { outline: none; border-color: #ba54f5; }

        /* --- STYLE BARRE PROGRESSION VERTE --- */
        .progress-bar-container {
            width: 100%;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            height: 22px;
            overflow: hidden;
            margin-top: 5px;
        }
        
        .progress-fill {
            height: 100%;
            background-color: #2ecc71 !important; /* VERT */
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 0.75rem;
            font-weight: bold;
            transition: width 0.6s ease;
            box-shadow: 0 0 10px rgba(46, 204, 113, 0.4);
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    
    <aside class="sidebar">
        <div class="logo"><i class="fas fa-shopping-cart"></i> Λrc0ps</div>
        <div class="user-profile">
            <?php 
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
            <a href="#" class="menu-item active"><i class="fas fa-folder-open"></i> Projet Actuel</a>
            <a href="project_settings.php?id=<?= $project_id ?>" class="menu-item"><i class="fas fa-cog"></i> Settings</a>
        </nav>
        <div class="footer">© Corporation</div>
    </aside>
    
    <main class="main-content">
        
        <div class="project-banner">
            <div class="project-banner-bg" style="background-image: url('<?= $bgImage ?>');"></div>
            <div class="project-banner-content">
                <div style="display:flex; justify-content:space-between; align-items:end;">
                    <div>
                        <h1><?= htmlspecialchars_decode($project['title']) ?></h1>
                        <div style="display:flex; gap:10px; align-items:center;">
                            <?= get_status_badge($project['status']) ?>
                            <span style="opacity:0.7; font-size:0.9rem;">
                                <i class="far fa-calendar"></i> Créé le <?= date('d/m/Y', strtotime($project['created_at'])) ?>
                            </span>
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <span style="font-size:2.5rem; font-weight:bold; color: var(--accent-green);">
                            <?= $project['progression'] ?>%
                        </span>
                        <div style="font-size:0.8rem; opacity:0.7;">COMPLÉTÉ</div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($error_msg)): ?>
            <div class="alert alert-error" style="margin-bottom:20px;"><?= $error_msg ?></div>
        <?php endif; ?>

        <div class="project-details-grid">
            
            <div class="left-column">
                
                <div class="detail-card">
                    <h3><i class="fas fa-chart-line"></i> Avancement Global</h3>
                    <div class="progress-bar-container">
                        <div class="progress-fill" style="width: <?= $project['progression'] ?>%;">
                            <?= $project['progression'] ?>%
                        </div>
                    </div>
                </div>

                <div class="detail-card">
                    <h3><i class="fas fa-align-left"></i> Description</h3>
                    <p style="line-height: 1.6; color:#ccc;">
                        <?= nl2br(htmlspecialchars_decode($project['description'])) ?>
                    </p>
                </div>

                <div class="detail-card">
                    <h3><i class="fas fa-tasks"></i> Objectifs du Sprint</h3>
                    <div class="objectives-list">
                        <?php if(count($objectives) > 0): ?>
                            <?php foreach($objectives as $obj): ?>
                                <div class="objective-item <?= $obj['is_done'] ? 'done' : '' ?>">
                                    <form method="POST" class="check-form">
                                        <input type="hidden" name="toggle_objective_id" value="<?= $obj['id'] ?>">
                                        <input type="checkbox" class="custom-checkbox" onchange="this.form.submit()" <?= $obj['is_done'] ? 'checked' : '' ?>>
                                    </form>
                                    <div style="flex:1;">
                                        <span><?= htmlspecialchars($obj['text']) ?></span>
                                        <?php if($obj['weight'] > 0): ?>
                                            <span style="font-size:0.7em; color:#888; margin-left:5px;">(<?= $obj['weight'] ?>%)</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color:#666; font-style:italic;">Aucun objectif défini.</p>
                        <?php endif; ?>
                    </div>
                    <form method="POST" class="obj-input-group">
                        <input type="text" name="new_objective_text" class="obj-input" placeholder="Ajout rapide..." required>
                        <button type="submit" class="btn-action" style="width:auto; margin:0;"><i class="fas fa-plus"></i></button>
                    </form>
                </div>

                <div class="detail-card">
                    <h3><i class="fas fa-sticky-note"></i> Notes de Suivi</h3>
                    <form method="POST" style="margin-bottom: 20px;">
                        <textarea name="new_note_content" class="note-textarea" placeholder="Écrire une note..." required></textarea>
                        <button type="submit" class="btn-action">Ajouter la note</button>
                    </form>
                    <?php if (count($projectNotes) > 0): ?>
                        <?php foreach ($projectNotes as $note): ?>
                            <div class="list-item">
                                <div>
                                    <span style="color:var(--purple-main); font-weight:bold;">
                                        <?= htmlspecialchars($note['auteur']) ?>
                                    </span>
                                    <p style="margin:5px 0 0 0; font-size:0.9rem; white-space: pre-wrap;"><?= htmlspecialchars_decode($note['contenu']) ?></p>
                                </div>
                                <small style="opacity:0.5; font-size:0.8rem;"><?= htmlspecialchars($note['date']) ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color:#666;">Aucune note.</p>
                    <?php endif; ?>
                </div>

            </div>

            <div class="right-column">
                
                <div class="detail-card">
                    <h3><i class="fas fa-users"></i> Équipe</h3>
                    <?php if(count($real_members) > 0): ?>
                        <?php foreach ($real_members as $member): ?>
                            <?php 
                                $memAvatar = !empty($member['avatar_url']) && file_exists($member['avatar_url']) 
                                            ? $member['avatar_url'] : 'assets/PhotoProfile/default_avatar.png';
                            ?>
                            <div class="list-item">
                                <div class="user-info">
                                    <img src="<?= htmlspecialchars($memAvatar) ?>" class="mini-avatar" alt="Avatar">
                                    <div>
                                        <span style="display:block; line-height:1;"><?= htmlspecialchars($member['username']) ?></span>
                                        <small style="font-size:0.85em; color:#999;">
                                            <?= htmlspecialchars(substr($member['bio'] ?? 'Pas de bio', 0, 30)) . (strlen($member['bio']) > 30 ? '...' : '') ?>
                                        </small>
                                    </div>
                                </div>
                                <span class="status-badge" style="background:rgba(255,255,255,0.1);"><?= htmlspecialchars($member['role']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color:#666;">Aucun membre assigné.</p>
                    <?php endif; ?>
                    <a href="project_settings.php?id=<?= $project_id ?>" class="btn-action" style="margin-top:15px; text-decoration:none;">Gérer l'équipe</a>
                </div>

                <div class="detail-card">
                    <h3><i class="fas fa-paperclip"></i> Fichiers <small style="font-size:0.6em; margin-left:auto; color:#666;"><?= count($projectFiles) ?>/10</small></h3>
                    <?php if (count($projectFiles) > 0): ?>
                        <?php foreach ($projectFiles as $file): ?>
                            <div class="list-item">
                                <a href="<?= $uploadDir . $file ?>" download style="text-decoration:none; color:inherit; display:flex; align-items:center; gap:10px; flex:1; overflow:hidden;">
                                    <?= get_file_icon($file) ?>
                                    <span style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars(substr($file, 11)) ?></span>
                                </a>
                                <form method="POST" onsubmit="return confirm('Supprimer ce fichier ?');">
                                    <input type="hidden" name="delete_file_name" value="<?= $file ?>">
                                    <button type="submit" class="file-delete-btn"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color:#666; font-style:italic; font-size:0.9rem;">Aucun fichier.</p>
                    <?php endif; ?>
                    <br>
                    <br>
                    <form method="POST" enctype="multipart/form-data">
                        <label class="upload-area">
                            <input type="file" name="new_file" style="display:none;" onchange="this.form.submit()">
                            <i class="fas fa-cloud-upload-alt"></i> Ajouter un fichier
                        </label>
                    </form>
                    <br>
                </div>

            </div>

        </div>

    </main>
</div>

</body>
</html>