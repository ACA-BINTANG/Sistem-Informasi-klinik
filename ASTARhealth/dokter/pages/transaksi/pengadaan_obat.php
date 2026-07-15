<?php
// File halaman ini dipanggil dari ../dashboard utama.
// Variabel dari dashboard utama tetap bisa dipakai di sini.
?>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h3 class="fw-bold mb-1">Pengadaan Obat</h3>
                <small class="text-muted">Catat dan lihat transaksi pengadaan obat klinik.</small>
            </div>
            <div class="d-flex gap-2 no-print">
                <button class="btn btn-primary fw-bold px-4" data-bs-toggle="modal" data-bs-target="#modalTambahPengadaan"><i class="bi bi-plus-circle me-1"></i>Buat Pengadaan</button>
            </div>
        </div>

        <!-- Obat Kurang Stok -->
        <div class="row g-4 mb-5">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-header bg-danger bg-opacity-10 border-0 px-4 py-3">
                        <h6 class="fw-bold text-danger mb-0">⚠️ Obat Kurang dari Stok Minimum</h6>
                    </div>

                    <div class="card-body p-4">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Obat</th>
                                        <th>Stok</th>
                                        <th>Min</th>
                                        <th>Pesan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $qKurang = mysqli_query(
                                        $conn,
                                        "
                                        SELECT id_obat, nama_obat, stok_sekarang, stok_minimum, stok_target
                                        FROM obatm
                                        WHERE stok_sekarang < stok_minimum
                                        ORDER BY stok_sekarang ASC
                                    ",
                                    );

                                    if (
                                        !$qKurang ||
                                        mysqli_num_rows($qKurang) == 0
                                    ) {
                                        echo "<tr><td colspan='4' class='text-center text-muted py-3'>Semua obat dalam kondisi baik ✓</td></tr>";
                                    } else {
                                        while (
                                            $ok = mysqli_fetch_assoc($qKurang)
                                        ): ?>
                                    <tr>
                                        <td class="fw-bold"><?= e(
                                            $ok["nama_obat"],
                                        ) ?></td>
                                        <td><span class="badge bg-danger"><?= $ok[
                                            "stok_sekarang"
                                        ] ?></span></td>
                                        <td><?= $ok["stok_minimum"] ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-warning fw-bold px-2"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalTambahPengadaan"
                                                    data-id-obat="<?= e(
                                                        $ok["id_obat"],
                                                    ) ?>"
                                                    data-nama-obat="<?= e(
                                                        $ok["nama_obat"],
                                                    ) ?>"
                                                    data-stok-sekarang="<?= $ok[
                                                        "stok_sekarang"
                                                    ] ?>"
                                                    data-stok-target="<?= $ok[
                                                        "stok_target"
                                                    ] ?>"
                                                    onclick="hitungJumlahOrder(this)">
                                                Pesan
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endwhile;
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ringkasan Obat -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-info bg-opacity-10 border-0 px-4 py-3">
                        <h6 class="fw-bold text-info mb-0">📊 Ringkasan Stok Obat</h6>
                    </div>

                    <div class="card-body p-4">
                        <?php
                        $qStats = mysqli_query(
                            $conn,
                            "
                            SELECT 
                                COUNT(*) as total_obat,
                                SUM(CASE WHEN stok_sekarang >= stok_minimum THEN 1 ELSE 0 END) as stok_baik,
                                SUM(CASE WHEN stok_sekarang < stok_minimum THEN 1 ELSE 0 END) as stok_kurang,
                                SUM(stok_sekarang) as total_stok
                            FROM obatm
                        ",
                        );
                        $stats = mysqli_fetch_assoc($qStats);
                        ?>

                        <div class="row g-3 text-center">
                            <div class="col-6">
                                <div class="p-3 bg-light rounded-3">
                                    <h5 class="fw-bold text-primary"><?= $stats[
                                        "total_obat"
                                    ] ?></h5>
                                    <small class="text-muted">Total Obat</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-light rounded-3">
                                    <h5 class="fw-bold text-success"><?= $stats[
                                        "stok_baik"
                                    ] ?></h5>
                                    <small class="text-muted">Stok Baik</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-light rounded-3">
                                    <h5 class="fw-bold text-danger"><?= $stats[
                                        "stok_kurang"
                                    ] ?></h5>
                                    <small class="text-muted">Stok Kurang</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-light rounded-3">
                                    <h5 class="fw-bold text-warning"><?= $stats[
                                        "total_stok"
                                    ] ?></h5>
                                    <small class="text-muted">Total Unit</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daftar Pengadaan -->
        <div class="data-container" id="printPengadaanHistory">
            <h5 class="fw-bold mb-4">Riwayat Pengadaan Obat</h5>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Obat</th>
                            <th>Pemasok</th>
                            <th>Jumlah Pesan</th>
                            <th>Jumlah Diterima</th>
                            <th>Tanggal Order</th>
                            <th>Target Tiba</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $qPengadaan = mysqli_query(
                            $conn,
                            "
                            SELECT p.*, o.nama_obat, s.nama_supplier
                            FROM pengadaan_obat p
                            LEFT JOIN obatm o ON p.id_obat = o.id_obat
                            LEFT JOIN supplierm s ON p.id_supplier = s.id_supplier
                            ORDER BY p.tgl_order DESC
                        ",
                        );

                        if (!$qPengadaan) {
                            echo "<tr><td colspan='9' class='text-center text-danger'>Query error: " .
                                e(mysqli_error($conn)) .
                                "</td></tr>";
                        } elseif (mysqli_num_rows($qPengadaan) == 0) {
                            echo "<tr><td colspan='9' class='text-center py-5 text-muted'>Belum ada data pengadaan.</td></tr>";
                        } else {
                            while ($p = mysqli_fetch_assoc($qPengadaan)):
                                $badgeClass =
                                    [
                                        "Pending" => "warning",
                                        "Diterima" => "success",
                                        "Batal" => "danger",
                                    ][$p["status"]] ?? "secondary"; ?>
                            <tr>
                                <td class="fw-bold small"><?= e(
                                    $p["id_pengadaan"],
                                ) ?></td>
                                <td><?= e($p["nama_obat"] ?? "N/A") ?></td>
                                <td><?= e(
                                    $p["nama_supplier"] ??
                                        ($p["nama_supplier"] ?? "-"),
                                ) ?></td>
                                <td class="fw-bold"><?= $p[
                                    "jumlah_order"
                                ] ?> unit</td>
                                <td>
                                    <?php
                                    $jumlahDiterima = (int) ($p["jumlah_diterima"] ?? 0);
                                    if ($p["status"] === "Diterima" && $jumlahDiterima <= 0) {
                                        $jumlahDiterima = (int) $p["jumlah_order"];
                                    }
                                    ?>
                                    <?php if ($p["status"] === "Diterima"): ?>
                                        <span class="fw-bold text-success"><?= $jumlahDiterima ?> unit</span>
                                        <?php if ($jumlahDiterima < (int) $p["jumlah_order"]): ?>
                                            <div class="small text-warning fw-bold">Diterima sebagian</div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date(
                                    "d/m/Y",
                                    strtotime($p["tgl_order"]),
                                ) ?></td>
                                <td><?= $p["tgl_estimasi_tiba"]
                                    ? date(
                                        "d/m/Y",
                                        strtotime($p["tgl_estimasi_tiba"]),
                                    )
                                    : "-" ?></td>
                                <td>
                                    <span class="badge bg-<?= $badgeClass ?> bg-opacity-10 text-<?= $badgeClass ?> fw-bold">
                                        <?= e($p["status"]) ?>
                                    </span>
                                    <?php if ($p["status"] === "Diterima"): ?>
                                        <div class="small text-success fw-bold mt-1">
                                            <i class="bi bi-check-circle-fill me-1"></i>Obat sudah diterima
                                        </div>
                                    <?php elseif ($p["status"] === "Batal" && strtotime($p["tgl_order"]) < strtotime("-5 days")): ?>
                                        <div class="small text-danger fw-bold mt-1">
                                            <i class="bi bi-x-circle-fill me-1"></i>Lebih dari 5 hari • otomatis Batal
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($p["status"] === "Pending"): ?>
                                        <button type="button"
                                                class="btn btn-sm btn-success fw-bold btn-konfirmasi-pengadaan"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalKonfirmasiPengadaan"
                                                data-id="<?= e($p["id_pengadaan"]) ?>"
                                                data-obat="<?= e($p["nama_obat"] ?? "-") ?>"
                                                data-jumlah-order="<?= (int) $p["jumlah_order"] ?>">
                                            <i class="bi bi-check-circle me-1"></i>Konfirmasi
                                        </button>
                                    <?php elseif ($p["status"] === "Diterima"): ?>
                                        <span class="text-success small fw-bold"><i class="bi bi-check-circle-fill me-1"></i>Terkonfirmasi</span>
                                    <?php else: ?>
                                        <span class="text-danger small fw-bold"><i class="bi bi-x-circle-fill me-1"></i>Dibatalkan</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php
                            endwhile;
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="alert alert-light border rounded-4 mb-4 small text-muted">
            <i class="bi bi-info-circle me-1"></i>
            Status pengadaan baru otomatis <strong>Pending</strong>. Saat obat datang, dokter menekan <strong>Konfirmasi</strong> lalu mengisi jumlah yang benar-benar diterima. Stok hanya bertambah sesuai jumlah tersebut. Jika belum dikonfirmasi lebih dari 5 hari sejak tanggal order, status otomatis menjadi <strong>Batal</strong>.
        </div>

        <!-- Modal Konfirmasi Penerimaan Pengadaan -->
        <div class="modal fade" id="modalKonfirmasiPengadaan" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" id="formKonfirmasiPengadaan" class="modal-content border-0 shadow-lg" style="border-radius:24px;" novalidate>
                    <div class="modal-header bg-success text-white border-0 py-4">
                        <div>
                            <h5 class="fw-bold mb-1">Konfirmasi Obat Diterima</h5>
                            <small class="text-white-50">Isi jumlah yang benar-benar sampai ke klinik.</small>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <input type="hidden" name="id_pengadaan" id="konfirmasi_id_pengadaan">
                        <input type="hidden" name="konfirmasi_pengadaan" value="1">

                        <div class="p-3 bg-light rounded-4 mb-4">
                            <div class="small text-muted mb-1">Obat</div>
                            <div class="fw-bold" id="konfirmasi_nama_obat">-</div>
                            <div class="small text-muted mt-2">Jumlah dipesan: <strong id="konfirmasi_jumlah_order">0</strong> unit</div>
                        </div>

                        <div>
                            <label for="konfirmasi_jumlah_diterima" class="form-label fw-bold">Jumlah yang Diterima</label>
                            <input type="number"
                                   name="jumlah_diterima"
                                   id="konfirmasi_jumlah_diterima"
                                   class="form-control bg-light border-0"
                                   min="1"
                                   placeholder="Masukkan jumlah obat yang datang">
                            <div class="form-text">Boleh lebih sedikit dari jumlah pesanan. Stok hanya bertambah sesuai angka ini.</div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 px-4 pb-4 d-flex gap-2">
                        <button type="button" class="btn btn-light flex-fill py-2 fw-bold" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success flex-fill py-2 fw-bold">
                            <i class="bi bi-check-circle me-1"></i>Simpan Konfirmasi
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('modalKonfirmasiPengadaan');
            const form = document.getElementById('formKonfirmasiPengadaan');
            const inputId = document.getElementById('konfirmasi_id_pengadaan');
            const namaObat = document.getElementById('konfirmasi_nama_obat');
            const jumlahOrderText = document.getElementById('konfirmasi_jumlah_order');
            const jumlahDiterima = document.getElementById('konfirmasi_jumlah_diterima');
            let jumlahOrderAktif = 0;

            if (modal) {
                modal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    if (!button) return;

                    jumlahOrderAktif = parseInt(button.getAttribute('data-jumlah-order') || '0', 10);
                    inputId.value = button.getAttribute('data-id') || '';
                    namaObat.textContent = button.getAttribute('data-obat') || '-';
                    jumlahOrderText.textContent = jumlahOrderAktif;
                    jumlahDiterima.value = jumlahOrderAktif > 0 ? jumlahOrderAktif : '';
                    jumlahDiterima.max = String(jumlahOrderAktif);
                    jumlahDiterima.classList.remove('is-invalid');
                });
            }

            if (form) {
                form.addEventListener('submit', function (event) {
                    const nilai = parseInt(jumlahDiterima.value || '0', 10);
                    if (!Number.isInteger(nilai) || nilai < 1 || nilai > jumlahOrderAktif) {
                        event.preventDefault();
                        jumlahDiterima.classList.add('is-invalid');
                        const pesan = nilai > jumlahOrderAktif
                            ? 'Jumlah yang diterima tidak boleh melebihi jumlah yang dipesan.'
                            : 'Masukkan jumlah obat yang benar-benar diterima.';

                        if (window.Swal) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Jumlah Tidak Sesuai',
                                text: pesan,
                                confirmButtonText: 'Oke',
                                allowOutsideClick: false,
                                allowEscapeKey: false
                            });
                        } else {
                            alert(pesan);
                        }
                        return;
                    }

                    jumlahDiterima.classList.remove('is-invalid');
                });

                jumlahDiterima.addEventListener('input', function () {
                    if (jumlahDiterima.classList.contains('is-invalid')) {
                        jumlahDiterima.classList.remove('is-invalid');
                    }
                });
            }
        });
        </script>

        <!-- Modal Tambah Pengadaan -->
        <div class="modal fade" id="modalTambahPengadaan" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" id="formTambahPengadaan" class="modal-content border-0 shadow-lg" style="border-radius:24px;" novalidate>
                    <div class="modal-header bg-primary text-white border-0 py-4">
                        <h5 class="fw-bold mb-0">Buat Pengadaan Obat</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="small fw-bold text-muted">PILIH OBAT</label>
                            <select name="id_obat" class="form-select bg-light border-0" required>
                                <option value="">-- Pilih Obat --</option>
                                <?php
                                $qObatList = mysqli_query(
                                    $conn,
                                    "
                                    SELECT id_obat, nama_obat, stok_sekarang, stok_minimum, stok_target
                                    FROM obatm
                                    ORDER BY nama_obat ASC
                                ",
                                );
                                while ($obt = mysqli_fetch_assoc($qObatList)):
                                    $status =
                                        $obt["stok_sekarang"] <
                                        $obt["stok_minimum"]
                                            ? "🔴 KURANG"
                                            : "🟢 OK"; ?>
                                <option value="<?= e(
                                    $obt["id_obat"],
                                ) ?>" title="Stok: <?= $obt[
    "stok_sekarang"
] ?>">
                                    <?= e(
                                        $obt["nama_obat"],
                                    ) ?> - <?= $status ?> (Stok: <?= $obt[
     "stok_sekarang"
 ] ?>)
                                </option>
                                <?php
                                endwhile;
                                ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="small fw-bold text-muted">SUPPLIER</label>
                            <select name="id_supplier" class="form-select bg-light border-0" required>
                                <?php
                                $qSupplier = mysqli_query(
                                    $conn,
                                    "SELECT * FROM supplierm ORDER BY nama_supplier ASC",
                                );
                                if (
                                    $qSupplier &&
                                    mysqli_num_rows($qSupplier) > 0
                                ) {
                                    while (
                                        $sup = mysqli_fetch_assoc($qSupplier)
                                    ): ?>
                                <option value="<?= e(
                                    $sup["id_supplier"],
                                ) ?>"><?= e($sup["nama_supplier"]) ?></option>
                                <?php endwhile;
                                } else {
                                    echo '<option value="">(Tidak ada supplier)</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="small fw-bold text-muted">JUMLAH ORDER</label>
                            <small class="text-warning" id="saran_jumlah" style="display:none;"></small>
                            <input type="number" name="jumlah_order" id="jumlah_order" class="form-control bg-light border-0" 
                                   placeholder="Jumlah unit" min="1" required>
                        </div>

                        <div class="mb-3">
                            <label class="small fw-bold text-muted">TARGET TIBA</label>
                            <input type="date"
                                   name="tgl_estimasi_tiba"
                                   id="target_tiba_pengadaan"
                                   class="form-control bg-light border-0"
                                   min="<?= date("Y-m-d") ?>">
                            <div class="form-text">Target tiba tidak boleh lebih awal dari tanggal pengadaan.</div>
                        </div>
                    </div>

                    <div class="modal-footer border-0 px-4 pb-4">
                        <button type="submit" name="add_pengadaan_obat" class="btn btn-primary w-100 py-3 fw-bold">
                            Buat Pengadaan
                        </button>
                    </div>
                </form>
            </div>
        </div>



<script>
document.addEventListener('DOMContentLoaded', function () {
    const formTambahPengadaan = document.getElementById('formTambahPengadaan');
    if (!formTambahPengadaan) return;

    const obat = formTambahPengadaan.querySelector('[name="id_obat"]');
    const supplier = formTambahPengadaan.querySelector('[name="id_supplier"]');
    const jumlah = formTambahPengadaan.querySelector('[name="jumlah_order"]');
    const targetTiba = document.getElementById('target_tiba_pengadaan');
    let sudahSubmit = false;

    function tanggalHariIni() {
        const now = new Date();
        const y = now.getFullYear();
        const m = String(now.getMonth() + 1).padStart(2, '0');
        const d = String(now.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    function hapusBorderSaatDiperbaiki(input) {
        if (!input) return;
        input.addEventListener('input', function () {
            if (sudahSubmit) input.classList.remove('is-invalid');
        });
        input.addEventListener('change', function () {
            if (sudahSubmit) input.classList.remove('is-invalid');
        });
    }

    [obat, supplier, jumlah, targetTiba].forEach(hapusBorderSaatDiperbaiki);

    formTambahPengadaan.addEventListener('submit', function (event) {
        sudahSubmit = true;
        [obat, supplier, jumlah, targetTiba].forEach(function (input) {
            if (input) input.classList.remove('is-invalid');
        });

        const kosong = [];
        if (!obat || !obat.value) kosong.push(obat);
        if (!supplier || !supplier.value) kosong.push(supplier);
        if (!jumlah || parseInt(jumlah.value || '0', 10) < 1) kosong.push(jumlah);

        if (kosong.length > 0) {
            event.preventDefault();
            kosong.forEach(function (input) {
                if (input) input.classList.add('is-invalid');
            });
            if (window.Swal) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Ada Input Kosong',
                    text: 'Silakan isi terlebih dahulu.',
                    confirmButtonText: 'Oke',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                });
            }
            return;
        }

        if (targetTiba && targetTiba.value && targetTiba.value < tanggalHariIni()) {
            event.preventDefault();
            targetTiba.classList.add('is-invalid');
            if (window.Swal) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Target Tiba Tidak Sesuai',
                    text: 'Target tiba tidak boleh lebih awal dari tanggal pengadaan.',
                    confirmButtonText: 'Oke',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                });
            }
        }
    });
});
</script>
