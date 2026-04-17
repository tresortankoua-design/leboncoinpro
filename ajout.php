<?php
session_start();
require 'config.php';

if (!isset($_SESSION['id'])) {
    header('Location: connexion.php');
    exit;
}

// Valider et nettoyer l'id d'annonce
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: liste_annonces.php');
    exit;
}
$id_annonce = (int) $_GET['id'];
$id_utilisateur = (int) $_SESSION['id'];

$sql = "INSERT IGNORE INTO favoris (user_id, annonce_id) VALUES (:user, :annonce)";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':user' => $id_utilisateur,
    ':annonce' => $id_annonce
]);

// Retour vers la page précédente si possible
if (!empty($_SERVER['HTTP_REFERER'])) {
    header('Location: ' . $_SERVER['HTTP_REFERER']);
} else {
    header('Location: liste_annonces.php');
}
exit();