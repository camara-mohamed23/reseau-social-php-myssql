<?php
require_once __DIR__ . '/_header.php';
if (!is_logged_in()) {
  echo '<div class="card"><p>Bienvenue ! <a href="register.php">Inscrivez-vous</a> ou <a href="login.php">connectez-vous</a> pour poster.</p></div>';
  require_once __DIR__ . '/_footer.php';
  exit;
}

$errors = []; $ok = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_post'])) {
    verify_csrf();
    $content = trim($_POST['content'] ?? '');
    $img_path = null;

    if (!empty($_FILES['image']['name'])) {
        $file = $_FILES['image'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            if ($file['size'] > 5*1024*1024) {
                $errors[] = 'Image trop volumineuse (max 5MB).';
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($file['tmp_name']);
                $ext = match($mime) {
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                    'image/gif' => 'gif',
                    default => null
                };
                if ($ext) {
                    $name = bin2hex(random_bytes(8)) . '.' . $ext;
                    $dest = __DIR__ . '/uploads/' . $name;
                    if (move_uploaded_file($file['tmp_name'], $dest)) {
                        $img_path = 'uploads/' . $name;
                    } else {
                        $errors[] = 'Échec upload image.';
                    }
                } else {
                    $errors[] = 'Format d\'image non supporté.';
                }
            }
        } else {
            $errors[] = 'Erreur d\'upload.';
        }
    }

    if (!$errors && ($content !== '' || $img_path)) {
        $stmt = pdo()->prepare('INSERT INTO posts (user_id, content, image_path) VALUES (?, ?, ?)');
        $stmt->execute([current_user_id(), $content ?: null, $img_path]);
        $ok = 'Post publié.';
    } elseif (!$errors) {
        $errors[] = 'Votre post est vide.';
    }
}

$stmt = pdo()->prepare('
  SELECT p.*, u.username, u.avatar_path,
    (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) AS like_count,
    EXISTS(SELECT 1 FROM likes l WHERE l.post_id = p.id AND l.user_id = ?) AS liked
  FROM posts p
  JOIN users u ON u.id = p.user_id
  ORDER BY p.created_at DESC
  LIMIT 50
');
$stmt->execute([current_user_id()]);
$posts = $stmt->fetchAll();
?>

<div class="card">
  <h3>Créer un post</h3>
  <?php foreach ($errors as $e): ?><div class="flash error"><?= e($e) ?></div><?php endforeach; ?>
  <?php if ($ok): ?><div class="flash ok"><?= e($ok) ?></div><?php endif; ?>
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="new_post" value="1">
    <textarea name="content" rows="3" class="input" placeholder="Quoi de neuf ?"></textarea>
    <br>
    <input type="file" name="image" accept="image/*">
    <div class="file-hint">Optionnel · max 5MB · jpg/png/webp/gif</div>
    <br>
    <button class="btn">Publier</button>
  </form>
</div>

<?php foreach ($posts as $p): ?>
  <div class="card post" id="post-<?= (int)$p['id'] ?>">
    <img class="avatar" src="<?= e($p['avatar_path'] ?: 'https://placehold.co/80x80/png') ?>" alt="avatar">
    <div style="flex:1;">
      <div><strong><a href="user.php?id=<?= (int)$p['user_id'] ?>">@<?= e($p['username']) ?></a></strong></div>
      <div class="meta"><?= e($p['created_at']) ?></div>
      <?php if (!empty($p['content'])): ?>
        <p><?= nl2br(e($p['content'])) ?></p>
      <?php endif; ?>
      <?php if (!empty($p['image_path'])): ?>
        <img src="<?= e($p['image_path']) ?>" alt="image" style="max-width:100%; border-radius:12px; border:1px solid #1f2937;">
      <?php endif; ?>
      <div style="display:flex; align-items:center; gap:12px; margin-top:8px;">
        <span class="like" data-id="<?= (int)$p['id'] ?>" data-liked="<?= (int)$p['liked'] ?>">
          ❤️ <span class="like-count"><?= (int)$p['like_count'] ?></span> <?= $p['liked'] ? '(Je like)' : '' ?>
        </span>
      </div>

      <?php
        $cstmt = pdo()->prepare('
          SELECT c.*, u.username, u.avatar_path
          FROM comments c JOIN users u ON u.id=c.user_id
          WHERE c.post_id=? ORDER BY c.created_at ASC
        ');
        $cstmt->execute([$p['id']]);
        $comments = $cstmt->fetchAll();
      ?>
      <div style="margin-top:8px;">
        <?php foreach ($comments as $c): ?>
          <div class="comment">
            <div style="display:flex; gap:10px; align-items:center;">
              <img class="avatar" src="<?= e($c['avatar_path'] ?: 'https://placehold.co/80x80/png') ?>" alt="">
              <div style="flex:1;">
                <div><strong>@<?= e($c['username']) ?></strong> · <span class="meta"><?= e($c['created_at']) ?></span></div>
                <div><?= nl2br(e($c['content'])) ?></div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <form method="post" action="comment_create.php" style="margin-top:8px;">
        <?= csrf_field() ?>
        <input type="hidden" name="post_id" value="<?= (int)$p['id'] ?>">
        <input class="input" name="content" placeholder="Ajouter un commentaire..." required maxlength="500">
        <br><button class="btn secondary">Commenter</button>
      </form>
    </div>
  </div>
<?php endforeach; ?>

<script>
document.querySelectorAll('.like').forEach(el => {
  el.addEventListener('click', async () => {
    const postId = el.dataset.id;
    const formData = new FormData();
    formData.append('post_id', postId);
    formData.append('csrf_token', '<?= e($_SESSION['csrf_token']) ?>');
    const res = await fetch('post_like.php', { method: 'POST', body: formData });
    if (!res.ok) return;
    const data = await res.json();
    if (data && typeof data.count !== 'undefined') {
      el.querySelector('.like-count').textContent = data.count;
      el.dataset.liked = data.liked ? '1' : '0';
      el.innerHTML = '❤️ <span class="like-count">' + data.count + '</span> ' + (data.liked ? '(Je like)' : '');
    }
  });
});
</script>

<?php require_once __DIR__ . '/_footer.php'; ?>
