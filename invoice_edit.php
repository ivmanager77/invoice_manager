<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_login();

$userId = current_user_id();
$invoiceId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$pdo = get_db();

$stmt = $pdo->prepare('SELECT * FROM invoices WHERE id = :id AND user_id = :user_id LIMIT 1');
$stmt->execute([':id' => $invoiceId, ':user_id' => $userId]);
$invoice = $stmt->fetch();

if ($invoice === false) {
    header('Location: invoices.php');
    exit;
}

$storesStmt = $pdo->prepare('SELECT id, name FROM stores WHERE user_id = :user_id ORDER BY name ASC');
$storesStmt->execute([':user_id' => $userId]);
$stores = $storesStmt->fetchAll();

$errors = [];
$storeId = (int)$invoice['store_id'];
$invoiceDate = $invoice['invoice_date'];
$invoiceType = $invoice['invoice_type'];
$amountRaw = $invoice['amount'] !== null ? (string)$invoice['amount'] : '';
$notes = $invoice['notes'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $storeId = (int)($_POST['store_id'] ?? 0);
    $invoiceDate = trim((string)($_POST['invoice_date'] ?? ''));
    $invoiceType = (string)($_POST['invoice_type'] ?? 'manual');
    $amountRaw = trim((string)($_POST['amount'] ?? ''));
    $notes = trim((string)($_POST['notes'] ?? ''));

    $ownStmt = $pdo->prepare('SELECT id FROM stores WHERE id = :id AND user_id = :user_id LIMIT 1');
    $ownStmt->execute([':id' => $storeId, ':user_id' => $userId]);
    if ($ownStmt->fetch() === false) {
        $errors[] = 'Selected store is invalid.';
    }

    if ($invoiceDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $invoiceDate)) {
        $errors[] = 'A valid invoice date is required.';
    }

    if (!in_array($invoiceType, ['pdf', 'image', 'manual'], true)) {
        $errors[] = 'Invalid invoice type.';
    }

    $amount = null;
    if ($amountRaw !== '') {
        if (!is_numeric($amountRaw) || (float)$amountRaw < 0) {
            $errors[] = 'Amount must be a valid non-negative number.';
        } else {
            $amount = (float)$amountRaw;
        }
    }

    $filePath = $invoice['file_path'];
    if (empty($errors)) {
        if ($invoiceType === 'manual') {
            if (!empty($filePath) && file_exists(__DIR__ . '/' . $filePath)) {
                @unlink(__DIR__ . '/' . $filePath);
            }
            $filePath = null;
        } elseif (isset($_FILES['file']) && $_FILES['file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload = handle_invoice_file_upload($_FILES['file'], $invoiceType);
            if ($upload['error'] !== null) {
                $errors[] = $upload['error'];
            } else {
                if (!empty($filePath) && file_exists(__DIR__ . '/' . $filePath)) {
                    @unlink(__DIR__ . '/' . $filePath);
                }
                $filePath = $upload['path'];
            }
        } elseif (empty($filePath)) {
            $errors[] = 'Please upload a file for the selected invoice type.';
        }
    }

    if (empty($errors)) {
        $update = $pdo->prepare(
            'UPDATE invoices
             SET store_id = :store_id, invoice_date = :invoice_date, amount = :amount,
                 invoice_type = :invoice_type, file_path = :file_path, notes = :notes
             WHERE id = :id AND user_id = :user_id'
        );
        $update->execute([
            ':store_id'     => $storeId,
            ':invoice_date' => $invoiceDate,
            ':amount'       => $amount,
            ':invoice_type' => $invoiceType,
            ':file_path'    => $filePath,
            ':notes'        => $notes !== '' ? $notes : null,
            ':id'           => $invoiceId,
            ':user_id'      => $userId,
        ]);

        header('Location: invoice_view.php?id=' . $invoiceId);
        exit;
    }
}

$pageTitle = 'Edit Invoice - Invoice Manager';
require __DIR__ . '/header.php';
?>

<div class="mx-auto max-w-2xl">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-text">Edit Invoice</h1>
    </div>

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

        <form method="POST" action="invoice_edit.php?id=<?= (int)$invoiceId ?>" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="id" value="<?= (int)$invoiceId ?>">

            <div>
                <label for="store_id" class="mb-1 block text-sm font-medium text-text">Store <span class="text-red-500">*</span></label>
                <select id="store_id" name="store_id" required
                        class="w-full rounded-lg border border-slate-300 bg-surface px-3 py-2 text-text focus:border-primary focus:outline-none">
                    <?php foreach ($stores as $st): ?>
                        <option value="<?= (int)$st['id'] ?>" <?= $storeId === (int)$st['id'] ? 'selected' : '' ?>>
                            <?= e($st['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="invoice_date" class="mb-1 block text-sm font-medium text-text">Invoice Date <span class="text-red-500">*</span></label>
                    <input type="date" id="invoice_date" name="invoice_date" value="<?= e($invoiceDate) ?>" required
                           class="w-full rounded-lg border border-slate-300 bg-surface px-3 py-2 text-text focus:border-primary focus:outline-none">
                </div>

                <div>
                    <label for="amount" class="mb-1 block text-sm font-medium text-text">Amount (optional)</label>
                    <input type="number" id="amount" name="amount" step="0.01" min="0" value="<?= e($amountRaw) ?>"
                           class="w-full rounded-lg border border-slate-300 bg-surface px-3 py-2 text-text focus:border-primary focus:outline-none">
                </div>
            </div>

            <div>
                <label for="invoice_type" class="mb-1 block text-sm font-medium text-text">Invoice Type <span class="text-red-500">*</span></label>
                <select id="invoice_type" name="invoice_type" required
                        class="w-full rounded-lg border border-slate-300 bg-surface px-3 py-2 text-text focus:border-primary focus:outline-none">
                    <option value="image" <?= $invoiceType === 'image' ? 'selected' : '' ?>>Image (JPG / PNG)</option>
                    <option value="pdf" <?= $invoiceType === 'pdf' ? 'selected' : '' ?>>PDF Document</option>
                    <option value="manual" <?= $invoiceType === 'manual' ? 'selected' : '' ?>>Manual Entry (No File)</option>
                </select>
            </div>

            <div id="file-upload-group">
                <label for="file" class="mb-1 block text-sm font-medium text-text">Replace File (optional)</label>
                <input type="file" id="file" name="file" accept=".pdf,image/jpeg,image/png"
                       class="w-full rounded-lg border border-slate-300 bg-surface px-3 py-2 text-sm text-text-muted file:mr-3 file:rounded-md file:border-0 file:bg-surface-muted file:px-3 file:py-1 file:text-xs file:font-semibold file:text-text hover:file:bg-slate-200">
                <p class="mt-1 text-xs text-text-muted">Leave empty to keep current file. PDF, JPG, or PNG up to 5 MB.</p>
            </div>

            <div>
                <label for="notes" class="mb-1 block text-sm font-medium text-text">Notes (optional)</label>
                <textarea id="notes" name="notes" rows="3"
                          class="w-full rounded-lg border border-slate-300 bg-surface px-3 py-2 text-text focus:border-primary focus:outline-none"><?= e($notes) ?></textarea>
            </div>

            <button type="submit" class="w-full rounded-lg bg-primary py-2.5 text-sm font-semibold text-white transition hover:bg-primary-hover">
                Update Invoice
            </button>
        </form>
    </div>
</div>

<script>
    (function () {
        const typeSelect = document.getElementById('invoice_type');
        const fileGroup = document.getElementById('file-upload-group');

        function toggleFileField() {
            fileGroup.style.display = typeSelect.value === 'manual' ? 'none' : 'block';
        }

        typeSelect.addEventListener('change', toggleFileField);
        toggleFileField();
    })();
</script>

<?php require __DIR__ . '/footer.php'; ?>
