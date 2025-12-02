<?php
session_start();

// 1. Inclusion de la base de données
$paths = ['config/db.php', 'db.php', '../config/db.php'];
$db_found = false;
foreach ($paths as $p) {
    if (file_exists($p)) { require_once $p; $db_found = true; break; }
}
if (!$db_found) die("❌ Erreur : Impossible de trouver db.php.");

// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 2. Récupération de l'ID du projet
$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 3. Récupération des infos du PROJET depuis la BDD
$sqlProject = "SELECT * FROM projects WHERE id = :id";
$stmt = $pdo->prepare($sqlProject);
$stmt->execute(['id' => $project_id]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

// Si le projet n'existe pas, on redirige ou on affiche une erreur
if (!$project) {
    die("❌ Projet introuvable ou vous n'avez pas les droits.");
}

// 4. Récupération de l'AGENDA depuis la BDD
$sqlAgenda = "SELECT * FROM project_agenda WHERE project_id = :pid ORDER BY start_event ASC";
$stmtAgenda = $pdo->prepare($sqlAgenda);
$stmtAgenda->execute(['pid' => $project_id]);
$agenda_items = $stmtAgenda->fetchAll(PDO::FETCH_ASSOC);

// --- Fonctions utilitaires ---
function get_status_badge($status) {
    // Normalisation pour éviter les soucis de casse
    $s = strtolower($status);
    if (strpos($s, 'termin') !== false) return '<span class="status-badge status-success">Terminé</span>';
    if (strpos($s, 'cour') !== false) return '<span class="status-badge status-primary">En Cours</span>';
    if (strpos($s, 'attent') !== false) return '<span class="status-badge status-warning">En Attente</span>';
    return '<span class="status-badge status-error">Bloqué</span>';
}

// Données statiques pour ce qui n'est pas encore en BDD (Notes, Membres)
// Tu pourras créer des tables pour ça plus tard
$static_notes = [
    ['date' => date('Y-m-d'), 'auteur' => 'Système', 'contenu' => 'Projet chargé depuis la base de données.'],
];

$static_members = [
    ['name' => $_SESSION['username'], 'role' => 'Chef de Projet'],
    ['name' => 'Sophie L.', 'role' => 'DevSecOps'],
];

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projet : <?= htmlspecialchars($project['title']) ?></title>
    
    <link rel="icon" type="image/x-icon" href="assets/logo_Arc0ps.ico">
    <link rel="stylesheet" href="style-dashboard.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Styles spécifiques à la page projet */
        :root {
            --primary-color: #ba54f5; 
            --accent-color: #58d68d; 
            --card-bg: #27293d; 
            --text-color-light: #ffffff;
            --separator-color: #444;
        }
        
        .project-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 20px; }
        .section-card { background-color: var(--card-bg); padding: 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); margin-bottom: 20px; color: var(--text-color-light); }
        .section-card h3 { color: var(--text-white); border-bottom: 2px solid var(--primary-color); padding-bottom: 10px; margin-bottom: 15px; font-size: 1.25em; }

        /* Progression dynamique */
        .progress-bar { height: 15px; border-radius: 5px; background-color: var(--separator-color); margin-top: 10px; }
        /* On injecte le % PHP directement dans le CSS inline */
        .progress-fill { height: 100%; width: <?= $project['progression'] ?>%; background-color: var(--accent-color); border-radius: 5px; transition: width 0.5s ease-out; }

        .note-item, .agenda-item { padding: 10px 0; border-bottom: 1px dashed var(--separator-color); }
        .note-item:last-child, .agenda-item:last-child { border-bottom: none; }
        .note-item small, .agenda-item small { color: #bbb; }

        .member-item { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px dashed var(--separator-color); }
        .member-item:last-child { border-bottom: none; }
        .role-badge { padding: 3px 8px; border-radius: 12px; font-size: 0.75em; font-weight: bold; background-color: #444; color: #fff;}

        /* Boutons */
        .btn-back { background-color: #444; border: none; padding: 8px 15px; border-radius: 5px; color: var(--text-color-light); text-decoration: none; display: inline-block; margin-bottom: 20px; }
        .btn-action { background-color: var(--primary-color); color: white; border: none; padding: 8px 15px; border-radius: 6px; cursor: pointer; font-size: 0.9em; margin-top: 15px; display: block; width: 100%; text-align: center; }
        .btn-action:hover { background-color: #a450e0; }

        /* Style Date Agenda */
        .date-badge { 
            background: rgba(255,255,255,0.1); 
            padding: 2px 6px; 
            border-radius: 4px; 
            font-size: 0.8em;
            margin-right: 5px;
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    
    <aside class="sidebar">
        <div class="logo"><i class="fas fa-cube"></i> Λrc0ps</div>
        <div class="user-profile">
            <div class="avatar"></div>
            <h3><?= htmlspecialchars($_SESSION['username'] ?? 'Invité') ?></h3>
        </div>
        <nav class="menu">
            <a href="dashboard.php" class="menu-item"><i class="fas fa-home"></i> Dashboard</a>
            <a href="#" class="menu-item active"><i class="fas fa-folder-open"></i> Projet Actuel</a>
            <a href="project_settings.php" class="menu-item"><i class="fas fa-cog"></i> Settings</a>
        </nav>
        <div class="footer">© Corporation</div>
    </aside>
    
    <main class="main-content">
        
        <a href="dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Retour au Dashboard</a>
        
        <header class="header">
            <div class="welcome-section">
                <h1><?= htmlspecialchars($project['title']) ?> <small style="font-size:0.5em; opacity:0.6;">(ID: <?= $project['id'] ?>)</small></h1>
                <p class="time">Statut : <?= get_status_badge($project['status']) ?></p>
            </div>
        </header>

        <div class="project-details-container">
            
            <div class="project-header">
                <p style="color: #bbb; line-height: 1.6;"><?= nl2br(htmlspecialchars($project['description'])) ?></p>
            </div>

            <div class="project-grid">
                
                <div>
                    <div class="section-card">
                        <h3><i class="fas fa-chart-line"></i> Avancement</h3>
                        <p class="mb-1">Progression estimée : <strong><?= $project['progression'] ?>%</strong></p>
                        <div class="progress-bar">
                            <div class="progress-fill"></div>
                        </div>
                    </div>

                    <div class="section-card">
                        <h3><i class="fas fa-comment-dots"></i> Notes de Suivi</h3>
                        <?php foreach ($static_notes as $note): ?>
                            <div class="note-item">
                                <div class="note-header">
                                    <span class="fw-bold"><?= htmlspecialchars($note['auteur']) ?></span>
                                    <small class="text-muted"><?= htmlspecialchars($note['date']) ?></small>
                                </div>
                                <p class="mt-1 mb-0"><?= nl2br(htmlspecialchars($note['contenu'])) ?></p>
                            </div>
                        <?php endforeach; ?>
                        <button class="btn-action">Ajouter une Note</button>
                    </div>
                </div>

                <div>
                    
                    <div class="section-card">
                        <h3><i class="fas fa-calendar-alt"></i> Agenda & Événements</h3>
                        
                        <?php if (count($agenda_items) > 0): ?>
                            <?php foreach ($agenda_items as $event): ?>
                                <?php 
                                    // Formatage des dates
                                    $start = new DateTime($event['start_event']);
                                    $end   = new DateTime($event['end_event']);
                                ?>
                                <div class="agenda-item">
                                    <p class="mb-0 fw-bold" style="color: #fff;"><?= htmlspecialchars($event['title']) ?></p>
                                    
                                    <div style="font-size: 0.85em; margin-top: 5px; color: #aaa;">
                                        <i class="far fa-clock"></i> 
                                        Du <span class="date-badge"><?= $start->format('d/m H:i') ?></span>
                                        au <span class="date-badge"><?= $end->format('d/m H:i') ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color: #888; font-style: italic;">Aucun événement prévu.</p>
                        <?php endif; ?>

                        <button class="btn-action">Ajouter un Événement</button>
                    </div>
                    
                    <div class="section-card">
                        <h3><i class="fas fa-users"></i> Équipe du Projet</h3>
                        <?php foreach ($static_members as $member): ?>
                            <div class="member-item">
                                <span><?= htmlspecialchars($member['name']) ?></span>
                                <span class="role-badge"><?= htmlspecialchars($member['role']) ?></span>
                            </div>
                        <?php endforeach; ?>
                        <button class="btn-action">Gérer les membres</button>
                    </div>

                    <div class="section-card">
                        <h3><i class="fas fa-paperclip"></i> Documents</h3>
                        <p><a href="#" style="color: var(--primary-color);">Rapport_<?= $project['id'] ?>.pdf</a></p>
                    </div>

                </div>
            </div>

        </div>
    </main>

</div>

</body>
</html>
