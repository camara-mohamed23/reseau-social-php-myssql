<?php
declare(strict_types=1);

function e(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

function current_user_id(): ?int {
    return $_SESSION['user_id'] ?? null;
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function redirect(string $path): void {
    header('Location: ' . $path);
    exit;
}

function find_user_by_id(int $id): ?array {
    $stmt = pdo()->prepare("SELECT id, username, email, bio, avatar_path, created_at FROM users WHERE id=?");
    $stmt->execute([$id]);
    $u = $stmt->fetch();
    return $u ?: null;
}
?>
