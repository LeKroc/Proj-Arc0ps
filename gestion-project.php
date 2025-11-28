<?php
// =========================================================================
// PHP : RÉCUPÉRATION ET SIMULATION DES DONNÉES DU PROJET
// =========================================================================

// 1. On récupère l'ID du projet envoyé via l'URL (avancement.php?id=101)
$project_id = $_GET['id'] ?? null; 

if ($project_id === null) {
    die("Erreur : Aucun identifiant de projet spécifié pour l'avancement.");
}

// 2. DONNÉES DE SIMULATION
// J'ai retiré le champ 'image_url' car vous ne voulez pas d'images.
$project_data = [
    '101' => [
        'title' => 'Refonte de l\'Expérience Utilisateur (UI/UX)',
        'description' => 'Ce projet vise à moderniser l\'interface de notre plateforme de commerce électronique (ShopMe) pour améliorer l\'engagement client, réduire le taux de rebond et optimiser le processus de paiement.',
        'progression' => 65,
        'statut' => 'En Cours',
        'owner' => 'Anish (Owner)',
        'admin' => ['Sophie L.', 'Marc D.'],
        'members' => ['Jules C.', 'Éva M.', 'Tom F.'],
        'notes' => [
            ['date' => '2025-11-26', 'auteur' => 'Sophie L.', 'contenu' => 'Wireframes de la page produit validés par le marketing.'],
            ['date' => '2025-11-27', 'auteur' => 'Anish', 'contenu' => 'Début de l\'intégration du nouveau module de recherche.'],
        ],
        'agenda' => [
            ['date' => '2025-12-05', 'tache' => 'Revue du design du tunnel de commande'],
        ],
    ],
    '102' => [
        'title' => 'Migration du Serveur vers Azure',
        'description' => 'Transfert de tous les services critiques vers l\'infrastructure cloud Azure pour améliorer la résilience et l\'évolutivité. Jalon 1 (Inventaire des dépendances) terminé.',
        'progression' => 10,
        'statut' => 'En Attente',
        'owner' => 'Marc D. (Owner)',
        'admin' => ['Benoît C.'],
        'members' => ['Élise P.', 'David S.'],
        'notes' => [
            ['date' => '2025-11-20', 'auteur' => 'Benoît', 'contenu' => 'Attente de la validation budgétaire pour l\'achat des licences Azure.'],
        ],
        'agenda' => [
            ['date' => '2025-12-01', 'tache' => 'Validation du budget Azure'],
            ['date' => '2025-12-10', 'tache' => 'Début de la synchronisation des données'],
        ],
    ],
];

// Récupération des données du projet ou données par défaut
$project = $project_data[$project_id] ?? [
    'title' => 'Projet Introuvable', 'description' => 'Veuillez vérifier l\'identifiant.', 
    'progression' => 0, 'statut' => 'Erreur', 'owner' => '', 
    'admin' => [], 'members' => [], 'notes' => [], 'agenda' => [], 
];

// Fonction utilitaire pour générer des badges de statut
function get_status_badge($status) {
    if ($status == 'Terminé') return '<span class="status-badge status-success">Terminé</span>';
    if ($status == 'En Cours') return '<span class="status-badge status-primary">En Cours</span>';
    if ($status == 'En Attente') return '<span class="status-badge status-warning">En Attente</span>';
    return '<span class="status-badge status-error">Erreur</span>';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avancement : <?= htmlspecialchars($project['title']) ?></title>
    
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    <link rel="stylesheet" href="style-dashboard.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Définitions de couleurs pour le thème */
        :root {
            --primary-color: #007bff; /* Bleu standard */
            --accent-color: #17a2b8; /* Cyan pour la progression */
            --card-bg: #242938; /* Fond des cartes */
            --text-color-light: #f0f0f0;
            --separator-color: #444;
        }
        
        /* Structure et lisibilité */
        /* Suppression de .project-header img */
        
        .project-grid {
            display: grid;
            grid-template-columns: 2fr 1fr; 
            gap: 20px;
            margin-top: 20px;
        }
        .section-card {
            background-color: var(--card-bg); 
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); 
            margin-bottom: 20px;
            color: var(--text-color-light); 
        }
        .section-card h3 {
            color: #ffffff; 
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
            margin-bottom: 15px;
            font-size: 1.25em;
        }

        /* Progression (Rendu visible) */
        .progress-bar {
            height: 15px;
            border-radius: 5px;
            background-color: var(--separator-color); 
            margin-top: 10px;
        }
        .progress-fill {
            height: 100%;
            width: <?= $project['progression'] ?>%; 
            background-color: var(--accent-color); 
            border-radius: 5psx;
            transition: width 0.5s ease-out;
        }

        /* Notes et Agenda */
        .note-item, .agenda-item {
            padding: 10px 0;
            border-bottom: 1px solid var(--separator-color);
        }
        .note-item:last-child, .agenda-item:last-child {
            border-bottom: none;
        }
        .note-item small, .agenda-item small {
            color: #bbb; 
        }

        /* Badges */
        .status-primary { background-color: var(--primary-color); }
        .status-success { background-color: #28a745; }
        .status-warning { background-color: #ffc107; color: #333; }
        .role-owner { background-color: #dc3545; } 
        .role-admin { background-color: #ffc107; color: #333; } 
        .role-badge { background-color: #6c757d; } 

        /* Bouton Retour */
        .btn-back {
            background-color: var(--separator-color);
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            color: var(--text-color-light);
            text-decoration: none;
            transition: background-color 0.2s;
            display: inline-block;
        }
        .btn-back:hover {
            background-color: #555;
        }
        
        /* Ajustement du titre principal pour le contraste */
        .project-header h2 {
            color: #fff;
            margin-bottom: 10px; /* Ajout d'une petite marge */
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    
    <aside class="sidebar">
        <div class="logo">
            <i class="fas fa-shopping-cart"></i> ShopMe
        </div>
        <div class="user-profile">
            <div class="avatar"></div>
            <h3>Admin name</h3>
        </div>
        <nav class="menu">
            <a href="dashboard.php" class="menu-item">
                <i class="fas fa-home"></i> Home
            </a>
            </nav>
        <div class="footer">
            © ShopMe Corporation
        </div>
    </aside>
    
    <main class="main-content">
        
        <div class="mb-3" style="margin-bottom: 20px;">
            <a href="dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Retour au Dashboard</a>
        </div>
        
        <header class="header">
            <div class="welcome-section">
                <h1>Projet : <?= htmlspecialchars($project['title']) ?></h1>
                <p class="time">Statut : <?= get_status_badge($project['statut']) ?></p>
            </div>
        </header>

        <div class="project-details-container">
            
            <div class="project-header">
                <p style="color: #bbb;"><?= nl2br(htmlspecialchars($project['description'])) ?></p>
            </div>

            <div class="project-grid">
                
                <div>
                    
                    <div class="section-card">
                        <h3><i class="fas fa-chart-line"></i> Avancement</h3>
                        <p class="mb-1">Progression estimée : <strong><?= $project['progression'] ?>%</strong></p>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?= $project['progression'] ?>%;"></div>
                        </div>
                    </div>

                    <div class="section-card">
                        <h3><i class="fas fa-comment-dots"></i> Notes de Suivi</h3>
                        <?php if (!empty($project['notes'])): ?>
                            <?php foreach ($project['notes'] as $note): ?>
                                <div class="note-item">
                                    <div class="note-header">
                                        <span class="fw-bold"><?= htmlspecialchars($note['auteur']) ?></span>
                                        <small class="text-muted"><?= htmlspecialchars($note['date']) ?></small>
                                    </div>
                                    <p class="mt-1 mb-0"><?= nl2br(htmlspecialchars($note['contenu'])) ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>Aucune note de suivi récente pour ce projet.</p>
                        <?php endif; ?>
                        <button class="btn-add-note mt-3">Ajouter une Note</button>
                    </div>
                </div>

                <div>
                    
                    <div class="section-card">
                        <h3><i class="fas fa-users"></i> Équipe du Projet</h3>
                        <div class="member-item">
                            <span><?= htmlspecialchars($project['owner']) ?></span>
                            <span class="role-badge role-owner">Owner</span>
                        </div>
                        <?php foreach ($project['admin'] as $admin): ?>
                            <div class="member-item">
                                <span><?= htmlspecialchars($admin) ?></span>
                                <span class="role-badge role-admin">Admin</span>
                            </div>
                        <?php endforeach; ?>
                        <?php foreach ($project['members'] as $member): ?>
                            <div class="member-item">
                                <span><?= htmlspecialchars($member) ?></span>
                                <span class="role-badge">Membre</span>
                            </div>
                        <?php endforeach; ?>
                        <button class="btn-manage-members mt-3">Gérer les membres</button>
                    </div>
                    
                    <div class="section-card">
                        <h3><i class="fas fa-calendar-alt"></i> Agenda & Jalons</h3>
                        <?php if (!empty($project['agenda'])): ?>
                            <?php foreach ($project['agenda'] as $jalon): ?>
                                <div class="agenda-item">
                                    <p class="mb-0 fw-bold"><?= htmlspecialchars($jalon['tache']) ?></p>
                                    <small class="text-muted"><i class="fas fa-clock"></i> Date : <?= htmlspecialchars($jalon['date']) ?></small>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                             <p>Aucun jalon planifié pour le moment.</p>
                        <?php endif; ?>
                        <button class="btn-add-milestone mt-3">Ajouter Jalon</button>
                    </div>
                    
                    <div class="section-card">
                        <h3><i class="fas fa-paperclip"></i> Documents</h3>
                        <p><a href="#" style="color: var(--primary-color);">Cahier des Charges Final (<?= htmlspecialchars($project_id) ?>)</a></p>
                        <p><a href="#" style="color: var(--primary-color);">Rapport de Tests QA</a></p>
                    </div>
                </div>
            </div>

        </div>
    </main>

</div>

</body>
</html>