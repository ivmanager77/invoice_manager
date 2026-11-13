<?php
declare(strict_types=1);
$pageTitle = $pageTitle ?? 'Invoice Manager';
$currentPage = basename($_SERVER['PHP_SELF']);
$loggedIn = function_exists('is_logged_in') && is_logged_in();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="assets/css/output.css">
</head>
<body class="min-h-screen flex flex-col bg-bg text-text">
    <header class="sticky top-0 z-30 border-b border-border bg-surface/95 backdrop-blur shadow-xs">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-6 py-4">
            <a href="index.php" class="flex items-center gap-2.5 text-lg font-bold tracking-tight text-text">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary text-sm font-bold text-white shadow-sm">IM</span>
                <span>Invoice Manager</span>
            </a>

            <nav class="flex flex-wrap items-center gap-1.5 text-sm font-medium">
                <?php if ($loggedIn): ?>
                    <?php
                    $navItems = [
                        'index.php'      => 'Home',
                        'stores.php'     => 'Stores',
                        'invoices.php'   => 'Invoices',
                        'warranties.php' => 'Warranties',
                        'profile.php'    => 'Profile',
                    ];
                    foreach ($navItems as $file => $label):
                        $isActive = ($currentPage === $file);
                        $cls = $isActive
                            ? 'rounded-lg bg-primary-soft px-3 py-2 text-primary font-semibold'
                            : 'rounded-lg px-3 py-2 text-text-muted transition hover:bg-surface-muted hover:text-text';
                    ?>
                        <a href="<?= $file ?>" class="<?= $cls ?>"><?= $label ?></a>
                    <?php endforeach; ?>
                    <a href="logout.php" class="ml-2 rounded-lg bg-red-50 px-3.5 py-2 text-red-600 font-semibold transition hover:bg-red-100">
                        Logout
                    </a>
                <?php else: ?>
                    <a href="login.php" class="rounded-lg px-3.5 py-2 text-text-muted transition hover:bg-surface-muted hover:text-text">
                        Sign In
                    </a>
                    <a href="register.php" class="rounded-lg bg-primary px-3.5 py-2 text-white transition hover:bg-primary-hover">
                        Create Account
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="mx-auto w-full max-w-6xl flex-1 px-6 py-8">
