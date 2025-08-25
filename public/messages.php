<?php
require_once __DIR__ . '/_header.php';
require_login();

$me  = (int) current_user_id();
$sel = isset($_GET['user']) ? (int)$_GET['user'] : 0;

/** Conversations récentes (dernier message par correspondant) */
$sql = "
  SELECT u.id AS user_id, u.username, u.avatar_path,
         m.body, m.created_at,
         CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END AS peer_id
  FROM messages m
  JOIN users u ON u.id = CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END
  WHERE m.sender_id = ? OR m.receiver_id = ?
  ORDER BY m.created_at DESC
";
$stmt = pdo()->prepare($sql);
$stmt->execute([$me, $me, $me, $me]);
$rows = $stmt->fetchAll();

$seen = [];
$convos = [];
foreach ($rows as $r) {
  $peer = (int)$r['user_id'];
  if (!isset($seen[$peer]) && $peer !== $me) {
    $convos[] = $r;
    $seen[$peer] = true;
  }
}

// Si rien de sélectionné, prendre la 1ère conv si dispo
if ($sel === 0 && !empty($convos)) {
  $sel = (int)$convos[0]['user_id'];
}

$peerUser = $sel ? find_user_by_id($sel) : null;

/** Fil de discussion */
$thread = [];
if ($peerUser) {
  $q = pdo()->prepare("
    SELECT m.*, su.username AS s_name, su.avatar_path AS s_avatar,
           ru.username AS r_name, ru.avatar_path AS r_avatar
    FROM messages m
    JOIN users su ON su.id = m.sender_id
    JOIN users ru ON ru.id = m.receiver_id
    WHERE (m.sender_id = ? AND m.receiver_id = ?)
       OR (m.sender_id = ? AND m.receiver_id = ?)
    ORDER BY m.created_at ASC
  ");
  $q->execute([$me, $sel, $sel, $me]);
  $thread = $q->fetchAll();

  // Marquer comme lus
  $mark = pdo()->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND sender_id = ?");
  $mark->execute([$me, $sel]);
}
?>

<div class="card" style="display:grid; grid-template-columns: 280px 1fr; gap:16px;">
  <div>
    <h3>Conversations</h3>
    <div style="display:flex; flex-direction:column; gap:8px; margin-top:8px;">
      <?php if (empty($convos)): ?>
        <div class="meta">Aucune conversation.</div>
      <?php else: ?>
        <?php foreach ($convos as $c): ?>
          <a class="card" href="messages.php?user=<?= (int)$c['user_id'] ?>" style="padding:10px; display:flex; gap:10px; align-items:center; <?= $sel===(int)$c['user_id']?'border-color:#2563eb;':'' ?>">
            <img class="avatar" src="<?= e($c['avatar_path'] ?: 'https://placehold.co/80x80/png') ?>" alt="">
            <div style="flex:1;">
              <div><strong>@<?= e($c['username']) ?></strong></div>
              <div class="meta" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                <?= e($c['body']) ?>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <div>
    <?php if ($peerUser): ?>
      <h3>Discussion avec @<?= e($peerUser['username']) ?></h3>
      <div class="card" style="max-height: 50vh; overflow:auto; display:flex; flex-direction:column; gap:8px;">
        <?php if (empty($thread)): ?>
          <div class="meta">Démarrez la conversation 👋</div>
        <?php else: ?>
          <?php foreach ($thread as $m): ?>
            <div style="display:flex; gap:10px; align-items:flex-start; <?= (int)$m['sender_id']===$me ? 'flex-direction:row-reverse;' : '' ?>">
              <img class="avatar" src="<?= e(((int)$m['sender_id']===$me ? $m['s_avatar'] : $m['r_avatar']) ?: 'https://placehold.co/80x80/png') ?>" alt="">
              <div class="comment" style="max-width:70%;">
                <div class="meta"><?= e($m['created_at']) ?></div>
                <div><?= nl2br(e($m['body'])) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <form method="post" action="message_send.php" style="margin-top:12px;">
        <?= csrf_field() ?>
        <input type="hidden" name="to" value="<?= (int)$peerUser['id'] ?>">
        <textarea name="body" class="input" rows="2" placeholder="Votre message..." required maxlength="2000"></textarea>
        <br>
        <button class="btn">Envoyer</button>
      </form>
    <?php else: ?>
      <div class="meta">Sélectionnez un utilisateur dans la colonne de gauche.</div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/_footer.php'; ?>
