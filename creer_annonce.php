<?php
// creer_annonce.php
session_start();
require 'config.php';

// Doit être connecté
if (!isset($_SESSION['id'])) {
    header("Location: connexion.php");
    exit;
}

$erreurs = [];
$success = false;

// S'assurer que la colonne user_id existe dans la table annonces
try {
    $stmtCol = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'annonces' AND column_name = 'user_id'");
    $stmtCol->execute();
    if ($stmtCol->fetchColumn() == 0) {
        // ajouter la colonne user_id
        $pdo->exec("ALTER TABLE annonces ADD COLUMN user_id INT(11) NOT NULL DEFAULT 0 AFTER image");
    }
} catch (Exception $e) {
    // ne pas bloquer l'utilisateur si l'opération échoue
}

if (isset($_POST['creer'])) {
    $titre       = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $prix        = $_POST['prix'] ?? 0;
    $user_id     = $_SESSION['id'];

    // Validations
    if (empty($titre))       $erreurs[] = "Le titre est obligatoire.";
    if (empty($description)) $erreurs[] = "La description est obligatoire.";
    if (!is_numeric($prix) || $prix < 0) $erreurs[] = "Le prix doit être un nombre positif.";

    // Gestion de l'image
    $nom_image = null;
    if (empty($_FILES['image']['name'])) {
        $erreurs[] = "Une photo est obligatoire.";
    } else {
        $ext_autorisees = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $ext_autorisees)) {
            $erreurs[] = "Format d'image non accepté (jpg, png, gif, webp).";
        } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
            $erreurs[] = "L'image ne doit pas dépasser 5 Mo.";
        } else {
            // Créer le dossier uploads si besoin
            if (!is_dir('uploads')) mkdir('uploads', 0755, true);
            $nom_image = uniqid('img_') . '.' . $ext;
            if (!move_uploaded_file($_FILES['image']['tmp_name'], 'uploads/' . $nom_image)) {
                $erreurs[] = "Erreur lors de l'upload de l'image.";
                $nom_image = null;
            }
        }
    }

    if (empty($erreurs)) {
        $prix_float = (float) $prix;
        // Insertion PDO
        $stmt = $pdo->prepare('INSERT INTO annonces (titre, description, prix, image, user_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
        $stmt->execute([$titre, $description, $prix_float, $nom_image ?: null, $user_id]);
        $nouvel_id = $pdo->lastInsertId();
        header("Location: detail_annonce.php?id=$nouvel_id");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style.css">
    <title>Déposer une annonce - Leboncoin</title>
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
        header nav a { color: white; text-decoration: none; margin-left: 15px; font-weight: bold; }

        main {
            max-width: 650px;
            margin: 40px auto;
            background: white;
            border-radius: 10px;
            padding: 35px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        h2 { font-size: 22px; margin-bottom: 25px; color: #333; }

        .erreurs {
            background: #fdecea;
            border-left: 4px solid #e74c3c;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .erreurs ul { padding-left: 20px; }
        .erreurs li { color: #c0392b; font-size: 14px; margin-bottom: 5px; }

        label { display: block; font-weight: bold; margin-bottom: 6px; color: #444; font-size: 14px; }

        input[type="text"],
        input[type="number"],
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 15px;
            margin-bottom: 20px;
            font-family: inherit;
        }
        input:focus, textarea:focus {
            outline: none;
            border-color: #f56b2a;
        }
        textarea { resize: vertical; min-height: 120px; }

        .upload-zone {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 25px;
            text-align: center;
            margin-bottom: 20px;
            cursor: pointer;
            transition: border-color 0.2s;
        }
        .upload-zone:hover { border-color: #f56b2a; }
        .upload-zone input { display: none; }
        .upload-zone p { color: #888; font-size: 14px; margin-top: 8px; }
        #preview { max-width: 100%; max-height: 200px; margin-top: 15px; border-radius: 5px; display: none; }

        .btn-submit {
            width: 100%;
            background: #f56b2a;
            color: white;
            padding: 14px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-submit:hover { background: #d4521a; }

        .retour { display: inline-block; margin-bottom: 20px; color: #f56b2a; text-decoration: none; font-size: 14px; }
        .retour:hover { text-decoration: underline; }
    </style>
</head>
<body>

<header>
    <h1>🏷️ Leboncoin</h1>
    <nav>
           <a href="accueil.php" class="btn">Accueil</a>
           <a href="liste_annonces.php">Annonces</a>
           <a href="mes_annonces.php">Mes annonces</a>
           <a href="deconnexion.php">Déconnexion</a>
    </nav>
</header>

<main>
    <a href="liste_annonces.php" class="retour">← Retour aux annonces</a>
    <h2>📝 Déposer une annonce</h2>

    <?php if (!empty($erreurs)): ?>
        <div class="erreurs">
            <ul>
                <?php foreach ($erreurs as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">

        <label for="titre">Titre de l'annonce *</label>
        <input type="text" id="titre" name="titre" placeholder="Ex: iPhone 13 Pro" value="<?= isset($_POST['titre']) ? htmlspecialchars($_POST['titre']) : '' ?>" required>

        <label for="prix">Prix (€) *</label>
        <input type="number" id="prix" name="prix" placeholder="Ex: 150" min="0" step="0.01" value="<?= isset($_POST['prix']) ? htmlspecialchars($_POST['prix']) : '' ?>" required>

        <label for="description">Description *</label>
        <textarea id="description" name="description" placeholder="Décrivez votre article en détail..." required><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>

        <label>Photo *</label>
        <div class="upload-zone" onclick="document.getElementById('image').click()">
            <input type="file" id="image" name="image" accept="image/*" onchange="previewImage(this)">
            <span style="font-size: 36px;">📷</span>
            <p>Cliquez pour choisir une photo<br><small>JPG, PNG, GIF, WEBP — max 5 Mo</small></p>
            <img id="preview" src="" alt="Aperçu">
        </div>

        <button type="submit" name="creer" class="btn-submit">✅ Publier l'annonce</button>
    </form>
</main>

<script>
function previewImage(input) {
    const preview = document.getElementById('preview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

</body>
</html>
