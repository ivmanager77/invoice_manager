<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_login();

$user = current_user();
$userId = current_user_id();
$pdo = get_db();

$profileErrors = [];
$profileSuccess = '';
$passwordErrors = [];
$passwordSuccess = '';

$name = $user['name'];
$email = $user['email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'update_profile') {
        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));

        if ($name === '') {
            $profileErrors[] = 'Name is required.';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $profileErrors[] = 'A valid email address is required.';
        }

        if (empty($profileErrors)) {
            $check = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1');
            $check->execute([':email' => $email, ':id' => $userId]);
            if ($check->fetch() !== false) {
                $profileErrors[] = 'This email is already used by another account.';
            } else {
                $update = $pdo->prepare('UPDATE users SET name = :name, email = :email WHERE id = :id');
                $update->execute([
                    ':name'  => $name,
                    ':email' => $email,
                    ':id'    => $userId,
                ]);
                $profileSuccess = 'Profile updated successfully.';
            }
        }
    } elseif ($action === 'change_password') {
        $currentPassword = (string)($_POST['current_password'] ?? '');
        $newPassword = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            $passwordErrors[] = 'All password fields are required.';
        } elseif (strlen($newPassword) < 6) {
            $passwordErrors[] = 'New password must be at least 6 characters.';
        } elseif ($newPassword !== $confirmPassword) {
            $passwordErrors[] = 'New passwords do not match.';
        } else {
            $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $userId]);
            $row = $stmt->fetch();

            if ($row === false || !password_verify($currentPassword, $row['password_hash'])) {
                $passwordErrors[] = 'Current password is incorrect.';
            } else {
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $update = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
                $update->execute([':hash' => $newHash, ':id' => $userId]);
                $passwordSuccess = 'Password changed successfully.';
            }
        }
    }
}

$pageTitle = 'Profile - Invoice Manager';
require __DIR__ . '/header.php';
?>

<div class="mb-8">
    <h1 class="text-2xl font-bold text-text">Profile Settings</h1>
    <p class="mt-1 text-sm text-text-muted">Manage your personal information and account security.</p>
</div>

<div class="grid gap-6 md:grid-cols-2">
    <section class="rounded-2xl border border-border bg-surface p-6 shadow-sm">
        <h2 class="mb-4 text-lg font-semibold text-text">Account Information</h2>

        <?php if ($profileSuccess !== ''): ?>
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">
                <?= e($profileSuccess) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($profileErrors)): ?>
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                <ul class="list-disc pl-5 space-y-1">
                    <?php foreach ($profileErrors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="profile.php" class="space-y-4">
            <input type="hidden" name="action" value="update_profile">

            <div>
                <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Name</label>
                <input type="text" id="name" name="name" value="<?= e($name) ?>" required
                       class="w-full rounded-lg border border-slate-300 bg-surface px-3 py-2 text-text focus:border-primary focus:outline-none focus:ring-2 focus:ring-blue-500/20">
            </div>

            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                <input type="email" id="email" name="email" value="<?= e($email) ?>" required
                       class="w-full rounded-lg border border-slate-300 bg-surface px-3 py-2 text-text focus:border-primary focus:outline-none focus:ring-2 focus:ring-blue-500/20">
            </div>

            <p class="text-xs text-slate-400">Member since: <?= e($user['created_at']) ?></p>

            <button type="submit" class="rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-hover">
                Save Changes
            </button>
        </form>
    </section>

    <section class="rounded-2xl border border-border bg-surface p-6 shadow-sm">
        <h2 class="mb-4 text-lg font-semibold text-text">Change Password</h2>

        <?php if ($passwordSuccess !== ''): ?>
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700">
                <?= e($passwordSuccess) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($passwordErrors)): ?>
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                <ul class="list-disc pl-5 space-y-1">
                    <?php foreach ($passwordErrors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="profile.php" class="space-y-4">
            <input type="hidden" name="action" value="change_password">

            <div>
                <label for="current_password" class="mb-1 block text-sm font-medium text-slate-700">Current Password</label>
                <input type="password" id="current_password" name="current_password" required
                       class="w-full rounded-lg border border-slate-300 bg-surface px-3 py-2 text-text focus:border-primary focus:outline-none focus:ring-2 focus:ring-blue-500/20">
            </div>

            <div>
                <label for="new_password" class="mb-1 block text-sm font-medium text-slate-700">New Password</label>
                <input type="password" id="new_password" name="new_password" minlength="6" required
                       class="w-full rounded-lg border border-slate-300 bg-surface px-3 py-2 text-text focus:border-primary focus:outline-none focus:ring-2 focus:ring-blue-500/20">
            </div>

            <div>
                <label for="confirm_password" class="mb-1 block text-sm font-medium text-slate-700">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" minlength="6" required
                       class="w-full rounded-lg border border-slate-300 bg-surface px-3 py-2 text-text focus:border-primary focus:outline-none focus:ring-2 focus:ring-blue-500/20">
            </div>

            <button type="submit" class="rounded-lg bg-slate-800 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700">
                Update Password
            </button>
        </form>
    </section>
</div>

<?php require __DIR__ . '/footer.php'; ?>
