<?php
// Informations de connexion à la base de données
$host = "localhost";
$dbname = "resto_escoa_db";
$username = "root";
$password = "";
$charset = "utf8mb4";

// Options PDO
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

// Connexion PDO
try {
    $conn = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=$charset",
        $username,
        $password,
        $options
    );
} catch (PDOException $e) {
    // En cas d'erreur
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}