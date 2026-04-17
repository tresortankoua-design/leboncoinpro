<?php
session_start(); // On récupère la session en cours

// On vide toutes les variables de session
session_unset();

// On détruit la session côté serveur
session_destroy();

// On redirige immédiatement vers la page d'inscription
header("Location: inscription.php");
exit();
?>