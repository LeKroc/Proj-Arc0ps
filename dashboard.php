<?php
session_start();

// --- 1. CONNEXION DB ---
$paths = ['config/db.php', 'db.php', '../config/db.php'];
$db_found = false;
foreach ($paths as $p) {
    if (file_exists($p)) { require_once $p; $db_found = true; break; }
}
if (!$db_found) die("❌ Fichier db.php introuvable.");

if (!isset($_SESSION['user_id'])) {
    
    if (isset($_COOKIE['mon_site_auth'])) {
        
        // On sépare : Données (Payload) . Signature
        $parts = explode('.', $_COOKIE['mon_site_auth']);
        
        if (count($parts) === 2) {
            $payload = $parts[0];
            $signature = $parts[1];

            // Vérification de la signature HMAC avec la clé secrète du db.php
            $checkSignature = hash_hmac('sha256', $payload, SECRET_KEY);

            if (hash_equals($checkSignature, $signature)) {
                
                // ON RETIRE LES 4 CARACTÈRES ALÉATOIRES (Suffixe)
                $cleanBase64 = substr($payload, 0, -4);
                
                $decoded = base64_decode($cleanBase64);

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

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id']; 

$currentUser = false;
$pinnedProjects = [];
$allProjects = [];

if (isset($pdo)) {
    try {
        // User
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

        // Projets Épinglés
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE owner_id = ? AND is_pinned = 1 ORDER BY updated_at DESC");
        $stmt->execute([$userId]);
        $pinnedProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Tous les projets
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE owner_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        $allProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) { /* Silent fail */ }
}

if (!$currentUser) {
   header('Location: logout.php');
   exit;
}

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
    return "<span class=\"$class\">" . htmlspecialchars($status) . "</span>";
}

$settingsMessage = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    // 1. Récupération des champs
    $newUsername = trim($_POST['username']);
    $newEmail    = trim($_POST['email']);
    $newBio      = trim($_POST['bio']);

    // 2. Validation basique
    if (empty($newUsername) || empty($newEmail)) {
        $settingsMessage = "<div class='alert alert-error'>Le nom et l'email sont obligatoires.</div>";
    } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $settingsMessage = "<div class='alert alert-error'>Format d'email invalide.</div>";
    } else {
        // 3. Mise à jour en Base de Données
        try {
            $sql = "UPDATE users SET username = ?, email = ?, bio = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            
            if ($stmt->execute([$newUsername, $newEmail, $newBio, $userId])) {
                $settingsMessage = "<div class='alert alert-success'>Profil mis à jour avec succès !</div>";
                
                // 4. Mettre à jour les variables actuelles pour affichage immédiat
                $_SESSION['username']    = $newUsername; // Important pour le "Welcome back"
                $currentUser['username'] = $newUsername;
                $currentUser['email']    = $newEmail;
                $currentUser['bio']      = $newBio;
            } else {
                $settingsMessage = "<div class='alert alert-error'>Erreur lors de la mise à jour.</div>";
            }
        } catch (PDOException $e) {
            $settingsMessage = "<div class='alert alert-error'>Erreur SQL : " . htmlspecialchars($e->getMessage()) . "</div>";
        }
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
            </div>

            <div id="tab-projects" class="content-section">
                <header class="header">
                    <h1><i class="fas fa-box"></i> My Projects</h1>
                </header>

                <div class="projects-grid">
                    <?php if (!empty($allProjects)): ?>
                        <?php foreach ($allProjects as $project): ?>
                            <?php 
                                // On extrait l'ID
                                $id = $project['id'] ?? 0;
                                
                                // --- LOGIQUE DE SIMULATION CYBERSÉCURITÉ BASÉE SUR L'ID ---
                                $progression = (int)($project['progression'] ?? rand(10, 90)); // Fallback simulation
                                
                                $title = "";
                                $description = "";

                                if ($id % 4 == 1) {
                                    $title = "Audit Code Source (SQLi/XSS)";
                                    $description = "Vérification des vulnérabilités critiques OWASP Top 10 sur le code.";
                                    $progression = 45;
                                    $status = 'En Cours';
                                } else if ($id % 4 == 2) {
                                    $title = "Mise en place d'un WAF (Web App Firewall)";
                                    $description = "Déploiement et configuration du pare-feu applicatif frontal.";
                                    $progression = 95;
                                    $status = 'Terminé';
                                } else if ($id % 4 == 3) {
                                    $title = "Plan de Réponse à Incident (IRP)";
                                    $description = "Création de la documentation et des procédures d'urgence.";
                                    $progression = 30;
                                    $status = 'En Attente';
                                } else {
                                    $title = "Pentest Externe (Phase 1)";
                                    $description = "Test d'intrusion externe sur l'infrastructure cloud.";
                                    $progression = 15;
                                    $status = 'Bloqué';
                                }

                                $status_html = get_status_badge_html($status);
                                $description_final = htmlspecialchars(substr($description ?? 'Pas de description.', 0, 50));
                                $created_at = date('d/m/Y', strtotime($project['created_at'] ?? 'now'));
                            ?>
                            
                            <a href="avancement.php?id=<?= $id ?>" class="project-card">
                                <img src="<?= getProjectImage($id) ?>" alt="Cover">
                                <div class="project-info">
                                    <h3><?= $title ?></h3>
                                    <p class="project-status">Statut : <?= $status_html ?></p>
                                    <p><?= $description_final ?>...</p>
                                    
                                    <div class="progress-bar-small-container">
                                        <small>Progression: <?= $progression ?>%</small>
                                        <div class="progress-bar-small">
                                            <div class="progress-fill-small" style="width: <?= $progression ?>%;"></div>
                                        </div>
                                    </div>
                                    <span class="project-date">Créé le : <?= $created_at ?></span>
                                </div>
                            </a>
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

                        <form class="settings-form" method="POST" action="">
                            <input type="hidden" name="action" value="update_profile">

                            <div class="form-group">
                                <label>Nom d'utilisateur</label>
                                <input type="text" name="username" value="<?php echo htmlspecialchars($currentUser['username']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($currentUser['email']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Bio (Visible sur le profil)</label>
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
                    <img src="<?= !empty($currentUser['avatar_url']) ? htmlspecialchars($currentUser['avatar_url']) : 'assets/default_avatar.png' ?>" alt="Avatar" class="avatar">
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