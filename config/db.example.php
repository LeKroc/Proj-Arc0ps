<?php
// db.php

// --- SECURITE ---
// Cette clé sert à signer tes cookies. Change-la pour une phrase unique et longue !
define('SECRET_KEY', 'salut_je_suis_une_cle_secrete_change_moi!');

// --- CONNEXION DB ---
// Paramètres de connexion
$host = 'localhost';      
$db   = 'nom_de_ta_db';   
$user = 'ton_user_mysql'; 
$pass = 'ton_mot_de_passe'; 
$charset = 'utf8mb4';     

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Erreur de connexion à la Base de Données : " . $e->getMessage());
}
?>