<?php
// db.php

// Paramètres de connexion (A changer avec les infos de ton hébergeur)
$host = 'localhost';      // Ou l'IP de ton serveur de base de données
$db   = 'nom_de_ta_db';   // Le nom de la base que tu as créée
$user = 'ton_user_mysql'; // Souvent "root" en local, mais JAMAIS en prod
$pass = 'ton_mot_de_passe'; 
$charset = 'utf8mb4';     // Important pour les emojis et caractères spéciaux

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Affiche les erreurs SQL
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Retourne des tableaux associatifs
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Sécurité contre les injections SQL
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    // Si rien ne s'affiche, c'est que c'est connecté !
} catch (\PDOException $e) {
    // En production, ne jamais afficher l'erreur exacte à l'utilisateur !
    die("Erreur de connexion à la Base de Données : " . $e->getMessage());
}
?>