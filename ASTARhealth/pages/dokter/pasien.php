<?php
// File halaman ini dipanggil dari ../dashboard utama.
// Variabel dari dashboard utama tetap bisa dipakai di sini.
?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1">Data Pasien</h3>
                <small class="text-muted">Daftar pasien yang terdaftar di klinik.</small>
            </div>
        </div>

        <div class="data-container">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>No Identitas</th>
                            <th>Nama Pasien</th>
                            <th>Kategori</th>
                            <th>Unit / Prodi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        $noP = 1;

                        $qPasien = mysqli_query(
                            $conn,
                            "
                            SELECT *
                            FROM pasienm
                            ORDER BY nama_pasien ASC
                        ",
                        );

                        if (!$qPasien) {
                            echo "<tr><td colspan='5' class='text-center text-danger'>Query error: " .
                                e(mysqli_error($conn)) .
                                "</td></tr>";
                        } elseif (mysqli_num_rows($qPasien) == 0) {
                            echo "<tr><td colspan='5' class='text-center py-5 text-muted'>Belum ada data pasien.</td></tr>";
                        }

                        if ($qPasien) {
                            while ($p = mysqli_fetch_assoc($qPasien)): ?>
                            <tr>
                                <td><?= $noP++ ?></td>
                                <td class="fw-bold text-primary"><?= e(
                                    $p["no_identitas"],
                                ) ?></td>
                                <td class="fw-bold"><?= e(
                                    $p["nama_pasien"],
                                ) ?></td>
                                <td><span class="badge bg-light text-dark border px-3"><?= e(
                                    $p["kategori_pasien"],
                                ) ?></span></td>
                                <td><?= e($p["unit_prodi"]) ?></td>
                            </tr>
                        <?php endwhile;
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
