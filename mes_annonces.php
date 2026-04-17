<?php
// mes_annonces.php
session_start();
require 'config.php';

if (!isset($_SESSION['id'])) {
    header("Location: connexion.php");
    exit;
}

$user_id = $_SESSION['id'];
$stmt = $pdo->prepare('SELECT * FROM annonces WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$user_id]);
$annonces = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style.css">
    <title>Mes annonces - Leboncoin</title>
</head>
<body>
<header>
    <h1>🏷️ Leboncoin</h1>
    <nav>
        <a href="accueil.php" class="btn">Accueil</a>
        <?php if (isset($_SESSION['id'])): ?>
            <a href="creer_annonce.php" class="btn-create">+ Déposer une annonce</a>
            <a href="deconnexion.php">Déconnexion</a>
        <?php else: ?>
            <a href="connexion.php">Connexion</a>
            <a href="inscription.php">Inscription</a>
        <?php endif; ?>
    </nav>
</header>

<main class="wrap">
    <div class="top-bar">
        <h2>📋 Mes annonces</h2>
        <a href="creer_annonce.php" class="btn-create">+ Nouvelle annonce</a>
    </div>

    <?php if (empty($annonces)): ?>
        <div class="no-annonces">
            <p>Vous n'avez pas encore d'annonce.</p>
            <a href="creer_annonce.php" class="btn-create">Déposer ma première annonce</a>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Photo</th>
                    <th>Titre</th>
                    <th>Prix</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($annonces as $annonce): ?>
                    <tr>
                        <td>
                            <?php if (!empty($annonce['image']) && file_exists('uploads/' . $annonce['image'])): ?>
                                <img class="annonce-img" src="uploads/<?= htmlspecialchars($annonce['image']) ?>" alt="">
                            <?php else: ?>
                                <div class="no-img-mini">Pas d'img</div>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($annonce['titre']) ?></td>
                        <td class="prix"><?= number_format($annonce['prix'], 2, ',', ' ') ?> €</td>
                        <td><?= date('d/m/Y', strtotime($annonce['created_at'])) ?></td>
                        <td>
                            <div class="actions-td">
                                <a href="detail_annonce.php?id=<?= $annonce['id'] ?>" class="btn-sm btn-voir">👁 Voir</a>
                                <a href="modifier_annonce.php?id=<?= $annonce['id'] ?>" class="btn-sm btn-edit">✏️ Modifier</a>
                                <a href="supprimer_annonce.php?id=<?= $annonce['id'] ?>" class="btn-sm btn-del">🗑️ Suppr.</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>

<footer class="wrap">
    <p>&copy; <?= date('Y') ?> Leboncoin</p>
</footer>

</body>
</html>
