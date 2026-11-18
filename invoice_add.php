<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_login();

$userId = current_user_id();
$pdo = get_db();

$storesStmt = $pdo->prepare('SELECT id, name FROM stores WHERE user_id = :user_id ORDER BY name ASC');
$storesStmt->execute([':user_id' => $userId]);
$stores = $storesStmt->fetchAll();

$errors = [];
$createdInvoice = null;

$storeId = (int)($_POST['store_id'] ?? 0);
$invoiceDate = trim((string)($_POST['invoice_date'] ?? date('Y-m-d')));
$invoiceType = (string)($_POST['invoice_type'] ?? 'image');
$amountRaw = trim((string)($_POST['amount'] ?? ''));
$notes = trim((string)($_POST['notes'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($storeId <= 0) {
        $errors[] = 'Please select a store.';
    } else {
        $ownStmt = $pdo->prepare('SELECT id, name FROM stores WHERE id = :id AND user_id = :user_id LIMIT 1');
        $ownStmt->execute([':id' => $storeId, ':user_id' => $userId]);
        $selectedStore = $ownStmt->fetch();
        if ($selectedStore === false) {
            $errors[] = 'Selected store is invalid.';
        }
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

    $filePath = null;
    if (empty($errors) && $invoiceType !== 'manual') {
        $upload = handle_invoice_file_upload($_FILES['file'] ?? [], $invoiceType);
        if ($upload['error'] !== null) {
            $errors[] = $upload['error'];
        } else {
            $filePath = $upload['path'];
        }
    }

    if (empty($errors)) {
        $qrToken = bin2hex(random_bytes(16));
        $insert = $pdo->prepare(
            'INSERT INTO invoices (user_id, store_id, invoice_date, amount, invoice_type, file_path, qr_token, notes)
             VALUES (:user_id, :store_id, :invoice_date, :amount, :invoice_type, :file_path, :qr_token, :notes)'
        );
        $insert->execute([
            ':user_id'      => $userId,
            ':store_id'     => $storeId,
            ':invoice_date' => $invoiceDate,
            ':amount'       => $amount,
            ':invoice_type' => $invoiceType,
            ':file_path'    => $filePath,
            ':qr_token'     => $qrToken,
            ':notes'        => $notes !== '' ? $notes : null,
        ]);

        $createdId = (int)$pdo->lastInsertId();
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseDir = rtrim(str_replace('\\', '/', dirname($_SERVER['PHP_SELF'])), '/');
        $qrUrl = $scheme . '://' . $host . $baseDir . '/invoice_view.php?token=' . $qrToken;

        $createdInvoice = [
            'id'           => $createdId,
            'store_name'   => $selectedStore['name'] ?? '',
            'invoice_date' => $invoiceDate,
            'amount'       => $amount,
            'invoice_type' => $invoiceType,
            'qr_token'     => $qrToken,
            'qr_url'       => $qrUrl,
        ];
    }
}

$pageTitle = 'Add Invoice - Invoice Manager';
require __DIR__ . '/header.php';
?>

<div class="mx-auto max-w-2xl">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-text">Add Invoice</h1>
        <p class="mt-1 text-sm text-text-muted">Upload a PDF or image invoice, or record one manually.</p>
    </div>

    <?php if ($createdInvoice !== null): ?>
        <div class="rounded-2xl border border-emerald-200 bg-surface p-6 shadow-sm text-center">
            <div class="mb-4 inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                Invoice Saved Successfully
            </div>
            <h2 class="text-xl font-bold text-text"><?= e($createdInvoice['store_name']) ?></h2>
            <p class="mt-1 text-sm text-text-muted">
                Date: <?= e($createdInvoice['invoice_date']) ?>
                <?php if ($createdInvoice['amount'] !== null): ?>
                    &middot; Amount: <?= number_format((float)$createdInvoice['amount'], 2) ?>
                <?php endif; ?>
            </p>

            <div class="my-6 flex flex-col items-center justify-center">
                <div id="qrcode" class="rounded-xl border border-border bg-white p-4 shadow-xs"></div>
                <p class="mt-3 text-xs text-text-muted">Scan this QR code to open the invoice.</p>
                <p class="mt-1 break-all text-xs text-primary"><?= e($createdInvoice['qr_url']) ?></p>
            </div>

            <div class="flex flex-wrap justify-center gap-3">
                <a href="invoice_add.php" class="rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-hover">
                    + Add Another Invoice
                </a>
                <a href="invoices.php" class="rounded-lg border border-border bg-surface px-5 py-2.5 text-sm font-semibold text-text transition hover:bg-surface-muted">
                    View All Invoices
                </a>
                <button type="button" id="download-qr-btn"
                        class="rounded-lg border border-border bg-surface-muted px-5 py-2.5 text-sm font-semibold text-text transition hover:bg-slate-200">
                    Download QR Code
                </button>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
        <script>
            new QRCode(document.getElementById("qrcode"), {
                text: <?= json_encode($createdInvoice['qr_url']) ?>,
                width: 180,
                height: 180
            });

            document.getElementById("download-qr-btn").addEventListener("click", function () {
                const container = document.getElementById("qrcode");
                const canvas = container.querySelector("canvas");
                const img = container.querySelector("img");
                const dataUrl = canvas ? canvas.toDataURL("image/png") : (img ? img.src : null);
                if (!dataUrl) return;

                const link = document.createElement("a");
                link.href = dataUrl;
                link.download = "invoice-qr-<?= (int)$createdInvoice['id'] ?>.png";
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });
        </script>
    <?php elseif (empty($stores)): ?>
        <div class="rounded-2xl border border-dashed border-border bg-surface p-10 text-center shadow-sm">
            <p class="mb-4 text-text-muted">You need to add at least one store before adding an invoice.</p>
            <a href="store_add.php" class="inline-block rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-hover">
                + Add Store First
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

            <form method="POST" action="invoice_add.php" enctype="multipart/form-data" class="space-y-4">
                <div>
                    <label for="store_id" class="mb-1 block text-sm font-medium text-text">Store <span class="text-red-500">*</span></label>
                    <select id="store_id" name="store_id" required
                            class="w-full rounded-lg border border-slate-300 bg-surface px-3 py-2 text-text focus:border-primary focus:outline-none">
                        <option value="">-- Select a Store --</option>
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
                        <input type="number" id="amount" name="amount" step="0.01" min="0" value="<?= e($amountRaw) ?>" placeholder="0.00"
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
                    <label for="file" class="mb-1 block text-sm font-medium text-text">Invoice File</label>
                    <input type="file" id="file" name="file" accept=".pdf,image/jpeg,image/png"
                           class="w-full rounded-lg border border-slate-300 bg-surface px-3 py-2 text-sm text-text-muted file:mr-3 file:rounded-md file:border-0 file:bg-surface-muted file:px-3 file:py-1 file:text-xs file:font-semibold file:text-text hover:file:bg-slate-200">
                    <p class="mt-1 text-xs text-text-muted">PDF, JPG, or PNG up to 5 MB.</p>
                </div>

                <div>
                    <label for="notes" class="mb-1 block text-sm font-medium text-text">Notes (optional)</label>
                    <textarea id="notes" name="notes" rows="3" placeholder="Items purchased, warranty note, etc."
                              class="w-full rounded-lg border border-slate-300 bg-surface px-3 py-2 text-text focus:border-primary focus:outline-none"><?= e($notes) ?></textarea>
                </div>

                <button type="submit" class="w-full rounded-lg bg-primary py-2.5 text-sm font-semibold text-white transition hover:bg-primary-hover">
                    Save Invoice &amp; Generate QR
                </button>
            </form>
        </div>

        <script>
            (function () {
                const typeSelect = document.getElementById('invoice_type');
                const fileGroup = document.getElementById('file-upload-group');
                const fileInput = document.getElementById('file');

                function toggleFileField() {
                    const isManual = typeSelect.value === 'manual';
                    fileGroup.style.display = isManual ? 'none' : 'block';
                    fileInput.required = !isManual;
                }

                typeSelect.addEventListener('change', toggleFileField);
                toggleFileField();
            })();
        </script>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/footer.php'; ?>
