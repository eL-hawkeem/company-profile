        </div> <!-- End .container-fluid dari .main-content -->
    </div> <!-- End .main-content -->
</div>
</div>
<footer class="footer mt-auto py-3" style="margin-left: 260px; transition: all 0.3s ease; position: relative; z-index: 1;">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-md-6">
                <span class="text-muted">
                    © <?= date('Y') ?> PT. Sarana Sentra Teknologi Utama. All rights reserved.
                </span>
            </div>
            <div class="col-md-6 text-md-end">
                <span class="text-muted">
                    Version 1.0.0 |
                    <a href="#" class="text-decoration-none">Documentation</a> |
                    <a href="#" class="text-decoration-none">Support</a>
                </span>
            </div>
        </div>
    </div>
</footer>

<div id="loadingOverlay" class="position-fixed top-0 start-0 w-100 h-100 d-none"
    style="background: rgba(255,255,255,0.8); z-index: 9999;">
    <div class="d-flex justify-content-center align-items-center h-100">
        <div class="text-center">
            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 mb-0">Memuat...</p>
        </div>
    </div>
</div>

<button class="btn btn-primary position-fixed bottom-0 end-0 m-4 rounded-circle d-none"
    id="scrollToTop" style="width: 50px; height: 50px; z-index: 1000;">
    <i class="bi bi-arrow-up"></i>
</button>

<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.1/dist/sweetalert2.all.min.js"></script>
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

<script>
    /**
     * Main Admin Dashboard JavaScript (jQuery-based)
     * PT. Sarana Sentra Teknologi Utama
     */
    class AdminDashboard {
        constructor() {
            this.init();
            this.bindEvents();
            this.initComponents();
        }

        init() {
            $.ajaxSetup({
                beforeSend: function(xhr, settings) {
                    if (!/^(GET|HEAD|OPTIONS|TRACE)$/i.test(settings.type) && !this.crossDomain) {
                        xhr.setRequestHeader("X-CSRFToken", $('meta[name=csrf-token]').attr('content'));
                    }
                }
            });

            $('[data-bs-toggle="tooltip"]').tooltip();
            $('[data-bs-toggle="popover"]').popover();

            setTimeout(() => {
                $('.alert:not(.alert-permanent)').fadeOut();
            }, 5000);
        }

        bindEvents() {
            $(window).scroll(this.handleScroll);
            $('#scrollToTop').on('click', this.scrollToTop);

            $('form').on('submit', function() {
                $(this).find('button[type="submit"]').prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...'
                );
            });
        }

        initComponents() {
            if ($.fn.DataTable) {
                $('.data-table').DataTable({
                    responsive: true,
                    pageLength: 25,
                    language: {
                        url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/id.json'
                    },
                    dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                        '<"row"<"col-sm-12"tr>>' +
                        '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                    drawCallback: function() {
                        $('[data-bs-toggle="tooltip"]').tooltip();
                    }
                });
            }

            if (typeof Quill !== 'undefined' && $('#editor').length) {
                this.initQuillEditor();
            }

            this.initFileUploadPreview();
            this.initFormValidation();
        }

        handleScroll() {
            const scrollButton = $('#scrollToTop');
            if ($(window).scrollTop() > 300) {
                scrollButton.removeClass('d-none');
            } else {
                scrollButton.addClass('d-none');
            }
        }

        scrollToTop() {
            $('html, body').animate({
                scrollTop: 0
            }, 600);
        }

        autoSaveDraft() {
            const form = $('#articleForm');
            if (form.length && form.find('#title').val().trim()) {
                const formData = new FormData(form[0]);
                formData.append('action', 'auto_save_draft');

                $.ajax({
                    url: 'api/auto-save-draft.php',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            AdminDashboard.showToast('success', 'Draft tersimpan otomatis', 2000);
                        }
                    }
                });
            }
        }

        initQuillEditor() {
            const quill = new Quill('#editor', {
                theme: 'snow',
                placeholder: 'Tulis konten artikel di sini...',
                modules: {
                    toolbar: [
                        [{
                            'header': [1, 2, 3, 4, 5, 6, false]
                        }],
                        ['bold', 'italic', 'underline', 'strike'],
                        ['blockquote', 'code-block'],
                        [{
                            'list': 'ordered'
                        }, {
                            'list': 'bullet'
                        }],
                        [{
                            'script': 'sub'
                        }, {
                            'script': 'super'
                        }],
                        [{
                            'indent': '-1'
                        }, {
                            'indent': '+1'
                        }],
                        [{
                            'direction': 'rtl'
                        }],
                        [{
                            'color': []
                        }, {
                            'background': []
                        }],
                        [{
                            'align': []
                        }],
                        ['link', 'image', 'video'],
                        ['clean']
                    ]
                }
            });

            quill.on('text-change', function() {
                $('#content').val(quill.root.innerHTML);
            });

            quill.getModule('toolbar').addHandler('image', function() {
                AdminDashboard.selectLocalImage(quill);
            });
        }

        selectLocalImage(quill) {
            const input = document.createElement('input');
            input.setAttribute('type', 'file');
            input.setAttribute('accept', 'image/*');
            input.click();

            input.onchange = function() {
                const file = input.files[0];
                if (file) {
                    AdminDashboard.uploadImageToServer(file, quill);
                }
            };
        }

        uploadImageToServer(file, quill) {
            const formData = new FormData();
            formData.append('image', file);
            AdminDashboard.showLoading();
            $.ajax({
                url: 'api/upload-image.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    AdminDashboard.hideLoading();
                    if (response.success) {
                        const range = quill.getSelection();
                        quill.insertEmbed(range.index, 'image', response.url);
                    } else {
                        AdminDashboard.showToast('error', response.message || 'Gagal mengupload gambar');
                    }
                },
                error: function() {
                    AdminDashboard.hideLoading();
                    AdminDashboard.showToast('error', 'Terjadi kesalahan saat mengupload gambar');
                }
            });
        }

        initFileUploadPreview() {
            $('.file-upload-input').on('change', function() {
                const file = this.files[0];
                const preview = $(this).siblings('.file-preview');

                if (file && file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.html(`<img src="${e.target.result}" class="img-thumbnail" style="max-width: 200px;">`);
                    };
                    reader.readAsDataURL(file);
                } else {
                    preview.html('<p class="text-muted">File dipilih: ' + (file ? file.name : 'Tidak ada file') + '</p>');
                }
            });
        }

        initFormValidation() {
            const forms = document.querySelectorAll('.needs-validation');
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                });
            });
        }

        static showLoading() {
            $('#loadingOverlay').removeClass('d-none');
        }

        static hideLoading() {
            $('#loadingOverlay').addClass('d-none');
        }

        static showToast(type, message, duration = 5000) {
            const toastContainer = $('.toast-container');
            const toastId = 'toast_' + Date.now();

            const bgClass = {
                'success': 'bg-success',
                'error': 'bg-danger',
                'warning': 'bg-warning',
                'info': 'bg-info'
            } [type] || 'bg-info';

            const toast = $(`
            <div class="toast ${bgClass} text-white" id="${toastId}" role="alert">
                <div class="toast-header ${bgClass} text-white border-0">
                    <strong class="me-auto">Notifikasi</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            </div>
        `);

            toastContainer.append(toast);

            const bsToast = new bootstrap.Toast(toast[0], {
                delay: duration
            });
            bsToast.show();

            toast[0].addEventListener('hidden.bs.toast', () => {
                toast.remove();
            });
        }

        static confirmAction(message, callback, title = 'Konfirmasi') {
            Swal.fire({
                title: title,
                text: message,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#6f42c1',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Lanjutkan',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed && typeof callback === 'function') {
                    callback();
                }
            });
        }

        static makeAjaxRequest(url, data, method = 'POST') {
            return $.ajax({
                url: url,
                method: method,
                data: data,
                dataType: 'json'
            });
        }
    }

    $(document).ready(function() {
        window.adminDashboard = new AdminDashboard();
    });

    $(document).on('click', '.dropdown-menu', function(e) {
        e.stopPropagation();
    });

    $(document).ajaxError(function(event, xhr, settings, thrownError) {
        if (xhr.status === 403) {
            AdminDashboard.showToast('error', 'Akses ditolak. Silakan login kembali.');
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 2000);
        } else if (xhr.status === 500) {
            AdminDashboard.showToast('error', 'Terjadi kesalahan server. Silakan coba lagi.');
        }
    });
</script>

<?php if (isset($additional_js)): ?>
    <?= $additional_js ?>
<?php endif; ?>

</body>

</html>