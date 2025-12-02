<?php
// ═══════════════════════════════════════════════════════════════════
//  DASHBOARD - VERSION SÉCURISÉE (OWASP Compliance)
// ═══════════════════════════════════════════════════════════════════

// Inclusion des fonctions de sécurité
require_once 'functions.php';

// Démarrage sécurisé de la session
secure_session_start();

// --- 1. CONNEXION DB ---
$paths = ['config/db.php', 'db.php', '../config/db.php'];
$db_found = false;
foreach ($paths as $p) {
    if (file_exists($p)) { require_once $p; $db_found = true; break; }
}
if (!$db_found) die("❌ Fichier db.php introuvable.");

// --- 2. AUTHENTIFICATION VIA COOKIE ---
if (!isset($_SESSION['user_id'])) {
    if (isset($_COOKIE['mon_site_auth'])) {
        $parts = explode('.', $_COOKIE['mon_site_auth']);
        if (count($parts) === 2) {
            $payload = $parts[0];
            $signature = $parts[1];
            if (hash_equals(hash_hmac('sha256', $payload, SECRET_KEY), $signature)) {
                $decoded = base64_decode(substr($payload, 0, -4));
                if (strpos($decoded, '|') !== false) {
                    list($username, $date) = explode('|', $decoded);
                    $stmtAuth = $pdo->prepare("SELECT id, username FROM users WHERE username = ?");
                    $stmtAuth->execute([$username]);
                    $userFound = $stmtAuth->fetch(PDO::FETCH_ASSOC);
                    if ($userFound) {
                        $_SESSION['user_id'] = $userFound['id'];
                        $_SESSION['username'] = $userFound['username'];
                    }
                }
            }
        }
    }
}

// Redirection si toujours pas connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = secure_int($_SESSION['user_id']); 
$settingsMessage = "";

// --- 3. PROTECTION CSRF SUR TOUS LES FORMULAIRES ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_protect();
}

// --- 4. TRAITEMENT CREATION PROJET (AVEC IMAGE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_project'])) {
    if (isset($pdo)) {
        // Sécurisation
        $title = clean_input($_POST['project_name']);
        $desc = clean_input($_POST['project_desc']);
        $owner = secure_int($_SESSION['user_id']);
        $imagePath = null; 

        // Upload Image Projet (SÉCURISÉ)
        if (isset($_FILES['project_image']) && $_FILES['project_image']['error'] === 0) {
            $validation = validate_file_upload($_FILES['project_image'], ['jpg', 'jpeg', 'png', 'gif', 'webp'], 5242880);
            
            if ($validation['success']) {
                $uploadDir = 'assets/imageProject/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                
                $destPath = $uploadDir . $validation['safe_name'];
                
                if (move_uploaded_file($_FILES['project_image']['tmp_name'], $destPath)) {
                    $imagePath = $destPath;
                } else {
                    log_security_event("Échec du déplacement du fichier uploadé : " . $_FILES['project_image']['name']);
                }
            } else {
                log_security_event("Tentative d'upload de fichier invalide : " . $validation['message']);
            }
        }

        // Insertion BDD
        $sql = "INSERT INTO projects (owner_id, title, description, created_at, updated_at, is_pinned, image_url, status, progression) VALUES (?, ?, ?, NOW(), NOW(), 0, ?, 'En Cours', 0)";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$owner, $title, $desc, $imagePath])) {
            
            // --- NOUVEAU : On ajoute le créateur comme OWNER dans les membres ---
            $newProjectId = (int)$pdo->lastInsertId();
            $stmtMember = $pdo->prepare("INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, 'owner')");
            $stmtMember->execute([$newProjectId, $owner]);
            // --------------------------------------------------------------------

            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    }
}

// --- 5. TRAITEMENT UPDATE PROFIL (SETTINGS) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $newUsername = clean_input($_POST['username']);
    $newEmail = clean_input($_POST['email']);
    $newBio = clean_input($_POST['bio']);
    
    $avatarPath = null;

    // Upload Avatar (SÉCURISÉ)
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === 0) {
        $validation = validate_file_upload($_FILES['avatar'], ['jpg', 'jpeg', 'png', 'gif'], 2097152); // 2Mo max
        
        if ($validation['success']) {
            $uploadDir = 'assets/PhotoProfile/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            
            $destPath = $uploadDir . $validation['safe_name'];
            
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $destPath)) {
                // Suppression ancien avatar si existe
                if (!empty($currentUser['avatar_url']) && file_exists($currentUser['avatar_url'])) {
                    if ($currentUser['avatar_url'] !== 'assets/PhotoProfile/default_avatar.png') {
                        unlink($currentUser['avatar_url']);
                    }
                }
                $avatarPath = $destPath;
            } else {
                log_security_event("Échec upload avatar pour user " . $userId);
            }
        } else {
            $settingsMessage = '<div class="alert alert-error">' . clean_output($validation['message']) . '</div>';
        }
    }

    // Mise à jour BDD
    if (empty($settingsMessage)) {
        if ($avatarPath) {
            $sql = "UPDATE users SET username = ?, email = ?, bio = ?, avatar_url = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$newUsername, $newEmail, $newBio, $avatarPath, $userId]);
        } else {
            $sql = "UPDATE users SET username = ?, email = ?, bio = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$newUsername, $newEmail, $newBio, $userId]);
        }
        
        $_SESSION['username'] = $newUsername;
        $settingsMessage = '<div class="alert alert-success">Profil mis à jour avec succès !</div>';
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// --- 6. RECUPERATION DES DONNEES (SELECT) ---
// On récupère les données MAINTENANT (donc après la potentielle mise à jour)
$currentUser = false;
$pinnedProjects = [];
$allProjects = [];

if (isset($pdo)) {
    try {
        // User
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

        // Projets
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE owner_id = ? AND is_pinned = 1 ORDER BY updated_at DESC");
        $stmt->execute([$userId]);
        $pinnedProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("SELECT * FROM projects WHERE owner_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        $allProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) { /* Silent fail */ }
}

if (!$currentUser) {
   header('Location: logout.php');
   exit;
}

// Fonctions Helper
function getProjectImage($id) {
    $seed = ['cyber', 'audit', 'waf', 'irp', 'pentest'][$id % 5] . $id;
    return "https://picsum.photos/seed/{$seed}/400/250"; 
}

function get_status_badge_html($status) {
    $class = 'status-badge';
    if ($status == 'Terminé') $class .= ' status-success';
    else if ($status == 'En Cours') $class .= ' status-primary';
    else if ($status == 'En Attente') $class .= ' status-warning';
    else $class .= ' status-error'; 
    return "<span class=\"$class\">" . clean_output($status) . "</span>";
}

// --- AJOUT : TRAITEMENT DU PIN (EPINGLE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_pin'])) {
    if (isset($pdo)) {
        $projId = secure_int($_POST['project_id']);
        // Inverse l'état : Si 0 devient 1, Si 1 devient 0
        $sql = "UPDATE projects SET is_pinned = NOT is_pinned WHERE id = ? AND owner_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$projId, $userId]);
        
        // Recharge la page pour voir l'étoile changer de couleur
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Λrc0ps Dashboard</title>
    
    <link rel="icon" type="image/x-icon" href="assets/logo_Arc0ps.ico">
    <link rel="stylesheet" href="style-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        
        <main class="main-content">
            
            <div id="tab-home" class="content-section active-section">
                <header class="header">
                    <div class="welcome-section">
                        <h1>Welcome back, <?php echo htmlspecialchars($currentUser['username']); ?> 👋</h1>
                        <p class="time">Time: <span id="time-display"><?php echo date('H:i'); ?></span></p>
                    </div>
                </header>

                <div class="stats-container">
                    <div class="card">
                        <div class="icon-wrapper"><i class="fas fa-thumbtack"></i></div>
                        <div class="card-info">
                            <span class="number"><?php echo count($pinnedProjects); ?></span>
                            <span class="label">Épinglés</span>
                        </div>
                    </div>
                    <div class="card">
                        <div class="icon-wrapper"><i class="fas fa-layer-group"></i></div>
                        <div class="card-info">
                            <span class="number"><?php echo count($allProjects); ?></span>
                            <span class="label">Total Projets</span>
                        </div>
                    </div>
                </div>

                <?php if (!empty($pinnedProjects)): ?>
                    <h2 style="margin-top: 40px; margin-bottom: 20px; font-size: 1.2rem; color: #a9a9b3; display:flex; align-items:center; gap:10px;">
                        <i class="fas fa-star" style="color: #ffd700;"></i> Vos favoris
                    </h2>
                    
                    <div class="projects-grid">
                        <?php foreach ($pinnedProjects as $project): ?>
                            <?php 
                                $id = $project['id'];
                                $title = htmlspecialchars($project['title']);
                                $status = htmlspecialchars($project['status']);
                                $progression = (int)$project['progression'];
                                $created_at = date('d/m/Y', strtotime($project['created_at']));
                                $status_html = get_status_badge_html($status);

                                $coverImage = getProjectImage($id); 
                                if (!empty($project['image_url']) && file_exists($project['image_url'])) {
                                    $coverImage = htmlspecialchars($project['image_url']); 
                                }
                            ?>
                            
                            <div class="project-card" onclick="window.location='avancement.php?id=<?= $id ?>'" style="cursor: pointer;">
                                
                                <div class="pin-container">
                                    <span class="btn-pin active" style="cursor: default;">
                                        <i class="fas fa-star"></i>
                                    </span>
                                </div>

                                <img src="<?= $coverImage ?>" alt="Cover">
                                <div class="project-info">
                                    <h3><?= $title ?></h3>
                                    <p class="project-status">Statut : <?= $status_html ?></p>
                                    
                                    <div class="progress-bar-small-container">
                                        <small>Progression: <?= $progression ?>%</small>
                                        <div class="progress-bar-small">
                                            <div class="progress-fill-small" style="width: <?= $progression ?>%;"></div>
                                        </div>
                                    </div>
                                    <br>
                                    <span class="project-date">Créé le : <?= $created_at ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div id="tab-projects" class="content-section">
                <header class="header header-projects">
                    <h1><i class="fas fa-box"></i> My Projects</h1>

                    <button class="btn-add-project" onclick="openModal()">
                        <i class="fas fa-plus"></i> Add Project
                    </button>
                    
                    <div id="modal-add-project" class="modal">
                        <div class="modal-content">
                            <span class="close-modal" onclick="closeModal()">&times;</span>
                            <h2>Nouveau Projet</h2>

                            <form method="POST" action="" enctype="multipart/form-data">
                                <?= csrf_field() ?>
                                <input type="hidden" name="create_project" value="1">

                                <div class="form-group">
                                    <label>Nom du projet</label>
                                    <input type="text" name="project_name" required placeholder="Ex: Migration Serveur">
                                </div>

                                <div class="form-group">
                                    <label>Image de couverture (Optionnel)</label>
                                    <div class="file-input-wrapper">
                                        <input type="file" name="project_image" id="proj_img" accept="image/*">
                                        <label for="proj_img" class="file-label">
                                            <i class="fas fa-image"></i> Choisir une image...
                                        </label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Description</label>
                                    <textarea name="project_desc" rows="4" placeholder="Détails du projet..."></textarea>
                                </div>

                                <button type="submit" class="btn-submit">Créer le projet</button>
                            </form>
                        </div>
                    </div>
                </header>

                <div class="projects-grid">
                    <?php if (!empty($allProjects)): ?>
                        <?php foreach ($allProjects as $project): ?>
                            <?php 
                                $id = $project['id'];
                                $title = htmlspecialchars($project['title']);
                                $description = htmlspecialchars($project['description']);
                                $status = htmlspecialchars($project['status']);
                                $progression = (int)$project['progression'];
                                $created_at = date('d/m/Y', strtotime($project['created_at']));
                                
                                // Vérif si épinglé
                                $isPinned = ($project['is_pinned'] == 1);

                                $coverImage = getProjectImage($id); 
                                if (!empty($project['image_url']) && file_exists($project['image_url'])) {
                                    $coverImage = htmlspecialchars($project['image_url']); 
                                }

                                $status_html = get_status_badge_html($status);
                                if (empty($description)) { $description = "Aucune description."; }
                                $description_short = substr($description, 0, 60) . (strlen($description) > 60 ? "..." : "");
                            ?>
                            
                            <div class="project-card" onclick="window.location='avancement.php?id=<?= $id ?>'" style="cursor: pointer;">
                                
                                <div class="pin-container" onclick="event.stopPropagation()">
                                    <form method="POST">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="toggle_pin" value="1">
                                        <input type="hidden" name="project_id" value="<?= $id ?>">
                                        
                                        <button type="submit" class="btn-pin <?= $isPinned ? 'active' : '' ?>" title="<?= $isPinned ? 'Retirer des favoris' : 'Ajouter aux favoris' ?>">
                                            <i class="<?= $isPinned ? 'fas' : 'far' ?> fa-star"></i>
                                        </button>
                                    </form>
                                </div>

                                <img src="<?= $coverImage ?>" alt="Cover">
                                
                                <div class="project-info">
                                    <h3><?= $title ?></h3>
                                    <p class="project-status">Statut : <?= $status_html ?></p>
                                    <p><?= $description_short ?></p>
                                    
                                    <div class="progress-bar-small-container">
                                        <small>Progression: <?= $progression ?>%</small>
                                        <div class="progress-bar-small">
                                            <div class="progress-fill-small" style="width: <?= $progression ?>%;"></div>
                                        </div>
                                    </div>
                                    <br>
                                    <span class="project-date">Créé le : <?= $created_at ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="empty-state">Aucun projet trouvé.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div id="tab-settings" class="content-section">
                <header class="header">
                    <h1><i class="fas fa-cog"></i> Settings</h1>
                </header>
                <div class="settings-container">
                    <div class="settings-card">
                        <h2>Mon Profil</h2>
                        
                        <?php if (!empty($settingsMessage)) echo $settingsMessage; ?>

                        <form class="settings-form" method="POST" action="" enctype="multipart/form-data">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="update_profile">

                            <div class="form-group">
                                <label>Photo de profil</label>
                                <div class="file-input-wrapper">
                                    <input type="file" name="avatar" id="avatar" accept="image/png, image/jpeg, image/gif">
                                    <label for="avatar" class="file-label">
                                        <i class="fas fa-upload"></i> Choisir une image...
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Nom d'utilisateur</label>
                                <input type="text" name="username" value="<?php echo htmlspecialchars($currentUser['username']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($currentUser['email']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Bio</label>
                                <textarea name="bio" rows="4"><?php echo htmlspecialchars($currentUser['bio'] ?? ''); ?></textarea>
                            </div>

                            <button type="submit" class="btn-save">
                                <i class="fas fa-save"></i> Sauvegarder les modifications
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            
            

        </main>

        <aside class="sidebar">
            <div class="logo">
                <i class="fas fa-shopping-cart"></i> Λrc0ps
            </div>

            <div class="user-profile">
                <div>
                    <?php 
                        // LOGIQUE D'AFFICHAGE DE L'IMAGE
                        // 1. On regarde si l'utilisateur a une URL dans la DB
                        $avatarPath = !empty($currentUser['avatar_url']) ? $currentUser['avatar_url'] : 'assets/PhotoProfile/default_avatar.png';
                        
                        // 2. Si le fichier n'existe pas physiquement (supprimé par erreur), on met le défaut
                        if (!file_exists($avatarPath)) {
                            $avatarPath = 'assets/PhotoProfile/default_avatar.png';
                        }
                        
                        // 3. Astuce anti-cache : on ajoute ?v=time() pour forcer le navigateur à recharger l'image si on vient de la changer
                        $displayPath = $avatarPath . "?v=" . time();
                    ?>
                    <img src="<?= htmlspecialchars($displayPath) ?>" alt="Avatar" class="avatar">
                </div>
                <h3><?php echo htmlspecialchars($currentUser['username']); ?></h3>
            </div>

            <nav class="menu">
                <a href="#" class="menu-item active" onclick="showPage(event, 'home')">
                    <i class="icon-container">
                        <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M11.293 3.293a1 1 0 0 1 1.414 0l6 6 2 2a1 1 0 0 1-1.414 1.414L19 12.414V19a2 2 0 0 1-2 2h-3a1 1 0 0 1-1-1v-3h-2v3a1 1 0 0 1-1 1H7a2 2 0 0 1-2-2v-6.586l-.293.293a1 1 0 0 1-1.414-1.414l2-2 6-6Z" clip-rule="evenodd"/>
                        </svg>
                    </i> 
                    Home 
                </a>
                
                <a href="#" class="menu-item" onclick="showPage(event, 'projects')">
                    <i class="icon-container">
                        <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M5 4a2 2 0 0 0-2 2v1h10.968l-1.9-2.28A2 2 0 0 0 10.532 4H5ZM3 19V9h18v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm9-8.5a1 1 0 0 1 1 1V13h1.5a1 1 0 1 1 0 2H13v1.5a1 1 0 1 1-2 0V15H9.5a1 1 0 1 1 0-2H11v-1.5a1 1 0 0 1 1-1Z" clip-rule="evenodd"/>
                        </svg>
                    </i> 
                    Projects
                </a>

                <a href="#" class="menu-item" onclick="showPage(event, 'settings')">
                    <i class="icon-container">
                        <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H4Zm10 5a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm0 3a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm0 3a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-8-5a3 3 0 1 1 6 0 3 3 0 0 1-6 0Zm1.942 4a3 3 0 0 0-2.847 2.051l-.044.133-.004.012c-.042.126-.055.167-.042.195.006.013.02.023.038.039.032.025.08.064.146.155A1 1 0 0 0 6 17h6a1 1 0 0 0 .811-.415.713.713 0 0 1 .146-.155c.019-.016.031-.026.038-.04.014-.027 0-.068-.042-.194l-.004-.012-.044-.133A3 3 0 0 0 10.059 14H7.942Z" clip-rule="evenodd"/>
                        </svg>
                    </i> 
                    Settings
                </a>
            </nav>
            

            <div class="logout-container">
                <a href="logout.php" class="menu-item btn-logout">
                    <i class="btn-logout">
                         <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12H4m12 0-4 4m4-4-4-4m3-4h2a3 3 0 0 1 3 3v10a3 3 0 0 1-3 3h-2"/>
                        </svg>
                    </i> 
                    Disconnect 
                </a>
            </div>
            <br>
            <div class="footer">
                © Λrc0ps Corporation
            </div>
        </aside>

    </div>

    <script>
        function openModal() {
            document.getElementById('modal-add-project').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('modal-add-project').style.display = 'none';
        }

        // Fermer si on clique sur le fond gris
        window.onclick = function(event) {
            const modal = document.getElementById('modal-add-project');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        function showPage(event, pageId) {
            event.preventDefault();

            const allPages = document.querySelectorAll('.content-section');
            allPages.forEach(page => {
                page.style.display = 'none';
                page.classList.remove('active-section');
            });

            const target = document.getElementById('tab-' + pageId);
            if (target) {
                target.style.display = 'block';
                setTimeout(() => target.classList.add('active-section'), 10);
            }

            const allMenus = document.querySelectorAll('.menu-item');

            allMenus.forEach(menu => {
                if(!menu.classList.contains('btn-logout')) {
                    menu.classList.remove('active');
                }
            });
            
            if(!event.currentTarget.classList.contains('btn-logout')) {
                event.currentTarget.classList.add('active');
            }
        }
        
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('fr-FR', {hour: '2-digit', minute:'2-digit'});
            const timeDisplay = document.getElementById('time-display');
            if(timeDisplay) timeDisplay.textContent = timeString;
        }
        setInterval(updateTime, 1000);
        updateTime();
    </script>

</body>
</html>