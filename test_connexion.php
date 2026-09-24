<?php
// On active l'affichage des erreurs
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "1. PHP fonctionne bien !<br>";

// Test de connexion à la base de données todo_db
$pdo = new PDO('mysql:host=localhost;dbname=todo_db;charset=utf8', 'root', '');

echo "2. Connexion à la base de données réussie !";
?>