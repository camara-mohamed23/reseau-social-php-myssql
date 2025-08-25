<?php
require_once __DIR__ . '/_header.php';

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = pdo()->prepare('SELECT id, password_hash FROM users WHERE email=?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = (int)$user['id'];
        redirect('index.php');
    } else {
        $error = 'Identifiants invalides.';
    }
}
?>

<div class="card">
  <h3>Connexion</h3>
  <?php if ($error): ?><div class="flash error"><?= e($error) ?></div><?php endif; ?>
  <form method="post" autocomplete="off">
    <?= csrf_field() ?>
    <label>Email</label>
    <input class="input" name="email" type="email" required>
    <label>Mot de passe</label>
    <input class="input" name="password" type="password" required>
    <br><br>
    <button class="btn">Se connecter</button>
  </form>
</div>

<?php require_once __DIR__ . '/_footer.php'; ?>
