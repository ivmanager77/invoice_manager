<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_login();

$userId = current_user_id();
$storeId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$pdo = get_db();

$stmt = $pdo->prepare('SELECT id, name, image_path, website_url, address FROM stores WHERE id = :id AND user_id = :user_id LIMIT 1');
$stmt->execute([':id' => $storeId, ':user_id' => $userId]);
$store = $stmt->fetch();

if ($store === false) {
    header('Location: stores.php');
    exit;
}

$errors = [];
$name = $store['name'];
$websiteUrl = $store['website_url'] ?? '';
$address = $store['address'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)($_POST['name'] ?? ''));
    $websiteUrl = trim((string)($_POST['website_url'] ?? ''));
    $address = trim((string)($_POST['address'] ?? ''));

    if ($name === '') {
        $errors[] = 'Store name is required.';
    }
    if ($websiteUrl !== '' && !filter_var($websiteUrl, FILTER_VALIDATE_URL)) {
        $errors[] = 'Please enter a valid website URL (e.g. https://example.com).';
    }

    $imagePath = $store['image_path'];
    if (empty($errors) && isset($_FILES['image'])) {
        $upload = handle_store_image_upload($_FILES['image'], $store['image_path']);
        if ($upload['error'] !== null) {
            $errors[] = $upload['error'];
        } else {
            $imagePath = $upload['path'];
        }
    }

    if (empty($errors)) {
        $update = $pdo->prepare('UPDATE stores SET name = :name, image_path = :image_path, website_url = :website_url, address = :address WHERE id = :id AND user_id = :user_id');
        $update->execute([
            ':name'        => $name,
            ':image_path'  => $imagePath,
            ':website_url' => $websiteUrl !== '' ? $websiteUrl : null,
            ':address'     => $address !== '' ? $address : null,
            ':id'          => $storeId,
            ':user_id'     => $userId,
        ]);

        header('Location: stores.php');
        exit;
    }
}

$pageTitle = 'Edit Store - Invoice Manager';
require __DIR__ . '/header.php';
?>

<div class="mx-auto max-w-lg rounded-2xl border border-border bg-surface p-6 shadow-sm">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-xl font-bold text-text">Edit Store</h1>
        <a href="stores.php" class="text-sm font-medium text-text-muted hover:text-text">&larr; Back to Stores</a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
            <ul class="list-disc pl-5 space-y-1">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="store_edit.php?id=<?= (int)$storeId ?>" enctype="multipart/form-data" class="space-y-4">
        <input type="hidden" name="id" value="<?= (int)$storeId ?>">

        <div>
            <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Store Name <span class="text-red-500">*</span></label>
            <input type="text" id="name" name="name" value="<?= e($name) ?>" required
                   class="w-full rounded-lg border border-slate-300 bg-surface px-3 py-2 text-text focus:border-primary focus:outline-none focus:ring-2 focus:ring-blue-500/20">
        </div>

        <div>
            <label for="website_url" class="mb-1 block text-sm font-medium text-slate-700">Website URL (optional)</label>
            <input type="url" id="website_url" name="website_url" value="<?= e($websiteUrl) ?>" placeholder="https://example.com"
                   class="w-full rounded-lg border border-slate-300 bg-surface px-3 py-2 text-text focus:border-primary focus:outline-none focus:ring-2 focus:ring-blue-500/20">
        </div>

        <div>
            <label for="address" class="mb-1 block text-sm font-medium text-slate-700">Address (optional)</label>
            <textarea id="address" name="address" rows="2"
                      class="w-full rounded-lg border border-slate-300 bg-surface px-3 py-2 text-text focus:border-primary focus:outline-none focus:ring-2 focus:ring-blue-500/20"><?= e($address) ?></textarea>
        </div>

        <div>
            <label for="image" class="mb-1 block text-sm font-medium text-slate-700">Store Logo / Image (optional)</label>
            <?php if (!empty($store['image_path'])): ?>
                <div class="mb-2 flex items-center gap-3">
                    <img src="<?= e($store['image_path']) ?>" alt="<?= e($store['name']) ?>" class="h-12 w-12 rounded-xl border border-border object-cover">
                    <span class="text-xs text-text-muted">Current image</span>
                </div>
            <?php endif; ?>
            <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp"
                   class="w-full rounded-lg border border-slate-300 bg-surface px-3 py-2 text-sm text-text-muted file:mr-3 file:rounded-md file:border-0 file:bg-surface-muted file:px-3 file:py-1 file:text-xs file:font-semibold file:text-slate-700 hover:file:bg-slate-200">
            <p class="mt-1 text-xs text-slate-400">Leave empty to keep current image. JPG, PNG, or WEBP up to 2 MB.</p>
        </div>

        <button type="submit" class="w-full rounded-lg bg-primary py-2.5 text-sm font-semibold text-white transition hover:bg-primary-hover">
            Update Store
        </button>
    </form>
</div>

<?php require __DIR__ . '/footer.php'; ?>
