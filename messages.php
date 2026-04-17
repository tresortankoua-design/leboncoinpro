<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['id'])) {
    header('Location: connexion.php');
    exit;
}

$me = (int) $_SESSION['id'];

// Détecter dynamiquement les noms de colonnes pour l'expéditeur/receveur
$senderCandidates = ['sender_id','sender','user_from','from_user'];
$receiverCandidates = ['receiver_id','recipient_id','recipient','to_user','user_to','receiver','destinataire_id'];
$senderCol = null;
$receiverCol = null;
foreach ($senderCandidates as $c) {
    $stmtC = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'messages' AND column_name = ?");
    $stmtC->execute([$c]);
    if ($stmtC->fetchColumn() > 0) { $senderCol = $c; break; }
}
foreach ($receiverCandidates as $c) {
    $stmtC = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'messages' AND column_name = ?");
    $stmtC->execute([$c]);
    if ($stmtC->fetchColumn() > 0) { $receiverCol = $c; break; }
}
// Fallbacks raisonnables
if (!$senderCol) $senderCol = 'sender_id';
if (!$receiverCol) $receiverCol = 'receiver_id';

// Sécurité: n'autoriser que les identifiants simples (lettres, chiffres, underscore)
if (!preg_match('/^[a-zA-Z0-9_]+$/', $senderCol)) $senderCol = 'sender_id';
if (!preg_match('/^[a-zA-Z0-9_]+$/', $receiverCol)) $receiverCol = 'receiver_id';

// AJAX: récupérer messages entre l'utilisateur connecté et un autre (param 'with')
if (isset($_GET['ajax']) && isset($_GET['with']) && is_numeric($_GET['with'])) {
    $other = (int) $_GET['with'];
    // vérifier si created_at existe pour choisir la colonne d'ordre
    $orderCol = 'id';
    try {
        $s = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'messages' AND column_name = 'created_at'");
        $s->execute();
        if ($s->fetchColumn() > 0) $orderCol = 'created_at';
    } catch (Exception $e) {
        $orderCol = 'id';
    }

    $sql = "SELECT * FROM messages WHERE ({$senderCol} = ? AND {$receiverCol} = ?) OR ({$senderCol} = ? AND {$receiverCol} = ?) ORDER BY {$orderCol} ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$me, $other, $other, $me]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Normalize column names for the client (always provide sender_id and receiver_id)
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'id' => $r['id'] ?? null,
            'annonce_id' => $r['annonce_id'] ?? null,
            'sender_id' => $r[$senderCol] ?? ($r['sender_id'] ?? null),
            'receiver_id' => $r[$receiverCol] ?? ($r['receiver_id'] ?? null),
            'message' => $r['message'] ?? ($r['contenu'] ?? null),
            'created_at' => $r['created_at'] ?? null,
        ];
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($out);
    exit;
}

// Construire la liste de conversations (autres utilisateurs avec qui on a échangé)
$sql = "SELECT DISTINCT CASE WHEN {$senderCol} = ? THEN {$receiverCol} ELSE {$senderCol} END AS uid
    FROM messages
    WHERE {$senderCol} = ? OR {$receiverCol} = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$me, $me, $me]);
$uids = $stmt->fetchAll(PDO::FETCH_COLUMN);

$conversations = [];
if (!empty($uids)) {
    // Charger infos utilisateurs
    $placeholders = implode(',', array_fill(0, count($uids), '?'));
    // detect users table
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
    $stmtCheck->execute(['utilisateurs']);
    $useUtilisateurs = (bool) $stmtCheck->fetchColumn();
    if ($useUtilisateurs) {
        $sql = "SELECT id, nom, prenom, email FROM utilisateurs WHERE id IN ($placeholders)";
    } else {
        $sql = "SELECT id, name AS nom, email FROM users WHERE id IN ($placeholders)";
    }
    $stmtUsers = $pdo->prepare($sql);
    $stmtUsers->execute($uids);
    $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);
    foreach ($users as $u) {
        $conversations[$u['id']] = $u;
    }
}

// Choix de la conversation sélectionnée via GET 'with'
$selected = null;
if (isset($_GET['with']) && is_numeric($_GET['with'])) {
    $sel = (int) $_GET['with'];
    if (isset($conversations[$sel])) $selected = $sel;
}
// si aucune sélection, prendre premier conversation
if ($selected === null && !empty($uids)) $selected = (int) $uids[0];

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="style.css">
    <title>Chat — Messages</title>
    <style>
        .chat-wrap{display:flex;gap:18px}
        .conversations{width:260px}
        .conversations .item{padding:10px;border-radius:8px;background:#fff;margin-bottom:8px;cursor:pointer}
        .conversations .item.active{box-shadow:0 4px 12px rgba(0,0,0,0.08)}
        .chatbox{flex:1;background:#fff;border-radius:8px;padding:12px;display:flex;flex-direction:column;min-height:400px}
        .messages{flex:1;overflow:auto;padding:8px}
        .message.me{text-align:right}
        .message .bubble{display:inline-block;padding:8px 12px;border-radius:12px;margin:6px 0;max-width:75%}
        .message.me .bubble{background:linear-gradient(90deg,var(--violet),var(--orange));color:#fff}
        .message.them .bubble{background:#f3f4f6;color:#111}
        .compose{display:flex;gap:8px;margin-top:8px}
        .compose textarea{flex:1;padding:10px;border-radius:8px;border:1px solid #e5e7eb}
    </style>
</head>
<body>
<div class="wrap">
    <header style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
        <div style="display:flex;gap:12px;align-items:center">
            <h1 style="margin:0">Chat</h1>
            <a href="accueil.php" class="btn">Accueil</a>
        </div>
        <div>
            <a href="envoyer_message.php" class="btn">Nouveau message</a>
            <a href="deconnexion.php" class="btn">Déconnexion</a>
        </div>
    </header>

    <div class="chat-wrap">
        <aside class="conversations">
            <h3>Conversations</h3>
            <?php if (empty($conversations)): ?>
                <div class="box">Aucune conversation pour l'instant.</div>
            <?php else: ?>
                <?php foreach ($conversations as $id => $u): 
                    $displayName = trim((($u['prenom'] ?? '') . ' ' . ($u['nom'] ?? $u['name'] ?? '')));
                    if ($displayName === '') $displayName = ($u['nom'] ?? $u['name'] ?? 'Utilisateur');
                ?>
                    <div class="item <?= ($selected === (int)$id) ? 'active' : '' ?>" data-uid="<?= (int)$id ?>">
                        <strong><?= htmlspecialchars($displayName) ?></strong>
                        <div style="font-size:12px;color:#6b7280"><?= htmlspecialchars($u['email'] ?? '') ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </aside>

        <section class="chatbox">
            <div class="messages" id="messages">
                <!-- messages loaded by JS -->
            </div>

            <form id="sendForm" class="compose">
                <textarea id="msgText" name="message" rows="3" placeholder="Écrire un message..."></textarea>
                <button type="submit" class="btn">Envoyer</button>
            </form>
        </section>
    </div>
</div>

<script>
const me = <?= json_encode($me) ?>;
let selected = <?= json_encode($selected) ?>;
selected = selected ? parseInt(selected, 10) : null;
function loadMessages(){
    if(!selected) return;
    fetch('messages.php?ajax=1&with='+selected)
    .then(r=>r.json()).then(data=>{
        const container=document.getElementById('messages');
        container.innerHTML='';
        data.forEach(m=>{
            const div=document.createElement('div');
            div.className='message '+(m.sender_id==me?'me':'them');
            const b=document.createElement('div');b.className='bubble';b.textContent=m.message||m.contenu||'';
            div.appendChild(b);
            container.appendChild(div);
        });
        container.scrollTop = container.scrollHeight;
    }).catch(console.error);
}

// set up conversation clicks
document.querySelectorAll('.conversations .item').forEach(it=>{
    it.addEventListener('click',()=>{
        selected = parseInt(it.dataset.uid, 10);
        document.querySelectorAll('.conversations .item').forEach(x=>x.classList.remove('active'));
        it.classList.add('active');
        loadMessages();
    });
});

document.getElementById('sendForm').addEventListener('submit',function(e){
    e.preventDefault();
    if(!selected) return alert('Sélectionnez une conversation');
    const txt=document.getElementById('msgText');
    const body=new FormData();
    body.append('receiver_id', selected);
    body.append('message', txt.value);
    body.append('ajax', '1');
    fetch('envoyer_message.php', {method:'POST', body: body})
    .then(r=>r.json())
    .then(res=>{
        if(res.success){ txt.value=''; loadMessages(); }
        else alert(res.error||'Erreur');
    }).catch(err=>{console.error(err); alert('Erreur réseau');});
});

// polling
setInterval(loadMessages, 3000);
// initial load
loadMessages();
</script>

</body>
</html>
