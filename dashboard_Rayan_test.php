<?php
session_start();

// --- 1. CONNEXION ---
$paths = ['config/db.php', 'db.php', '../config/db.php'];
$db_found = false;
foreach ($paths as $p) {
    if (file_exists($p)) { require_once $p; $db_found = true; break; }
}
if (!$db_found) die("❌ Fichier db.php introuvable.");

// --- 2. DATA ---
$userId = $_SESSION['user_id'] ?? 1; 

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
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE owner_id = ?  ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        $allProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) { /* Silent fail */ }
}

// Fallback User
if (!$currentUser) {
    $currentUser = [
        'username' => 'Admin System',
        'email' => 'admin@arcops.net',
        'role' => 'admin',
        'avatar_url' => 'assets/default_avatar.png',
        'bio' => 'Compte de secours',
        'theme' => 'dark'
    ];
}

// Helper Image
function getProjectImage($id) {
    return "https://picsum.photos/seed/arcops{$id}/400/250"; 
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare. com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        
        <main class="main-content">
            
            <!-- ONGLET HOME -->
            <div id="tab-home" class="tab-content active-section">
                <header class="header">
                    <div class="welcome-section">
                        <h1>Welcome back, <?php echo $currentUser['username']; ?> 👋</h1>
                        <p class="time">Time: <span id="time-display">--:--</span></p>
                    </div>
                </header>
        </div>

        </main>
        <aside class="sidebar">
            <div class="logo">
                <i class="fas fa-shopping-cart"></i> Λrc0ps
            </div>

            <div class="user-profile">
                <div class="avatar">
                    <img src="<?= !empty($currentUser['avatar_url']) ? htmlspecialchars($currentUser['avatar_url']) : 'assets/default_avatar.png' ?>" 
                    alt="Avatar" class="avatar">
                </div>
                <h3><?php echo $currentUser['username']; ?></h3>
            </div>

            <nav class="menu">
                <a href="#" class="menu-item active" data-tab="home" onclick="showPage(event, 'home')">
                    <i class="fas fa-home">
                        <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M11.293 3.293a1 1 0 0 1 1.414 0l6 6 2 2a1 1 0 0 1-1.414 1.414L19 12.414V19a2 2 0 0 1-2 2h-3a1 1 0 0 1-1-1v-3h-2v3a1 1 0 0 1-1 1H7a2 2 0 0 1-2-2v-6.586l-.293.293a1 1 0 0 1-1.414-1.414l2-2 6-6Z" clip-rule="evenodd"/>
                        </svg>
                    </i> Home 
                </a>
                

                <a href="#" class="menu-item" data-tab="projects" onclick="showPage(event, 'projects')">
                    <i class="fas fa-box">
                        <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M5 4a2 2 0 0 0-2 2v1h10.968l-1.9-2.28A2 2 0 0 0 10.532 4H5ZM3 19V9h18v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm9-8.5a1 1 0 0 1 1 1V13h1.5a1 1 0 1 1 0 2H13v1.5a1 1 0 1 1-2 0V15H9.5a1 1 0 1 1 0-2H11v-1.5a1 1 0 0 1 1-1Z" clip-rule="evenodd"/>
                        </svg>

                    </i> Projects
                </a>
                <a href="#" class="menu-item" data-tab="settings" onclick="showPage(event, 'settings')">
                    <i class="fas fa-cog">
                        <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H4Zm10 5a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm0 3a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm0 3a1 1 0 0 1 1-1h3a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1Zm-8-5a3 3 0 1 1 6 0 3 3 0 0 1-6 0Zm1.942 4a3 3 0 0 0-2.847 2.051l-.044.133-.004.012c-.042.126-.055.167-.042.195.006.013.02.023.038.039.032.025.08.064.146.155A1 1 0 0 0 6 17h6a1 1 0 0 0 .811-.415.713.713 0 0 1 .146-.155c.019-.016.031-.026.038-.04.014-.027 0-.068-.042-.194l-.004-.012-.044-.133A3 3 0 0 0 10.059 14H7.942Z" clip-rule="evenodd"/>
                        </svg>
                    </i> Settings
                </a>
            </nav>
    
            <div class="footer">
                © ShopMe Corporation
            </div>
        </aside>

    </div>

    <script>
        function showPage(pageId) {
            const allPages = document.querySelectorAll('.content-section');
            
            allPages.forEach(page => {
                page.classList.remove('active-section');
            });

            const selectedPage = document.getElementById(pageId);
            if (selectedPage) {
                selectedPage.classList.add('active-section');
            }
        }
    </script>

</body>
</html>