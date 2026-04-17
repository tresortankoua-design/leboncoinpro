<?php
session_start();
require_once 'config.php'; // fournit $pdo

if (!isset($_SESSION['id'])) {
    header('Location: connexion.php');
    exit;
}

$errors = [];

// Si on a un annonce_id en GET, déterminer le destinataire
$destinataire_id = null;
if (isset($_GET['annonce_id']) && is_numeric($_GET['annonce_id'])) {
    $annonce_id = (int) $_GET['annonce_id'];
    $stmt = $pdo->prepare('SELECT user_id, titre FROM annonces WHERE id = ?');
    $stmt->execute([$annonce_id]);
    $ann = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($ann) {
        $destinataire_id = $ann['user_id'];
        $annonce_titre = $ann['titre'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Accept both legacy field names and the AJAX-friendly ones
    $to = 0;
    if (isset($_POST['to_user'])) $to = (int) $_POST['to_user'];
    elseif (isset($_POST['receiver_id'])) $to = (int) $_POST['receiver_id'];
    $contenu = trim($_POST['contenu'] ?? ($_POST['message'] ?? ''));

    if ($to <= 0) $errors[] = 'Destinataire invalide.';
    if ($contenu === '') $errors[] = 'Le message ne peut pas être vide.';

    if (empty($errors)) {
        // Inserer avec annonce_id si fourni
        $ann_id = isset($_POST['annonce_id']) && is_numeric($_POST['annonce_id']) ? (int) $_POST['annonce_id'] : null;

        // Détecter noms de colonnes réels dans la table messages pour compatibilité
        $senderCandidates = ['sender_id','sender','user_from','from_user'];
        $receiverCandidates = ['receiver_id','recipient_id','recipient','to_user','user_to','receiver','destinataire_id'];
        $messageCandidates = ['message','contenu','body','texte'];
        $senderCol = null; $receiverCol = null; $messageCol = null;
        foreach ($senderCandidates as $c) {
            $s = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'messages' AND column_name = ?");
            $s->execute([$c]); if ($s->fetchColumn() > 0) { $senderCol = $c; break; }
        }
        foreach ($receiverCandidates as $c) {
            $s = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'messages' AND column_name = ?");
            $s->execute([$c]); if ($s->fetchColumn() > 0) { $receiverCol = $c; break; }
        }
        foreach ($messageCandidates as $c) {
            $s = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'messages' AND column_name = ?");
            $s->execute([$c]); if ($s->fetchColumn() > 0) { $messageCol = $c; break; }
        }
        if (!$senderCol) $senderCol = 'sender_id';
        if (!$receiverCol) $receiverCol = 'receiver_id';
        if (!$messageCol) $messageCol = 'message';

        // Construire la requête dynamiquement
        $cols = [ $senderCol, $receiverCol, $messageCol ];
        $placeholders = ['?','?','?'];
        $params = [ $_SESSION['id'], $to, $contenu ];
        if ($ann_id) {
            $cols[] = 'annonce_id';
            $placeholders[] = '?';
            $params[] = $ann_id;
        }

        // Vérifier si la colonne created_at existe dans la table messages
        $hasCreated = false;
        try {
            $s = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'messages' AND column_name = 'created_at'");
            $s->execute();
            $hasCreated = (bool) $s->fetchColumn();
        } catch (Exception $e) {
            $hasCreated = false;
        }

        if ($hasCreated) {
            // include created_at with NOW()
            $sql = 'INSERT INTO messages (' . implode(',', $cols) . ', created_at) VALUES (' . implode(',', $placeholders) . ', NOW())';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        } else {
            // do not reference created_at
            $sql = 'INSERT INTO messages (' . implode(',', $cols) . ') VALUES (' . implode(',', $placeholders) . ')';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        }

        // Envoyer une notification e-mail au destinataire si possible
        try {
            // récupérer email du destinataire
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
            $stmtCheck->execute(['utilisateurs']);
            $useUtilisateurs = (bool) $stmtCheck->fetchColumn();
            if ($useUtilisateurs) {
                $stmtMail = $pdo->prepare('SELECT email, nom, prenom FROM utilisateurs WHERE id = ?');
            } else {
                $stmtMail = $pdo->prepare('SELECT email, name FROM users WHERE id = ?');
            }
            $stmtMail->execute([$to]);
            $dest = $stmtMail->fetch(PDO::FETCH_ASSOC);
            if (!empty($dest['email'])) {
                $toEmail = $dest['email'];
                $subject = 'Nouveau message sur LeBonCoin';
                $siteLink = (isset($_SERVER['HTTP_HOST']) ? 'http://' . $_SERVER['HTTP_HOST'] : '') . dirname($_SERVER['REQUEST_URI']);
                $body = "Vous avez reçu un nouveau message de la part d'un utilisateur.\n\n" . strip_tags($contenu) . "\n\nConnectez-vous pour répondre: " . $siteLink . "/messages.php";
                $headers = 'From: no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n";
                @mail($toEmail, $subject, $body, $headers);
            }
        } catch (Exception $e) {
            // ne pas bloquer l'envoi si l'email échoue
        }

        // Si requête AJAX, renvoyer JSON
        if (isset($_POST['ajax']) && $_POST['ajax']) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'message' => $contenu, 'to' => $to]);
            exit;
        }

        header('Location: messages.php');
        exit;
    }
}

// Récupérer la liste des utilisateurs pour le select (sauf soi)
$users = [];
// Détecter table utilisateurs / users
$stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
$stmtCheck->execute(['utilisateurs']);
$useUtilisateurs = (bool) $stmtCheck->fetchColumn();
if ($useUtilisateurs) {
    $stmt = $pdo->prepare('SELECT id, nom, prenom, email FROM utilisateurs WHERE id != ? ORDER BY nom, prenom');
    $stmt->execute([$_SESSION['id']]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // users table may have 'name' and 'email'
    $stmt = $pdo->prepare('SELECT id, name, email FROM users WHERE id != ? ORDER BY id');
    $stmt->execute([$_SESSION['id']]);
    $tmp = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Map to nom/prenom/email format for compatibility with template
    foreach ($tmp as $t) {
        $users[] = [
            'id' => $t['id'],
            'nom' => $t['name'] ?? '',
            'prenom' => '',
            'email' => $t['email'] ?? ''
        ];
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="style.css">
    <title>Envoyer un message</title>
    <style>body{font-family:Arial, sans-serif; padding:20px; background:#f5f5f5} .card{background:#fff;padding:20px;border-radius:8px;max-width:700px;margin:auto}</style>
</head>
<body>
<div class="card">
    <p><a href="accueil.php" class="btn">Accueil</a></p>
    <h2>Envoyer un message</h2>

    <?php if (!empty($errors)): ?>
        <div style="color:red;"><ul><?php foreach($errors as $e) echo '<li>'.htmlspecialchars($e).'</li>'; ?></ul></div>
    <?php endif; ?>

    <?php if (isset($annonce_titre)): ?>
        <p>Vous contactez le vendeur pour l'annonce: <strong><?= htmlspecialchars($annonce_titre) ?></strong></p>
    <?php endif; ?>

    <form method="post">
        <?php if (isset($annonce_id)): ?>
            <input type="hidden" name="annonce_id" value="<?= (int)$annonce_id ?>">
        <?php endif; ?>
        <label>Destinataire</label><br>
        <select name="to_user" required>
            <option value="">-- Choisir --</option>
            <?php foreach ($users as $u): ?>
                <option value="<?= $u['id'] ?>" <?= ($destinataire_id && $destinataire_id == $u['id']) ? 'selected' : '' ?>><?= htmlspecialchars($u['prenom'].' '.$u['nom'].' ('.$u['email'].')') ?></option>
            <?php endforeach; ?>
        </select>

        <p>
            <label>Message</label><br>
            <textarea name="contenu" rows="6" style="width:100%"></textarea>
        </p>

        <p>
            <button type="submit">Envoyer</button>
            <a href="messages.php">Annuler</a>
        </p>
    </form>
</div>
</body>
</html>
