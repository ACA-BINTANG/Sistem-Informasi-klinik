<?php
// File halaman ini dipanggil dari ../dashboard utama.
// Variabel dari dashboard utama tetap bisa dipakai di sini.
?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1">Rujukan Pasien</h3>
                <small class="text-muted">Kelola surat rujukan pasien ke rumah sakit tujuan.</small>
            </div>
            <button class="btn btn-primary fw-bold px-4" data-bs-toggle="modal" data-bs-target="#mAddRujukan">
                <i class="bi bi-plus-circle me-1"></i> Buat Rujukan
            </button>
        </div>

        <?php
        $totalRjk = 0;
        $aktifRjk = 0;
        $bulanIniRjk = 0;
        $qRjkCount = mysqli_query(
            $conn,
            "SELECT id_rujukan FROM rujukan WHERE id_staff = '$id_dokter'",
        );
        if ($qRjkCount) {
            $totalRjk = mysqli_num_rows($qRjkCount);
        }
        $qRjkAktif = mysqli_query(
            $conn,
            "SELECT id_rujukan FROM rujukan WHERE id_staff = '$id_dokter' AND status = 'Aktif'",
        );
        $aktifRjk = $qRjkAktif ? mysqli_num_rows($qRjkAktif) : 0;
        $qRjkBulan = mysqli_query(
            $conn,
            "SELECT id_rujukan FROM rujukan WHERE id_staff = '$id_dokter' AND MONTH(tgl_rujukan) = MONTH(CURDATE()) AND YEAR(tgl_rujukan) = YEAR(CURDATE())",
        );
        $bulanIniRjk = $qRjkBulan ? mysqli_num_rows($qRjkBulan) : 0;
        ?>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div>
                        <div class="small text-muted fw-bold">TOTAL RUJUKAN</div>
                        <div class="h2 fw-bold text-primary mb-0"><?= $totalRjk ?></div>
                    </div>
                    <div class="icon-badge"><i class="bi bi-file-earmark-medical"></i></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card success">
                    <div>
                        <div class="small text-muted fw-bold">STATUS AKTIF</div>
                        <div class="h2 fw-bold text-success mb-0"><?= $aktifRjk ?></div>
                    </div>
                    <div class="icon-badge success"><i class="bi bi-hospital"></i></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div>
                        <div class="small text-muted fw-bold">BULAN INI</div>
                        <div class="h2 fw-bold text-warning mb-0"><?= $bulanIniRjk ?></div>
                    </div>
                    <div class="icon-badge warning"><i class="bi bi-calendar-check"></i></div>
                </div>
            </div>
        </div>

        <div class="data-container">
            <h5 class="fw-bold mb-4">
                <i class="bi bi-clock-history text-primary me-2"></i>Riwayat Surat Rujukan
            </h5>

            <div class="row g-3" data-astar-list-pagination>
                <?php
                $qRjk = mysqli_query(
                    $conn,
                    "SELECT r.*, p.nama_pasien, p.no_identitas FROM rujukan r JOIN pasienm p ON r.id_pasien = p.id_pasien WHERE r.id_staff = '$id_dokter' ORDER BY r.tgl_rujukan DESC",
                );

                if (!$qRjk || mysqli_num_rows($qRjk) == 0) {
                    echo "
                        <div class='col-12'>
                            <div class='text-center py-5 text-muted'>
                                <i class='bi bi-file-earmark-medical' style='font-size:4rem;'></i>
                                <h5 class='fw-bold mt-3'>Belum Ada Rujukan</h5>
                                <p class='mb-0'>Surat rujukan yang dibuat akan muncul di sini.</p>
                            </div>
                        </div>
                    ";
                } else {
                    while ($r = mysqli_fetch_assoc($qRjk)): ?>
                <div class="col-md-6" data-astar-pagination-item>
                    <div class="rujukan-card h-100">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rujukan-icon"><i class="bi bi-file-earmark-medical"></i></div>
                                <div>
                                    <div class="rujukan-id-badge mb-1"><?= e(
                                        $r["id_rujukan"],
                                    ) ?></div>
                                    <h6 class="fw-bold mb-0"><?= e(
                                        $r["nama_pasien"],
                                    ) ?></h6>
                                    <small class="text-muted"><?= e(
                                        $r["no_identitas"],
                                    ) ?></small>
                                </div>
                            </div>
                            <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill fw-bold"><?= e(
                                $r["status"],
                            ) ?></span>
                        </div>

                        <div class="ps-1 mb-3">
                            <div class="d-flex align-items-center gap-2 text-muted small mb-2">
                                <i class="bi bi-hospital text-primary"></i>
                                <span class="fw-600 text-dark"><?= e(
                                    $r["tujuan_rs"],
                                ) ?></span>
                            </div>
                            <div class="d-flex align-items-center gap-2 text-muted small">
                                <i class="bi bi-calendar3 text-primary"></i>
                                <?= date(
                                    "d M Y",
                                    strtotime($r["tgl_rujukan"]),
                                ) ?>
                            </div>
                        </div>

                        <div class="bg-light rounded-4 p-3 mb-3">
                            <label class="small fw-bold text-muted text-uppercase" style="font-size: 10px;">Alasan Rujukan</label>
                            <p class="small text-dark mb-0 text-truncate-2"><?= e(
                                $r["alasan_rujukan"],
                            ) ?></p>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button onclick="printRujukan('<?= e(
                                $r["id_rujukan"],
                            ) ?>')" class="btn btn-sm btn-light border fw-bold px-3">
                                <i class="bi bi-printer me-1"></i> Cetak Surat
                            </button>
                        </div>
                    </div>
                </div>
                <?php endwhile;
                }
                ?>
            </div>
        </div>

        <!-- MODAL TAMBAH RUJUKAN -->
        <div class="modal fade" id="mAddRujukan" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content border-0 shadow-lg" style="border-radius: 24px;" method="POST">
                    <div class="modal-header bg-primary text-white border-0 py-4">
                        <h5 class="fw-bold mb-0"><i class="bi bi-file-earmark-plus me-2"></i>Buat Surat Rujukan</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <!-- Input Search Pasien -->
                        <div class="mb-4 position-relative">
                            <label class="small fw-bold text-muted text-uppercase">Cari Pasien (NIM / NAMA)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0"><i class="bi bi-search"></i></span>
                                <input type="text" id="inputKeyword" class="form-control border-0 bg-light" placeholder="Masukkan NIM atau Nama..." autocomplete="off">
                            </div>
                            <!-- Wadah Hasil -->
                            <div id="hasilPencarian" class="d-none"></div> 
                        </div>

                        <!-- Box Info Pasien Terpilih -->
                        <div id="infoTerpilih" class="alert alert-primary border-0 d-none rounded-4 mb-4">
                            <input type="hidden" name="id_pasien" id="id_pasien_fix" required>
                            <div class="d-flex align-items-center gap-3">
                                <i class="bi bi-person-check-fill fs-2"></i>
                                <div>
                                    <h6 class="mb-0 fw-bold" id="nama_pasien_fix"></h6>
                                    <small id="nim_pasien_fix"></small>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="small fw-bold text-muted text-uppercase">RS Tujuan</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0"><i class="bi bi-hospital"></i></span>
                                <input type="text" name="tujuan_rs" class="form-control border-0 bg-light" placeholder="Nama Rumah Sakit..." required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="small fw-bold text-muted text-uppercase">Alasan Rujukan</label>
                            <textarea name="alasan_rujukan" class="form-control border-0 bg-light" rows="2" placeholder="Alasan medis..." required></textarea>
                        </div>
                        <div class="mb-0">
                            <label class="small fw-bold text-muted text-uppercase">Hasil Pemeriksaan</label>
                            <textarea name="hasil_rujukan" class="form-control border-0 bg-light" rows="2" placeholder="Diagnosa sementara..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4">
                        <button type="submit" name="add_rujukan" class="btn btn-primary w-100 py-3 fw-bold">
                            <i class="bi bi-send-check me-2"></i>Simpan & Terbitkan Surat
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
