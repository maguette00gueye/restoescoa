<?php

// Démarrer la session
session_start();
// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    // Rediriger vers la page de connexion
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

// Vérifier si l'utilisateur a les permissions nécessaires pour accéder à la page actuelle
// Par exemple, si l'utilisateur n'est pas admin, il ne peut pas accéder à certaines pages
$current_page = basename($_SERVER['PHP_SELF']);
$admin_pages = ['all_user.php', 'add_user.php', 'edit_user.php', 'view_user.php', 'index.php', 'all_menu.php', 'all_ff.php','all_ab.php', ];

// Si la page actuelle est admin et que l'utilisateur n'est pas admin
if (in_array($current_page, $admin_pages) && $_SESSION['user_role'] != 'super_admin' && $_SESSION['user_role'] != 'admin_resto') {
    // Rediriger vers la page d'accès refusé
    header("Location: homepage.php");
    exit();
}

?>
