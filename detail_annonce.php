<?php
// detail_annonce.php
session_start();
require 'config.php';

// Vérification de l'id en GET
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: liste_annonces.php");
    exit;
}

$id = (int) $_GET['id'];
$annonce = null;
// S'assurer que la colonne user_id existe (ajout si nécessaire)
try {
    $stmtCol = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'annonces' AND column_name = 'user_id'");
    $stmtCol->execute();
    if ($stmtCol->fetchColumn() == 0) {
        $pdo->exec("ALTER TABLE annonces ADD COLUMN user_id INT(11) NOT NULL DEFAULT 0 AFTER image");
    }
} catch (Exception $e) {}

// Détecter la table utilisateurs / users et faire la jointure correspondante
$stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
$stmtCheck->execute(['utilisateurs']);
$useUtilisateurs = (bool) $stmtCheck->fetchColumn();
if ($useUtilisateurs) {
    $sql = 'SELECT annonces.*, utilisateurs.nom AS vendeur_nom, utilisateurs.prenom AS vendeur_prenom, utilisateurs.email AS vendeur_email, utilisateurs.id AS vendeur_id FROM annonces JOIN utilisateurs ON annonces.user_id = utilisateurs.id WHERE annonces.id = ?';
} else {
    $sql = 'SELECT annonces.*, users.name AS vendeur_nom, users.email AS vendeur_email, users.id AS vendeur_id FROM annonces JOIN users ON annonces.user_id = users.id WHERE annonces.id = ?';
}
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$annonce = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$annonce) {
    header("Location: liste_annonces.php");
    exit;
}

$est_proprio = isset($_SESSION['id']) && $_SESSION['id'] == ($annonce['user_id'] ?? $annonce['vendeur_id'] ?? 0);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style.css">
    <title><?= htmlspecialchars($annonce['titre']) ?> - Leboncoin</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }

        header {
            background: #f56b2a;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        header h1 { font-size: 24px; }
        header nav a {
            color: white;
            text-decoration: none;
            margin-left: 15px;
            font-weight: bold;
        }

        main {
            max-width: 900px;
            margin: 40px auto;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
        }

        .retour {
            display: inline-block;
            margin-bottom: 20px;
            color: #f56b2a;
            text-decoration: none;
            font-size: 14px;
        }
        .retour:hover { text-decoration: underline; }

        .annonce-img {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        .no-img {
            width: 100%;
            height: 250px;
            background: #eee;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #aaa;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 16px;
        }

        h2 { font-size: 26px; color: #222; margin-bottom: 10px; }
        .prix { font-size: 28px; color: #f56b2a; font-weight: bold; margin-bottom: 20px; }
        .description { font-size: 15px; color: #444; line-height: 1.6; margin-bottom: 20px; }
        .date { font-size: 12px; color: #aaa; margin-bottom: 30px; }

        .actions { display: flex; gap: 15px; flex-wrap: wrap; }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 15px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-modifier { background: #3498db; color: white; }
        .btn-modifier:hover { background: #2179ae; }
        .btn-supprimer { background: #e74c3c; color: white; }
        .btn-supprimer:hover { background: #c0392b; }
        .btn-contacter { background: #2ecc71; color: white; }
        .btn-contacter:hover { background: #27ae60; }
        .btn-retour { background: #eee; color: #333; }
        .btn-retour:hover { background: #ddd; }
    </style>
</head>
<body>

<header>
    <h1>🏷️ Leboncoin</h1>
    <nav>
        <a href="accueil.php" class="btn">Accueil</a>
        <?php if (isset($_SESSION['id'])): ?>
            <a href="mes_annonces.php">Mes annonces</a>
            <a href="deconnexion.php">Déconnexion</a>
        <?php else: ?>
            <a href="connexion.php">Connexion</a>
            <a href="inscription.php">Inscription</a>
        <?php endif; ?>
    </nav>
</header>

<main>
    <a href="liste_annonces.php" class="retour">← Retour aux annonces</a>

    <?php if (!empty($annonce['image']) && file_exists('uploads/' . $annonce['image'])): ?>
        <img class="annonce-img" src="uploads/<?= htmlspecialchars($annonce['image']) ?>" alt="<?= htmlspecialchars($annonce['titre']) ?>">
    <?php else: ?>
        <div class="no-img">Pas d'image disponible</div>
    <?php endif; ?>

    <h2><?= htmlspecialchars($annonce['titre']) ?></h2>
    <div class="prix"><?= number_format($annonce['prix'], 2, ',', ' ') ?> €</div>
    <div class="description"><?= nl2br(htmlspecialchars($annonce['description'])) ?></div>
    <div class="date">Publiée le <?= date('d/m/Y à H:i', strtotime($annonce['created_at'])) ?></div>

    <hr style="margin:18px 0">
    <h3>Vendeur</h3>
    <div><strong><?= htmlspecialchars(trim(($annonce['vendeur_prenom'] ?? '') . ' ' . ($annonce['vendeur_nom'] ?? $annonce['vendeur_nom'] ?? ''))) ?></strong></div>
    <div style="font-size:14px;color:#6b7280"><?= htmlspecialchars($annonce['vendeur_email'] ?? '') ?></div>

    <div class="actions">
        <?php if ($est_proprio): ?>
            <a href="modifier_annonce.php?id=<?= $annonce['id'] ?>" class="btn btn-modifier">✏️ Modifier</a>
            <a href="supprimer_annonce.php?id=<?= $annonce['id'] ?>" class="btn btn-supprimer">🗑️ Supprimer</a>
        <?php elseif (isset($_SESSION['id'])): ?>
            <!-- Bouton pour ouvrir la messagerie avec le propriétaire -->
            <?php $vendeur_id = $annonce['vendeur_id'] ?? $annonce['user_id'] ?? 0; ?>
            <a href="messages.php?with=<?= (int)$vendeur_id ?>" class="btn btn-contacter">✉️ Contacter le vendeur</a>
        <?php else: ?>
            <a href="connexion.php" class="btn btn-contacter">🔒 Connectez-vous pour contacter</a>
        <?php endif; ?>
        <?php // Favoris: si connecté, afficher ajouter/retirer ?>
        <?php if (isset($_SESSION['id'])): ?>
            <?php
                $stmtF = $pdo->prepare('SELECT COUNT(*) FROM favoris WHERE user_id = ? AND annonce_id = ?');
                $stmtF->execute([$_SESSION['id'], $annonce['id']]);
                $isFav = (bool) $stmtF->fetchColumn();
            ?>
            <?php if ($isFav): ?>
                <a href="supprimer.php?id=<?= $annonce['id'] ?>" class="btn" style="background:linear-gradient(90deg,var(--orange),#d97706)">💔 Retirer des favoris</a>
            <?php else: ?>
                <a href="ajout.php?id=<?= $annonce['id'] ?>" class="btn">♡ Ajouter aux favoris</a>
            <?php endif; ?>
        <?php else: ?>
            <a href="connexion.php" class="btn">♡ Ajouter aux favoris</a>
        <?php endif; ?>
        <a href="liste_annonces.php" class="btn btn-retour">← Retour</a>
    </div>
</main>

</body>
</html>
