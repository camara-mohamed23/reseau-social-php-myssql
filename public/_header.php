<?php require_once __DIR__ . '/../config/bootstrap.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>🌐 Mini Réseau Social</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <div class="container">
    <div class="header">
      <h2><a href="index.php">🌐 Mini Réseau Social</a></h2>
      <nav class="nav">
        <a href="index.php">Accueil</a>
        <?php if (is_logged_in()): ?>
          <?php
          // Compter les messages non lus
          $unread = 0;
          try {
            $st = pdo()->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id=? AND is_read=0");
            $st->execute([current_user_id()]);
            $unread = (int)$st->fetchColumn();
          } catch (Throwable $e) {
            // silencieux si la table n'existe pas encore
          }
          ?>
          <a href="messages.php">Messages<?= $unread ? ' ('.$unread.')' : '' ?></a>
          <a href="profile.php">Profil</a>
          <a href="logout.php">Déconnexion</a>
        <?php else: ?>
          <a href="login.php">Connexion</a>
          <a href="register.php">Inscription</a>
        <?php endif; ?>
      </nav>
    </div>
