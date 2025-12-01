<?php
session_start();

$project_id = $_GET['id'] ?? 'X'; 

$progression = 0; 
$status = '';
$title = '';
$description = '';
$static_notes = [];

if ($project_id % 4 == 1) {
    $title = "Audit Code Source (SQLi/XSS)";
    $status = 'En Cours';
    $progression = 45;
    $description = "Vérification complète des vulnérabilités critiques OWASP Top 10 sur le code. Focus sur la sanitization des inputs et le chiffrement des données sensibles en transit.";
    $static_notes = [
        ['date' => '2025-11-27', 'auteur' => 'Anish', 'contenu' => '4 vulnérabilités XSS stockées détectées dans le module de commentaires.'],
        ['date' => '2025-11-26', 'auteur' => 'Sophie L.', 'contenu' => 'Démarrage du scan statique SAST sur le dépôt Git.'],
    ];
} else if ($project_id % 4 == 2) {
    $title = "Mise en place d'un WAF (Web App Firewall)";
    $status = 'Terminé';
    $progression = 95;
    $description = "Déploiement et configuration du pare-feu applicatif (WAF) Cloudflare pour bloquer les attaques courantes de couche 7.";
    $static_notes = [
        ['date' => '2025-12-01', 'auteur' => 'Jules C.', 'contenu' => 'WAF en mode ' . htmlspecialchars('blocking') . ' activé en production. Monitoring 24h.'],
        ['date' => '2025-11-30', 'auteur' => 'Anish', 'contenu' => 'Test de contournement du WAF réussi. Prêt pour le déploiement.'],
    ];
} else if ($project_id % 4 == 3) {
    $title = "Plan de Réponse à Incident (IRP)";
    $status = 'En Attente';
    $progression = 30;
    $description = "Création de la documentation et des procédures d'urgence pour le plan de continuité des activités (PCA).";
    $static_notes = [
        ['date' => '2025-11-27', 'auteur' => 'Anish', 'contenu' => 'Revue du draft de la section Communication de Crise avec la direction.'],
        ['date' => '2025-11-26', 'auteur' => 'Sophie L.', 'contenu' => 'Collecte des contacts d\'urgence et des fournisseurs clés.'],
    ];
} else {
    $title = "Pentest Externe de l'Infrastructure";
    $status = 'Bloqué';
    $progression = 25;
    $description = "Phase initiale de test d'intrusion externe (Black Box) pour identifier les failles d'exposition des serveurs et des API.";
    $static_notes = [
        ['date' => '2025-12-02', 'auteur' => 'Tom F.', 'contenu' => 'Collecte de renseignements (Reconnaissance) terminée.'],
        ['date' => '2025-11-28', 'auteur' => 'Sophie L.', 'contenu' => 'Attente de la validation du périmètre légal du Pentest.'],
    ];
}

$title_final = htmlspecialchars($title) . " (ID: " . htmlspecialchars($project_id) . ")";

function get_status_badge($status) {
    if ($status == 'Terminé') return '<span class="status-badge status-success">Terminé</span>';
    if ($status == 'En Cours') return '<span class="status-badge status-primary">En Cours</span>';
    if ($status == 'En Attente') return '<span class="status-badge status-warning">En Attente</span>';
    return '<span class="status-badge status-error">Bloqué</span>';
}

$static_members = [
    ['name' => 'Anish', 'role' => 'CISO (Chef Sécurité)'],
    ['name' => 'Sophie L.', 'role' => 'DevSecOps Engineer'],
    ['name' => 'Jules C.', 'role' => 'Analyste SOC'],
    ['name' => 'Éva M.', 'role' => 'Développeur'],
];

$static_agenda = [
    ['date' => '2025-12-10', 'tache' => 'Revue du Rapport Pentest'],
    ['date' => '2025-12-15', 'tache' => 'Patching des Vulnérabilités XSS'],
    ['date' => '2025-12-20', 'tache' => 'Formation Équipe sur les Inputs Sécurisés'],
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avancement : <?= $title_final ?></title>
    
    <link rel="icon" type="image/x-icon" href="assets/logo_Arc0ps.ico">
    <link rel="stylesheet" href="style-dashboard.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Styles spécifiques à la page d'avancement */
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

        /* Progression */
        .progress-bar { height: 15px; border-radius: 5px; background-color: var(--separator-color); margin-top: 10px; }
        .progress-fill { height: 100%; width: <?= $progression ?>%; background-color: var(--accent-color); border-radius: 5px; transition: width 0.5s ease-out; }

        /* Notes et Agenda */
        .note-item, .agenda-item { padding: 10px 0; border-bottom: 1px dashed var(--separator-color); }
        .note-item:last-child, .agenda-item:last-child { border-bottom: none; }
        .note-item small, .agenda-item small { color: #bbb; }

        /* Badges de rôle dans l'équipe - classes CSS spécifiques pour les rôles Cyber */
        .member-item { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px dashed var(--separator-color); }
        .member-item:last-child { border-bottom: none; }
        .role-badge { padding: 3px 8px; border-radius: 12px; font-size: 0.75em; font-weight: bold; color: var(--text-color-light); }
        .role-CISOChefSécurité { background-color: #e74c3c; } 
        .role-DevSecOpsEngineer { background-color: #f1c40f; color: #333; }
        .role-AnalysteSOC { background-color: #3498db; } 
        .role-Développeur { background-color: #58d68d; } 

        /* Boutons */
        .btn-back { background-color: #444; border: none; padding: 8px 15px; border-radius: 5px; color: var(--text-color-light); text-decoration: none; display: inline-block; margin-bottom: 20px; }
        .btn-add-note, .btn-manage-members, .btn-add-milestone { background-color: var(--primary-color); color: white; border: none; padding: 8px 15px; border-radius: 6px; cursor: pointer; font-size: 0.9em; margin-top: 15px; display: block; width: 100%; text-align: center; }
        .btn-add-note:hover, .btn-manage-members:hover, .btn-add-milestone:hover { background-color: #a450e0; }
    </style>
</head>
<body>

<div class="dashboard-container">
    
    <aside class="sidebar">
        <div class="logo"><i class="fas fa-cube"></i> Λrc0ps</div>
        <div class="user-profile"><div class="avatar"></div><h3><?= htmlspecialchars($_SESSION['username'] ?? 'Invité') ?></h3></div>
        <nav class="menu">
            <a href="dashboard.php" class="menu-item"><i class="fas fa-home"></i> Home</a>
            <a href="#" class="menu-item active"><i class="fas fa-tasks"></i> Projet</a>
        </nav>
        <div class="footer">© Corporation</div>
    </aside>
    
    <main class="main-content">
        
        <a href="dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Retour au Dashboard</a>
        
        <header class="header">
            <div class="welcome-section">
                <h1><?= $title_final ?></h1>
                <p class="time">Statut : <?= get_status_badge($status) ?></p>
            </div>
        </header>

        <div class="project-details-container">
            
            <div class="project-header">
                <p style="color: #bbb;"><?= nl2br(htmlspecialchars($description)) ?></p>
            </div>

            <div class="project-grid">
                
                <div>
                    
                    <div class="section-card">
                        <h3><i class="fas fa-chart-line"></i> Avancement</h3>
                        <p class="mb-1">Progression estimée : <strong><?= $progression ?>%</strong></p>
                        <div class="progress-bar">
                            <div class="progress-fill"></div>
                        </div>
                    </div>

                    <div class="section-card">
                        <h3><i class="fas fa-comment-dots"></i> Notes de Suivi (Statique Sécurité)</h3>
                        <?php foreach ($static_notes as $note): ?>
                            <div class="note-item">
                                <div class="note-header">
                                    <span class="fw-bold"><?= htmlspecialchars($note['auteur']) ?></span>
                                    <small class="text-muted"><?= htmlspecialchars($note['date']) ?></small>
                                </div>
                                <p class="mt-1 mb-0"><?= nl2br(htmlspecialchars($note['contenu'])) ?></p>
                            </div>
                        <?php endforeach; ?>
                        <button class="btn-add-note">Ajouter une Note</button>
                    </div>
                </div>

                <div>
                    
                    <div class="section-card">
                        <h3><i class="fas fa-users"></i> Équipe du Projet (Statique Sécurité)</h3>
                        <?php foreach ($static_members as $member): ?>
                            <?php 
                                $role_class = str_replace([' ', '(', ')'], '', ucwords(strtolower($member['role']))); 
                            ?>
                            <div class="member-item">
                                <span><?= htmlspecialchars($member['name']) ?></span>
                                <span class="role-badge role-<?= $role_class ?>"><?= htmlspecialchars($member['role']) ?></span>
                            </div>
                        <?php endforeach; ?>
                        <button class="btn-manage-members">Gérer les membres</button>
                    </div>
                    
                    <div class="section-card">
                        <h3><i class="fas fa-calendar-alt"></i> Agenda & Jalons (Statique Sécurité)</h3>
                        <?php foreach ($static_agenda as $jalon): ?>
                            <div class="agenda-item">
                                <p class="mb-0 fw-bold"><?= htmlspecialchars($jalon['tache']) ?></p>
                                <small class="text-muted"><i class="fas fa-clock"></i> Date : <?= htmlspecialchars($jalon['date']) ?></small>
                            </div>
                        <?php endforeach; ?>
                        <button class="btn-add-milestone">Ajouter Jalon</button>
                    </div>
                    
                    <div class="section-card">
                        <h3><i class="fas fa-paperclip"></i> Documents (Statique)</h3>
                        <p><a href="#" style="color: var(--primary-color);">Rapport de Vulnérabilités ID-<?= htmlspecialchars($project_id) ?></a></p>
                    </div>
                </div>
            </div>

        </div>
    </main>

</div>

</body>
</html>