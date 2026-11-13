<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
$user = current_user();
?>
<?php if ($user !== null): ?>
    <?php
    $pageTitle = 'Dashboard - Invoice Manager';
    require __DIR__ . '/header.php';
    ?>
    <div class="rounded-2xl border border-border bg-surface p-8 text-center shadow-sm">
        <h1 class="mb-2 text-3xl font-bold text-text">Welcome, <?= e($user['name']) ?>!</h1>
        <p class="mb-6 text-text-muted">You are signed in as <?= e($user['email']) ?>.</p>
        <div class="flex flex-wrap justify-center gap-3">
            <a href="stores.php" class="rounded-lg bg-primary px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-hover">
                Manage Stores
            </a>
            <a href="profile.php" class="rounded-lg border border-slate-300 bg-surface px-6 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-bg">
                Profile Settings
            </a>
            <a href="logout.php" class="rounded-lg bg-red-50 px-6 py-2.5 text-sm font-semibold text-red-600 transition hover:bg-red-100">
                Logout
            </a>
        </div>
    </div>
    <?php require __DIR__ . '/footer.php'; ?>
<?php else: ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Invoice Manager</title>
    <link rel="stylesheet" href="assets/css/output.css">
</head>
<body class="min-h-screen bg-bg text-text flex items-center justify-center p-6">

    <main class="w-full max-w-2xl rounded-2xl border border-border bg-surface p-8 text-center shadow-sm sm:p-12">

        <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-2xl bg-primary text-3xl font-bold text-white shadow-md">
            IM
        </div>

        <p class="mb-3 text-sm font-semibold uppercase tracking-[0.25em] text-primary">
            Personal Finance Organizer
        </p>

        <h1 class="mb-5 text-3xl font-bold tracking-tight text-text sm:text-5xl">
            Welcome to Invoice Manager
        </h1>

        <p class="mx-auto mb-8 max-w-lg leading-7 text-text-muted">
            Keep your invoices organized, manage your stores, and track your product warranties in one place.
        </p>

        <div class="flex flex-col justify-center gap-3 sm:flex-row">
            <a href="login.php"
               class="rounded-lg bg-primary px-6 py-3 font-semibold text-white transition hover:bg-primary-hover">
                Sign In
            </a>

            <a href="register.php"
               class="rounded-lg border border-slate-300 bg-surface px-6 py-3 font-semibold text-slate-700 transition hover:bg-bg">
                Create Account
            </a>
        </div>

    </main>

</body>
</html>
<?php endif; ?>
