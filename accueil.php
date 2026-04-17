<?php
session_start();

// Sécurité : si l'utilisateur n'est pas connecté, on le renvoie à la page de connexion
if (!isset($_SESSION['id'])) {
    header("Location: connexion.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style.css">
    <title>Mon Compte - Leboncoin</title>
</head>
<body>
    <header>
        <nav>
            <a href="accueil.php" class="btn">Accueil</a>
            <a href="liste_annonces.php" class="btn">Annonces</a>
        </nav>
    </header>

    <h1>Bienvenue sur votre espace, <?php echo htmlspecialchars($_SESSION['prenom'] . " " . $_SESSION['nom']); ?> !</h1>
    
    <p>Vous êtes maintenant connecté en toute sécurité.</p>

    <hr>

    <ul>
        <li><a href="creer_annonce.php" class="btn-sm">Créer une annonce</a></li>
        <li><a href="liste_annonces.php" class="btn-sm">Voir les annonces</a></li>
        <li><a href="favoris.php" class="btn-sm">Mes favoris</a></li>
        <li><a href="messages.php" class="btn-sm">Messages</a></li>
        <li><a href="deconnexion.php" class="btn-sm">Déconnexion</a></li>
    </ul>

</body>
</html>