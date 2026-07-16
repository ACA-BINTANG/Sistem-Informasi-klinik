<?php
// Tampilan tabel global: scroll horizontal dan format tanggal Indonesia yang konsisten.
?>
<style>
    .table-responsive {
        width: 100%;
        max-width: 100%;
        overflow-x: auto !important;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin;
        scrollbar-color: #a9bfdc #edf3fa;
    }

    .table-responsive::-webkit-scrollbar { height: 9px; }
    .table-responsive::-webkit-scrollbar-track {
        background: #edf3fa;
        border-radius: 999px;
    }
    .table-responsive::-webkit-scrollbar-thumb {
        background: #a9bfdc;
        border-radius: 999px;
    }
    .table-responsive::-webkit-scrollbar-thumb:hover { background: #7fa5d2; }

    /* Tabel tetap selebar kontainer, tetapi boleh melebar mengikuti isi dan digeser. */
    .table-responsive > table {
        width: max-content !important;
        min-width: 100% !important;
        margin-bottom: 0;
    }

    .table-responsive > table th,
    .table-responsive > table td {
        vertical-align: middle;
    }

    .table-responsive > table th,
    .table-responsive > table td.text-nowrap,
    .table-responsive > table td:last-child {
        white-space: nowrap;
    }

    .astar-filter-actions {
        display: flex;
        align-items: end;
        gap: 10px;
        height: 100%;
    }

    .astar-filter-actions .btn { min-height: 38px; }

    @media (max-width: 767.98px) {
        .astar-filter-actions { align-items: stretch; }
        .astar-filter-actions .btn { flex: 1 1 0; }
    }
</style>
<script>
(function () {
    'use strict';
    if (window.__ASTAR_TABLE_UI_LOADED__) return;
    window.__ASTAR_TABLE_UI_LOADED__ = true;

    const monthNames = {
        '01': 'Januari', '02': 'Februari', '03': 'Maret', '04': 'April',
        '05': 'Mei', '06': 'Juni', '07': 'Juli', '08': 'Agustus',
        '09': 'September', '10': 'Oktober', '11': 'November', '12': 'Desember'
    };
    const monthAliases = {
        jan: 'Januari', january: 'Januari', januari: 'Januari',
        feb: 'Februari', february: 'Februari', februari: 'Februari',
        mar: 'Maret', march: 'Maret', maret: 'Maret',
        apr: 'April', april: 'April',
        may: 'Mei', mei: 'Mei',
        jun: 'Juni', june: 'Juni', juni: 'Juni',
        jul: 'Juli', july: 'Juli', juli: 'Juli',
        aug: 'Agustus', august: 'Agustus', agu: 'Agustus', agustus: 'Agustus',
        sep: 'September', sept: 'September', september: 'September',
        oct: 'Oktober', october: 'Oktober', okt: 'Oktober', oktober: 'Oktober',
        nov: 'November', november: 'November',
        dec: 'Desember', december: 'Desember', des: 'Desember', desember: 'Desember'
    };

    function padDay(value) {
        const number = Number(value);
        return Number.isFinite(number) ? String(number).padStart(2, '0') : value;
    }

    function formatDateText(text) {
        let result = text;

        // YYYY-MM-DD, termasuk bila diikuti jam.
        result = result.replace(/\b(\d{4})-(\d{2})-(\d{2})\b/g, function (_, year, month, day) {
            return padDay(day) + '-' + (monthNames[month] || month) + '-' + year;
        });

        // DD-MM-YYYY / DD/MM/YYYY.
        result = result.replace(/\b(\d{1,2})[-\/](\d{2})[-\/](\d{4})\b/g, function (_, day, month, year) {
            return padDay(day) + '-' + (monthNames[month] || month) + '-' + year;
        });

        // DD Mon YYYY / DD Month YYYY.
        result = result.replace(/\b(\d{1,2})\s+(Jan(?:uary)?|Feb(?:ruary)?|Mar(?:ch)?|Apr(?:il)?|May|Jun(?:e)?|Jul(?:y)?|Aug(?:ust)?|Sep(?:t(?:ember)?)?|Oct(?:ober)?|Nov(?:ember)?|Dec(?:ember)?|Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember)\s+(\d{4})\b/gi,
            function (_, day, month, year) {
                return padDay(day) + '-' + (monthAliases[String(month).toLowerCase()] || month) + '-' + year;
            }
        );
        return result;
    }

    function formatTableDates(root) {
        const scope = root || document;
        scope.querySelectorAll('table td').forEach(function (cell) {
            const walker = document.createTreeWalker(cell, NodeFilter.SHOW_TEXT);
            const textNodes = [];
            while (walker.nextNode()) textNodes.push(walker.currentNode);
            textNodes.forEach(function (node) {
                const next = formatDateText(node.nodeValue || '');
                if (next !== node.nodeValue) node.nodeValue = next;
            });
        });
    }

    function prepareScrollableTables(root) {
        const scope = root || document;
        scope.querySelectorAll('.table-responsive > table').forEach(function (table) {
            const columnCount = table.querySelectorAll('thead th').length;
            if (columnCount >= 9) table.style.minWidth = '1200px';
            else if (columnCount >= 7) table.style.minWidth = '980px';
            else if (columnCount >= 5) table.style.minWidth = '780px';
            else table.style.minWidth = '100%';
        });
    }

    function refresh(root) {
        formatTableDates(root);
        prepareScrollableTables(root);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { refresh(document); });
    } else {
        refresh(document);
    }

    window.ASTARTableUI = { refresh: refresh, formatDates: formatTableDates };
})();
</script>
