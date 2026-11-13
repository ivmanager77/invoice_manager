<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_login();

$userId = current_user_id();
$pdo = get_db();
$errorMsg = '';
$successMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $storeId = (int)($_POST['store_id'] ?? 0);

    $stmt = $pdo->prepare('SELECT id, image_path FROM stores WHERE id = :id AND user_id = :user_id LIMIT 1');
    $stmt->execute([':id' => $storeId, ':user_id' => $userId]);
    $store = $stmt->fetch();

    if ($store === false) {
        $errorMsg = 'Store not found or access denied.';
    } else {
        $invCount = $pdo->prepare('SELECT COUNT(*) FROM invoices WHERE store_id = :store_id AND user_id = :user_id');
        $invCount->execute([':store_id' => $storeId, ':user_id' => $userId]);

        $warCount = $pdo->prepare('SELECT COUNT(*) FROM warranties WHERE store_id = :store_id AND user_id = :user_id');
        $warCount->execute([':store_id' => $storeId, ':user_id' => $userId]);

        if ((int)$invCount->fetchColumn() > 0 || (int)$warCount->fetchColumn() > 0) {
            $errorMsg = 'Cannot delete this store because it is linked to existing invoices or warranties.';
        } else {
            $del = $pdo->prepare('DELETE FROM stores WHERE id = :id AND user_id = :user_id');
            $del->execute([':id' => $storeId, ':user_id' => $userId]);

            if (!empty($store['image_path']) && file_exists(__DIR__ . '/' . $store['image_path'])) {
                @unlink(__DIR__ . '/' . $store['image_path']);
            }
            $successMsg = 'Store deleted successfully.';
        }
    }
}

$stmt = $pdo->prepare('SELECT id, name, image_path, website_url, address, created_at FROM stores WHERE user_id = :user_id ORDER BY id DESC');
$stmt->execute([':user_id' => $userId]);
$stores = $stmt->fetchAll();

$pageTitle = 'Stores - Invoice Manager';
require __DIR__ . '/header.php';
?>

<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-text">My Stores</h1>
        <p class="mt-1 text-sm text-text-muted">Manage the stores where you shop and keep invoices.</p>
    </div>
    <a href="store_add.php" class="rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-hover">
        + Add Store
    </a>
</div>

<?php if ($errorMsg !== ''): ?>
    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
        <?= e($errorMsg) ?>
    </div>
<?php endif; ?>

<?php if ($successMsg !== ''): ?>
    <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">
        <?= e($successMsg) ?>
    </div>
<?php endif; ?>

<?php if (empty($stores)): ?>
    <div class="rounded-2xl border border-dashed border-slate-300 bg-surface p-12 text-center shadow-sm">
        <p class="mb-4 text-text-muted">You haven't added any stores yet.</p>
        <a href="store_add.php" class="inline-block rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary-hover">
            Add Your First Store
        </a>
    </div>
<?php else: ?>
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($stores as $store): ?>
            <div class="flex flex-col justify-between rounded-2xl border border-border bg-surface p-5 shadow-sm transition hover:shadow-md">
                <div>
                    <div class="mb-4 flex items-center gap-3.5">
                        <?php if (!empty($store['image_path'])): ?>
                            <img src="<?= e($store['image_path']) ?>" alt="<?= e($store['name']) ?>"
                                 class="h-12 w-12 rounded-xl border border-border object-cover">
                        <?php else: ?>
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary-soft text-lg font-bold text-primary">
                                <?= e(strtoupper(substr($store['name'], 0, 1))) ?>
                            </div>
                        <?php endif; ?>
                        <div class="min-w-0 flex-1">
                            <h2 class="truncate text-lg font-semibold text-text"><?= e($store['name']) ?></h2>
                            <?php if (!empty($store['address'])): ?>
                                <p class="mt-0.5 truncate text-xs text-text-muted"><?= e($store['address']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!empty($store['website_url'])): ?>
                        <div class="mb-4">
                            <a href="<?= e($store['website_url']) ?>" target="_blank" rel="noopener noreferrer"
                               class="inline-flex items-center gap-1.5 rounded-lg bg-primary-soft px-3 py-1.5 text-xs font-semibold text-primary transition hover:bg-primary hover:text-white">
                                <span>Visit Website</span>
                                <span aria-hidden="true">&nearr;</span>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mt-2 flex items-center justify-end gap-2 border-t border-border pt-3">
                    <a href="store_edit.php?id=<?= (int)$store['id'] ?>"
                       class="rounded-lg border border-border bg-bg px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-surface-muted">
                        Edit
                    </a>
                    <form method="POST" action="stores.php" onsubmit="return confirm('Delete this store?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="store_id" value="<?= (int)$store['id'] ?>">
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
