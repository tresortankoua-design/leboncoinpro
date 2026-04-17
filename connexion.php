<?php
session_start();
require_once 'config.php'; 

if (isset($_POST['connexion'])) {
    $mail = $_POST['mail'];
    $mdp = $_POST['mdp'];

    // Détecter la table utilisateur utilisée
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
    $stmtCheck->execute(['utilisateurs']);
    $useUtilisateurs = (bool) $stmtCheck->fetchColumn();

    if ($useUtilisateurs) {
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    }
    $stmt->execute([$mail]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Comparaison du mot de passe saisi avec le hachage en base
    if ($user && password_verify($mdp, $user['password'])) {
        // 3. Création des variables de session
        $_SESSION['id'] = $user['id'];
        if ($useUtilisateurs) {
            $_SESSION['nom'] = $user['nom'] ?? '';
            $_SESSION['prenom'] = $user['prenom'] ?? '';
            $_SESSION['email'] = $user['email'] ?? '';
        } else {
            // table 'users' may have 'name' or only email
            if (isset($user['name'])) {
                $_SESSION['nom'] = $user['name'];
                $_SESSION['prenom'] = '';
            } else {
                $_SESSION['nom'] = '';
                $_SESSION['prenom'] = '';
            }
            $_SESSION['email'] = $user['email'] ?? '';
        }

        header("Location: accueil.php");
        exit();
    } else {
        echo "Email ou mot de passe incorrect.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style.css">
    <title>Connexion</title>
</head>
<body>
    <p><a href="accueil.php" class="btn">Accueil</a></p>
    <h1>Connexion</h1>

    <form action="connexion.php" method="POST">
        <input type="email" name="mail" placeholder="Email" required><br><br>
        <input type="password" name="mdp" placeholder="Mot de passe" required><br><br>
        <input type="submit" name="connexion" value="Se connecter">
    </form>
</body>
</html>