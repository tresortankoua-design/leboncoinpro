<?php
// supprimer_annonce.php
session_start();
require 'config.php';

// Doit être connecté
if (!isset($_SESSION['id'])) {
    header("Location: connexion.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: liste_annonces.php");
    exit;
}

$id = (int) $_GET['id'];
$user_id = $_SESSION['id'];

// Récupérer l'annonce ET vérifier que c'est bien celle de l'user
$stmt = $pdo->prepare('SELECT * FROM annonces WHERE id = ? AND user_id = ?');
$stmt->execute([$id, $user_id]);
$annonce = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$annonce) {
    header("Location: liste_annonces.php");
    exit;
}

// Si confirmé via POST
if (isset($_POST['confirmer'])) {
    // Supprimer les messages liés
    $stmtDelMsg = $pdo->prepare('DELETE FROM messages WHERE annonce_id = ?');
    $stmtDelMsg->execute([$id]);

    // Supprimer les favoris liés
    $stmtDelFav = $pdo->prepare('DELETE FROM favoris WHERE annonce_id = ?');
    $stmtDelFav->execute([$id]);

    // Supprimer le fichier image
    if (!empty($annonce['image']) && file_exists('uploads/' . $annonce['image'])) {
        unlink('uploads/' . $annonce['image']);
    }

    // Supprimer l'annonce
    $stmtDel = $pdo->prepare('DELETE FROM annonces WHERE id = ? AND user_id = ?');
    $stmtDel->execute([$id, $user_id]);

    header("Location: liste_annonces.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
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
            background: #f56b2a;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        header h1 { font-size: 24px; }
        header nav a { color: white; text-decoration: none; margin-left: 15px; font-weight: bold; }

        main {
            max-width: 550px;
            margin: 80px auto;
            background: white;
            border-radius: 10px;
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .icone { font-size: 60px; margin-bottom: 20px; }
        h2 { font-size: 22px; color: #333; margin-bottom: 15px; }

        .annonce-titre {
            font-size: 18px;
            font-weight: bold;
            color: #f56b2a;
            background: #fff5f0;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        p { color: #666; font-size: 15px; margin-bottom: 30px; line-height: 1.5; }

        .btns { display: flex; gap: 15px; justify-content: center; }

        .btn-confirmer {
            background: #e74c3c;
            color: white;
            padding: 14px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-confirmer:hover { background: #c0392b; }

        .btn-annuler {
            background: #eee;
            color: #333;
            padding: 14px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            font-weight: bold;
        }
        .btn-annuler:hover { background: #ddd; }
    </style>
</head>
<body>

<header>
    <h1>🏷️ Leboncoin</h1>
    <nav>
        <a href="liste_annonces.php">Annonces</a>
        <a href="mes_annonces.php">Mes annonces</a>
        <a href="deconnexion.php">Déconnexion</a>
    </nav>
</header>

<main>
    <div class="icone">⚠️</div>
    <h2>Confirmer la suppression</h2>
    <div class="annonce-titre"><?= htmlspecialchars($annonce['titre']) ?></div>
    <p>Vous êtes sur le point de supprimer cette annonce définitivement.<br>
    Cette action est <strong>irréversible</strong>. Les messages liés seront également supprimés.</p>

    <form method="post">
        <div class="btns">
            <button type="submit" name="confirmer" class="btn-confirmer">🗑️ Oui, supprimer</button>
            <a href="detail_annonce.php?id=<?= $id ?>" class="btn-annuler">❌ Annuler</a>
        </div>
    </form>
</main>

</body>
</html>
