<?php
declare(strict_types=1);
$pageTitle = $pageTitle ?? 'Invoice Manager';
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="assets/css/output.css">
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 flex flex-col">
    <header class="border-b border-slate-800/80 bg-slate-900/90 backdrop-blur sticky top-0 z-30">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-6 py-4">
            <a href="index.php" class="flex items-center gap-2.5 text-lg font-bold tracking-tight text-white">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-600 text-sm font-bold shadow">IM</span>
                <span>Invoice Manager</span>
            </a>

            <nav class="flex flex-wrap items-center gap-2 text-sm font-medium">
                <?php if (function_exists('is_logged_in') && is_logged_in()): ?>
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
                            ? 'rounded-lg bg-slate-800 px-3 py-2 text-white'
                            : 'rounded-lg px-3 py-2 text-slate-300 transition hover:bg-slate-800/60 hover:text-white';
                    ?>
                        <a href="<?= $file ?>" class="<?= $cls ?>"><?= $label ?></a>
                    <?php endforeach; ?>
                    <a href="logout.php" class="ml-2 rounded-lg bg-red-600/90 px-3.5 py-2 text-white transition hover:bg-red-500">
                        Logout
                    </a>
                <?php else: ?>
                    <a href="login.php" class="rounded-lg px-3.5 py-2 text-slate-300 transition hover:bg-slate-800 hover:text-white">
                        Sign In
                    </a>
                    <a href="register.php" class="rounded-lg bg-blue-600 px-3.5 py-2 text-white transition hover:bg-blue-500">
                        Create Account
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="mx-auto w-full max-w-6xl flex-1 px-6 py-8">
