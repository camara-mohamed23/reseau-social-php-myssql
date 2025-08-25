<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
verify_csrf();

$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
$content = trim($_POST['content'] ?? '');

if ($post_id <= 0 || $content === '') {
    http_response_code(400);
    exit('Requête invalide.');
}

if (strlen($content) > 500) {
    $content = substr($content, 0, 500);
}

$stmt = pdo()->prepare('INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)');
$stmt->execute([$post_id, current_user_id(), $content]);

header('Location: index.php#post-' . $post_id);
exit;
