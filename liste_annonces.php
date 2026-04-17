<?php
// liste_annonces.php
session_start();
require 'config.php';

// Récupération de toutes les annonces (accessible sans connexion)
$stmt = $pdo->query('SELECT * FROM annonces ORDER BY created_at DESC');
$annonces = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer la liste des favoris de l'utilisateur pour afficher l'état
$userFavs = [];
if (isset($_SESSION['id'])) {
    $stmtFav = $pdo->prepare('SELECT annonce_id FROM favoris WHERE user_id = ?');
    $stmtFav->execute([$_SESSION['id']]);
    $userFavs = $stmtFav->fetchAll(PDO::FETCH_COLUMN);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style.css">
    <title>Leboncoin - Annonces</title>
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
        header nav a:hover { text-decoration: underline; }

        main { padding: 30px; max-width: 1200px; margin: auto; }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .top-bar h2 { font-size: 20px; color: #333; }
        .btn-create {
            background: #f56b2a;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 15px;
        }
        .btn-create:hover { background: #d4521a; }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: transform 0.2s;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .card:hover { transform: translateY(-3px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }

        .card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }
        .card-no-img {
            width: 100%;
            height: 180px;
            background: #eee;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #aaa;
            font-size: 14px;
        }

        .card-body { padding: 15px; }
        .card-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #222;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .card-prix {
            font-size: 18px;
            color: #f56b2a;
            font-weight: bold;
            margin-bottom: 6px;
        }
        .card-desc {
            font-size: 13px;
            color: #666;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .card-date {
            font-size: 11px;
            color: #aaa;
            margin-top: 8px;
        }

        .no-annonces {
            text-align: center;
            color: #888;
            font-size: 18px;
            margin-top: 60px;
        }
    </style>
</head>
<body>

<header>
    <h1>🏷️ Leboncoin</h1>
    <nav>
        <a href="accueil.php" class="btn">Accueil</a>
        <?php if (isset($_SESSION['id'])): ?>
            <a href="mes_annonces.php">Mes annonces</a>
            <a href="creer_annonce.php">+ Déposer une annonce</a>
            <a href="deconnexion.php">Déconnexion</a>
        <?php else: ?>
            <a href="connexion.php">Connexion</a>
            <a href="inscription.php">Inscription</a>
        <?php endif; ?>
    </nav>
</header>

<main>
    <div class="top-bar">
        <h2>Toutes les annonces</h2>
        <?php if (isset($_SESSION['id'])): ?>
            <a href="creer_annonce.php" class="btn-create">+ Déposer une annonce</a>
        <?php endif; ?>
    </div>

    <?php if (empty($annonces)): ?>
        <div class="no-annonces">Aucune annonce disponible pour le moment.</div>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($annonces as $annonce): ?>
                <a class="card" href="detail_annonce.php?id=<?= $annonce['id'] ?>">
                    <?php if (!empty($annonce['image']) && file_exists('uploads/' . $annonce['image'])): ?>
                        <img src="uploads/<?= htmlspecialchars($annonce['image']) ?>" alt="<?= htmlspecialchars($annonce['titre']) ?>">
                    <?php else: ?>
                        <div class="card-no-img">Pas d'image</div>
                    <?php endif; ?>
                    <div class="card-body">
                        <div class="card-title"><?= htmlspecialchars($annonce['titre']) ?></div>
                        <div class="card-prix"><?= number_format($annonce['prix'], 2, ',', ' ') ?> €</div>
                        <div class="card-desc"><?= htmlspecialchars($annonce['description']) ?></div>
                        <div class="card-date"><?= date('d/m/Y', strtotime($annonce['created_at'])) ?></div>
                        <div style="margin-top:10px">
                            <?php if (isset($_SESSION['id'])): ?>
                                <?php if (in_array($annonce['id'], $userFavs)): ?>
                                    <a href="supprimer.php?id=<?= $annonce['id'] ?>" class="btn" style="background:linear-gradient(90deg,var(--orange),#d97706)">💔 Retirer</a>
                                <?php else: ?>
                                    <a href="ajout.php?id=<?= $annonce['id'] ?>" class="btn">♡ Favoris</a>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="connexion.php" class="btn">♡ Favoris</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

</body>
</html>
