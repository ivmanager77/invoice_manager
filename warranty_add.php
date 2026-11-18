<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_login();

$userId = current_user_id();
$pdo = get_db();

$invoicesStmt = $pdo->prepare(
    'SELECT i.id, i.store_id, i.invoice_date, i.amount, s.name AS store_name
     FROM invoices i
     INNER JOIN stores s ON s.id = i.store_id
     WHERE i.user_id = :user_id
     ORDER BY i.invoice_date DESC, i.id DESC'
);
$invoicesStmt->execute([':user_id' => $userId]);
$invoices = $invoicesStmt->fetchAll();

$errors = [];
$invoiceId = (int)($_POST['invoice_id'] ?? 0);
$productName = trim((string)($_POST['product_name'] ?? ''));
$startDate = trim((string)($_POST['start_date'] ?? date('Y-m-d')));
$duration = (int)($_POST['duration'] ?? 12);
$notes = trim((string)($_POST['notes'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ownInv = $pdo->prepare('SELECT id, store_id FROM invoices WHERE id = :id AND user_id = :user_id LIMIT 1');
    $ownInv->execute([':id' => $invoiceId, ':user_id' => $userId]);
    $selectedInvoice = $ownInv->fetch();

    if ($selectedInvoice === false) {
        $errors[] = 'Please select a valid invoice.';
    }

    if ($productName === '') {
        $errors[] = 'Product name or description is required.';
    }
    if ($startDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
        $errors[] = 'A valid start date is required.';
    }
    if ($duration < 1 || $duration > 1200) {
        $errors[] = 'Duration must be between 1 and 1200 months.';
    }

    if (empty($errors)) {
        $endDate = (new DateTimeImmutable($startDate))
            ->modify('+' . $duration . ' months')
            ->format('Y-m-d');

        $insert = $pdo->prepare(
            'INSERT INTO warranties (user_id, store_id, invoice_id, product_name, start_date, duration, end_date, notes)
             VALUES (:user_id, :store_id, :invoice_id, :product_name, :start_date, :duration, :end_date, :notes)'
        );
        $insert->execute([
            ':user_id'      => $userId,
            ':store_id'     => (int)$selectedInvoice['store_id'],
            ':invoice_id'   => (int)$selectedInvoice['id'],
            ':product_name' => $productName,
            ':start_date'   => $startDate,
            ':duration'     => $duration,
            ':end_date'     => $endDate,
            ':notes'        => $notes !== '' ? $notes : null,
        ]);

        header('Location: warranties.php');
        exit;
    }
}

$pageTitle = 'Add Warranty - Invoice Manager';
require __DIR__ . '/header.php';
?>

<div class="mx-auto max-w-2xl">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-text">Add Warranty</h1>
        <p class="mt-1 text-sm text-text-muted">Select an invoice to register a product warranty. The store is linked automatically.</p>
    </div>

    <?php if (empty($invoices)): ?>
        <div class="rounded-2xl border border-dashed border-border bg-surface p-10 text-center shadow-sm">
            <p class="mb-4 text-text-muted">You need to add at least one invoice before adding a warranty.</p>
            <a href="invoice_add.php" class="inline-block rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-hover">
                + Add Invoice First
            </a>
        </div>
    <?php else: ?>
        <div class="rounded-2xl border border-border bg-surface p-6 shadow-sm">
            <?php if (!empty($errors)): ?>
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                    <ul class="list-disc pl-5 space-y-1">
                        <?php foreach ($errors as $err): ?>
                            <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="warranty_add.php" class="space-y-4">
                <div>
                    <label for="invoice_id" class="mb-1 block text-sm font-medium text-text">Invoice <span class="text-red-500">*</span></label>
                    <select id="invoice_id" name="invoice_id" required
                            class="w-full rounded-lg border border-slate-300 bg-surface px-3 py-2 text-text focus:border-primary focus:outline-none">
                        <option value="">-- Select an Invoice --</option>
                        <?php foreach ($invoices as $inv): ?>
                            <option value="<?= (int)$inv['id'] ?>" <?= $invoiceId === (int)$inv['id'] ? 'selected' : '' ?>>
                                <?= e($inv['store_name']) ?> &mdash; <?= e($inv['invoice_date']) ?>
                                <?= $inv['amount'] !== null ? ' (' . number_format((float)$inv['amount'], 2) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="product_name" class="mb-1 block text-sm font-medium text-text">Product Name / Description <span class="text-red-500">*</span></label>
                    <input type="text" id="product_name" name="product_name" value="<?= e($productName) ?>" required
                           placeholder="e.g. MacBook Pro 14-inch"
                           class="w-full rounded-lg border border-slate-300 bg-surface px-3 py-2 text-text focus:border-primary focus:outline-none">
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="start_date" class="mb-1 block text-sm font-medium text-text">Start Date <span class="text-red-500">*</span></label>
                        <input type="date" id="start_date" name="start_date" value="<?= e($startDate) ?>" required
                               class="w-full rounded-lg border border-slate-300 bg-surface px-3 py-2 text-text focus:border-primary focus:outline-none">
                    </div>

                    <div>
                        <label for="duration" class="mb-1 block text-sm font-medium text-text">Duration (Months) <span class="text-red-500">*</span></label>
                        <input type="number" id="duration" name="duration" min="1" max="1200" value="<?= (int)$duration ?>" required
                               class="w-full rounded-lg border border-slate-300 bg-surface px-3 py-2 text-text focus:border-primary focus:outline-none">
                    </div>
                </div>

                <div>
                    <label for="notes" class="mb-1 block text-sm font-medium text-text">Notes (optional)</label>
                    <textarea id="notes" name="notes" rows="3" placeholder="Serial number, coverage details, etc."
                              class="w-full rounded-lg border border-slate-300 bg-surface px-3 py-2 text-text focus:border-primary focus:outline-none"><?= e($notes) ?></textarea>
                </div>

                <button type="submit" class="w-full rounded-lg bg-primary py-2.5 text-sm font-semibold text-white transition hover:bg-primary-hover">
                    Save Warranty
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/footer.php'; ?>
