<?php
// Tampilan global untuk form edit/ubah data agar konsisten di seluruh role.
?>
<style>
    .astar-edit-modal .modal-content {
        border: 0 !important;
        border-radius: 22px !important;
        overflow: hidden;
        box-shadow: 0 24px 70px rgba(15, 61, 130, 0.22) !important;
    }

    .astar-edit-modal .modal-header {
        background: linear-gradient(120deg, #0f3d82 0%, #0568d8 58%, #2f8df4 100%) !important;
        color: #fff !important;
        border: 0 !important;
        padding: 22px 24px !important;
    }

    .astar-edit-modal .modal-header h5,
    .astar-edit-modal .modal-header .modal-title,
    .astar-edit-modal .modal-header small {
        color: #fff !important;
    }

    .astar-edit-modal .modal-header .btn-close {
        filter: invert(1) grayscale(1) brightness(200%);
        opacity: .9;
    }

    .astar-edit-modal .modal-body {
        padding: 24px !important;
        background: #fff;
    }

    .astar-edit-modal .astar-field-label,
    .astar-edit-modal .form-label {
        display: block;
        margin: 0 0 8px !important;
        color: #334155 !important;
        font-size: 13px !important;
        font-weight: 700 !important;
        text-transform: none !important;
        letter-spacing: 0 !important;
    }

    .astar-edit-modal .form-control,
    .astar-edit-modal .form-select {
        min-height: 48px;
        border: 1px solid #e3eaf4 !important;
        border-radius: 12px !important;
        background: #f6f8fc !important;
        padding: 11px 14px !important;
    }

    .astar-edit-modal textarea.form-control {
        min-height: 105px;
        resize: vertical;
    }

    .astar-edit-modal .input-group .input-group-text {
        border: 1px solid #e3eaf4 !important;
        border-right: 0 !important;
        background: #eef3f9 !important;
    }

    .astar-edit-modal .input-group .form-control {
        border-left: 0 !important;
    }

    .astar-edit-modal .form-control:focus,
    .astar-edit-modal .form-select:focus {
        background: #fff !important;
        border-color: #2f8df4 !important;
        box-shadow: 0 0 0 4px rgba(47, 141, 244, .12) !important;
    }

    .astar-edit-modal .modal-footer {
        border: 0 !important;
        padding: 0 24px 24px !important;
        background: #fff;
        gap: 10px;
    }

    .astar-edit-modal .modal-footer .btn-primary,
    .astar-edit-modal button[name^="update_"],
    .astar-edit-modal button[name*="update_"] {
        background: linear-gradient(120deg, #0568d8, #2f8df4) !important;
        border: 0 !important;
        color: #fff !important;
        font-weight: 700 !important;
        min-height: 46px;
        border-radius: 12px !important;
    }

    .astar-edit-modal .modal-footer .btn-light,
    .astar-edit-modal .modal-footer .btn-secondary {
        min-height: 46px;
        border-radius: 12px !important;
    }

    .astar-edit-modal .form-text {
        font-size: 12px;
        line-height: 1.5;
    }
</style>
<script>
(function () {
    'use strict';
    if (window.__ASTAR_FORM_UI_LOADED__) return;
    window.__ASTAR_FORM_UI_LOADED__ = true;

    const fieldLabels = {
        username: 'Username',
        email: 'Email',
        password: 'Kata Sandi',
        nama_lengkap: 'Nama Lengkap',
        nama_pasien: 'Nama Pasien',
        nama_supplier: 'Nama Pemasok',
        nama_obat: 'Nama Obat',
        nama_penyakit: 'Nama Penyakit',
        no_identitas: 'NIM / NIP / NIK',
        jenis_kelamin: 'Jenis Kelamin',
        kategori_pasien: 'Kategori Pasien',
        unit_prodi: 'Unit / Prodi',
        no_hp: 'Nomor WhatsApp',
        kontak: 'Nomor Kontak',
        alamat: 'Alamat',
        role: 'Peran Akun',
        role_akun: 'Peran Akun',
        jabatan: 'Jabatan',
        instansi: 'Instansi',
        npa_idi: 'NPA IDI',
        stok_sekarang: 'Stok Sekarang',
        stok_minimum: 'Stok Minimum',
        stok_target: 'Target Stok',
        satuan: 'Satuan',
        harga_per_pcs: 'Harga per Pcs',
        kategori: 'Kategori',
        tipe: 'Tipe',
        tanggal: 'Hari',
        jam_mulai: 'Jam Mulai',
        jam_selesai: 'Jam Selesai',
        status: 'Status',
        status_rujukan: 'Status Rujukan',
        alasan_rujukan: 'Alasan Rujukan',
        hasil_rujukan: 'Hasil Pemeriksaan',
        jumlah_diterima: 'Jumlah Diterima'
    };

    function titleFromName(name) {
        if (!name) return 'Data';
        if (fieldLabels[name]) return fieldLabels[name];
        return name
            .replace(/\[\]$/g, '')
            .replace(/[_-]+/g, ' ')
            .replace(/\b\w/g, function (char) { return char.toUpperCase(); });
    }

    function cleanPlaceholder(value) {
        const text = String(value || '').trim();
        if (!text || /^pilih\b/i.test(text)) return '';
        return text
            .replace(/^masukkan\s+/i, '')
            .replace(/^isi\s+/i, '')
            .replace(/^contoh\s*:\s*/i, '')
            .replace(/\s*\(opsional\)\s*/i, '')
            .trim();
    }

    function findDirectLabel(field) {
        if (field.id) {
            try {
                const byFor = field.closest('.modal')?.querySelector('label[for="' + CSS.escape(field.id) + '"]');
                if (byFor) return byFor;
            } catch (_) {}
        }

        let previous = field.previousElementSibling;
        if (previous && previous.tagName === 'LABEL') return previous;

        const group = field.closest('.input-group');
        if (group) {
            previous = group.previousElementSibling;
            if (previous && previous.tagName === 'LABEL') return previous;
        }

        const parent = field.parentElement;
        if (parent) {
            const directLabels = Array.from(parent.children).filter(function (el) { return el.tagName === 'LABEL'; });
            if (directLabels.length === 1) return directLabels[0];
        }
        return null;
    }

    function ensureLabel(field) {
        if (!field || field.type === 'hidden' || field.type === 'submit' || field.type === 'button') return;

        let label = findDirectLabel(field);
        const placeholderText = cleanPlaceholder(field.getAttribute('placeholder'));
        const labelText = field.dataset.label || fieldLabels[field.name] || placeholderText || titleFromName(field.name);

        if (!label) {
            label = document.createElement('label');
            label.className = 'form-label astar-field-label';
            label.textContent = labelText;
            if (field.id) label.htmlFor = field.id;

            const inputGroup = field.closest('.input-group');
            if (inputGroup && inputGroup.parentNode) {
                inputGroup.parentNode.insertBefore(label, inputGroup);
            } else if (field.parentNode) {
                field.parentNode.insertBefore(label, field);
            }
        } else {
            label.classList.add('form-label', 'astar-field-label');
            if (!String(label.textContent || '').trim()) label.textContent = labelText;
        }

        if (!field.getAttribute('placeholder') && field.tagName !== 'SELECT') {
            field.setAttribute('placeholder', 'Masukkan ' + labelText.toLowerCase());
        }
    }

    function isEditModal(modal) {
        const form = modal.querySelector('form');
        if (!form) return false;
        const id = String(modal.id || '').toLowerCase();
        const title = String(modal.querySelector('.modal-title, .modal-header h5')?.textContent || '').toLowerCase();
        const hasUpdateAction = !!form.querySelector('[name^="update_"], [name*="update_"], button[name="update_user"], button[name="update_staff"], button[name="update_pasien"], button[name="update_supplier"], button[name="update_obat"], button[name="update_diagnosa"], button[name="update_jadwal_dokter"]');
        return id.includes('edit') || id.startsWith('medit') || title.includes('edit') || title.includes('ubah status') || hasUpdateAction;
    }

    function normalizeModal(modal) {
        if (!isEditModal(modal)) return;
        modal.classList.add('astar-edit-modal');

        const header = modal.querySelector('.modal-header');
        if (header) {
            header.classList.remove('bg-warning', 'text-dark');
            header.classList.add('bg-primary', 'text-white');
            const close = header.querySelector('.btn-close');
            if (close) close.classList.add('btn-close-white');
        }

        modal.querySelectorAll('input:not([type="hidden"]):not([type="submit"]):not([type="button"]), select, textarea').forEach(ensureLabel);
    }

    function refresh(root) {
        (root || document).querySelectorAll('.modal').forEach(normalizeModal);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { refresh(document); });
    } else {
        refresh(document);
    }

    document.addEventListener('shown.bs.modal', function (event) {
        if (event.target && event.target.matches('.modal')) normalizeModal(event.target);
    });

    window.ASTARFormUI = { refresh: refresh };
})();
</script>
