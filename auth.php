<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function is_logged_in(): bool
{
    return isset($_SESSION['user_id']) && is_int($_SESSION['user_id']);
}

function current_user_id(): ?int
{
    return is_logged_in() ? $_SESSION['user_id'] : null;
}

function current_user(): ?array
{
    $userId = current_user_id();
    if ($userId === null) {
        return null;
    }

    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT id, name, email, created_at FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();

    if ($user === false) {
        unset($_SESSION['user_id']);
        return null;
    }

    return $user;
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function redirect_if_logged_in(): void
{
    if (is_logged_in()) {
        header('Location: index.php');
        exit;
    }
}
