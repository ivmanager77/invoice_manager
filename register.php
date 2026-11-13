<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
redirect_if_logged_in();

$errors = [];
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    if ($name === '') {
        $errors[] = 'Name is required.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $pdo = get_db();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);

        if ($stmt->fetch() !== false) {
            $errors[] = 'An account with this email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $insert = $pdo->prepare('INSERT INTO users (name, email, password_hash) VALUES (:name, :email, :password_hash)');
            $insert->execute([
                ':name' => $name,
                ':email' => $email,
                ':password_hash' => $hash,
            ]);

            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$pdo->lastInsertId();
            header('Location: index.php');
            exit;
        }
    }
}

$pageTitle = 'Create Account - Invoice Manager';
require __DIR__ . '/header.php';
?>

<div class="mx-auto max-w-md rounded-2xl border border-border bg-surface p-6 shadow-sm">
    <h1 class="mb-4 text-2xl font-bold text-text">Create Account</h1>

    <?php if (!empty($errors)): ?>
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
            <ul class="list-disc pl-5 space-y-1">
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="register.php" class="space-y-4">
        <div>
            <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Name</label>
            <input type="text" id="name" name="name" value="<?= e($name) ?>" required
                   class="w-full rounded-lg border border-slate-300 bg-surface px-3 py-2 text-text focus:border-primary focus:outline-none focus:ring-2 focus:ring-blue-600/20">
        </div>

        <div>
            <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
            <input type="email" id="email" name="email" value="<?= e($email) ?>" required
                   class="w-full rounded-lg border border-slate-300 bg-surface px-3 py-2 text-text focus:border-primary focus:outline-none focus:ring-2 focus:ring-blue-600/20">
        </div>

        <div>
            <label for="password" class="mb-1 block text-sm font-medium text-slate-700">Password</label>
            <input type="password" id="password" name="password" autocomplete="new-password" minlength="6" required
                   class="w-full rounded-lg border border-slate-300 bg-surface px-3 py-2 text-text focus:border-primary focus:outline-none focus:ring-2 focus:ring-blue-600/20">
        </div>

        <div>
            <label for="confirm_password" class="mb-1 block text-sm font-medium text-slate-700">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" minlength="6" required
                   class="w-full rounded-lg border border-slate-300 bg-surface px-3 py-2 text-text focus:border-primary focus:outline-none focus:ring-2 focus:ring-blue-600/20">
        </div>

        <button type="submit" class="w-full rounded-lg bg-primary py-2.5 font-semibold text-white transition hover:bg-primary-hover">
            Create Account
        </button>
    </form>

    <p class="mt-4 text-center text-sm text-text-muted">
        Already have an account? <a href="login.php" class="font-medium text-primary hover:underline">Sign In</a>
    </p>
</div>

<?php require __DIR__ . '/footer.php'; ?>
