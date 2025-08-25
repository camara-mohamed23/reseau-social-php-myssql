<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
verify_csrf();

$to   = isset($_POST['to']) ? (int)$_POST['to'] : 0;
$body = trim($_POST['body'] ?? '');

if ($to <= 0 || $body === '' || $to === current_user_id()) {
    http_response_code(400);
    exit('Requête invalide.');
}
if (strlen($body) > 2000) {
    $body = substr($body, 0, 2000);
}

// Vérifier destinataire
$exists = pdo()->prepare("SELECT id FROM users WHERE id = ?");
$exists->execute([$to]);
if (!$exists->fetchColumn()) {
    http_response_code(404);
    exit('Destinataire introuvable.');
}

$stmt = pdo()->prepare("INSERT INTO messages (sender_id, receiver_id, body) VALUES (?, ?, ?)");
$stmt->execute([current_user_id(), $to, $body]);

header('Location: messages.php?user=' . $to);
exit;
