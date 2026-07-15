<?php
// Helper cetak riwayat transaksi. Tidak menyimpan data baru ke database.
?>
<script>
(function () {
    'use strict';

    if (window.ASTARPrintHistory) return;

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function prepareClone(source) {
        const clone = source.cloneNode(true);

        clone.querySelectorAll('.astar-pagination-hidden, .astar-pagination-group-hidden').forEach(function (item) {
            item.classList.remove('astar-pagination-hidden', 'astar-pagination-group-hidden');
            item.style.display = '';
        });

        clone.querySelectorAll(
            '.astar-simple-pagination, .no-print, button, form, .modal, [data-bs-toggle="modal"], .dropdown-menu'
        ).forEach(function (item) {
            item.remove();
        });

        clone.querySelectorAll('[style]').forEach(function (item) {
            if (item.style.display === 'none') item.style.display = '';
        });

        return clone;
    }

    function print(areaId, title) {
        const source = document.getElementById(areaId);
        if (!source) {
            if (window.Swal) {
                Swal.fire({
                    icon: 'error',
                    title: 'Riwayat tidak ditemukan',
                    text: 'Bagian data yang akan dicetak tidak tersedia.',
                    confirmButtonText: 'Oke',
                    confirmButtonColor: '#0d6efd'
                });
            }
            return;
        }

        const clone = prepareClone(source);
        const popup = window.open('', '_blank', 'width=1100,height=760');
        if (!popup) {
            if (window.Swal) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Popup diblokir',
                    text: 'Izinkan popup pada browser, lalu klik Cetak Riwayat kembali.',
                    confirmButtonText: 'Oke',
                    confirmButtonColor: '#0d6efd'
                });
            }
            return;
        }

        const documentTitle = title || 'Riwayat Transaksi';
        const dateText = new Date().toLocaleString('id-ID', {
            dateStyle: 'long',
            timeStyle: 'short'
        });

        popup.document.open();
        popup.document.write(`<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>${escapeHtml(documentTitle)}</title>
    <link rel="stylesheet" href="../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/vendor/bootstrap-icons/bootstrap-icons.css">
    <style>
        body { font-family: Arial, sans-serif; color:#1f2937; padding:28px; }
        .print-heading { border-bottom:2px solid #0d6efd; padding-bottom:14px; margin-bottom:22px; }
        .print-heading h2 { margin:0 0 5px; font-size:24px; font-weight:700; }
        .print-heading small { color:#64748b; }
        .data-container, .stat-card, .card, .rujukan-card { box-shadow:none !important; }
        .data-container { border:1px solid #dbe4f0; border-radius:12px; padding:16px; margin-bottom:16px; }
        table { width:100%; border-collapse:collapse; font-size:12px; }
        th, td { border:1px solid #d8dee8 !important; padding:7px !important; vertical-align:top; }
        th { background:#eef5ff !important; color:#123b70 !important; }
        .row { display:flex; flex-wrap:wrap; }
        [class*="col-"] { box-sizing:border-box; }
        .col-12 { width:100%; }
        .col-md-6 { width:50%; padding:7px; }
        .col-md-4 { width:33.333%; padding:7px; }
        .badge { border:1px solid #cbd5e1; color:#334155 !important; background:#f8fafc !important; }
        a { color:inherit; text-decoration:none; }
        @page { size:A4 landscape; margin:12mm; }
        @media print {
            body { padding:0; }
            .col-md-6 { width:50%; }
            .col-md-4 { width:33.333%; }
        }
    </style>
</head>
<body>
    <div class="print-heading">
        <h2>${escapeHtml(documentTitle)}</h2>
        <small>Dicetak pada ${escapeHtml(dateText)}</small>
    </div>
    ${clone.outerHTML}
</body>
</html>`);
        popup.document.close();
        popup.focus();
        window.setTimeout(function () {
            popup.print();
        }, 500);
    }

    window.ASTARPrintHistory = { print };
})();
</script>
