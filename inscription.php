<?php
// On inclut la connexion à la base de données

require_once 'config.php'; 
session_start();

$message = "";

// 1. Vérification de l'envoi du formulaire via la méthode POST
if (isset($_POST['inscription'])) {
    
    // Récupération des données saisies par l'utilisateur
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $email = $_POST['mail'];
    $mdp = $_POST['mdp'];

    // 2. Vérification de la longueur du mot de passe (Minimum 10 caractères)
    if (strlen($mdp) < 10) {
        $message = "Erreur : Le mot de passe doit faire au moins 10 caractères.";
    } 
    // 3. Vérification si les champs obligatoires sont remplis
    elseif (empty($email) || empty($mdp)) {
        $message = "Erreur : L'email et le mot de passe sont obligatoires.";
    }
    else {
        // 4. Hachage du mot de passe (Algorithme BCRYPT par défaut)
        // C'est ici que le mot de passe devient une clé sécurisée en base de données
        $mdp_crypte = password_hash($mdp, PASSWORD_DEFAULT);

        try {
            // Détecter la table utilisateur utilisée
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
            $stmtCheck->execute(['utilisateurs']);
            $useUtilisateurs = (bool) $stmtCheck->fetchColumn();

            // Vérifier si la table 'users' possède une colonne 'name'
            $hasName = false;
            if (!$useUtilisateurs) {
                $stmtCol = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'name'");
                $stmtCol->execute();
                $hasName = (bool) $stmtCol->fetchColumn();
            }

            if ($useUtilisateurs) {
                $sql = "INSERT INTO utilisateurs (nom, prenom, email, password) VALUES (?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$nom, $prenom, $email, $mdp_crypte]);
                $newId = $pdo->lastInsertId();
                $_SESSION['id'] = $newId;
                $_SESSION['nom'] = $nom;
                $_SESSION['prenom'] = $prenom;
                $_SESSION['email'] = $email;
            } else {
                if ($hasName) {
                    $full = trim($prenom . ' ' . $nom);
                    $sql = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$full, $email, $mdp_crypte]);
                    $newId = $pdo->lastInsertId();
                    $_SESSION['id'] = $newId;
                    $_SESSION['nom'] = $full;
                    $_SESSION['prenom'] = '';
                    $_SESSION['email'] = $email;
                } else {
                    // users table without name column (only email/password)
                    $sql = "INSERT INTO users (email, password) VALUES (?, ?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$email, $mdp_crypte]);
                    $newId = $pdo->lastInsertId();
                    $_SESSION['id'] = $newId;
                    $_SESSION['nom'] = $nom;
                    $_SESSION['prenom'] = $prenom;
                    $_SESSION['email'] = $email;
                }
            }

            header('Location: accueil.php');
            exit();

        } catch (PDOException $e) {
            $message = "Erreur : Cette adresse email est déjà enregistrée.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style.css">
    <title>Inscription - LeBonCoin</title>
</head>
<body>
    <p><a href="accueil.php" class="btn">Accueil</a></p>
    <h1>Créer un compte</h1>

    <?php if ($message) echo "<p style='color:red'>$message</p>"; ?>

    <form action="inscription.php" method="POST">
        <input type="text" name="nom" placeholder="Nom" required><br><br>
        <input type="text" name="prenom" placeholder="Prénom" required><br><br>
        <input type="email" name="mail" placeholder="Email" required><br><br>
        <input type="password" name="mdp" placeholder="Mot de passe (10 min)" required><br><br>
        
        <input type="submit" name="inscription" value="S'inscrire">
    </form>
    <p>Déjà un compte ? <a href="connexion.php">Se connecter</a></p>
</body>
</html>