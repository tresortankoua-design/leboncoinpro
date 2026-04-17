<?php
session_start();
require 'config.php';

if (!isset($_SESSION['id'])) {
    header('Location: connexion.php');
    exit;
}

$id_annonce = $_GET['id'];
$id_utilisateur = $_SESSION['id'];

$sql = "DELETE FROM favoris 
        WHERE user_id = :user 
        AND annonce_id = :annonce";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':user' => $id_utilisateur,
    ':annonce' => $id_annonce
]);
// Retour vers page précédente si possible
if (!empty($_SERVER['HTTP_REFERER'])) {
    header('Location: ' . $_SERVER['HTTP_REFERER']);
} else {
    header("Location: favoris.php");
}
exit();