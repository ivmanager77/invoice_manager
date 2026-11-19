<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
$user = current_user();
?>
<?php if ($user !== null): ?>
    <?php
    $pdo = get_db();
    $userId = current_user_id();

    $storeCount = (int)$pdo->query("SELECT COUNT(*) FROM stores WHERE user_id = " . (int)$userId)->fetchColumn();
    $invoiceCount = (int)$pdo->query("SELECT COUNT(*) FROM invoices WHERE user_id = " . (int)$userId)->fetchColumn();

    $today = date('Y-m-d');
    $soon = date('Y-m-d', strtotime('+30 days'));

    $activeStmt = $pdo->prepare('SELECT COUNT(*) FROM warranties WHERE user_id = :uid AND end_date >= :today');
    $activeStmt->execute([':uid' => $userId, ':today' => $today]);
    $activeWarranties = (int)$activeStmt->fetchColumn();

    $soonStmt = $pdo->prepare('SELECT COUNT(*) FROM warranties WHERE user_id = :uid AND end_date >= :today AND end_date <= :soon');
    $soonStmt->execute([':uid' => $userId, ':today' => $today, ':soon' => $soon]);
    $expiringSoon = (int)$soonStmt->fetchColumn();

    $pageTitle = 'Home - Invoice Manager';
    require __DIR__ . '/header.php';
    ?>

    <section class="mb-8 rounded-2xl border border-border bg-surface p-6 sm:p-8 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-primary">Personal Invoice &amp; Warranty Hub</p>
                <h1 class="mt-1 text-2xl sm:text-3xl font-bold text-text">Welcome back, <?= e($user['name']) ?>!</h1>
                <p class="mt-1 text-sm text-text-muted">Organize your stores, save invoices with QR codes, and never miss a warranty expiration.</p>
            </div>
            <div class="flex flex-wrap gap-2.5">
                <a href="invoice_add.php" class="rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-hover">
                    + Add Invoice
                </a>
                <a href="qr_scan.php" class="rounded-lg border border-border bg-surface-muted px-4 py-2.5 text-sm font-semibold text-text transition hover:bg-slate-200">
                    Scan QR
                </a>
            </div>
        </div>
    </section>

    <section class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <a href="stores.php" class="rounded-2xl border border-border bg-surface p-5 shadow-sm transition hover:border-primary/40 hover:shadow-md">
            <p class="text-xs font-medium uppercase text-text-muted">My Stores</p>
            <p class="mt-2 text-3xl font-bold text-text"><?= $storeCount ?></p>
            <p class="mt-1 text-xs text-primary">Manage stores &rarr;</p>
        </a>

        <a href="invoices.php" class="rounded-2xl border border-border bg-surface p-5 shadow-sm transition hover:border-primary/40 hover:shadow-md">
            <p class="text-xs font-medium uppercase text-text-muted">Saved Invoices</p>
            <p class="mt-2 text-3xl font-bold text-text"><?= $invoiceCount ?></p>
            <p class="mt-1 text-xs text-primary">View &amp; filter &rarr;</p>
        </a>

        <a href="warranties.php" class="rounded-2xl border border-border bg-surface p-5 shadow-sm transition hover:border-primary/40 hover:shadow-md">
            <p class="text-xs font-medium uppercase text-text-muted">Active Warranties</p>
            <p class="mt-2 text-3xl font-bold text-emerald-600"><?= $activeWarranties ?></p>
            <p class="mt-1 text-xs text-primary">Track coverage &rarr;</p>
        </a>

        <a href="warranties.php" class="rounded-2xl border border-border bg-surface p-5 shadow-sm transition hover:border-primary/40 hover:shadow-md">
            <p class="text-xs font-medium uppercase text-text-muted">Expiring in 30 Days</p>
            <p class="mt-2 text-3xl font-bold text-amber-600"><?= $expiringSoon ?></p>
            <p class="mt-1 text-xs text-primary">Check soon &rarr;</p>
        </a>
    </section>

    <section class="mb-8">
        <h2 class="mb-4 text-lg font-bold text-text">How Invoice Manager Works</h2>
        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-2xl border border-border bg-surface p-5 shadow-sm">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-primary-soft text-sm font-bold text-primary">1</span>
                <h3 class="mt-3 text-base font-semibold text-text">Add Your Stores</h3>
                <p class="mt-1 text-sm text-text-muted">Save the stores you shop from with their logo, website link, and address.</p>
            </div>

            <div class="rounded-2xl border border-border bg-surface p-5 shadow-sm">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-primary-soft text-sm font-bold text-primary">2</span>
                <h3 class="mt-3 text-base font-semibold text-text">Upload Invoices &amp; QR</h3>
                <p class="mt-1 text-sm text-text-muted">Upload a PDF, JPG/PNG image, or record a manual invoice. Every invoice gets a unique QR code.</p>
            </div>

            <div class="rounded-2xl border border-border bg-surface p-5 shadow-sm">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-primary-soft text-sm font-bold text-primary">3</span>
                <h3 class="mt-3 text-base font-semibold text-text">Track Warranties &amp; Ask AI</h3>
                <p class="mt-1 text-sm text-text-muted">Link warranties to your invoices, monitor expiration status, and ask the AI Assistant anytime.</p>
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-border bg-surface p-6 shadow-sm">
        <h2 class="mb-4 text-lg font-bold text-text">Frequently Asked Questions</h2>
        <div class="divide-y divide-border">
            <details class="group py-3" open>
                <summary class="flex cursor-pointer items-center justify-between text-sm font-semibold text-text">
                    <span>How do we upload invoices and warranties?</span>
                    <span class="text-primary transition group-open:rotate-180">&#9662;</span>
                </summary>
                <p class="mt-2 text-sm text-text-muted">
                    Go to <strong>Add Invoice</strong>, select your store, and upload a PDF, JPG/PNG image, or enter it manually. Then go to <strong>Add Warranty</strong>, pick the invoice, and enter the product name, start date, and duration in months.
                </p>
            </details>

            <details class="group py-3">
                <summary class="flex cursor-pointer items-center justify-between text-sm font-semibold text-text">
                    <span>How does the system read and track warranty status?</span>
                    <span class="text-primary transition group-open:rotate-180">&#9662;</span>
                </summary>
                <p class="mt-2 text-sm text-text-muted">
                    It automatically calculates the expiration date from your start date and duration, then marks each warranty as <strong>Active</strong>, <strong>Expiring Soon</strong> (within 30 days), or <strong>Expired</strong>.
                </p>
            </details>

            <details class="group py-3">
                <summary class="flex cursor-pointer items-center justify-between text-sm font-semibold text-text">
                    <span>What makes Invoice Manager different from other apps?</span>
                    <span class="text-primary transition group-open:rotate-180">&#9662;</span>
                </summary>
                <p class="mt-2 text-sm text-text-muted">
                    It is lightweight, private, and built specifically for personal use—combining store management, instant QR code generation &amp; scanning, warranty tracking, and an AI Chatbot that answers questions directly from your own data.
                </p>
            </details>
        </div>
    </section>

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

        <img src="assets/images/logo.png" alt="Invoice Manager" class="mx-auto mb-6 h-20 w-20 rounded-2xl object-contain">

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
