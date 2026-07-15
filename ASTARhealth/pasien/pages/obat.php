<?php
// File halaman ini dipanggil dari ../dashboard utama.
// Variabel dari dashboard utama tetap bisa dipakai di sini.
?>

        <h4 class="fw-bold mb-4">Informasi Stok Farmasi</h4>
        <div class="data-container">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr><th>Nama Obat</th><th>Satuan</th><th class="text-center">Ketersediaan</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        $qo = mysqli_query(
                            $conn,
                            "SELECT * FROM obatm ORDER BY nama_obat ASC",
                        );
                        if ($qo && mysqli_num_rows($qo) == 0) {
                            echo "<tr><td colspan='3' class='text-center text-muted py-4'>Belum ada data obat.</td></tr>";
                        }
                        if ($qo) {
                            while ($ro = mysqli_fetch_assoc($qo)): ?>
                            <tr>
                                <td class="fw-bold text-primary"><?= e(
                                    $ro["nama_obat"],
                                ) ?></td>
                                <td><span class="badge bg-light text-dark border px-3"><?= e(
                                    $ro["satuan"] ?? "Umum",
                                ) ?></span></td>
                                <td class="text-center">
                                    <span class="badge <?= $ro[
                                        "stok_sekarang"
                                    ] > 0
                                        ? "bg-success"
                                        : "bg-danger" ?> bg-opacity-10 text-<?= $ro[
     "stok_sekarang"
 ] > 0
     ? "success"
     : "danger" ?> px-4 py-2 rounded-pill fw-bold">
                                        <?= $ro["stok_sekarang"] > 0
                                            ? "Tersedia"
                                            : "Habis" ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile;
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

