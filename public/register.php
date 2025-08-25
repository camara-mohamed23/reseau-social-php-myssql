<?php
require_once __DIR__ . '/_header.php';

$errors = [];
$ok = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || !preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        $errors[] = 'Username invalide (3-30, lettres/chiffres/underscore).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email invalide.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Mot de passe trop court (min 6).';
    }

    if (!$errors) {
        $stmt = pdo()->prepare('SELECT id FROM users WHERE email=? OR username=?');
        $stmt->execute([$email, $username]);
        if ($stmt->fetch()) {
            $errors[] = 'Email ou username déjà utilisé.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = pdo()->prepare('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)');
            $stmt->execute([$username, $email, $hash]);
            $ok = 'Inscription réussie ! Vous pouvez vous connecter.';
        }
    }
}
?>

<div class="card">
  <h3>Créer un compte</h3>
  <?php foreach ($errors as $e): ?>
    <div class="flash error"><?= e($e) ?></div>
  <?php endforeach; ?>
  <?php if ($ok): ?><div class="flash ok"><?= e($ok) ?></div><?php endif; ?>

  <form method="post" autocomplete="off">
    <?= csrf_field() ?>
    <label>Username</label>
    <input class="input" name="username" required>
    <label>Email</label>
    <input class="input" name="email" type="email" required>
    <label>Mot de passe</label>
    <input class="input" name="password" type="password" required>
    <br><br>
    <button class="btn">S'inscrire</button>
  </form>
</div>

<?php require_once __DIR__ . '/_footer.php'; ?>
