(function () {
    'use strict';

    if (window.__ASTAR_GLOBAL_VALIDATION_LOADED__) return;
    window.__ASTAR_GLOBAL_VALIDATION_LOADED__ = true;

    const FIELD_LABELS = {
        username: 'Username',
        email: 'Email',
        password: 'Kata Sandi',
        nama: 'Nama Lengkap',
        nama_lengkap: 'Nama Lengkap',
        nama_pasien: 'Nama Pasien',
        nama_supplier: 'Nama Pemasok',
        no_identitas: 'NIM / NIP / NIK',
        identitas: 'Nomor Identitas',
        jenis_kelamin: 'Jenis Kelamin',
        jk: 'Jenis Kelamin',
        kategori_pasien: 'Kategori Pasien',
        kategori: 'Kategori',
        role: 'Peran Akun',
        role_akun: 'Peran Akun',
        jabatan: 'Jabatan',
        instansi: 'Instansi',
        no_hp: 'Nomor WhatsApp',
        kontak: 'Nomor Kontak',
        alamat: 'Alamat',
        nama_obat: 'Nama Obat',
        nama_penyakit: 'Nama Penyakit',
        stok_sekarang: 'Stok Sekarang',
        stok_minimum: 'Stok Minimum',
        stok_target: 'Target Stok',
        harga_per_pcs: 'Harga per Pcs',
        satuan: 'Satuan',
        tanggal: 'Hari',
        jam_mulai: 'Jam Mulai',
        jam_selesai: 'Jam Selesai',
        id_obat: 'Obat',
        id_supplier: 'Pemasok',
        jumlah_order: 'Jumlah Order',
        jumlah_diterima: 'Jumlah Diterima',
        tgl_estimasi_tiba: 'Target Tiba',
        id_diagnosa: 'Diagnosa',
        keluhan: 'Keluhan',
        keluhan_booking: 'Keluhan',
        hasil_pemeriksaan: 'Hasil Pemeriksaan',
        tujuan_rs: 'Rumah Sakit Tujuan',
        alasan_rujukan: 'Alasan Rujukan',
        hasil_rujukan: 'Hasil Rujukan',
        status_rujukan: 'Status Rujukan',
        tgl_awal: 'Tanggal Awal',
        tgl_mulai: 'Tanggal Mulai',
        tgl_akhir: 'Tanggal Akhir',
        jam_booking: 'Jam Booking',
        jumlah_keluar: 'Jumlah Obat',
        catatan_obat: 'Aturan Pakai'
    };

    const PERSON_NAME_FIELDS = new Set(['nama', 'nama_lengkap', 'nama_pasien']);
    const PHONE_FIELDS = new Set(['no_hp', 'kontak']);
    const IDENTITY_FIELDS = new Set(['no_identitas', 'identitas']);
    const DATE_START_NAMES = ['tgl_awal', 'tgl_mulai'];
    const DATE_END_NAMES = ['tgl_akhir'];

    function baseName(field) {
        return String(field && field.name ? field.name : '')
            .replace(/\[\]$/g, '')
            .trim();
    }

    function cleanText(value) {
        return String(value == null ? '' : value).trim();
    }

    function textLength(value) {
        return Array.from(String(value || '')).length;
    }

    function getLabel(field) {
        if (!field) return 'Inputan';

        if (field.dataset && field.dataset.label) return field.dataset.label;

        if (field.id) {
            try {
                const label = document.querySelector('label[for="' + CSS.escape(field.id) + '"]');
                if (label && cleanText(label.textContent)) {
                    return cleanText(label.textContent).replace(/\*/g, '').replace(/\s+/g, ' ');
                }
            } catch (_) {}
        }

        const wrapper = field.closest('.mb-1, .mb-2, .mb-3, .mb-4, .col, [class*="col-"], .form-group, .input-group');
        if (wrapper) {
            const label = wrapper.querySelector('label');
            if (label && cleanText(label.textContent)) {
                return cleanText(label.textContent).replace(/\*/g, '').replace(/\s+/g, ' ');
            }
        }

        const name = baseName(field);
        if (FIELD_LABELS[name]) return FIELD_LABELS[name];

        const placeholder = cleanText(field.getAttribute && field.getAttribute('placeholder'));
        if (placeholder) {
            return placeholder
                .replace(/^masukkan\s+/i, '')
                .replace(/^pilih\s+/i, '')
                .replace(/^contoh\s*:\s*/i, '')
                .replace(/\s+/g, ' ');
        }

        return name
            ? name.replace(/[_-]+/g, ' ').replace(/\b\w/g, function (letter) { return letter.toUpperCase(); })
            : 'Inputan';
    }

    function isIgnoredField(field) {
        if (!field || field.disabled) return true;
        if (['submit', 'button', 'reset', 'image', 'file'].includes(field.type)) return true;
        if (field.closest('template')) return true;
        return false;
    }

    function fieldIsEmpty(field) {
        if (!field) return true;
        if (field.type === 'checkbox' || field.type === 'radio') return !field.checked;
        return cleanText(field.value) === '';
    }

    function markInvalid(field, message) {
        if (!field) return;
        field.classList.add('is-invalid');
        field.setAttribute('aria-invalid', 'true');
        if (message) field.setAttribute('title', message);

        if (window.jQuery && jQuery(field).hasClass('select2-hidden-accessible')) {
            jQuery(field).next('.select2-container').addClass('astar-invalid');
        }
    }

    function clearInvalid(field) {
        if (!field) return;
        field.classList.remove('is-invalid');
        field.removeAttribute('aria-invalid');
        field.removeAttribute('title');

        if (window.jQuery && jQuery(field).hasClass('select2-hidden-accessible')) {
            jQuery(field).next('.select2-container').removeClass('astar-invalid');
        }
    }

    function addError(errors, field, message) {
        if (!field || !message) return;
        const exists = errors.some(function (entry) { return entry.field === field; });
        if (!exists) errors.push({ field: field, message: message, label: getLabel(field) });
    }

    function categoryValue(form) {
        const category = form.querySelector('[name="kategori_pasien"], [name="kategori"]');
        return category ? cleanText(category.value) : '';
    }

    function normalizeNationalPhone(value) {
        let digits = String(value || '').replace(/\D/g, '');
        digits = digits.replace(/^62/, '').replace(/^0+/, '');
        return digits;
    }

    function nativeValidationMessage(field) {
        const label = getLabel(field);
        const validity = field.validity;
        if (!validity) return '';

        if (validity.valueMissing) return label + ' wajib diisi. Silakan lengkapi kolom ini.';
        if (validity.typeMismatch && field.type === 'email') return 'Format email belum benar. Gunakan format seperti nama@email.com.';
        if (validity.tooShort) return label + ' terlalu pendek. Isi minimal ' + field.minLength + ' karakter.';
        if (validity.tooLong) return label + ' terlalu panjang. Kurangi hingga maksimal ' + field.maxLength + ' karakter.';
        if (validity.rangeUnderflow) return label + ' minimal ' + field.min + '.';
        if (validity.rangeOverflow) return label + ' maksimal ' + field.max + '.';
        if (validity.stepMismatch) return label + ' harus diisi dengan angka yang sesuai.';
        if (validity.patternMismatch) return label + ' belum sesuai dengan format yang diminta.';
        if (validity.badInput) return label + ' harus diisi dengan nilai yang benar.';
        return field.validationMessage || '';
    }

    function validateField(field, form, errors) {
        if (isIgnoredField(field)) return;

        const name = baseName(field);
        const value = cleanText(field.value);
        const label = getLabel(field);

        if (field.required && fieldIsEmpty(field)) {
            addError(errors, field, label + ' wajib diisi. Silakan lengkapi kolom ini.');
            return;
        }

        if (value === '' && !field.required) return;

        if (name === 'username') {
            const isInstitutionAccount = /^[^\s@]+@polytechnic\.astar\.ac\.id$/i.test(value);
            const isRegularUsername = /^[A-Za-z0-9._-]+$/.test(value);
            if (textLength(value) < 3) {
                addError(errors, field, 'Username terlalu pendek. Tambahkan hingga minimal 3 karakter.');
                return;
            }
            if (textLength(value) > 100) {
                addError(errors, field, 'Username terlalu panjang. Kurangi hingga maksimal 100 karakter.');
                return;
            }
            if (!isRegularUsername && !isInstitutionAccount) {
                addError(errors, field, 'Username hanya boleh memakai huruf, angka, titik, garis bawah, atau tanda minus. Akun institusi boleh memakai format email ASTAR.');
                return;
            }
        }

        if (name === 'email') {
            if (textLength(value) > 100) {
                addError(errors, field, 'Email terlalu panjang. Kurangi hingga maksimal 100 karakter.');
                return;
            }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(value)) {
                addError(errors, field, 'Format email belum benar. Gunakan format seperti nama@email.com.');
                return;
            }
        }

        if (name === 'password') {
            if (String(field.value || '').length < 8) {
                addError(errors, field, 'Kata sandi terlalu pendek. Tambahkan hingga minimal 8 karakter.');
                return;
            }
            if (String(field.value || '').length > 72) {
                addError(errors, field, 'Kata sandi terlalu panjang. Kurangi hingga maksimal 72 karakter.');
                return;
            }
        }

        if (PERSON_NAME_FIELDS.has(name)) {
            if (textLength(value) < 3) {
                addError(errors, field, label + ' terlalu pendek. Masukkan minimal 3 huruf.');
                return;
            }
            if (textLength(value) > 100) {
                addError(errors, field, label + ' terlalu panjang. Kurangi hingga maksimal 100 karakter.');
                return;
            }
            if (!/^[\p{L} .,'-]+$/u.test(value)) {
                addError(errors, field, label + ' mengandung angka atau simbol yang tidak diperbolehkan. Gunakan huruf dan tanda baca nama yang wajar.');
                return;
            }
        }

        if (IDENTITY_FIELDS.has(name)) {
            const digits = value.replace(/\D/g, '');
            const category = categoryValue(form);
            if (!/^\d+$/.test(value)) {
                addError(errors, field, label + ' hanya boleh berisi angka. Hapus huruf, spasi, titik, garis, atau simbol lainnya.');
                return;
            }
            if (category === 'Tamu' && digits.length !== 16) {
                addError(errors, field, 'NIK untuk kategori Tamu harus tepat 16 angka.');
                return;
            }
            if (category !== 'Tamu' && (digits.length < 3 || digits.length > 30)) {
                addError(errors, field, label + ' harus berisi minimal 3 dan maksimal 30 angka.');
                return;
            }
        }

        if (PHONE_FIELDS.has(name)) {
            const phone = normalizeNationalPhone(value);
            if (!/^8\d{8,12}$/.test(phone)) {
                addError(errors, field, 'Nomor telepon belum benar. Setelah +62, nomor harus dimulai angka 8 dan berisi 9–13 angka.');
                return;
            }
        }

        if (field.type === 'number' && value !== '') {
            const numberValue = Number(field.value);
            if (!Number.isFinite(numberValue)) {
                addError(errors, field, label + ' harus berupa angka yang benar.');
                return;
            }
            if (field.min !== '' && numberValue < Number(field.min)) {
                addError(errors, field, label + ' minimal ' + field.min + '.');
                return;
            }
            if (field.max !== '' && numberValue > Number(field.max)) {
                addError(errors, field, label + ' tidak boleh melebihi ' + field.max + '.');
                return;
            }
        }

        if (!field.checkValidity()) {
            addError(errors, field, nativeValidationMessage(field));
        }
    }

    function validateDateRange(form, errors) {
        let start = null;
        DATE_START_NAMES.some(function (name) {
            start = form.querySelector('[name="' + name + '"]');
            return Boolean(start);
        });

        let end = null;
        DATE_END_NAMES.some(function (name) {
            end = form.querySelector('[name="' + name + '"]');
            return Boolean(end);
        });

        if (start && end && start.value && end.value && start.value > end.value) {
            addError(errors, start, 'Tanggal awal tidak boleh lebih besar dari tanggal akhir.');
            addError(errors, end, 'Tanggal akhir harus sama dengan atau setelah tanggal awal.');
        }
    }

    function validateTimeRange(form, errors) {
        const start = form.querySelector('[name="jam_mulai"]');
        const end = form.querySelector('[name="jam_selesai"]');
        if (start && end && start.value && end.value && start.value >= end.value) {
            addError(errors, start, 'Jam mulai harus lebih kecil dari jam selesai.');
            addError(errors, end, 'Jam selesai harus lebih besar dari jam mulai.');
        }
    }

    function validateStockRelation(form, errors) {
        const current = form.querySelector('[name="stok_sekarang"]');
        const minimum = form.querySelector('[name="stok_minimum"]');
        const target = form.querySelector('[name="stok_target"]');

        if (minimum && target && minimum.value !== '' && target.value !== '' && Number(target.value) < Number(minimum.value)) {
            addError(errors, target, 'Target stok tidak boleh lebih kecil dari stok minimum.');
        }

        if (current && target && current.value !== '' && target.value !== '' && Number(target.value) < 0) {
            addError(errors, target, 'Target stok tidak boleh kurang dari 0.');
        }
    }

    function validatePrescriptionRows(form, errors) {
        if (form.id !== 'formTambahResepObat' && !form.classList.contains('pemeriksaan-form')) return;

        if (form.id === 'formTambahResepObat') {
            const diagnoses = Array.from(form.querySelectorAll('select[name="id_diagnosa[]"]'));
            if (diagnoses.length && !diagnoses.some(function (field) { return cleanText(field.value) !== ''; })) {
                addError(errors, diagnoses[0], 'Pilih minimal satu penyakit atau keluhan.');
            }
        }

        const rows = Array.from(form.querySelectorAll('.resep-obat-langsung-row, .obat-pemeriksaan-row'));
        if (!rows.length) return;

        let selectedCount = 0;
        rows.forEach(function (row) {
            const medicine = row.querySelector('select[name="id_obat[]"]');
            const quantity = row.querySelector('input[name="jumlah_keluar[]"]');
            const note = row.querySelector('textarea[name="catatan_obat[]"]');
            const selected = medicine && cleanText(medicine.value) !== '';

            if (!selected) return;
            selectedCount += 1;

            const amount = quantity ? Number(quantity.value) : 0;
            if (!quantity || !Number.isFinite(amount) || amount < 1) {
                addError(errors, quantity || medicine, 'Jumlah obat yang dipilih minimal 1.');
            }

            const option = medicine.options ? medicine.options[medicine.selectedIndex] : null;
            const stock = option && option.dataset ? Number(option.dataset.stock) : NaN;
            if (quantity && Number.isFinite(stock) && amount > stock) {
                addError(errors, quantity, 'Jumlah obat tidak boleh melebihi stok tersedia (' + stock + ').');
            }

            if (form.id === 'formTambahResepObat' && note && cleanText(note.value) === '') {
                addError(errors, note, 'Aturan pakai wajib diisi untuk setiap obat yang dipilih.');
            }
        });

        if (form.id === 'formTambahResepObat' && selectedCount === 0) {
            const firstMedicine = form.querySelector('select[name="id_obat[]"]');
            if (firstMedicine) addError(errors, firstMedicine, 'Pilih minimal satu obat.');
        }
    }

    function validateForm(form) {
        const errors = [];
        const fields = Array.from(form.querySelectorAll('input, select, textarea'));
        fields.forEach(function (field) { validateField(field, form, errors); });
        validateDateRange(form, errors);
        validateTimeRange(form, errors);
        validateStockRelation(form, errors);
        validatePrescriptionRows(form, errors);
        return errors;
    }

    function focusField(field) {
        if (!field) return;
        field.scrollIntoView({ behavior: 'smooth', block: 'center' });
        window.setTimeout(function () {
            if (window.jQuery && jQuery(field).hasClass('select2-hidden-accessible')) {
                jQuery(field).select2('open');
            } else if (typeof field.focus === 'function' && field.type !== 'hidden') {
                field.focus({ preventScroll: true });
            }
        }, 120);
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function showErrors(errors) {
        if (!errors.length) return;
        errors.forEach(function (entry) { markInvalid(entry.field, entry.message); });

        const first = errors[0];
        const messages = Array.from(new Set(errors.map(function (entry) {
            return entry.message;
        })));
        const single = messages.length === 1;
        const listHtml = '<div style="text-align:left"><ul style="margin:0;padding-left:1.25rem">' +
            messages.map(function (message) {
                return '<li style="margin:.3rem 0">' + escapeHtml(message) + '</li>';
            }).join('') +
            '</ul></div>';
        const options = {
            icon: 'warning',
            title: single ? 'Periksa ' + first.label : 'Periksa Data',
            html: listHtml,
            confirmButtonText: 'Perbaiki',
            confirmButtonColor: '#175cdd',
            allowOutsideClick: true,
            allowEscapeKey: true
        };

        if (window.Swal) {
            Swal.fire(options).then(function () { focusField(first.field); });
        } else {
            window.alert(messages.join('\n'));
            focusField(first.field);
        }
    }

    function shouldSkipForm(form) {
        if (!form) return true;
        if (form.matches('.js-swal-confirm, [data-no-astar-validation="true"]')) return true;
        const submitter = form.__astarSubmitter;
        if (submitter && /hapus|delete|batal/i.test(String(submitter.name || '') + ' ' + String(submitter.value || ''))) return true;
        return false;
    }

    document.addEventListener('click', function (event) {
        const submitter = event.target.closest('button[type="submit"], input[type="submit"]');
        if (submitter && submitter.form) submitter.form.__astarSubmitter = submitter;
    }, true);

    document.addEventListener('input', function (event) {
        const field = event.target.closest('input, textarea');
        if (field) clearInvalid(field);
    }, true);

    document.addEventListener('change', function (event) {
        const field = event.target.closest('select, input, textarea');
        if (field) clearInvalid(field);
    }, true);

    document.addEventListener('submit', function (event) {
        const form = event.target.closest('form');
        if (!form || shouldSkipForm(form)) return;

        form.noValidate = true;
        form.querySelectorAll('.is-invalid').forEach(clearInvalid);

        const errors = validateForm(form);
        if (!errors.length) return;

        event.preventDefault();
        event.stopImmediatePropagation();
        showErrors(errors);
    }, true);

    function initialize() {
        document.querySelectorAll('form').forEach(function (form) {
            if (!form.matches('[data-native-validation="true"]')) form.noValidate = true;
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        initialize();
    }

    window.ASTARValidation = {
        validateForm: validateForm,
        markInvalid: markInvalid,
        clearInvalid: clearInvalid
    };
})();
