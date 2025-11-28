<?php
session_start();

// --- 1. CONNEXION ROBUSTE ---
$paths = ['config/db.php', 'db.php', '../config/db.php'];
$db_found = false;
foreach ($paths as $p) {
    if (file_exists($p)) { require_once $p; $db_found = true; break; }
}
if (!$db_found) die("❌ Fichier db.php introuvable.");

// --- 2. RÉCUPÉRATION DATA ---
$userId = $_SESSION['user_id'] ?? 1; // ID par défaut pour le test

$currentUser = false;
$pinnedProjects = [];
$allProjects = [];

if (isset($pdo)) {
    try {
        // A. Utilisateur
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

        // B. Projets Épinglés (Home)
        // On sélectionne ceux où is_pinned = 1
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE owner_id = ? AND is_pinned = 1 ORDER BY updated_at DESC");
        $stmt->execute([$userId]);
        $pinnedProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // C. Tous les projets (Onglet Projets)
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE owner_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        $allProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        // En prod, on log l'erreur
    }
}

// Sécurité : Si user non trouvé, on crée un faux admin pour l'affichage
if (!$currentUser) {
    $currentUser = [
        'username' => 'Admin System',
        'email' => 'admin@arcops.net',
        'role' => 'admin',
        'avatar_url' => 'assets/default_avatar.png',
        'bio' => 'Mode Debug',
        'theme' => 'dark'
    ];
}

// Fonction pour générer une image aléatoire (car pas de colonne image dans la DB)
function getProjectImage($id) {
    // Utilise un service d'image placeholder avec l'ID pour avoir toujours la même image par projet
    return "https://picsum.photos/seed/arcops{$id}/300/200"; 
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arc0ps - Dashboard</title>
    <link rel="stylesheet" href="style-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .content-section { display: none; animation: fadeIn 0.3s; }
        .active-section { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

    <div class="dashboard-container">
        
        <main class="main-content">
            
            <header class="header">
                <div class="welcome-section">
                    <h1>Bonjour, <?php echo htmlspecialchars($currentUser['username']); ?> 👋</h1>
                    <p class="time">
                        <i class="far fa-clock"></i> <span id="time-display">--:--</span> 
                        &nbsp;|&nbsp; 
                        <span id="date-display">--/--/----</span>
                    </p>
                </div>
            </header>

            <div id="tab-home" class="content-section active-section">
                <h2 class="section-title"><i class="fas fa-thumbtack"></i> Projets Épinglés</h2>
                
                <div class="pokemon-grid">
                    <?php if (!empty($pinnedProjects)): ?>
                        <?php foreach ($pinnedProjects as $p): ?>
                            <div class="pokemon-card">
                                <div class="pk-image-container">
                                    <img src="<?php echo getProjectImage($p['id']); ?>" alt="Project Cover">
                                    <span class="pk-badge <?php echo $p['status']; ?>">
                                        <?php echo ucfirst($p['status']); ?>
                                    </span>
                                </div>
                                <div class="pk-content">
                                    <h3><?php echo htmlspecialchars($p['name']); ?></h3>
                                    <div class="pk-desc">
                                        <?php echo htmlspecialchars(substr($p['description'] ?? 'Pas de description', 0, 60)) . '...'; ?>
                                    </div>
                                    <div class="pk-footer">
                                        <span><i class="fas fa-calendar-alt"></i> <?php echo date('d/m/y', strtotime($p['created_at'])); ?></span>
                                        <i class="fas fa-star pinned-icon"></i>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-data">Aucun projet épinglé pour le moment.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div id="tab-projects" class="content-section">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <h2 class="section-title"><i class="fas fa-folder-open"></i> Tous les Projets</h2>
                    <button class="btn-primary"><i class="fas fa-plus"></i> Créer</button>
                </div>

                <div class="pokemon-grid">
                    <?php if (!empty($allProjects)): ?>
                        <?php foreach ($allProjects as $p): ?>
                            <div class="pokemon-card">
                                <div class="pk-image-container">
                                    <img src="<?php echo getProjectImage($p['id']); ?>" alt="Project Cover">
                                    <span class="pk-badge <?php echo $p['status']; ?>">
                                        <?php echo ucfirst($p['status']); ?>
                                    </span>
                                </div>
                                <div class="pk-content">
                                    <h3><?php echo htmlspecialchars($p['name']); ?></h3>
                                    <div class="pk-desc">
                                        <?php echo htmlspecialchars(substr($p['description'] ?? '...', 0, 60)); ?>...
                                    </div>
                                    <div class="pk-footer">
                                        <span><i class="fas fa-clock"></i> <?php echo date('d/m/y', strtotime($p['updated_at'])); ?></span>
                                        <?php if($p['is_pinned']): ?>
                                            <i class="fas fa-thumbtack" style="color:var(--purple-main)"></i>
                                        <?php else: ?>
                                            <i class="fas fa-ellipsis-h"></i>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-data">Vous n'avez pas encore de projets.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div id="tab-settings" class="content-section">
                <h2 class="section-title"><i class="fas fa-cog"></i> Paramètres</h2>
                <div class="settings-box">
                    <form>
                        <div class="form-group">
                            <label>Nom d'utilisateur</label>
                            <input type="text" value="<?php echo htmlspecialchars($currentUser['username']); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label>Bio</label>
                            <textarea rows="3"><?php echo htmlspecialchars($currentUser['bio'] ?? ''); ?></textarea>
                        </div>
                        <button type="button" class="btn-primary">Sauvegarder</button>
                    </form>
                </div>
            </div>

        </main>

        <aside class="sidebar">
            <div class="logo">
                <i class="fas fa-network-wired" style="color:var(--purple-main)"></i> Arc0ps
            </div>

            <div class="user-profile">
                <img src="<?php echo htmlspecialchars($currentUser['avatar_url'] ?? 'assets/default_avatar.png'); ?>" class="avatar-img">
                <h3><?php echo htmlspecialchars($currentUser['username']); ?></h3>
                <span class="role-tag"><?php echo htmlspecialchars($currentUser['role']); ?></span>
            </div>

            <nav class="menu">
                <a href="#" onclick="switchTab('home', this)" class="menu-item active">
                    <i class="fas fa-home"></i> Home
                </a>
                <a href="#" onclick="switchTab('projects', this)" class="menu-item">
                    <i class="fas fa-folder-open"></i> Projets
                </a>
                <a href="#" onclick="switchTab('settings', this)" class="menu-item">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </nav>

            <div class="footer">© Arc0ps Systems</div>
        </aside>
    </div>

    <script>
        function switchTab(tabId, el) {
            document.querySelectorAll('.content-section').forEach(s => s.style.display = 'none');
            document.querySelectorAll('.menu-item').forEach(m => m.classList.remove('active'));
            document.getElementById('tab-' + tabId).style.display = 'block';
            el.classList.add('active');
        }

        function updateTime() {
            const now = new Date();
            document.getElementById('time-display').innerText = now.toLocaleTimeString('fr-FR');
            document.getElementById('date-display').innerText = now.toLocaleDateString('fr-FR');
        }
        setInterval(updateTime, 1000);
        updateTime();
    </script>
</body>
</html>