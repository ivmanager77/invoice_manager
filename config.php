<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_PATH', __DIR__ . '/database/invoice_manager.db');
define('SCHEMA_PATH', __DIR__ . '/database/schema.sql');

function get_db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON;');

    // Initialize schema if users table does not exist yet
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
    if ($stmt->fetch() === false && file_exists(SCHEMA_PATH)) {
        $sql = file_get_contents(SCHEMA_PATH);
        if ($sql !== false) {
            $pdo->exec($sql);
        }
    }

    return $pdo;
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Validate and store an uploaded store image.
 * Returns ['path' => ?string, 'error' => ?string].
 */
function handle_store_image_upload(array $file, ?string $oldPath = null): array
{
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['path' => $oldPath, 'error' => null];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['path' => $oldPath, 'error' => 'Failed to upload image.'];
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        return ['path' => $oldPath, 'error' => 'Image size must not exceed 2 MB.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($allowed[$mime])) {
        return ['path' => $oldPath, 'error' => 'Only JPG, PNG, and WEBP images are allowed.'];
    }

    $uploadDir = __DIR__ . '/uploads/stores';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
    $destPath = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        return ['path' => $oldPath, 'error' => 'Could not save uploaded image.'];
    }

    if ($oldPath !== null && $oldPath !== '' && file_exists(__DIR__ . '/' . $oldPath)) {
        @unlink(__DIR__ . '/' . $oldPath);
    }

    return ['path' => 'uploads/stores/' . $filename, 'error' => null];
}

/**
 * Validate and store an uploaded invoice file (PDF or JPG/PNG).
 * Returns ['path' => ?string, 'error' => ?string].
 */
function handle_invoice_file_upload(array $file, string $invoiceType): array
{
    if ($invoiceType === 'manual') {
        return ['path' => null, 'error' => null];
    }

    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['path' => null, 'error' => 'Please upload a file for the selected invoice type.'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['path' => null, 'error' => 'Failed to upload invoice file.'];
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        return ['path' => null, 'error' => 'File size must not exceed 5 MB.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    $allowedByType = [
        'pdf'   => ['application/pdf' => 'pdf'],
        'image' => ['image/jpeg' => 'jpg', 'image/png' => 'png'],
    ];

    $allowed = $allowedByType[$invoiceType] ?? [];
    if (!isset($allowed[$mime])) {
        $msg = $invoiceType === 'pdf'
            ? 'Only PDF files are allowed for PDF invoices.'
            : 'Only JPG and PNG images are allowed for image invoices.';
        return ['path' => null, 'error' => $msg];
    }

    $uploadDir = __DIR__ . '/uploads/invoices';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
    $destPath = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        return ['path' => null, 'error' => 'Could not save uploaded invoice file.'];
    }

    return ['path' => 'uploads/invoices/' . $filename, 'error' => null];
}
