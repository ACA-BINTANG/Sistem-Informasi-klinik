<?php
/**
 * SweetAlert2 global untuk seluruh notifikasi aplikasi.
 * Gaya dibuat sederhana dan konsisten pada semua role.
 *
 * Catatan UX:
 * - Popup gagal tidak dapat ditutup dengan klik area luar atau tombol Esc.
 * - Jika proses dari sebuah modal gagal di server lalu halaman dimuat ulang,
 *   modal dan data input terakhir akan dipulihkan agar pengguna tidak keluar
 *   dari form yang sedang dikerjakan.
 */
$swalFlash = null;
$scriptDirectory = basename(dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '')));
$assetPrefix = in_array($scriptDirectory, ['admin', 'dokter', 'pasien'], true) ? '../' : '';

if (isset($_GET['err']) && trim((string) $_GET['err']) !== '') {
    $swalFlash = [
        'icon' => 'error',
        'title' => 'Gagal',
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
<style>
    .select2-container.astar-invalid .select2-selection,
    .select2-container--bootstrap-5.astar-invalid .select2-selection {
        border-color: #dc3545 !important;
    }
</style>
<script>
(function () {
    const flash = <?= json_encode($swalFlash, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const FAILED_FORM_STATE_KEY = 'astar_failed_form_context_v1';

    const baseOptions = {
        confirmButtonText: 'Oke',
        allowOutsideClick: true,
        allowEscapeKey: true
    };

    const lockedErrorOptions = {
        confirmButtonText: 'Oke',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: true
    };

    window.ASTARSwal = {
        success: function (text, title) {
            return Swal.fire(Object.assign({}, baseOptions, {
                icon: 'success',
                title: title || 'Berhasil',
                text: text || 'Data berhasil diproses.'
            }));
        },
        error: function (text, title) {
            return Swal.fire(Object.assign({}, lockedErrorOptions, {
                icon: 'error',
                title: title || 'Gagal',
                text: text || 'Proses tidak dapat dilakukan. Silakan coba kembali.'
            }));
        },
        warning: function (text, title) {
            return Swal.fire(Object.assign({}, baseOptions, {
                icon: 'warning',
                title: title || 'Peringatan',
                text: text || 'Silakan periksa kembali data yang dimasukkan.'
            }));
        },
        info: function (text, title) {
            return Swal.fire(Object.assign({}, baseOptions, {
                icon: 'info',
                title: title || 'Informasi',
                text: text || ''
            }));
        },
        confirm: function (options) {
            options = options || {};
            return Swal.fire({
                icon: options.icon || 'question',
                title: options.title || 'Konfirmasi',
                text: options.text || 'Apakah Anda yakin ingin melanjutkan?',
                showCancelButton: true,
                confirmButtonText: options.confirmText || 'Ya',
                cancelButtonText: options.cancelText || 'Batal',
                reverseButtons: true,
                focusCancel: true
            });
        }
    };

    function getCleanPageKey() {
        const url = new URL(window.location.href);
        ['msg', 'err', 'pesan'].forEach(function (key) {
            url.searchParams.delete(key);
        });
        return url.pathname + (url.search ? url.search : '');
    }

    function cleanFlashQuery() {
        if (!window.history || !window.history.replaceState) return;
        const url = new URL(window.location.href);
        ['msg', 'err', 'pesan'].forEach(function (key) {
            url.searchParams.delete(key);
        });
        window.history.replaceState({}, document.title, url.pathname + (url.search ? url.search : '') + url.hash);
    }

    function clearSavedFormContext() {
        try {
            sessionStorage.removeItem(FAILED_FORM_STATE_KEY);
        } catch (error) {
            // sessionStorage bisa dinonaktifkan browser; aplikasi tetap berjalan normal.
        }
    }

    function buildFieldState(form) {
        return Array.from(form.elements || []).map(function (field, index) {
            if (!field || !field.name || field.disabled || field.type === 'submit' || field.type === 'button' || field.type === 'file') {
                return null;
            }

            // Password tidak disimpan oleh helper global. Halaman registrasi/login
            // memiliki mekanisme penyimpanan inputnya sendiri.
            if (field.type === 'password') {
                return null;
            }

            return {
                index: index,
                name: field.name,
                type: field.type || field.tagName.toLowerCase(),
                value: field.value,
                checked: Boolean(field.checked)
            };
        }).filter(Boolean);
    }

    function saveFormContext(form) {
        const modal = form ? form.closest('.modal') : null;

        if (!form || !modal || !modal.id) {
            // Mencegah modal gagal lama terbuka lagi saat aksi lain gagal.
            clearSavedFormContext();
            return;
        }

        try {
            sessionStorage.setItem(FAILED_FORM_STATE_KEY, JSON.stringify({
                pageKey: getCleanPageKey(),
                modalId: modal.id,
                formId: form.id || '',
                formClass: form.className || '',
                fields: buildFieldState(form),
                savedAt: Date.now()
            }));
        } catch (error) {
            // Tidak mengganggu submit jika penyimpanan state gagal.
        }
    }

    function restoreFieldState(form, fields) {
        if (!form || !Array.isArray(fields)) return;

        fields.forEach(function (saved) {
            let field = null;
            const byName = Array.from(form.elements || []).filter(function (candidate) {
                return candidate && candidate.name === saved.name;
            });

            // Prioritaskan indeks DOM bila masih sama, lalu fallback berdasarkan nama.
            const indexedField = form.elements && form.elements[saved.index] ? form.elements[saved.index] : null;
            if (indexedField && indexedField.name === saved.name) {
                field = indexedField;
            } else if (byName.length === 1) {
                field = byName[0];
            } else if (byName.length > 1) {
                field = byName.find(function (candidate) {
                    return candidate.type === saved.type && String(candidate.value) === String(saved.value);
                }) || byName[0];
            }

            if (!field || field.type === 'password' || field.type === 'file') return;

            if (field.type === 'checkbox' || field.type === 'radio') {
                field.checked = Boolean(saved.checked);
            } else {
                field.value = saved.value == null ? '' : saved.value;
            }

            field.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }

    function restoreFailedFormContext() {
        let saved = null;
        try {
            saved = JSON.parse(sessionStorage.getItem(FAILED_FORM_STATE_KEY) || 'null');
        } catch (error) {
            clearSavedFormContext();
            return false;
        }

        if (!saved || !saved.modalId || saved.pageKey !== getCleanPageKey()) {
            return false;
        }

        // Buang state yang sudah terlalu lama agar tidak membuka form yang tidak relevan.
        if (!saved.savedAt || (Date.now() - Number(saved.savedAt)) > 30 * 60 * 1000) {
            clearSavedFormContext();
            return false;
        }

        const modal = document.getElementById(saved.modalId);
        if (!modal) return false;

        let form = saved.formId ? document.getElementById(saved.formId) : null;
        if (!form || !modal.contains(form)) {
            form = modal.querySelector('form');
        }

        restoreFieldState(form, saved.fields);

        if (window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(modal).show();
        } else {
            modal.classList.add('show');
            modal.style.display = 'block';
            modal.removeAttribute('aria-hidden');
            modal.setAttribute('aria-modal', 'true');
        }

        return true;
    }

    function markInvalid(field) {
        if (!field) return;
        field.classList.add('is-invalid');
        field.setAttribute('aria-invalid', 'true');
        if (window.jQuery && jQuery(field).hasClass('select2-hidden-accessible')) {
            jQuery(field).next('.select2-container').addClass('astar-invalid');
        }
    }

    function clearInvalid(field) {
        if (!field) return;
        field.classList.remove('is-invalid');
        field.removeAttribute('aria-invalid');
        if (window.jQuery && jQuery(field).hasClass('select2-hidden-accessible')) {
            jQuery(field).next('.select2-container').removeClass('astar-invalid');
        }
    }

    function getInvalidFields(form) {
        return Array.from(form.querySelectorAll('input, select, textarea')).filter(function (field) {
            if (field.disabled || field.type === 'hidden' || field.type === 'submit' || field.type === 'button') return false;
            return !field.checkValidity();
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Matikan popup validasi bawaan browser. Semua form role memakai SweetAlert.
        document.querySelectorAll('form').forEach(function (form) {
            form.noValidate = true;
        });

        document.querySelectorAll('input, select, textarea').forEach(function (field) {
            const eventName = field.tagName === 'SELECT' ? 'change' : 'input';
            field.addEventListener(eventName, function () {
                if (field.checkValidity()) clearInvalid(field);
            });
        });

        if (flash && window.Swal) {
            const isError = flash.icon === 'error';

            // Jika kegagalan berasal dari form modal, buka kembali modalnya terlebih dahulu.
            // SweetAlert lalu tampil di atas modal sehingga pengguna tetap berada pada form yang sama.
            if (isError) {
                restoreFailedFormContext();
            } else if (flash.icon === 'success') {
                clearSavedFormContext();
            }

            const popupOptions = Object.assign({}, isError ? lockedErrorOptions : baseOptions, {
                icon: flash.icon || 'info',
                title: flash.title || 'Informasi',
                text: flash.text || ''
            });

            Swal.fire(popupOptions).then(function () {
                // Query flash dibersihkan setelah pengguna menutup popup.
                // Dengan begitu popup gagal tidak menghilang sebelum pengguna menekan Oke.
                cleanFlashQuery();
            });
        }

        // Validasi umum field required untuk semua form, kecuali pemeriksaan yang punya aturan khusus.
        document.addEventListener('submit', function (event) {
            const form = event.target.closest('form');
            if (!form || form.classList.contains('pemeriksaan-form')) return;

            const invalidFields = getInvalidFields(form);
            if (invalidFields.length === 0) return;

            event.preventDefault();
            event.stopImmediatePropagation();
            invalidFields.forEach(markInvalid);

            const hasEmptyField = invalidFields.some(function (field) {
                return field.validity && field.validity.valueMissing;
            });
            const popupTitle = hasEmptyField ? 'Ada Input Kosong' : 'Input Tidak Sesuai';
            const popupText = hasEmptyField ? 'Silakan isi terlebih dahulu.' : 'Silakan periksa kembali data yang dimasukkan.';

            ASTARSwal.warning(popupText, popupTitle).then(function () {
                const first = invalidFields[0];
                if (window.jQuery && jQuery(first).hasClass('select2-hidden-accessible')) {
                    jQuery(first).select2('open');
                } else {
                    first.focus();
                }
            });
        }, true);

        // Simpan konteks modal hanya ketika form benar-benar akan dikirim.
        document.addEventListener('submit', function (event) {
            const form = event.target.closest('form');
            if (!form || event.defaultPrevented || form.classList.contains('js-swal-confirm')) return;
            saveFormContext(form);
        });

        document.addEventListener('click', function (event) {
            const link = event.target.closest('a.js-swal-confirm');
            if (!link || link.dataset.swalConfirmed === '1') return;

            event.preventDefault();
            clearSavedFormContext();
            ASTARSwal.confirm({
                title: 'Konfirmasi',
                text: 'Apakah Anda yakin ingin melanjutkan?',
                confirmText: 'Ya',
                cancelText: 'Batal'
            }).then(function (result) {
                if (result.isConfirmed) {
                    link.dataset.swalConfirmed = '1';
                    window.location.href = link.href;
                }
            });
        });

        document.addEventListener('submit', function (event) {
            const form = event.target.closest('form.js-swal-confirm');
            if (!form || form.dataset.swalConfirmed === '1' || event.defaultPrevented) return;

            event.preventDefault();
            ASTARSwal.confirm({
                title: 'Konfirmasi',
                text: 'Apakah Anda yakin ingin melanjutkan?',
                confirmText: 'Ya',
                cancelText: 'Batal'
            }).then(function (result) {
                if (result.isConfirmed) {
                    saveFormContext(form);
                    form.dataset.swalConfirmed = '1';
                    HTMLFormElement.prototype.submit.call(form);
                }
            });
        });

        document.addEventListener('click', function (event) {
            const infoButton = event.target.closest('.js-swal-info');
            if (!infoButton) return;

            event.preventDefault();
            ASTARSwal.info(
                infoButton.dataset.swalText || '',
                infoButton.dataset.swalTitle || 'Informasi'
            );
        });

        document.addEventListener('click', function (event) {
            const logout = event.target.closest('.js-swal-logout');
            if (!logout) return;

            event.preventDefault();
            clearSavedFormContext();
            ASTARSwal.confirm({
                title: 'Konfirmasi',
                text: 'Apakah Anda yakin ingin keluar?',
                confirmText: 'Ya, Keluar',
                cancelText: 'Batal'
            }).then(function (result) {
                if (result.isConfirmed) {
                    window.location.href = logout.getAttribute('href') || 'index.php';
                }
            });
        });
    });
})();
</script>
