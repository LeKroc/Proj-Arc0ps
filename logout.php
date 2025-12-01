<?php
session_start();

// 1. On vide la session
$_SESSION = [];

// 2. On détruit la session côté serveur
session_destroy();

// 3. ON TUE LE COOKIE
// Pour supprimer un cookie, on le renvoie avec une date d'expiration dans le PASSÉ (time() - 3600)
// IMPORTANT : Il faut remettre les mêmes paramètres (Path, Secure, HttpOnly) que lors de la création
if (isset($_COOKIE['mon_site_auth'])) {
    setcookie('mon_site_auth', '', time() - 3600, '/', '', false, true);
    // On supprime aussi la variable du tableau $_COOKIE pour le reste du script
    unset($_COOKIE['mon_site_auth']);
}

// 4. Redirection vers l'accueil
header("Location: index.html");
exit;
?>