<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
redirect_if_logged_in();

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $errors[] = 'Email and password are required.';
    } else {
        $pdo = get_db();
        $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user === false || !password_verify($password, $user['password_hash'])) {
            $errors[] = 'Invalid email or password.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            header('Location: index.php');
            exit;
        }
    }
}

$pageTitle = 'Sign In - Invoice Manager';
require __DIR__ . '/header.php';
?>

<div class="mx-auto max-w-md rounded-xl border border-slate-800 bg-slate-900 p-6">
    <h1 class="mb-4 text-2xl font-bold">Sign In</h1>

    <?php if (!empty($errors)): ?>
        <div class="mb-4 rounded-lg border border-red-700 bg-red-950/60 p-3 text-sm text-red-200">
            <ul class="list-disc pl-5 space-y-1">
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php" class="space-y-4">
        <div>
            <label for="email" class="mb-1 block text-sm font-medium text-slate-300">Email</label>
            <input type="email" id="email" name="email" value="<?= e($email) ?>" required
                   class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-white focus:border-blue-500 focus:outline-none">
        </div>

        <div>
            <label for="password" class="mb-1 block text-sm font-medium text-slate-300">Password</label>
            <input type="password" id="password" name="password" required
                   class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-white focus:border-blue-500 focus:outline-none">
        </div>

        <button type="submit" class="w-full rounded-lg bg-blue-600 py-2.5 font-semibold text-white hover:bg-blue-500">
            Sign In
        </button>
    </form>

    <p class="mt-4 text-center text-sm text-slate-400">
        Don't have an account? <a href="register.php" class="text-blue-400 hover:underline">Create Account</a>
    </p>
</div>

<?php require __DIR__ . '/footer.php'; ?>
