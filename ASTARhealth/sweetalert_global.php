<?php
/**
 * SweetAlert2 global untuk notifikasi dan konfirmasi.
 * File ini aman dipanggil lebih dari sekali dalam satu halaman.
 */
$swalFlash = null;
$scriptDirectory = basename(dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '')));
$assetPrefix = in_array($scriptDirectory, ['admin', 'dokter', 'pasien'], true) ? '../' : '';

if (isset($_GET['err']) && trim((string) $_GET['err']) !== '') {
    $swalFlash = [
        'icon' => 'error',
        'title' => 'Proses Gagal',
        'text' => trim((string) $_GET['err']),
    ];
} elseif (isset($_GET['msg']) && trim((string) $_GET['msg']) !== '') {
    $swalFlash = [
        'icon' => 'success',
        'title' => 'Berhasil',
        'text' => trim((string) $_GET['msg']),
    ];
} elseif (isset($_GET['pesan']) && trim((string) $_GET['pesan']) !== '') {
    $swalFlash = [
        'icon' => 'info',
        'title' => 'Informasi',
        'text' => trim((string) $_GET['pesan']),
    ];
}
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.26.25/dist/sweetalert2.all.min.js"></script>
<script src="<?= htmlspecialchars($assetPrefix, ENT_QUOTES, 'UTF-8') ?>assets/js/sweetalert-fallback.js"></script>
<script>
(function () {
    const flash = <?= json_encode($swalFlash, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    function cleanFlashQuery() {
        if (!window.history || !window.history.replaceState) return;
        const url = new URL(window.location.href);
        ['msg', 'err', 'pesan'].forEach(function (key) {
            url.searchParams.delete(key);
        });
        window.history.replaceState({}, document.title, url.pathname + (url.search ? url.search : '') + url.hash);
    }

    function confirmationOptions(element) {
        return {
            icon: element.dataset.swalIcon || 'warning',
            title: element.dataset.swalTitle || 'Apakah Anda yakin?',
            text: element.dataset.swalText || 'Tindakan ini akan mengubah data.',
            showCancelButton: true,
            confirmButtonText: element.dataset.swalConfirm || 'Ya, lanjutkan',
            cancelButtonText: element.dataset.swalCancel || 'Batal',
            confirmButtonColor: element.dataset.swalConfirmColor || '#dc3545',
            cancelButtonColor: '#6c757d',
            reverseButtons: true,
            focusCancel: true
        };
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (flash && window.Swal) {
            Swal.fire({
                icon: flash.icon || 'info',
                title: flash.title || 'Informasi',
                text: flash.text || '',
                confirmButtonText: 'Oke',
                confirmButtonColor: '#175cdd'
            });
            cleanFlashQuery();
        }

        document.addEventListener('click', function (event) {
            const link = event.target.closest('a.js-swal-confirm');
            if (!link || link.dataset.swalConfirmed === '1') return;

            event.preventDefault();
            Swal.fire(confirmationOptions(link)).then(function (result) {
                if (result.isConfirmed) {
                    link.dataset.swalConfirmed = '1';
                    window.location.href = link.href;
                }
            });
        });

        document.addEventListener('submit', function (event) {
            const form = event.target.closest('form.js-swal-confirm');
            if (!form || form.dataset.swalConfirmed === '1') return;

            event.preventDefault();
            Swal.fire(confirmationOptions(form)).then(function (result) {
                if (result.isConfirmed) {
                    form.dataset.swalConfirmed = '1';
                    HTMLFormElement.prototype.submit.call(form);
                }
            });
        });

        document.addEventListener('click', function (event) {
            const infoButton = event.target.closest('.js-swal-info');
            if (!infoButton) return;

            event.preventDefault();
            Swal.fire({
                icon: infoButton.dataset.swalIcon || 'info',
                title: infoButton.dataset.swalTitle || 'Informasi',
                text: infoButton.dataset.swalText || '',
                confirmButtonText: 'Oke',
                confirmButtonColor: '#175cdd'
            });
        });

        document.addEventListener('click', function (event) {
            const logout = event.target.closest('.js-swal-logout');
            if (!logout) return;

            event.preventDefault();
            Swal.fire({
                icon: 'question',
                title: 'Keluar dari Sistem?',
                text: 'Pastikan semua data sudah tersimpan.',
                showCancelButton: true,
                confirmButtonText: 'Ya, Keluar',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                reverseButtons: true
            }).then(function (result) {
                if (result.isConfirmed) {
                    window.location.href = logout.getAttribute('href') || 'index.php';
                }
            });
        });
    });
})();
</script>
