<?php

declare(strict_types=1);

require_once '../../config/database.php';
require_once '../../config/auth.php';
require_once '../../includes/functions.php';

// Check authentication
requireAuth();

$db = Database::getInstance()->getConnection();

function formatIndonesianDate($timestamp)
{
    return date('d M Y, H:i', strtotime($timestamp));
}

try {
    $query = "SELECT id, name, email, subject, message, submitted_at, status FROM contact_messages ORDER BY submitted_at DESC";
    $stmt = $db->query($query);
    $messages = $stmt->fetchAll();
} catch (\PDOException $e) {
    error_log("Error mengambil data pesan: " . $e->getMessage());
    $messages = [];
}

$pageTitle = "Messages Management";
include '../../includes/header.php';
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

    #messages-page {
        font-family: 'Inter', sans-serif;
        background-color: var(--bg-color);
        color: var(--text-color);
        margin: 0;
        line-height: 1.6;
    }

    #messages-page .dashboard-container {
        width: 100%;
        background-color: var(--panel-color);
        border-radius: 12px;
        box-shadow: var(--shadow);
        overflow: hidden;
    }

    #messages-page .controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 25px;
        border-bottom: 1px solid var(--border-color);
        flex-wrap: wrap;
        gap: 15px;
    }

    #messages-page .search-bar input {
        padding: 10px 15px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        width: 100%;
        max-width: 300px;
        font-size: 14px;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    #messages-page .search-bar input:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.2);
    }

    #messages-page .filters button {
        background-color: transparent;
        border: 1px solid var(--border-color);
        color: var(--text-color-light);
        padding: 8px 16px;
        margin-left: 8px;
        border-radius: 20px;
        cursor: pointer;
        font-weight: 500;
        font-size: 14px;
        transition: background-color 0.2s, color 0.2s;
    }

    #messages-page .filters button:hover {
        background-color: #e9ecef;
        color: var(--text-color);
    }

    #messages-page .filters button.active {
        background-color: var(--primary-color);
        color: #fff;
        border-color: var(--primary-color);
    }

    #messages-page .message-list {
        width: 100%;
        border-collapse: collapse;
    }

    #messages-page .message-list th,
    #messages-page .message-list td {
        padding: 15px 25px;
        text-align: left;
        border-bottom: 1px solid var(--border-color);
    }

    #messages-page .message-list th {
        font-size: 12px;
        text-transform: uppercase;
        color: var(--text-color-light);
        font-weight: 600;
    }

    #messages-page .message-list tr:last-child td {
        border-bottom: none;
    }

    #messages-page .message-list tr:hover {
        background-color: #f8f9fa;
    }

    #messages-page .message-list tr.unread .subject-link {
        font-weight: 600;
        color: var(--text-color);
    }

    #messages-page .sender-info div {
        font-size: 15px;
    }

    #messages-page .sender-info small {
        font-size: 13px;
        color: var(--text-color-light);
    }

    #messages-page .subject-link {
        cursor: pointer;
        color: var(--primary-color);
        text-decoration: none;
    }

    #messages-page .subject-link:hover {
        text-decoration: underline;
    }

    #messages-page .status-badge {
        padding: 4px 12px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: 600;
        text-transform: capitalize;
    }

    #messages-page .status-badge.unread {
        background-color: rgba(253, 126, 20, 0.15);
        color: var(--orange-color);
    }

    #messages-page .status-badge.read {
        background-color: rgba(40, 167, 69, 0.15);
        color: var(--green-color);
    }

    #messages-page .status-badge.replied {
        background-color: rgba(23, 162, 184, 0.15);
        color: var(--blue-color);
    }

    #messages-page .status-badge.archived {
        background-color: rgba(108, 117, 125, 0.15);
        color: var(--gray-color);
    }

    #messages-page .action-menu-container {
        position: relative;
    }

    #messages-page .action-menu-toggle {
        background: #f1f3f5;
        border: 1px solid var(--border-color);
        border-radius: 6px;
        padding: 5px 10px;
        cursor: pointer;
    }

    #messages-page .action-menu-dropdown {
        display: none;
        position: absolute;
        right: 0;
        top: 100%;
        margin-top: 5px;
        background-color: white;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        z-index: 10;
        min-width: 180px;
        padding: 5px 0;
    }

    #messages-page .action-menu-dropdown.show {
        display: block;
    }

    #messages-page .action-menu-item {
        display: block;
        padding: 10px 15px;
        color: var(--text-color);
        text-decoration: none;
        font-size: 14px;
    }

    #messages-page .action-menu-item:hover {
        background-color: #f8f9fa;
    }

    #messages-page .action-menu-dropdown.dropup {
        top: auto;
        bottom: 100%;
        margin-top: 0;
        margin-bottom: 5px;
    }

    /* Gmail reply button styling */
    #messages-page .gmail-reply-btn {
        background-color: #ea4335;
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
    }

    #messages-page .gmail-reply-btn:hover {
        background-color: #d73527;
        color: white;
        text-decoration: none;
        transform: translateY(-1px);
    }

    #messages-page .gmail-reply-btn i {
        font-size: 13px;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        #messages-page .controls {
            flex-direction: column;
            align-items: stretch;
        }

        #messages-page .search-bar {
            margin-bottom: 15px;
        }

        #messages-page .search-bar input {
            max-width: none;
        }

        #messages-page .filters {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 5px;
        }

        #messages-page .filters button {
            margin-left: 0;
            margin-bottom: 5px;
        }

        #messages-page .message-list th,
        #messages-page .message-list td {
            padding: 10px 15px;
        }

        #messages-page .message-list {
            font-size: 14px;
        }

        #messages-page .sender-info div {
            font-size: 14px;
        }

        #messages-page .sender-info small {
            font-size: 12px;
        }

        #messages-page .status-badge {
            font-size: 11px;
            padding: 3px 8px;
        }
    }

    @media (max-width: 576px) {
        #messages-page .message-list {
            display: block;
            overflow-x: auto;
        }

        #messages-page .message-list thead {
            display: none;
        }

        #messages-page .message-list tbody {
            display: block;
        }

        #messages-page .message-list tr {
            display: block;
            margin-bottom: 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 15px;
            background-color: white;
        }

        #messages-page .message-list td {
            display: block;
            padding: 5px 0;
            border: none;
            text-align: left;
        }

        #messages-page .message-list td:before {
            content: attr(data-label) ": ";
            font-weight: 600;
            color: var(--text-color-light);
        }

        #messages-page .action-menu-container {
            text-align: center;
            margin-top: 10px;
        }

        #messages-page .action-menu-dropdown {
            position: static;
            margin-top: 10px;
            box-shadow: none;
            border: 1px solid var(--border-color);
        }

        #messages-page .controls {
            padding: 15px;
        }

        #messages-page .search-bar input {
            font-size: 16px;
            /* Prevent zoom on iOS */
        }

        #messages-page .filters button {
            font-size: 14px;
            padding: 8px 12px;
        }
    }

    /* Additional responsive improvements */
    @media (max-width: 480px) {
        #messages-page .card-body {
            padding: 10px;
        }

        #messages-page .controls {
            padding: 10px;
        }

        #messages-page .message-list tr {
            padding: 10px;
            margin-bottom: 10px;
        }

        #messages-page .sender-info div {
            font-size: 13px;
        }

        #messages-page .sender-info small {
            font-size: 11px;
        }

        #messages-page .subject-link {
            font-size: 13px;
        }
    }

    /* table responsiveness */
    #messages-page .table-responsive {
        border-radius: 0;
    }

    /* Better mobile experience */
    @media (max-width: 576px) {
        #messages-page .card {
            margin: 0;
            border-radius: 0;
        }

        #messages-page .card-header {
            border-radius: 0;
        }

        #messages-page .controls {
            border-radius: 0;
        }
    }
</style>

<div id="messages-page">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-envelope"></i> Messages Management
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../../index.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Messages</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Messages Container -->
    <div class="card shadow">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold text-primary">Messages List</h6>
        </div>
        <div class="card-body p-0">
            <!-- Controls -->
            <div class="controls">
                <div class="search-bar">
                    <input type="search" id="searchInput" placeholder="Cari berdasarkan pengirim atau subjek...">
                </div>
                <div class="filters" id="filterContainer">
                    <button class="active" data-filter="all">Semua</button>
                    <button data-filter="unread">Belum Dibaca</button>
                    <button data-filter="read">Sudah Dibaca</button>
                    <button data-filter="replied">Dibalas</button>
                    <button data-filter="archived">Diarsipkan</button>
                </div>
            </div>

            <!-- Messages Table -->
            <div class="table-responsive">
                <table class="table table-hover mb-0 message-list">
                    <thead class="table-light">
                        <tr>
                            <th>Pengirim</th>
                            <th>Subjek</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="message-list-body">
                        <?php if (count($messages) > 0): ?>
                            <?php foreach ($messages as $msg): ?>
                                <tr class="message-row"
                                    data-id="<?= $msg['id'] ?>"
                                    data-status="<?= htmlspecialchars($msg['status']) ?>"
                                    data-search-term="<?= htmlspecialchars(strtolower($msg['name'] . ' ' . $msg['subject'])) ?>"
                                    data-sender-name="<?= htmlspecialchars($msg['name']) ?>"
                                    data-sender-email="<?= htmlspecialchars($msg['email']) ?>"
                                    data-message-subject="<?= htmlspecialchars($msg['subject']) ?>"
                                    data-message-content="<?= htmlspecialchars($msg['message']) ?>"
                                    data-message-date="<?= formatIndonesianDate($msg['submitted_at']) ?>">
                                    <td data-label="Pengirim">
                                        <div class="sender-info">
                                            <div><?= htmlspecialchars($msg['name']) ?></div>
                                            <small><?= htmlspecialchars($msg['email']) ?></small>
                                        </div>
                                    </td>
                                    <td data-label="Subjek">
                                        <a href="#" class="subject-link"
                                            data-subject="<?= htmlspecialchars($msg['subject']) ?>"
                                            data-message="<?= htmlspecialchars($msg['message']) ?>">
                                            <?= htmlspecialchars($msg['subject']) ?>
                                        </a>
                                    </td>
                                    <td data-label="Tanggal"><?= formatIndonesianDate($msg['submitted_at']) ?></td>
                                    <td data-label="Status">
                                        <span class="status-badge <?= htmlspecialchars($msg['status']) ?>"><?= htmlspecialchars($msg['status']) ?></span>
                                    </td>
                                    <td data-label="Aksi">
                                        <div class="action-menu-container">
                                            <button class="btn btn-sm btn-outline-secondary action-menu-toggle">Aksi ▾</button>
                                            <div class="action-menu-dropdown">
                                                <a href="#" class="action-menu-item gmail-reply-link" data-action="gmail_reply">
                                                    <i class="fab fa-google"></i> Balas via Gmail
                                                </a>

                                                <?php if ($msg['status'] == 'unread'): ?>
                                                    <a href="#" class="action-menu-item" data-action="mark_read">Tandai Sudah Dibaca</a>
                                                <?php endif; ?>

                                                <?php if ($msg['status'] == 'read'): ?>
                                                    <a href="#" class="action-menu-item" data-action="mark_replied">Tandai Sudah Dibalas</a>
                                                    <a href="#" class="action-menu-item" data-action="mark_unread">Tandai Belum Dibaca</a>
                                                <?php endif; ?>

                                                <?php if ($msg['status'] == 'replied'): ?>
                                                    <a href="#" class="action-menu-item" data-action="mark_read">Kembalikan ke 'Dibaca'</a>
                                                <?php endif; ?>

                                                <?php if ($msg['status'] != 'archived'): ?>
                                                    <a href="#" class="action-menu-item" data-action="archive">Arsipkan</a>
                                                <?php else: ?>
                                                    <a href="#" class="action-menu-item" data-action="unarchive">Keluarkan dari Arsip</a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <h5>Tidak ada pesan</h5>
                                    <p class="text-muted">Belum ada pesan masuk untuk ditampilkan.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Preview Modal -->
    <div class="modal fade" id="previewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="previewModalSubject"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <strong>From:</strong> <span id="previewModalSender"></span><br>
                        <strong>Date:</strong> <span id="previewModalDate"></span>
                    </div>
                    <div class="border-top pt-3">
                        <p id="previewModalMessage" class="mb-0"></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="#" class="gmail-reply-btn" id="modalReplyGmailBtn">
                        <i class="fab fa-google"></i> Reply via Gmail
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const searchInput = document.getElementById('searchInput');
        const filterContainer = document.getElementById('filterContainer');
        const messageListBody = document.getElementById('message-list-body');
        const previewModal = document.getElementById('previewModal');
        const previewModalSubject = document.getElementById('previewModalSubject');
        const previewModalMessage = document.getElementById('previewModalMessage');
        const previewModalSender = document.getElementById('previewModalSender');
        const previewModalDate = document.getElementById('previewModalDate');
        const modalReplyGmailBtn = document.getElementById('modalReplyGmailBtn');

        let currentFilter = 'all';

        // --- GMAIL COMPOSE URL GENERATOR ---
        function generateGmailComposeUrl(to, subject, body) {
            const baseUrl = 'https://mail.google.com/mail/';
            const params = new URLSearchParams({
                view: 'cm',
                fs: '1',
                tf: '1',
                to: to,
                subject: subject,
                body: body
            });

            return baseUrl + '?' + params.toString();
        }

        // --- CREATE REPLY TEMPLATE ---
        function createReplyTemplate(senderName, senderEmail, originalSubject, originalMessage, originalDate) {
            const companyName = "PT. Sarana Sentra Teknologi Utama";

            return `Halo ${senderName},

Terima kasih telah menghubungi ${companyName}. Kami telah menerima pesan Anda dan akan merespons secepatnya.



---
Best Regards,
${companyName}
Customer Service Team


--- Pesan Asli ---
Dari: ${senderName} <${senderEmail}>
Tanggal: ${originalDate}
Subjek: ${originalSubject}

${originalMessage}`;
        }

        // --- HANDLE GMAIL REPLY CLICK ---
        function handleGmailReply(row) {
            const senderName = row.dataset.senderName;
            const senderEmail = row.dataset.senderEmail;
            const originalSubject = row.dataset.messageSubject;
            const originalMessage = row.dataset.messageContent;
            const originalDate = row.dataset.messageDate;

            const replySubject = `Re: ${originalSubject}`;
            const replyBody = createReplyTemplate(senderName, senderEmail, originalSubject, originalMessage, originalDate);

            const gmailUrl = generateGmailComposeUrl(senderEmail, replySubject, replyBody);

            // Open Gmail in new tab
            window.open(gmailUrl, '_blank');

            // Show notification
            showNotification('Membuka Gmail untuk membalas pesan...', 'success');
        }

        // --- LOGIKA FILTER DAN PENCARIAN ---
        const applyFiltersAndSearch = () => {
            const searchTerm = searchInput.value.toLowerCase();
            messageListBody.querySelectorAll('.message-row').forEach(row => {
                const status = row.dataset.status;
                const searchTermData = row.dataset.searchTerm;

                const isFilterMatch = currentFilter === 'all' || status === currentFilter;
                const isSearchMatch = searchTermData.includes(searchTerm);

                row.style.display = (isFilterMatch && isSearchMatch) ? '' : 'none';
            });
        };

        searchInput.addEventListener('input', applyFiltersAndSearch);

        filterContainer.addEventListener('click', (e) => {
            if (e.target.tagName === 'BUTTON') {
                filterContainer.querySelector('.active').classList.remove('active');
                e.target.classList.add('active');
                currentFilter = e.target.dataset.filter;
                applyFiltersAndSearch();
            }
        });

        // --- LOGIKA MODAL PRATINJAU ---
        messageListBody.addEventListener('click', (e) => {
            if (e.target.classList.contains('subject-link')) {
                e.preventDefault();

                const row = e.target.closest('.message-row');
                const senderName = row.dataset.senderName;
                const senderEmail = row.dataset.senderEmail;
                const originalSubject = row.dataset.messageSubject;
                const originalMessage = row.dataset.messageContent;
                const originalDate = row.dataset.messageDate;

                previewModalSubject.textContent = originalSubject;
                previewModalMessage.textContent = originalMessage;
                previewModalSender.textContent = `${senderName} (${senderEmail})`;
                previewModalDate.textContent = originalDate;

                // Set Gmail reply link
                const replySubject = `Re: ${originalSubject}`;
                const replyBody = createReplyTemplate(senderName, senderEmail, originalSubject, originalMessage, originalDate);
                const gmailUrl = generateGmailComposeUrl(senderEmail, replySubject, replyBody);

                modalReplyGmailBtn.href = gmailUrl;
                modalReplyGmailBtn.target = '_blank';

                // Show modal
                const modal = new bootstrap.Modal(previewModal);
                modal.show();
            }
        });

        // --- LOGIKA DROPDOWN AKSI ---
        document.addEventListener('click', (e) => {
            const isDropdownButton = e.target.matches('.action-menu-toggle');

            document.querySelectorAll('.action-menu-dropdown.show').forEach(dropdown => {
                if (!dropdown.parentElement.contains(e.target)) {
                    dropdown.classList.remove('show');
                }
            });

            // Logika utama saat tombol "Aksi" diklik
            if (isDropdownButton) {
                const dropdown = e.target.nextElementSibling;

                const isOpening = !dropdown.classList.contains('show');

                dropdown.classList.toggle('show');

                // === LOGIKA DROPDOWN FLEKSIBEL ===
                if (isOpening) {
                    dropdown.classList.remove('dropup');

                    const dropdownRect = dropdown.getBoundingClientRect();

                    const viewportHeight = window.innerHeight;

                    if (dropdownRect.bottom > viewportHeight) {
                        dropdown.classList.add('dropup');
                    }
                }
            }
        });

        // --- LOGIKA UTAMA UNTUK SEMUA AKSI ---
        messageListBody.addEventListener('click', async (e) => {
            if (e.target.classList.contains('action-menu-item')) {
                const action = e.target.dataset.action;

                // Handle Gmail reply
                if (action === 'gmail_reply') {
                    e.preventDefault();
                    const row = e.target.closest('.message-row');
                    handleGmailReply(row);

                    // Close dropdown
                    const dropdown = e.target.closest('.action-menu-dropdown');
                    if (dropdown) dropdown.classList.remove('show');

                    return;
                }

                e.preventDefault();

                const dropdown = e.target.closest('.action-menu-dropdown');
                if (dropdown) dropdown.classList.remove('show');

                const row = e.target.closest('.message-row');
                const messageId = row.dataset.id;

                const success = await updateStatusOnServer(messageId, action);

                if (success) {
                    location.reload();
                }
            }
        });

        // --- HANDLE GMAIL BUTTON CLICK IN MODAL ---
        modalReplyGmailBtn.addEventListener('click', () => {
            showNotification('Membuka Gmail untuk membalas pesan...', 'success');
        });

        // --- FUNGSI HELPER UNTUK MEMANGGIL API ---
        async function updateStatusOnServer(messageId, action) {
            const formData = new FormData();
            formData.append('id', messageId);
            formData.append('action', action);

            try {
                const response = await fetch('process/api_update_pesan.php', {
                    method: 'POST',
                    body: formData
                });
                if (!response.ok) {
                    const errText = await response.text();
                    throw new Error(`Server error: ${response.status} ${response.statusText} - ${errText.substring(0, 200)}`);
                }

                const contentType = response.headers.get('content-type') || '';
                let result;
                if (contentType.includes('application/json')) {
                    result = await response.json();
                } else {
                    const text = await response.text();
                    throw new Error(`Response bukan JSON: ${text.substring(0, 200)}`);
                }
                if (result.status !== 'success') {
                    showNotification(`Terjadi kesalahan: ${result.message}`, 'error');
                    return false;
                }

                showNotification('Status berhasil diperbarui', 'success');
                return true;
            } catch (error) {
                console.error('Fetch error:', error);
                showNotification('Gagal menghubungi server. Periksa koneksi atau coba lagi.', 'error');
                return false;
            }
        }

        // --- FUNGSI NOTIFIKASI ---
        function showNotification(message, type = 'info') {
            const toastContainer = document.querySelector('.toast-container');
            if (!toastContainer) return;

            const toastId = 'toast_' + Date.now();
            const bgClass = {
                'success': 'bg-success',
                'error': 'bg-danger',
                'warning': 'bg-warning',
                'info': 'bg-info'
            } [type] || 'bg-info';

            const toast = document.createElement('div');
            toast.className = `toast ${bgClass} text-white`;
            toast.id = toastId;
            toast.innerHTML = `
                <div class="toast-header ${bgClass} text-white border-0">
                    <strong class="me-auto">Messages</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            `;

            toastContainer.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast, {
                delay: 5000
            });
            bsToast.show();

            toast.addEventListener('hidden.bs.toast', () => {
                toast.remove();
            });
        }

        // --- RESPONSIVE HANDLING ---
        function handleResponsiveLayout() {
            const isMobile = window.innerWidth <= 576;
            const isTablet = window.innerWidth <= 768;
            if (isMobile) {
                document.querySelectorAll('.action-menu-dropdown').forEach(dropdown => {
                    dropdown.classList.remove('dropup');
                });
            }
        }

        handleResponsiveLayout();

        window.addEventListener('resize', handleResponsiveLayout);
    });
</script>

<?php include '../../includes/footer.php'; ?>