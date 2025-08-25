<?php
require_once __DIR__ . '/../config/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non connecté']); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']); exit;
}

$token = $_POST['csrf_token'] ?? '';
if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    http_response_code(400);
    echo json_encode(['error' => 'CSRF invalide']); exit;
}

$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
if ($post_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'post_id manquant']); exit;
}

$uid = current_user_id();
$pdo = pdo();
$pdo->beginTransaction();
try {
    $exists = $pdo->prepare('SELECT 1 FROM likes WHERE post_id=? AND user_id=?');
    $exists->execute([$post_id, $uid]);
    if ($exists->fetchColumn()) {
        $del = $pdo->prepare('DELETE FROM likes WHERE post_id=? AND user_id=?');
        $del->execute([$post_id, $uid]);
        $liked = false;
    } else {
        $ins = $pdo->prepare('INSERT INTO likes (post_id, user_id) VALUES (?, ?)');
        $ins->execute([$post_id, $uid]);
        $liked = true;
    }
    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM likes WHERE post_id=?');
    $countStmt->execute([$post_id]);
    $count = (int)$countStmt->fetchColumn();
    $pdo->commit();
    echo json_encode(['count' => $count, 'liked' => $liked]);
} catch (Throwable $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur']);
}
