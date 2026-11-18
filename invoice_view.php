<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_login();

$userId = current_user_id();
$pdo = get_db();

$invoiceId = (int)($_GET['id'] ?? 0);
$token = trim((string)($_GET['token'] ?? ''));

$invoice = false;
if ($invoiceId > 0) {
    $stmt = $pdo->prepare(
        'SELECT i.*, s.name AS store_name
         FROM invoices i
         INNER JOIN stores s ON s.id = i.store_id
         WHERE i.id = :id AND i.user_id = :user_id
         LIMIT 1'
    );
    $stmt->execute([':id' => $invoiceId, ':user_id' => $userId]);
    $invoice = $stmt->fetch();
} elseif ($token !== '') {
    $stmt = $pdo->prepare(
        'SELECT i.*, s.name AS store_name
         FROM invoices i
         INNER JOIN stores s ON s.id = i.store_id
         WHERE i.qr_token = :token AND i.user_id = :user_id
         LIMIT 1'
    );
    $stmt->execute([':token' => $token, ':user_id' => $userId]);
    $invoice = $stmt->fetch();
}

$pageTitle = 'Invoice Details - Invoice Manager';
require __DIR__ . '/header.php';
?>

<?php if ($invoice === false): ?>
    <div class="mx-auto max-w-lg rounded-2xl border border-red-200 bg-surface p-8 text-center shadow-sm">
        <h1 class="mb-2 text-xl font-bold text-text">Invoice Not Found</h1>
        <p class="mb-5 text-sm text-text-muted">This invoice does not exist, the QR code is invalid, or you do not have permission to view it.</p>
        <a href="invoices.php" class="inline-block rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary-hover">
            Go to Invoices
        </a>
    </div>
<?php else: ?>
    <?php
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $baseDir = rtrim(str_replace('\\', '/', dirname($_SERVER['PHP_SELF'])), '/');
    $qrUrl = $scheme . '://' . $host . $baseDir . '/invoice_view.php?token=' . $invoice['qr_token'];
    ?>
    <div class="mx-auto max-w-3xl space-y-6">
        <div class="rounded-2xl border border-border bg-surface p-6 shadow-sm">
            <div class="mb-4 flex flex-wrap items-start justify-between gap-4 border-b border-border pb-4">
                <div>
                    <h1 class="text-2xl font-bold text-text"><?= e($invoice['store_name']) ?></h1>
                    <p class="mt-1 text-sm text-text-muted">Invoice Date: <?= e($invoice['invoice_date']) ?></p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex rounded-full bg-primary-soft px-3 py-1 text-xs font-semibold uppercase text-primary">
                        <?= e($invoice['invoice_type']) ?>
                    </span>
                    <a href="invoice_edit.php?id=<?= (int)$invoice['id'] ?>"
                       class="rounded-lg border border-border bg-surface-muted px-3 py-1.5 text-xs font-semibold text-text transition hover:bg-slate-200">
                        Edit
                    </a>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <p class="text-xs font-medium uppercase text-text-muted">Amount</p>
                    <p class="mt-1 text-lg font-semibold text-text">
                        <?= $invoice['amount'] !== null ? number_format((float)$invoice['amount'], 2) : 'Not specified' ?>
                    </p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase text-text-muted">Created At</p>
                    <p class="mt-1 text-sm text-text"><?= e($invoice['created_at']) ?></p>
                </div>
            </div>

            <?php if (!empty($invoice['notes'])): ?>
                <div class="mt-4 border-t border-border pt-4">
                    <p class="text-xs font-medium uppercase text-text-muted">Notes</p>
                    <p class="mt-1 whitespace-pre-line text-sm text-text"><?= e($invoice['notes']) ?></p>
                </div>
            <?php endif; ?>
        </div>

        <div class="rounded-2xl border border-border bg-surface p-6 shadow-sm">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-semibold text-text">Attachment</h2>
                <?php if ($invoice['invoice_type'] !== 'manual' && !empty($invoice['file_path'])): ?>
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="<?= e($invoice['file_path']) ?>" target="_blank" rel="noopener noreferrer"
                           class="rounded-lg border border-border bg-surface-muted px-3.5 py-1.5 text-xs font-semibold text-text transition hover:bg-slate-200">
                            View
                        </a>
                        <a href="<?= e($invoice['file_path']) ?>" download
                           class="rounded-lg bg-primary px-3.5 py-1.5 text-xs font-semibold text-white transition hover:bg-primary-hover">
                            Download File
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($invoice['invoice_type'] === 'manual' || empty($invoice['file_path'])): ?>
                <p class="text-sm text-text-muted">This is a manual invoice with no attached file.</p>
            <?php elseif ($invoice['invoice_type'] === 'image'): ?>
                <img src="<?= e($invoice['file_path']) ?>" alt="Invoice Attachment"
                     class="mx-auto max-h-[520px] w-auto rounded-xl border border-border object-contain">
            <?php else: ?>
                <iframe src="<?= e($invoice['file_path']) ?>" title="Invoice PDF"
                        class="h-[520px] w-full rounded-xl border border-border"></iframe>
            <?php endif; ?>
        </div>

        <div class="rounded-2xl border border-border bg-surface p-6 text-center shadow-sm">
            <h2 class="mb-3 text-lg font-semibold text-text">Invoice QR Code</h2>
            <div class="flex flex-col items-center justify-center">
                <div id="qrcode" class="rounded-xl border border-border bg-white p-4 shadow-xs"></div>
                <p class="mt-3 break-all text-xs text-primary"><?= e($qrUrl) ?></p>
                <button type="button" id="download-qr-btn"
                        class="mt-4 rounded-lg border border-border bg-surface-muted px-4 py-2 text-xs font-semibold text-text transition hover:bg-slate-200">
                    Download QR Code
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script>
        new QRCode(document.getElementById("qrcode"), {
            text: <?= json_encode($qrUrl) ?>,
            width: 160,
            height: 160
        });

        document.getElementById("download-qr-btn").addEventListener("click", function () {
            const container = document.getElementById("qrcode");
            const canvas = container.querySelector("canvas");
            const img = container.querySelector("img");
            const dataUrl = canvas ? canvas.toDataURL("image/png") : (img ? img.src : null);
            if (!dataUrl) return;

            const link = document.createElement("a");
            link.href = dataUrl;
            link.download = "invoice-qr-<?= (int)$invoice['id'] ?>.png";
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
    </script>
<?php endif; ?>

<?php require __DIR__ . '/footer.php'; ?>
