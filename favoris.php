<?php
session_start();
require 'config.php';

if (!isset($_SESSION['id'])) {
    header('Location: connexion.php');
    exit;
}

$user_id = $_SESSION['id'];

$sql = "SELECT annonces.*
    FROM annonces
    JOIN favoris ON annonces.id = favoris.annonce_id
    WHERE favoris.user_id = :user_id";

$stmt = $pdo->prepare($sql);
$stmt->execute(['user_id' => $user_id]);

$annonces = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style.css">
    <title>Mes favoris - Leboncoin</title>
</head>
<body>
<header>
    <h1>🏷️ Leboncoin</h1>
    <nav>
        <a href="accueil.php" class="btn">Accueil</a>
        <a href="liste_annonces.php">Annonces</a>
        <a href="deconnexion.php">Déconnexion</a>
    </nav>
</header>

<main class="wrap">
    <h2>Mes favoris</h2>

    <?php if ($annonces): ?>
        <div class="grid">
            <?php foreach ($annonces as $annonce): ?>
                <div class="card">
                    <div class="card-body">
                        <div class="card-title"><?= htmlspecialchars($annonce['titre']) ?></div>
                        <div class="card-prix"><?= number_format($annonce['prix'], 2, ',', ' ') ?> €</div>
                        <div class="card-desc"><?= htmlspecialchars($annonce['description']) ?></div>
                        <p style="margin-top:10px"><a href="detail_annonce.php?id=<?= $annonce['id'] ?>" class="btn-sm btn-voir">Voir</a>
                        <a href="supprimer.php?id=<?= $annonce['id'] ?>" class="btn-sm btn-del">Retirer</a></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-annonces">Aucune annonce en favoris.</div>
    <?php endif; ?>
</main>

<footer class="wrap">&copy; <?= date('Y') ?> Leboncoin</footer>
</body>
</html>