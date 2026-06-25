<script>
document.getElementById('btnSOS').addEventListener('click', function() {
    const statusText = document.getElementById('statusSOS');
    const btn = document.getElementById('btnSOS');
    
    statusText.innerText = "Mencari koordinat lokasi...";
    btn.disabled = true;

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(kirimDataDarurat, tampilkanError, {
            enableHighAccuracy: true
        });
    } else {
        statusText.innerText = "Browser perangkat tidak mendukung pelacakan GPS.";
        btn.disabled = false;
    }
});

function kirimDataDarurat(position) {
    const latitude = position.coords.latitude;
    const longitude = position.coords.longitude;
    const nimMhs = "<?php echo $nim_mhs; ?>";

    document.getElementById('statusSOS').innerText = "Mengirim sinyal darurat ke server...";

    // Ganti baris fetch lama menjadi seperti ini:
fetch('<?php echo BASE_URL; ?>config/proses_sos.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ nim: nimMhs, lat: latitude, lng: longitude })
    })
    .then(response => response.text())
    .then(data => {
        const statusText = document.getElementById('statusSOS');
        const btn = document.getElementById('btnSOS');
        
        statusText.innerText = "Sinyal terkirim! Tim medis segera menuju lokasi.";
        statusText.style.color = '#16a34a';
        
        btn.innerText = "✅ BANTUAN DALAM PERJALANAN";
        btn.style.background = '#16a34a';
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('statusSOS').innerText = "Gagal menghubungi server klinik.";
        document.getElementById('btnSOS').disabled = false;
    });
}

function tampilkanError(error) {
    const statusText = document.getElementById('statusSOS');
    document.getElementById('btnSOS').disabled = false;
    
    switch(error.code) {
        case error.PERMISSION_DENIED:
            statusText.innerText = "Akses lokasi ditolak. Izinkan GPS pada browser.";
            break;
        case error.POSITION_UNAVAILABLE:
            statusText.innerText = "Informasi lokasi tidak tersedia saat ini.";
            break;
        case error.TIMEOUT:
            statusText.innerText = "Waktu permintaan lokasi habis.";
            break;
    }
}

// Otomatis memunculkan modal saat pertama kali login
document.addEventListener("DOMContentLoaded", function() {
    var modalElement = document.getElementById('firstLoginModal');
    if (modalElement) {
        var myModal = new bootstrap.Modal(modalElement);
        myModal.show();
    }
});
</script>