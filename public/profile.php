<?php
require_once __DIR__ . '/_header.php';
require_login();

$u = find_user_by_id(current_user_id());
$ok = null; $error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $bio = trim($_POST['bio'] ?? '');
    if (strlen($bio) > 280) $bio = substr($bio, 0, 280);

    $avatar_path = $u['avatar_path'] ?? null;
    if (!empty($_FILES['avatar']['name'])) {
        $file = $_FILES['avatar'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            if ($file['size'] > 5*1024*1024) {
                $error = 'Avatar trop volumineux (max 5MB).';
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
                        $avatar_path = 'uploads/' . $name;
                    } else {
                        $error = 'Échec upload avatar.';
                    }
                } else {
                    $error = 'Format d\'image non supporté.';
                }
            }
        } else {
            $error = 'Erreur d\'upload.';
        }
    }

    if (!$error) {
        $stmt = pdo()->prepare('UPDATE users SET bio=?, avatar_path=? WHERE id=?');
        $stmt->execute([$bio ?: null, $avatar_path, current_user_id()]);
        $ok = 'Profil mis à jour.';
        $u = find_user_by_id(current_user_id());
    }
}
?>

<div class="card">
  <h3>Mon profil</h3>
  <?php if ($ok): ?><div class="flash ok"><?= e($ok) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="flash error"><?= e($error) ?></div><?php endif; ?>

  <div style="display:flex; gap:16px; align-items:center;">
    <img class="avatar" src="<?= e($u['avatar_path'] ?: 'https://placehold.co/80x80/png') ?>" alt="avatar">
    <div>
      <div><strong>@<?= e($u['username']) ?></strong></div>
      <div class="meta"><?= e($u['email']) ?> · créé le <?= e($u['created_at']) ?></div>
    </div>
  </div>

  <hr>
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <label>Bio (max 280)</label>
    <textarea class="input" name="bio" rows="3" maxlength="280"><?= e($u['bio'] ?? '') ?></textarea>
    <label>Avatar</label>
    <input type="file" name="avatar" accept="image/*">
    <div class="file-hint">Formats: jpg, png, webp, gif · max 5MB</div>
    <br>
    <button class="btn">Enregistrer</button>
  </form>
</div>

<?php require_once __DIR__ . '/_footer.php'; ?>
