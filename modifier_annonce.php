<?php
// modifier_annonce.php
session_start();
require 'config.php';

// Doit être connecté
if (!isset($_SESSION['id'])) {
    header("Location: connexion.php");
    exit;
}

// Vérification de l'id
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
    // Annonce inexistante ou pas la sienne
    header("Location: liste_annonces.php");
    exit;
}
$erreurs = [];

if (isset($_POST['modifier'])) {
    $titre       = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $prix        = $_POST['prix'] ?? 0;

    // Validations
    if (empty($titre))       $erreurs[] = "Le titre est obligatoire.";
    if (empty($description)) $erreurs[] = "La description est obligatoire.";
    if (!is_numeric($prix) || $prix < 0) $erreurs[] = "Le prix doit être un nombre positif.";

    // Gestion de la nouvelle image (optionnelle en modif)
    $nom_image = $annonce['image']; // On garde l'ancienne par défaut

    if (!empty($_FILES['image']['name'])) {
        $ext_autorisees = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $ext_autorisees)) {
            $erreurs[] = "Format d'image non accepté (jpg, png, gif, webp).";
        } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
            $erreurs[] = "L'image ne doit pas dépasser 5 Mo.";
        } else {
            if (!is_dir('uploads')) mkdir('uploads', 0755, true);
            $nouveau_nom = uniqid('img_') . '.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], 'uploads/' . $nouveau_nom)) {
                // Supprimer l'ancienne image
                if (!empty($annonce['image']) && file_exists('uploads/' . $annonce['image'])) {
                    unlink('uploads/' . $annonce['image']);
                }
                $nom_image = $nouveau_nom;
            } else {
                $erreurs[] = "Erreur lors de l'upload de la nouvelle image.";
            }
        }
    }

    if (empty($erreurs)) {
        $prix_float = (float) $prix;
        // Mise à jour via PDO
        $sql = 'UPDATE annonces SET titre = ?, description = ?, prix = ?, image = ? WHERE id = ? AND user_id = ?';
        $stmtUp = $pdo->prepare($sql);
        $stmtUp->execute([$titre, $description, $prix_float, $nom_image ?: null, $id, $user_id]);
        header("Location: detail_annonce.php?id=$id");
        exit;
    }
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
        input:focus, textarea:focus { outline: none; border-color: #f56b2a; }
        textarea { resize: vertical; min-height: 120px; }

        .image-actuelle { margin-bottom: 15px; }
        .image-actuelle img { max-width: 100%; max-height: 200px; border-radius: 8px; }
        .image-actuelle p { font-size: 13px; color: #888; margin-bottom: 8px; }

        .upload-zone {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
            cursor: pointer;
        }
        .upload-zone:hover { border-color: #f56b2a; }
        .upload-zone input { display: none; }
        .upload-zone p { color: #888; font-size: 13px; margin-top: 6px; }
        #preview { max-width: 100%; max-height: 180px; margin-top: 12px; border-radius: 5px; display: none; }

        .btns { display: flex; gap: 15px; }
        .btn-modifier {
            flex: 1;
            background: #3498db;
            color: white;
            padding: 14px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-modifier:hover { background: #2179ae; }
        .btn-annuler {
            flex: 1;
            background: #eee;
            color: #333;
            padding: 14px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            font-weight: bold;
        }
        .btn-annuler:hover { background: #ddd; }

        .retour { display: inline-block; margin-bottom: 20px; color: #f56b2a; text-decoration: none; font-size: 14px; }
        .retour:hover { text-decoration: underline; }
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
    <a href="detail_annonce.php?id=<?= $id ?>" class="retour">← Retour à l'annonce</a>
    <h2>✏️ Modifier l'annonce</h2>

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

        <label for="titre">Titre *</label>
        <input type="text" id="titre" name="titre" value="<?= htmlspecialchars(isset($_POST['titre']) ? $_POST['titre'] : $annonce['titre']) ?>" required>

        <label for="prix">Prix (€) *</label>
        <input type="number" id="prix" name="prix" min="0" step="0.01" value="<?= htmlspecialchars(isset($_POST['prix']) ? $_POST['prix'] : $annonce['prix']) ?>" required>

        <label for="description">Description *</label>
        <textarea id="description" name="description" required><?= htmlspecialchars(isset($_POST['description']) ? $_POST['description'] : $annonce['description']) ?></textarea>

        <label>Photo actuelle</label>
        <div class="image-actuelle">
            <?php if (!empty($annonce['image']) && file_exists('uploads/' . $annonce['image'])): ?>
                <p>Image actuelle (laissez vide pour la conserver) :</p>
                <img src="uploads/<?= htmlspecialchars($annonce['image']) ?>" alt="Image actuelle">
            <?php else: ?>
                <p>Aucune image actuellement.</p>
            <?php endif; ?>
        </div>

        <label>Changer la photo (optionnel)</label>
        <div class="upload-zone" onclick="document.getElementById('image').click()">
            <input type="file" id="image" name="image" accept="image/*" onchange="previewImage(this)">
            <span style="font-size: 30px;">📷</span>
            <p>Cliquez pour choisir une nouvelle photo<br><small>JPG, PNG, GIF, WEBP — max 5 Mo</small></p>
            <img id="preview" src="" alt="Aperçu">
        </div>

        <div class="btns">
            <button type="submit" name="modifier" class="btn-modifier">💾 Enregistrer</button>
            <a href="detail_annonce.php?id=<?= $id ?>" class="btn-annuler">❌ Annuler</a>
        </div>
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
