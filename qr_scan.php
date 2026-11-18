<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_login();

$pageTitle = 'Scan QR Code - Invoice Manager';
require __DIR__ . '/header.php';
?>

<div class="mx-auto max-w-xl">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-text">Scan Invoice QR Code</h1>
        <p class="mt-1 text-sm text-text-muted">Choose between scanning with your camera or uploading a QR code image.</p>
    </div>

    <div class="rounded-2xl border border-border bg-surface p-6 shadow-sm">
        <div class="mb-5 grid grid-cols-2 gap-2 rounded-xl bg-surface-muted p-1">
            <button type="button" id="tab-file"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white shadow-xs transition">
                Upload Image
            </button>
            <button type="button" id="tab-camera"
                    class="rounded-lg px-4 py-2 text-sm font-semibold text-text-muted transition hover:text-text">
                Use Camera
            </button>
        </div>

        <div id="file-panel">
            <label for="qr-file-input"
                   class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-300 bg-surface-muted/50 px-6 py-12 text-center transition hover:border-primary hover:bg-primary-soft/30">
                <span class="mb-2 text-sm font-semibold text-text">Click to choose a QR code image</span>
                <span class="text-xs text-text-muted">PNG, JPG, or WEBP</span>
                <input type="file" id="qr-file-input" accept="image/*" class="hidden">
            </label>
        </div>

        <div id="camera-panel" class="hidden">
            <div id="qr-reader" class="overflow-hidden rounded-xl border border-border bg-slate-950/5"></div>
            <p class="mt-3 text-center text-xs text-text-muted">Point your camera at an Invoice Manager QR code.</p>
        </div>

        <div id="qr-error" class="mt-4 hidden rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700"></div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
    (function () {
        const tabCamera = document.getElementById('tab-camera');
        const tabFile = document.getElementById('tab-file');
        const cameraPanel = document.getElementById('camera-panel');
        const filePanel = document.getElementById('file-panel');
        const fileInput = document.getElementById('qr-file-input');
        const errorBox = document.getElementById('qr-error');

        const html5QrCode = new Html5Qrcode('qr-reader');
        let cameraRunning = false;

        function showError(msg) {
            errorBox.textContent = msg;
            errorBox.classList.remove('hidden');
        }

        function clearError() {
            errorBox.textContent = '';
            errorBox.classList.add('hidden');
        }

        function handleDecodedText(decodedText) {
            let token = '';
            try {
                const url = new URL(decodedText);
                token = url.searchParams.get('token') || '';
            } catch (e) {
                if (/^[a-f0-9]{32}$/i.test(decodedText.trim())) {
                    token = decodedText.trim();
                }
            }

            if (token === '') {
                showError('Invalid QR code. Please scan a valid Invoice Manager QR code.');
                return;
            }

            if (cameraRunning) {
                html5QrCode.stop().catch(() => {});
            }
            window.location.href = 'invoice_view.php?token=' + encodeURIComponent(token);
        }

        function startCamera() {
            clearError();
            if (cameraRunning) return;
            html5QrCode.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: { width: 220, height: 220 } },
                handleDecodedText,
                function () {}
            ).then(() => {
                cameraRunning = true;
            }).catch(() => {
                showError('Camera access was denied or no camera was found. You can switch to "Upload Image".');
            });
        }

        function stopCamera() {
            if (!cameraRunning) return;
            html5QrCode.stop().then(() => {
                cameraRunning = false;
            }).catch(() => {});
        }

        tabCamera.addEventListener('click', function () {
            tabCamera.className = 'rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white shadow-xs transition';
            tabFile.className = 'rounded-lg px-4 py-2 text-sm font-semibold text-text-muted transition hover:text-text';
            cameraPanel.classList.remove('hidden');
            filePanel.classList.add('hidden');
            startCamera();
        });

        tabFile.addEventListener('click', function () {
            tabFile.className = 'rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white shadow-xs transition';
            tabCamera.className = 'rounded-lg px-4 py-2 text-sm font-semibold text-text-muted transition hover:text-text';
            filePanel.classList.remove('hidden');
            cameraPanel.classList.add('hidden');
            clearError();
            stopCamera();
        });

        fileInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (!file) return;
            clearError();
            html5QrCode.scanFile(file, true)
                .then(handleDecodedText)
                .catch(() => {
                    showError('Could not detect a valid QR code in this image.');
                });
        });
    })();
</script>

<?php require __DIR__ . '/footer.php'; ?>
