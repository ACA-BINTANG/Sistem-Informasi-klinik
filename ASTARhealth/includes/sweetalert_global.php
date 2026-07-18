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
    $errorText = trim((string) $_GET['err']);
    $errorTextLower = strtolower($errorText);
    $errorTitle = 'Proses Gagal';
    $errorDetails = array_values(array_filter(array_map('trim', explode('|', $errorText))));

    if (str_contains($errorTextLower, 'wajib diisi') || str_contains($errorTextLower, 'belum lengkap') || str_contains($errorTextLower, 'input kosong')) {
        $errorTitle = 'Data Belum Lengkap';
    } elseif (str_contains($errorTextLower, 'sudah digunakan') || str_contains($errorTextLower, 'duplikat') || str_contains($errorTextLower, 'duplicate')) {
        $errorTitle = 'Data Sudah Terdaftar';
    } elseif (str_contains($errorTextLower, 'format') || str_contains($errorTextLower, 'tidak valid') || str_contains($errorTextLower, 'tidak sesuai')) {
        $errorTitle = 'Data Tidak Valid';
    } elseif (str_contains($errorTextLower, 'jumlah obat') || str_contains($errorTextLower, 'jumlah order') || str_contains($errorTextLower, 'jumlah yang diterima')) {
        $errorTitle = 'Jumlah Obat Tidak Valid';
    } elseif (str_contains($errorTextLower, 'stok')) {
        $errorTitle = 'Stok Obat Bermasalah';
    } elseif (str_contains($errorTextLower, 'jadwal') || str_contains($errorTextLower, 'jam booking')) {
        $errorTitle = 'Jadwal Tidak Tersedia';
    } elseif (str_contains($errorTextLower, 'antrean aktif')) {
        $errorTitle = 'Antrean Masih Aktif';
    }

    $swalFlash = [
        'icon' => 'error',
        'title' => $errorTitle,
        'text' => count($errorDetails) > 1 ? '' : $errorText,
        'html' => count($errorDetails) > 1
            ? '<div style="text-align:left"><p style="margin:0 0 10px">Perbaiki bagian berikut:</p><ul style="margin:0;padding-left:20px"><li>' . implode('</li><li>', array_map(static fn($item) => htmlspecialchars($item, ENT_QUOTES, 'UTF-8'), $errorDetails)) . '</li></ul></div>'
            : '',
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
                text: text || 'Data yang dimasukkan belum memenuhi ketentuan. Periksa field yang ditandai.'
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

    function getFieldLabel(field) {
        if (!field) return 'Field';

        const explicitLabel = field.id ? document.querySelector('label[for="' + CSS.escape(field.id) + '"]') : null;
        if (explicitLabel && explicitLabel.textContent.trim()) {
            return explicitLabel.textContent.replace(/\s*\*\s*$/, '').trim();
        }

        const wrapper = field.closest('.mb-3, .mb-4, .form-group, .col, [class*="col-"]');
        const nearbyLabel = wrapper ? wrapper.querySelector('label') : null;
        if (nearbyLabel && nearbyLabel.textContent.trim()) {
            return nearbyLabel.textContent.replace(/\s*\*\s*$/, '').trim();
        }

        const fallback = field.getAttribute('aria-label') || field.getAttribute('placeholder') || field.name || 'Field';
        return String(fallback)
            .replace(/\[.*?\]/g, '')
            .replace(/[_-]+/g, ' ')
            .replace(/\b\w/g, function (letter) { return letter.toUpperCase(); })
            .trim() || 'Field';
    }

    function getValidationMessage(field) {
        const label = getFieldLabel(field);
        const validity = field.validity || {};

        if (validity.valueMissing) return label + ' wajib diisi.';
        if (validity.typeMismatch) {
            if (field.type === 'email') return label + ' harus menggunakan format email yang valid, contoh: nama@domain.com.';
            if (field.type === 'url') return label + ' harus menggunakan format URL yang valid.';
            return 'Format ' + label + ' tidak valid.';
        }
        if (validity.patternMismatch) {
            const rule = field.getAttribute('title');
            return rule ? label + ': ' + rule : 'Format ' + label + ' tidak sesuai ketentuan.';
        }
        if (validity.tooShort) return label + ' minimal ' + field.minLength + ' karakter.';
        if (validity.tooLong) return label + ' maksimal ' + field.maxLength + ' karakter.';
        if (validity.rangeUnderflow) {
            const fieldIdentity = ((field.name || '') + ' ' + (field.id || '') + ' ' + (field.className || '')).toLowerCase();
            if ((fieldIdentity.includes('jumlah') || fieldIdentity.includes('qty')) && Number(field.min || 0) >= 1) {
                return 'Jumlah obat harus lebih dari 0. Masukkan minimal 1 unit.';
            }
            return label + ' tidak boleh kurang dari ' + field.min + '.';
        }
        if (validity.rangeOverflow) return label + ' tidak boleh lebih dari ' + field.max + '.';
        if (validity.stepMismatch) return 'Nilai ' + label + ' tidak sesuai interval yang diperbolehkan.';
        if (validity.badInput) return label + ' berisi nilai yang tidak dapat diproses.';
        return label + ' belum sesuai ketentuan.';
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = String(value == null ? '' : value);
        return div.innerHTML;
    }

    function showDetailedValidationPopup(invalidFields) {
        const messages = invalidFields.map(getValidationMessage);
        const uniqueMessages = messages.filter(function (message, index) {
            return messages.indexOf(message) === index;
        });
        const visibleMessages = uniqueMessages.slice(0, 6);
        const remaining = uniqueMessages.length - visibleMessages.length;
        let html = '<div style="text-align:left"><p style="margin:0 0 10px">Periksa bagian berikut:</p><ul style="margin:0;padding-left:20px">';
        html += visibleMessages.map(function (message) {
            return '<li style="margin-bottom:6px">' + escapeHtml(message) + '</li>';
        }).join('');
        html += '</ul>';
        if (remaining > 0) {
            html += '<p style="margin:10px 0 0">Dan ' + remaining + ' kesalahan lainnya.</p>';
        }
        html += '</div>';

        const hasEmptyField = invalidFields.some(function (field) {
            return field.validity && field.validity.valueMissing;
        });

        return Swal.fire(Object.assign({}, lockedErrorOptions, {
            icon: 'warning',
            title: hasEmptyField ? 'Data Belum Lengkap' : 'Data Belum Sesuai',
            html: html
        }));
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
                title: flash.title || 'Informasi'
            });
            if (flash.html) {
                popupOptions.html = flash.html;
            } else {
                popupOptions.text = flash.text || '';
            }

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

            showDetailedValidationPopup(invalidFields).then(function () {
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

            // Native form.submit() tidak ikut mengirim name/value tombol submit.
            // Simpan submitter agar aksi seperti hapus/edit tetap terbaca oleh PHP.
            const submitter = event.submitter || null;
            const submitterName = submitter && submitter.name ? submitter.name : '';
            const submitterValue = submitter && submitter.value ? submitter.value : '1';

            event.preventDefault();
            ASTARSwal.confirm({
                title: form.dataset.swalTitle || 'Konfirmasi',
                text: form.dataset.swalText || 'Apakah Anda yakin ingin melanjutkan?',
                confirmText: form.dataset.swalConfirm || 'Ya',
                cancelText: 'Batal'
            }).then(function (result) {
                if (result.isConfirmed) {
                    if (submitterName && !form.querySelector('input[type="hidden"][data-swal-submitter="1"][name="' + CSS.escape(submitterName) + '"]')) {
                        const hiddenSubmitter = document.createElement('input');
                        hiddenSubmitter.type = 'hidden';
                        hiddenSubmitter.name = submitterName;
                        hiddenSubmitter.value = submitterValue;
                        hiddenSubmitter.dataset.swalSubmitter = '1';
                        form.appendChild(hiddenSubmitter);
                    }

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
