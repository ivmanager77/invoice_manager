<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_login();

$userId = current_user_id();
$pdo = get_db();
$errorMsg = '';
$successMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $warrantyId = (int)($_POST['warranty_id'] ?? 0);
    $stmt = $pdo->prepare('SELECT id FROM warranties WHERE id = :id AND user_id = :user_id LIMIT 1');
    $stmt->execute([':id' => $warrantyId, ':user_id' => $userId]);

    if ($stmt->fetch() === false) {
        $errorMsg = 'Warranty not found or access denied.';
    } else {
        $del = $pdo->prepare('DELETE FROM warranties WHERE id = :id AND user_id = :user_id');
        $del->execute([':id' => $warrantyId, ':user_id' => $userId]);
        $successMsg = 'Warranty deleted successfully.';
    }
}

$stmt = $pdo->prepare(
    'SELECT w.*, s.name AS store_name, s.website_url AS store_url,
            i.invoice_date AS linked_invoice_date
     FROM warranties w
     INNER JOIN stores s ON s.id = w.store_id
     LEFT JOIN invoices i ON i.id = w.invoice_id AND i.user_id = w.user_id
     WHERE w.user_id = :user_id
     ORDER BY w.end_date ASC, w.id DESC'
);
$stmt->execute([':user_id' => $userId]);
$warranties = $stmt->fetchAll();

$today = new DateTimeImmutable('today');
$soonThreshold = $today->modify('+30 days');

$pageTitle = 'Warranties - Invoice Manager';
require __DIR__ . '/header.php';
?>

<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-text">My Warranties</h1>
        <p class="mt-1 text-sm text-text-muted">Track product warranties and expiration dates.</p>
    </div>
    <a href="warranty_add.php" class="rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-hover">
        + Add Warranty
    </a>
</div>

<?php if ($errorMsg !== ''): ?>
    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700"><?= e($errorMsg) ?></div>
<?php endif; ?>
<?php if ($successMsg !== ''): ?>
    <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700"><?= e($successMsg) ?></div>
<?php endif; ?>

<?php if (empty($warranties)): ?>
    <div class="rounded-2xl border border-dashed border-slate-300 bg-surface p-12 text-center shadow-sm">
        <p class="mb-4 text-text-muted">You haven't added any warranties yet.</p>
        <a href="warranty_add.php" class="inline-block rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary-hover">
            Add Your First Warranty
        </a>
    </div>
<?php else: ?>
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($warranties as $w): ?>
            <?php
            $endDateObj = new DateTimeImmutable($w['end_date']);
            if ($endDateObj < $today) {
                $statusLabel = 'Expired';
                $statusClass = 'bg-red-50 text-red-700 border border-red-200';
            } elseif ($endDateObj <= $soonThreshold) {
                $statusLabel = 'Expiring Soon';
                $statusClass = 'bg-amber-50 text-amber-700 border border-amber-200';
            } else {
                $statusLabel = 'Active';
                $statusClass = 'bg-emerald-50 text-emerald-700 border border-emerald-200';
            }
            ?>
            <div class="flex flex-col justify-between rounded-2xl border border-border bg-surface p-5 shadow-sm transition hover:shadow-md">
                <div>
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="truncate text-lg font-semibold text-text"><?= e($w['product_name']) ?></h2>
                            <p class="mt-0.5 text-xs text-text-muted"><?= e($w['store_name']) ?></p>
                        </div>
                        <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold <?= $statusClass ?>">
                            <?= $statusLabel ?>
                        </span>
                    </div>

                    <div class="mb-3 grid grid-cols-2 gap-2 rounded-xl bg-surface-muted p-3 text-xs">
                        <div>
                            <p class="text-text-muted">Start Date</p>
                            <p class="mt-0.5 font-semibold text-text"><?= e($w['start_date']) ?></p>
                        </div>
                        <div>
                            <p class="text-text-muted">Expires On</p>
                            <p class="mt-0.5 font-semibold text-text"><?= e($w['end_date']) ?></p>
                        </div>
                    </div>

                    <div class="mb-3 flex flex-wrap gap-2">
                        <?php if (!empty($w['store_url'])): ?>
                            <a href="<?= e($w['store_url']) ?>" target="_blank" rel="noopener noreferrer"
                               class="rounded-lg bg-primary-soft px-2.5 py-1 text-xs font-semibold text-primary transition hover:bg-primary hover:text-white">
                                Store Website &nearr;
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($w['invoice_id'])): ?>
                            <a href="invoice_view.php?id=<?= (int)$w['invoice_id'] ?>"
                               class="rounded-lg border border-border bg-surface-muted px-2.5 py-1 text-xs font-semibold text-text transition hover:bg-slate-200">
                                View Invoice (<?= e($w['linked_invoice_date'] ?? '') ?>)
                            </a>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($w['notes'])): ?>
                        <p class="mb-4 line-clamp-2 text-xs text-text-muted"><?= e($w['notes']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="mt-2 flex items-center justify-end gap-2 border-t border-border pt-3">
                    <a href="warranty_edit.php?id=<?= (int)$w['id'] ?>"
                       class="rounded-lg border border-border bg-surface-muted px-3 py-1.5 text-xs font-medium text-text transition hover:bg-slate-200">
                        Edit
                    </a>
                    <form method="POST" action="warranties.php" onsubmit="return confirm('Delete this warranty?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="warranty_id" value="<?= (int)$w['id'] ?>">
                        <button type="submit"
                                class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-medium text-red-600 transition hover:bg-red-100">
                            Delete
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
