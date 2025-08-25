<?php
require_once __DIR__ . '/_header.php';
require_login();

$uid  = isset($_GET['id']) ? (int)$_GET['id'] : current_user_id();
$user = find_user_by_id($uid);
if (!$user) { echo '<div class="card">Utilisateur introuvable.</div>'; require_once __DIR__ . '/_footer.php'; exit; }

$isOwn = ((int)$user['id'] === (int)current_user_id());
?>
<div class="card">
  <div style="display:flex; gap:16px; align-items:center;">
    <img class="avatar" src="<?= e($user['avatar_path'] ?: 'https://placehold.co/80x80/png') ?>" alt="avatar">
    <div>
      <div><strong>@<?= e($user['username']) ?></strong></div>
      <div class="meta"><?= e($user['email']) ?> · créé le <?= e($user['created_at']) ?></div>
      <?php if (!empty($user['bio'])): ?>
        <p style="margin-top:8px;"><?= nl2br(e($user['bio'])) ?></p>
      <?php endif; ?>

      <?php if (!$isOwn): ?>
        <a class="btn" href="messages.php?user=<?= (int)$user['id'] ?>" style="display:inline-block; margin-top:8px;">
          Envoyer un message
        </a>
      <?php endif; ?>
    </div>
  </div>
</div>
