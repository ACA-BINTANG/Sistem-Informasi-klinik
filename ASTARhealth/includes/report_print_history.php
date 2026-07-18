<?php
/**
 * Riwayat cetak laporan.
 * - Membuat tabel riwayat otomatis bila belum tersedia.
 * - Mencatat klik Export PDF dari seluruh halaman laporan dokter.
 * - Menampilkan modal Riwayat Cetak pada setiap laporan.
 */

if (!function_exists('astarEnsureReportPrintHistoryTable')) {
    function astarEnsureReportPrintHistoryTable(mysqli $conn): bool
    {
        static $ready = null;
        if ($ready !== null) {
            return $ready;
        }

        $sql = "CREATE TABLE IF NOT EXISTS riwayat_cetak_laporan (
            id_riwayat BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            jenis_laporan VARCHAR(50) NOT NULL,
            judul_laporan VARCHAR(150) NOT NULL,
            id_user VARCHAR(30) NULL,
            nama_pencetak VARCHAR(150) NOT NULL,
            parameter_filter TEXT NULL,
            tanggal_cetak DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id_riwayat),
            KEY idx_jenis_tanggal (jenis_laporan, tanggal_cetak),
            KEY idx_id_user (id_user)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $ready = (bool) mysqli_query($conn, $sql);
        return $ready;
    }
}

if (!function_exists('astarReportMap')) {
    function astarReportMap(): array
    {
        return [
            'siloam' => 'Laporan Siloam',
            'dinkes' => 'Laporan Dinkes',
            'internal_pasien' => 'Laporan Internal Pasien',
            'k3' => 'Laporan K3 Astar',
            'keuangan' => 'Laporan Finance Obat',
        ];
    }
}

if (!function_exists('astarCleanReportFilterQuery')) {
    function astarCleanReportFilterQuery(string $query): string
    {
        parse_str($query, $params);
        unset($params['page'], $params['ajax']);

        $clean = [];
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', array_map('strval', $value));
            }
            $value = trim((string) $value);
            if ($value !== '') {
                $clean[(string) $key] = $value;
            }
        }

        return http_build_query($clean);
    }
}

if (!function_exists('astarFormatReportFilter')) {
    function astarFormatReportFilter(?string $query): string
    {
        $query = trim((string) $query);
        if ($query === '') {
            return 'Tanpa filter';
        }

        parse_str($query, $params);
        $labels = [
            'search' => 'Pencarian',
            'status' => 'Status',
            'tgl_awal' => 'Dari',
            'tgl_akhir' => 'Sampai',
            'prodi' => 'Prodi',
            'kategori' => 'Kategori',
            'bulan' => 'Bulan',
            'tahun' => 'Tahun',
        ];

        $parts = [];
        foreach ($params as $key => $value) {
            if ($key === 'page' || $key === 'ajax') {
                continue;
            }
            if (is_array($value)) {
                $value = implode(', ', array_map('strval', $value));
            }
            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }
            $parts[] = ($labels[$key] ?? ucwords(str_replace('_', ' ', (string) $key))) . ': ' . $value;
        }

        return $parts ? implode(' | ', $parts) : 'Tanpa filter';
    }
}

if (!function_exists('astarFetchReportPrintHistory')) {
    function astarFetchReportPrintHistory(mysqli $conn, string $reportKey, int $limit = 100): array
    {
        if (!astarEnsureReportPrintHistoryTable($conn)) {
            return [];
        }

        $limit = max(1, min($limit, 500));
        $stmt = mysqli_prepare(
            $conn,
            "SELECT id_riwayat, nama_pencetak, parameter_filter, tanggal_cetak
             FROM riwayat_cetak_laporan
             WHERE jenis_laporan = ?
             ORDER BY tanggal_cetak DESC, id_riwayat DESC
             LIMIT $limit"
        );
        if (!$stmt) {
            return [];
        }

        mysqli_stmt_bind_param($stmt, 's', $reportKey);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $rows = [];
        while ($result && ($row = mysqli_fetch_assoc($result))) {
            $rows[] = $row;
        }
        mysqli_stmt_close($stmt);
        return $rows;
    }
}

// Endpoint AJAX pencatatan riwayat cetak.
if (
    isset($conn) && $conn instanceof mysqli &&
    ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' &&
    (($_GET['ajax'] ?? '') === 'catat_riwayat_cetak')
) {
    header('Content-Type: application/json; charset=UTF-8');

    $reportKey = trim((string) ($_POST['report_key'] ?? ''));
    $reportMap = astarReportMap();
    if (!isset($reportMap[$reportKey])) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Jenis laporan tidak valid.']);
        exit();
    }

    if (!astarEnsureReportPrintHistoryTable($conn)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Tabel riwayat cetak tidak dapat disiapkan.']);
        exit();
    }

    $reportTitle = $reportMap[$reportKey];
    $filterQuery = astarCleanReportFilterQuery((string) ($_POST['filter_query'] ?? ''));
    $sessionUserId = trim((string) ($_SESSION['id_user'] ?? ''));
    $sessionName = trim((string) ($_SESSION['nama_lengkap'] ?? 'Dokter'));
    if ($sessionName === '') {
        $sessionName = 'Dokter';
    }

    $stmt = mysqli_prepare(
        $conn,
        'INSERT INTO riwayat_cetak_laporan
        (jenis_laporan, judul_laporan, id_user, nama_pencetak, parameter_filter, tanggal_cetak)
        VALUES (?, ?, NULLIF(?, \'\'), ?, ?, NOW())'
    );

    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Riwayat cetak gagal disimpan.']);
        exit();
    }

    mysqli_stmt_bind_param($stmt, 'sssss', $reportKey, $reportTitle, $sessionUserId, $sessionName, $filterQuery);
    $ok = mysqli_stmt_execute($stmt);
    $newId = $ok ? mysqli_insert_id($conn) : 0;
    mysqli_stmt_close($stmt);

    if (!$ok) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Riwayat cetak gagal disimpan.']);
        exit();
    }

    echo json_encode([
        'success' => true,
        'row' => [
            'id_riwayat' => $newId,
            'nama_pencetak' => $sessionName,
            'filter_text' => astarFormatReportFilter($filterQuery),
            'tanggal_cetak' => date('d M Y H:i'),
        ],
    ]);
    exit();
}

if (!function_exists('renderReportPrintHistoryActions')) {
    function renderReportPrintHistoryActions(
        mysqli $conn,
        string $reportKey,
        string $reportTitle,
        string $exportUrl,
        string $exportLabel = 'Export PDF'
    ): void {
        $rows = astarFetchReportPrintHistory($conn, $reportKey);
        $safeKey = preg_replace('/[^a-z0-9_\-]/i', '', $reportKey);
        $modalId = 'modalRiwayatCetak_' . $safeKey;
        $tbodyId = 'riwayatCetakBody_' . $safeKey;
        ?>
        <div class="d-flex flex-wrap gap-2 justify-content-end align-items-center no-print">
            <div class="input-group" style="max-width: 330px;">
                <span class="input-group-text bg-white"><i class="bi bi-calendar-month"></i></span>
                <input type="month"
                       class="form-control js-report-month"
                       value="<?= date('Y-m') ?>"
                       aria-label="Pilih bulan laporan">
                <button type="button"
                        class="btn btn-outline-primary fw-bold js-report-monthly-export"
                        data-export-url="<?= htmlspecialchars($exportUrl, ENT_QUOTES, 'UTF-8') ?>"
                        data-report-key="<?= htmlspecialchars($reportKey, ENT_QUOTES, 'UTF-8') ?>"
                        data-history-body="<?= htmlspecialchars($tbodyId, ENT_QUOTES, 'UTF-8') ?>">
                    <i class="bi bi-printer me-1"></i>Cetak Bulanan
                </button>
            </div>
            <button type="button" class="btn btn-outline-primary fw-bold" data-bs-toggle="modal" data-bs-target="#<?= htmlspecialchars($modalId, ENT_QUOTES, 'UTF-8') ?>">
                <i class="bi bi-clock-history me-2"></i>Riwayat Cetak
            </button>
            <a href="<?= htmlspecialchars($exportUrl, ENT_QUOTES, 'UTF-8') ?>"
               target="_blank" rel="noopener"
               class="btn btn-primary fw-bold js-report-export"
               data-report-key="<?= htmlspecialchars($reportKey, ENT_QUOTES, 'UTF-8') ?>"
               data-history-body="<?= htmlspecialchars($tbodyId, ENT_QUOTES, 'UTF-8') ?>">
                <i class="bi bi-file-earmark-pdf me-2"></i><?= htmlspecialchars($exportLabel, ENT_QUOTES, 'UTF-8') ?>
            </a>
        </div>

        <div class="modal fade" id="<?= htmlspecialchars($modalId, ENT_QUOTES, 'UTF-8') ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="modal-header text-white border-0 px-4 py-3" style="background: linear-gradient(135deg, #0753a6, #2f85ef);">
                        <div>
                            <h5 class="modal-title fw-bold mb-1"><i class="bi bi-clock-history me-2"></i>Riwayat Cetak</h5>
                            <small class="text-white-50"><?= htmlspecialchars($reportTitle, ENT_QUOTES, 'UTF-8') ?></small>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 190px;">Tanggal Cetak</th>
                                        <th style="width: 220px;">Dicetak Oleh</th>
                                        <th>Filter Laporan</th>
                                    </tr>
                                </thead>
                                <tbody id="<?= htmlspecialchars($tbodyId, ENT_QUOTES, 'UTF-8') ?>">
                                    <?php if ($rows): ?>
                                        <?php foreach ($rows as $row): ?>
                                            <tr data-history-id="<?= (int) $row['id_riwayat'] ?>">
                                                <td class="fw-semibold"><?= htmlspecialchars(date('d M Y H:i', strtotime($row['tanggal_cetak'])), ENT_QUOTES, 'UTF-8') ?></td>
                                                <td><?= htmlspecialchars($row['nama_pencetak'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                                                <td><span class="text-muted small"><?= htmlspecialchars(astarFormatReportFilter($row['parameter_filter'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr class="report-history-empty-row">
                                            <td colspan="3" class="text-center py-5 text-muted">Belum ada riwayat cetak untuk laporan ini.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php

        static $scriptRendered = false;
        if ($scriptRendered) {
            return;
        }
        $scriptRendered = true;
        ?>
        <script>
        (function () {
            'use strict';
            if (window.__ASTAR_REPORT_PRINT_HISTORY__) return;
            window.__ASTAR_REPORT_PRINT_HISTORY__ = true;

            function escapeHtml(value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function insertHistoryRow(tbodyId, row) {
                const tbody = document.getElementById(tbodyId);
                if (!tbody || !row) return;

                tbody.querySelectorAll('.report-history-empty-row').forEach(function (emptyRow) {
                    emptyRow.remove();
                });

                if (row.id_riwayat && tbody.querySelector('[data-history-id="' + row.id_riwayat + '"]')) {
                    return;
                }

                const tr = document.createElement('tr');
                if (row.id_riwayat) tr.dataset.historyId = row.id_riwayat;
                tr.innerHTML =
                    '<td class="fw-semibold">' + escapeHtml(row.tanggal_cetak || '-') + '</td>' +
                    '<td>' + escapeHtml(row.nama_pencetak || '-') + '</td>' +
                    '<td><span class="text-muted small">' + escapeHtml(row.filter_text || 'Tanpa filter') + '</span></td>';
                tbody.prepend(tr);
            }

            function showPopupBlocked() {
                if (window.Swal) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Popup Diblokir',
                        text: 'Izinkan popup pada browser lalu coba cetak laporan kembali.',
                        confirmButtonText: 'Oke',
                        confirmButtonColor: '#0d6efd'
                    });
                }
            }

            function openAndRecordReport(urlValue, reportKey, historyBody) {
                const popup = window.open(urlValue, '_blank');
                if (!popup) {
                    showPopupBlocked();
                    return;
                }

                let filterQuery = '';
                try {
                    const url = new URL(urlValue, window.location.href);
                    filterQuery = url.searchParams.toString();
                } catch (error) {
                    filterQuery = window.location.search.replace(/^\?/, '');
                }

                const body = new FormData();
                body.append('report_key', reportKey || '');
                body.append('filter_query', filterQuery);

                fetch('index.php?ajax=catat_riwayat_cetak', {
                    method: 'POST',
                    body: body,
                    credentials: 'same-origin',
                    keepalive: true
                })
                    .then(function (response) { return response.json(); })
                    .then(function (data) {
                        if (data && data.success) {
                            insertHistoryRow(historyBody || '', data.row);
                        }
                    })
                    .catch(function () {
                        // Pencetakan tetap berjalan walaupun pencatatan riwayat gagal.
                    });
            }

            document.addEventListener('click', function (event) {
                const monthlyButton = event.target.closest('.js-report-monthly-export');
                if (monthlyButton) {
                    event.preventDefault();
                    const wrapper = monthlyButton.closest('.input-group');
                    const monthInput = wrapper ? wrapper.querySelector('.js-report-month') : null;
                    const monthValue = monthInput ? String(monthInput.value || '') : '';

                    if (!/^\d{4}-\d{2}$/.test(monthValue)) {
                        if (window.Swal) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Bulan Belum Dipilih',
                                text: 'Pilih bulan yang akan dicetak terlebih dahulu.',
                                confirmButtonText: 'Oke',
                                confirmButtonColor: '#0d6efd'
                            });
                        }
                        monthInput?.focus();
                        return;
                    }

                    const parts = monthValue.split('-');
                    const year = Number(parts[0]);
                    const month = Number(parts[1]);
                    const lastDay = new Date(year, month, 0).getDate();
                    const url = new URL(monthlyButton.dataset.exportUrl || '', window.location.href);
                    url.searchParams.set('tgl_awal', monthValue + '-01');
                    url.searchParams.set('tgl_akhir', monthValue + '-' + String(lastDay).padStart(2, '0'));
                    url.searchParams.delete('page');

                    openAndRecordReport(
                        url.toString(),
                        monthlyButton.dataset.reportKey || '',
                        monthlyButton.dataset.historyBody || ''
                    );
                    return;
                }

                const link = event.target.closest('.js-report-export');
                if (!link) return;
                event.preventDefault();
                openAndRecordReport(link.href, link.dataset.reportKey || '', link.dataset.historyBody || '');
            });
        })();
        </script>
        <?php
    }
}
