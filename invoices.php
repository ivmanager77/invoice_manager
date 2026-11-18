<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_login();

$userId = current_user_id();
$pdo = get_db();
$errorMsg = '';
$successMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $invoiceId = (int)($_POST['invoice_id'] ?? 0);
    $stmt = $pdo->prepare('SELECT id, file_path FROM invoices WHERE id = :id AND user_id = :user_id LIMIT 1');
    $stmt->execute([':id' => $invoiceId, ':user_id' => $userId]);
    $inv = $stmt->fetch();

    if ($inv === false) {
        $errorMsg = 'Invoice not found or access denied.';
    } else {
        $del = $pdo->prepare('DELETE FROM invoices WHERE id = :id AND user_id = :user_id');
        $del->execute([':id' => $invoiceId, ':user_id' => $userId]);

        if (!empty($inv['file_path']) && file_exists(__DIR__ . '/' . $inv['file_path'])) {
            @unlink(__DIR__ . '/' . $inv['file_path']);
        }
        $successMsg = 'Invoice deleted successfully.';
    }
}

$storesStmt = $pdo->prepare('SELECT id, name FROM stores WHERE user_id = :user_id ORDER BY name ASC');
$storesStmt->execute([':user_id' => $userId]);
$stores = $storesStmt->fetchAll();

$filterStoreId = (int)($_GET['store_id'] ?? 0);
$dateFrom = trim((string)($_GET['date_from'] ?? ''));
$dateTo = trim((string)($_GET['date_to'] ?? ''));
$amountMin = trim((string)($_GET['amount_min'] ?? ''));
$amountMax = trim((string)($_GET['amount_max'] ?? ''));

$sql = 'SELECT i.id, i.invoice_date, i.amount, i.invoice_type, i.notes, s.name AS store_name
        FROM invoices i
        INNER JOIN stores s ON s.id = i.store_id
        WHERE i.user_id = :user_id';
$params = [':user_id' => $userId];

if ($filterStoreId > 0) {
    $sql .= ' AND i.store_id = :store_id';
    $params[':store_id'] = $filterStoreId;
}
if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
    $sql .= ' AND i.invoice_date >= :date_from';
    $params[':date_from'] = $dateFrom;
}
if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
    $sql .= ' AND i.invoice_date <= :date_to';
    $params[':date_to'] = $dateTo;
}
if ($amountMin !== '' && is_numeric($amountMin)) {
    $sql .= ' AND i.amount >= :amount_min';
    $params[':amount_min'] = (float)$amountMin;
}
if ($amountMax !== '' && is_numeric($amountMax)) {
    $sql .= ' AND i.amount <= :amount_max';
    $params[':amount_max'] = (float)$amountMax;
}

$sql .= ' ORDER BY i.invoice_date DESC, i.id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$invoices = $stmt->fetchAll();

$pageTitle = 'Invoices - Invoice Manager';
require __DIR__ . '/header.php';
?>

<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-text">My Invoices</h1>
        <p class="mt-1 text-sm text-text-muted">Search, filter, and manage your saved invoices.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2.5">
        <a href="qr_scan.php" class="rounded-lg border border-border bg-surface px-4 py-2.5 text-sm font-semibold text-text shadow-xs transition hover:bg-surface-muted">
            Scan QR
        </a>
        <a href="invoice_add.php" class="rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-hover">
            + Add Invoice
        </a>
    </div>
</div>

<?php if ($errorMsg !== ''): ?>
    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700"><?= e($errorMsg) ?></div>
<?php endif; ?>
<?php if ($successMsg !== ''): ?>
    <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700"><?= e($successMsg) ?></div>
<?php endif; ?>

<form method="GET" action="invoices.php" class="mb-6 rounded-2xl border border-border bg-surface p-4 shadow-sm">
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
        <div>
            <label for="store_id" class="mb-1 block text-xs font-medium text-text-muted">Store</label>
            <select id="store_id" name="store_id" class="w-full rounded-lg border border-slate-300 bg-surface px-3 py-2 text-sm text-text focus:border-primary focus:outline-none">
                <option value="">All Stores</option>
                <?php foreach ($stores as $st): ?>
                    <option value="<?= (int)$st['id'] ?>" <?= $filterStoreId === (int)$st['id'] ? 'selected' : '' ?>>
                        <?= e($st['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="date_from" class="mb-1 block text-xs font-medium text-text-muted">From Date</label>
            <input type="date" id="date_from" name="date_from" value="<?= e($dateFrom) ?>"
                   class="w-full rounded-lg border border-slate-300 bg-surface px-3 py-2 text-sm text-text focus:border-primary focus:outline-none">
        </div>

        <div>
            <label for="date_to" class="mb-1 block text-xs font-medium text-text-muted">To Date</label>
            <input type="date" id="date_to" name="date_to" value="<?= e($dateTo) ?>"
                   class="w-full rounded-lg border border-slate-300 bg-surface px-3 py-2 text-sm text-text focus:border-primary focus:outline-none">
        </div>

        <div>
            <label for="amount_min" class="mb-1 block text-xs font-medium text-text-muted">Min Amount</label>
            <input type="number" step="0.01" id="amount_min" name="amount_min" value="<?= e($amountMin) ?>" placeholder="0.00"
                   class="w-full rounded-lg border border-slate-300 bg-surface px-3 py-2 text-sm text-text focus:border-primary focus:outline-none">
        </div>

        <div>
            <label for="amount_max" class="mb-1 block text-xs font-medium text-text-muted">Max Amount</label>
            <input type="number" step="0.01" id="amount_max" name="amount_max" value="<?= e($amountMax) ?>" placeholder="0.00"
                   class="w-full rounded-lg border border-slate-300 bg-surface px-3 py-2 text-sm text-text focus:border-primary focus:outline-none">
        </div>

        <div class="flex items-end gap-2">
            <button type="submit" class="flex-1 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-hover">
                Filter
            </button>
            <a href="invoices.php" class="rounded-lg border border-border bg-surface-muted px-3 py-2 text-sm font-medium text-text transition hover:bg-slate-200">
                Reset
            </a>
        </div>
    </div>
</form>

<?php if (empty($invoices)): ?>
    <div class="rounded-2xl border border-dashed border-slate-300 bg-surface p-12 text-center shadow-sm">
        <p class="mb-4 text-text-muted">No invoices found matching your criteria.</p>
        <a href="invoice_add.php" class="inline-block rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary-hover">
            + Add Invoice
        </a>
    </div>
<?php else: ?>
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($invoices as $inv): ?>
            <div class="flex flex-col justify-between rounded-2xl border border-border bg-surface p-5 shadow-sm transition hover:shadow-md">
                <div>
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="truncate text-lg font-semibold text-text"><?= e($inv['store_name']) ?></h2>
                            <p class="mt-0.5 text-xs text-text-muted"><?= e($inv['invoice_date']) ?></p>
                        </div>
                        <span class="shrink-0 rounded-full bg-primary-soft px-2.5 py-1 text-xs font-semibold uppercase text-primary">
                            <?= e($inv['invoice_type']) ?>
                        </span>
                    </div>

                    <div class="mb-3 rounded-xl bg-surface-muted px-3.5 py-2.5">
                        <p class="text-xs text-text-muted">Amount</p>
                        <p class="text-lg font-bold text-text">
                            <?= $inv['amount'] !== null ? number_format((float)$inv['amount'], 2) : 'Not specified' ?>
                        </p>
                    </div>

                    <?php if (!empty($inv['notes'])): ?>
                        <p class="mb-4 line-clamp-2 text-xs text-text-muted"><?= e($inv['notes']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="mt-2 flex items-center justify-end gap-2 border-t border-border pt-3">
                    <a href="invoice_view.php?id=<?= (int)$inv['id'] ?>"
                       class="rounded-lg bg-primary-soft px-3 py-1.5 text-xs font-semibold text-primary transition hover:bg-primary hover:text-white">
                        View
                    </a>
                    <a href="invoice_edit.php?id=<?= (int)$inv['id'] ?>"
                       class="rounded-lg border border-border bg-surface-muted px-3 py-1.5 text-xs font-medium text-text transition hover:bg-slate-200">
                        Edit
                    </a>
                    <form method="POST" action="invoices.php" onsubmit="return confirm('Delete this invoice?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="invoice_id" value="<?= (int)$inv['id'] ?>">
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
