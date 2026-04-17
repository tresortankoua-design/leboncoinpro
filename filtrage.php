<?php
session_start();
require 'config.php';

// Pas de session forcée ici : garder anonyme si besoin

$sql = "SELECT * FROM annonces WHERE 1=1";
$params = [];

/* FILTRE PRIX */
if (isset($_GET['prix_max']) && $_GET['prix_max'] !== "") {
    $sql .= " AND prix <= :prix";
    $params[':prix'] = $_GET['prix_max'];
}

/* FILTRE CATEGORIE */
if (isset($_GET['categorie']) && $_GET['categorie'] !== "") {
    $sql .= " AND categorie = :categorie";
    $params[':categorie'] = $_GET['categorie'];
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$annonces = $stmt->fetchAll();
?>

<p><a href="accueil.php" class="btn">Accueil</a></p>
<h1>Liste des annonces</h1>

<form method="GET">
    <input type="number" name="prix_max" placeholder="Prix max">

    <select name="categorie">
        <option value="">Toutes les catégories</option>
        <option value="Voiture">Voiture</option>
        <option value="Téléphone">Téléphone</option>
        <option value="Immobilier">Immobilier</option>
    </select>

    <button type="submit">Filtrer</button>
</form>

<hr>

<?php if (empty($annonces)): ?>
    <p>Aucune annonce trouvée.</p>
<?php else: ?>
    <?php foreach ($annonces as $a): ?>
        <h3><?= $a['titre'] ?></h3>
        <p><?= $a['prix'] ?> €</p>
        <p><?= $a['categorie'] ?></p>
        <hr>
    <?php endforeach; ?>
<?php endif; ?>