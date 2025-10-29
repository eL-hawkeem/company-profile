<?php

declare(strict_types=1);

require_once '../../config/database.php';
require_once '../../config/auth.php';
require_once '../../includes/functions.php';

requireAuth();

$db = Database::getInstance()->getConnection();

// --- Get data comments ---
try {
    $query = "
        SELECT c.id,
               c.author_name,
               c.author_email,
               c.content,
               c.status,
               c.created_at,
               a.title AS article_title
        FROM comments c
        LEFT JOIN articles a ON c.article_id = a.id
        ORDER BY c.created_at DESC
    ";
    $stmt = $db->query($query);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error mengambil data komentar: " . $e->getMessage());
    $comments = [];
}

$pageTitle = "Kelola Komentar";
include '../../includes/header.php';
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

    #comments-page {
        font-family: 'Inter', sans-serif;
        background-color: var(--bg-color);
        color: var(--text-color);
        margin: 0;
        line-height: 1.6;
    }

    #comments-page .dashboard-container {
        width: 100%;
        background-color: var(--panel-color);
        border-radius: 12px;
        box-shadow: var(--shadow);
        overflow: hidden;
    }

    #comments-page .controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 25px;
        border-bottom: 1px solid var(--border-color);
        flex-wrap: wrap;
        gap: 15px;
    }

    #comments-page .search-bar input {
        padding: 10px 15px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        width: 100%;
        max-width: 300px;
        font-size: 14px;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    #comments-page .search-bar input:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.2);
    }

    #comments-page .filters button {
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

    #comments-page .filters button:hover {
        background-color: #e9ecef;
        color: var(--text-color);
    }

    #comments-page .filters button.active {
        background-color: var(--primary-color);
        color: #fff;
        border-color: var(--primary-color);
    }

    #comments-page .comment-list {
        width: 100%;
        min-width: 830px;
        border-collapse: collapse;
        table-layout: fixed;
        margin: 0;
    }

    #comments-page .comment-list th,
    #comments-page .comment-list td {
        padding: 15px 25px;
        text-align: left;
        border-bottom: 1px solid var(--border-color);
    }

    #comments-page .comment-list th {
        font-size: 12px;
        text-transform: uppercase;
        color: var(--text-color-light);
        font-weight: 600;
        background-color: #f8f9fa;
    }

    #comments-page .comment-list tr:last-child td {
        border-bottom: none;
    }

    #comments-page .comment-list tr:hover {
        background-color: #f8f9fa;
    }

    /* === CONTENT STYLES === */
    #comments-page .author-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    #comments-page .author-name {
        font-weight: 600;
        color: var(--text-color);
        font-size: 15px;
    }

    #comments-page .author-email {
        font-size: 13px;
        color: var(--text-color-light);
    }

    #comments-page .comment-content {
        max-height: 80px;
        overflow: hidden;
        line-height: 1.4;
        position: relative;
    }

    #comments-page .comment-text {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
        word-break: break-word;
        cursor: pointer;
        color: var(--primary-color);
        text-decoration: none;
    }

    #comments-page .comment-text:hover {
        text-decoration: underline;
    }

    #comments-page .article-title {
        font-weight: 500;
        color: var(--text-color);
        line-height: 1.3;
        word-break: break-word;
        max-height: 40px;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }

    #comments-page .date-text {
        font-size: 13px;
        color: var(--text-color-light);
        white-space: nowrap;
    }

    /* === STATUS BADGES === */
    #comments-page .status-badge {
        padding: 4px 12px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: 600;
        text-transform: capitalize;
    }

    #comments-page .status-badge.pending {
        background-color: rgba(253, 126, 20, 0.15);
        color: #fd7e14;
    }

    #comments-page .status-badge.approved {
        background-color: rgba(40, 167, 69, 0.15);
        color: #28a745;
    }

    #comments-page .status-badge.spam {
        background-color: rgba(220, 53, 69, 0.15);
        color: #dc3545;
    }

    /* === ACTION MENU === */
    #comments-page .action-menu-container {
        position: relative;
    }

    #comments-page .action-menu-toggle {
        background: #f1f3f5;
        border: 1px solid var(--border-color);
        border-radius: 6px;
        padding: 5px 10px;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.2s ease;
    }

    #comments-page .action-menu-toggle:hover {
        background: #e9ecef;
        border-color: #adb5bd;
    }

    #comments-page .action-menu-dropdown {
        display: none;
        position: absolute;
        right: 0;
        top: 100%;
        margin-top: 5px;
        background-color: white;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        z-index: 1000;
        min-width: 180px;
        padding: 5px 0;
    }

    #comments-page .action-menu-dropdown.show {
        display: block;
    }

    #comments-page .action-menu-item {
        display: flex;
        align-items: center;
        padding: 10px 15px;
        color: var(--text-color);
        text-decoration: none;
        font-size: 14px;
        transition: all 0.2s ease;
    }

    #comments-page .action-menu-item i {
        width: 16px;
        margin-right: 8px;
    }

    #comments-page .action-menu-item:hover {
        background-color: #f8f9fa;
        color: var(--text-color);
        text-decoration: none;
    }

    #comments-page .action-menu-item.text-danger:hover {
        background-color: #f8d7da;
        color: #dc3545;
    }

    #comments-page .action-menu-dropdown hr {
        margin: 4px 0;
        border-color: #dee2e6;
    }

    #comments-page .action-menu-dropdown.dropup {
        top: auto;
        bottom: 100%;
        margin-top: 0;
        margin-bottom: 5px;
    }

    /* === EMPTY STATE === */
    #comments-page .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: var(--text-color-light);
    }

    #comments-page .empty-state i {
        font-size: 3rem;
        margin-bottom: 16px;
        opacity: 0.5;
    }

    #comments-page .empty-state h6 {
        margin-bottom: 8px;
        color: var(--text-color);
    }

    #comments-page .empty-state p {
        margin: 0;
        font-size: 14px;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        #comments-page .controls {
            flex-direction: column;
            align-items: stretch;
        }

        #comments-page .search-bar {
            margin-bottom: 15px;
        }

        #comments-page .search-bar input {
            max-width: none;
        }

        #comments-page .filters {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 5px;
        }

        #comments-page .filters button {
            margin-left: 0;
            margin-bottom: 5px;
        }

        #comments-page .comment-list th,
        #comments-page .comment-list td {
            padding: 10px 15px;
        }

        #comments-page .comment-list th:nth-child(1),
        #comments-page .comment-list td:nth-child(1) {
            width: 140px;
            min-width: 140px;
            max-width: 140px;
        }

        #comments-page .comment-list th:nth-child(2),
        #comments-page .comment-list td:nth-child(2) {
            width: 220px;
            min-width: 220px;
            max-width: 220px;
        }

        #comments-page .comment-list th:nth-child(3),
        #comments-page .comment-list td:nth-child(3) {
            width: 180px;
            min-width: 180px;
            max-width: 180px;
        }

        #comments-page .comment-list th:nth-child(4),
        #comments-page .comment-list td:nth-child(4) {
            width: 100px;
            min-width: 100px;
            max-width: 100px;
        }

        #comments-page .comment-list th:nth-child(5),
        #comments-page .comment-list td:nth-child(5) {
            width: 90px;
            min-width: 90px;
            max-width: 90px;
        }

        #comments-page .comment-list th:nth-child(6),
        #comments-page .comment-list td:nth-child(6) {
            width: 100px;
            min-width: 100px;
            max-width: 100px;
        }

        #comments-page .comment-list {
            font-size: 14px;
        }

        #comments-page .author-info div {
            font-size: 14px;
        }

        #comments-page .author-info small {
            font-size: 12px;
        }

        #comments-page .status-badge {
            font-size: 11px;
            padding: 3px 8px;
        }
    }

    @media (max-width: 576px) {
        #comments-page .comment-list {
            display: block;
            overflow-x: auto;
        }

        #comments-page .comment-list thead {
            display: none;
        }

        #comments-page .comment-list tbody {
            display: block;
        }

        #comments-page .comment-list tr {
            display: block;
            margin-bottom: 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 15px;
            background-color: white;
        }

        #comments-page .comment-list td {
            display: block;
            padding: 5px 0;
            border: none;
            text-align: left;
        }

        #comments-page .comment-list td:before {
            content: attr(data-label) ": ";
            font-weight: 600;
            color: var(--text-color-light);
        }

        #comments-page .action-menu-container {
            text-align: center;
            margin-top: 10px;
        }

        #comments-page .action-menu-dropdown {
            position: static;
            margin-top: 10px;
            box-shadow: none;
            border: 1px solid var(--border-color);
        }

        #comments-page .controls {
            padding: 15px;
        }

        #comments-page .search-bar input {
            font-size: 16px;
            /* Prevent zoom on iOS */
        }

        #comments-page .filters button {
            font-size: 14px;
            padding: 8px 12px;
        }
    }

    /* Additional responsive */
    @media (max-width: 480px) {
        #comments-page .card-body {
            padding: 10px;
        }

        #comments-page .controls {
            padding: 10px;
        }

        #comments-page .comment-list tr {
            padding: 10px;
            margin-bottom: 10px;
        }

        #comments-page .author-info div {
            font-size: 13px;
        }

        #comments-page .author-info small {
            font-size: 11px;
        }

        #comments-page .comment-text {
            font-size: 13px;
        }
    }

    /* table responsiveness */
    #comments-page .table-responsive {
        border-radius: 0;
    }

    /* Better mobile experience */
    @media (max-width: 576px) {
        #comments-page .card {
            margin: 0;
            border-radius: 0;
        }

        #comments-page .card-header {
            border-radius: 0;
        }

        #comments-page .controls {
            border-radius: 0;
        }
    }

    #comments-page .table-container {
        overflow-x: auto;
        overflow-y: visible;
        border-radius: 0 0 12px 12px;
        max-width: 100vw;
        -webkit-overflow-scrolling: touch;
    }

    @media (max-width: 1200px) {
        #comments-page .comment-list {
            min-width: 750px;
        }
    }

    @media (max-width: 992px) {
        #comments-page .comment-list {
            min-width: 650px;
        }

        #comments-page .comment-list th:nth-child(3),
        #comments-page .comment-list td:nth-child(3) {
            width: 120px;
            min-width: 120px;
            max-width: 120px;
        }
    }
</style>

<div id="comments-page">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-comments"></i> Kelola Komentar
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../../index.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Komentar</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Comments Container -->
    <div class="card shadow">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Komentar</h6>
        </div>
        <div class="card-body p-0">
            <!-- Controls -->
            <div class="controls">
                <div class="search-bar">
                    <input type="search" id="searchInput" placeholder="Cari nama, email, atau isi komentar...">
                </div>
                <div class="filters" id="filterContainer">
                    <button class="active" data-filter="all">Semua</button>
                    <button data-filter="pending">Pending</button>
                    <button data-filter="approved">Disetujui</button>
                    <button data-filter="spam">Spam</button>
                </div>
            </div>

            <!-- Comments Table -->
            <div class="table-responsive">
                <table class="table table-hover mb-0 comment-list">
                    <thead class="table-light">
                        <tr>
                            <th>Pengomentar</th>
                            <th>Komentar</th>
                            <th>Artikel</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="comment-list-body">
                        <?php if (count($comments) > 0): ?>
                            <?php foreach ($comments as $c): ?>
                                <tr class="comment-row"
                                    data-id="<?= $c['id'] ?>"
                                    data-status="<?= htmlspecialchars($c['status']) ?>"
                                    data-search-term="<?= htmlspecialchars(strtolower($c['author_name'] . ' ' . $c['author_email'] . ' ' . $c['content'])) ?>">
                                    <td data-label="Pengomentar">
                                        <div class="author-info">
                                            <div class="author-name"><?= htmlspecialchars($c['author_name']) ?></div>
                                            <small class="author-email"><?= htmlspecialchars($c['author_email']) ?></small>
                                        </div>
                                    </td>
                                    <td data-label="Komentar">
                                        <div class="comment-content">
                                            <div class="comment-text"
                                                data-content="<?= htmlspecialchars($c['content']) ?>"
                                                title="Klik untuk melihat komentar lengkap"><?= htmlspecialchars($c['content']) ?></div>
                                        </div>
                                    </td>
                                    <td data-label="Artikel">
                                        <div class="article-title" title="<?= htmlspecialchars($c['article_title'] ?? '-') ?>">
                                            <?= htmlspecialchars($c['article_title'] ?? '-') ?>
                                        </div>
                                    </td>
                                    <td data-label="Tanggal">
                                        <div class="date-text">
                                            <?= date('d M Y', strtotime($c['created_at'])) ?><br>
                                            <small><?= date('H:i', strtotime($c['created_at'])) ?></small>
                                        </div>
                                    </td>
                                    <td data-label="Status">
                                        <span class="status-badge <?= $c['status'] ?>"><?= ucfirst($c['status']) ?></span>
                                    </td>
                                    <td data-label="Aksi">
                                        <div class="action-menu-container">
                                            <button class="btn btn-sm btn-outline-secondary action-menu-toggle">Aksi ▾</button>
                                            <div class="action-menu-dropdown" id="dropdown-<?= $c['id'] ?>">
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <i class="fas fa-inbox"></i>
                                        <h6>Tidak ada komentar</h6>
                                        <p>Belum ada komentar untuk dikelola.</p>
                                    </div>
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
                    <h5 class="modal-title">Detail Komentar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <strong>Dari:</strong> <span id="previewModalAuthor"></span><br>
                        <strong>Email:</strong> <span id="previewModalEmail"></span><br>
                        <strong>Artikel:</strong> <span id="previewModalArticle"></span><br>
                        <strong>Tanggal:</strong> <span id="previewModalDate"></span>
                    </div>
                    <div class="border-top pt-3">
                        <strong>Komentar:</strong>
                        <p id="previewModalComment" class="mb-0 mt-2"></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // ========================================
        // DOM ELEMENTS SELECTION
        // ========================================
        const searchInput = document.getElementById('searchInput');
        const filterContainer = document.getElementById('filterContainer');
        const commentListBody = document.getElementById('comment-list-body');
        const previewModal = document.getElementById('previewModal');
        const previewModalAuthor = document.getElementById('previewModalAuthor');
        const previewModalEmail = document.getElementById('previewModalEmail');
        const previewModalArticle = document.getElementById('previewModalArticle');
        const previewModalDate = document.getElementById('previewModalDate');
        const previewModalComment = document.getElementById('previewModalComment');

        // ========================================
        // GLOBAL VARIABLES
        // ========================================
        let currentFilter = 'all';

        // ========================================
        // ACTION MENU MANAGEMENT
        // ========================================

        /**
         * Generate HTML untuk action menu berdasarkan status
         */
        function generateActionMenuHTML(status, commentId) {
            let menuItems = '';

            if (status === 'pending') {
                menuItems += `
                <a href="#" class="action-menu-item" data-action="approve" data-id="${commentId}">
                    <i class="fas fa-check"></i> Setujui
                </a>
                <a href="#" class="action-menu-item" data-action="spam" data-id="${commentId}">
                    <i class="fas fa-exclamation-circle"></i> Tandai Spam
                </a>
            `;
            } else if (status === 'approved') {
                menuItems += `
                <a href="#" class="action-menu-item" data-action="pending" data-id="${commentId}">
                    <i class="fas fa-clock"></i> Kembalikan ke Pending
                </a>
                <a href="#" class="action-menu-item" data-action="spam" data-id="${commentId}">
                    <i class="fas fa-exclamation-circle"></i> Tandai Spam
                </a>
            `;
            } else if (status === 'spam') {
                menuItems += `
                <a href="#" class="action-menu-item" data-action="approve" data-id="${commentId}">
                    <i class="fas fa-check"></i> Tandai Bukan Spam & Setujui
                </a>
                <a href="#" class="action-menu-item" data-action="pending" data-id="${commentId}">
                    <i class="fas fa-clock"></i> Tandai Bukan Spam & Pending
                </a>
            `;
            }

            menuItems += `
            <hr>
            <a href="#" class="action-menu-item text-danger" data-action="delete" data-id="${commentId}">
                <i class="fas fa-trash"></i> Hapus
            </a>
        `;

            return menuItems;
        }

        /**
         * Inisialisation all actiont
         */
        function initializeActionMenus() {
            document.querySelectorAll('.comment-row').forEach(row => {
                const commentId = row.dataset.id;
                const status = row.dataset.status;
                const dropdown = document.getElementById(`dropdown-${commentId}`);

                if (dropdown) {
                    dropdown.innerHTML = generateActionMenuHTML(status, commentId);
                }
            });
        }

        /**
         * Update action menu untuk row tertentu
         */
        function updateActionMenu(commentId, newStatus) {
            const dropdown = document.getElementById(`dropdown-${commentId}`);
            if (dropdown) {
                dropdown.innerHTML = generateActionMenuHTML(newStatus, commentId);
            }
        }

        // ========================================
        // SEARCH & FILTER FUNCTIONALITY
        // ========================================

        /**
         * filter dan pencarian
         */
        function applyFiltersAndSearch() {
            const searchTerm = searchInput.value.toLowerCase().trim();
            const rows = commentListBody.querySelectorAll('.comment-row');

            rows.forEach(row => {
                const status = row.dataset.status;
                const searchData = row.dataset.searchTerm;

                const matchFilter = currentFilter === 'all' || status === currentFilter;
                const matchSearch = !searchTerm || searchData.includes(searchTerm);

                row.style.display = (matchFilter && matchSearch) ? '' : 'none';
            });
        }

        /**
         * Event listener search input
         */
        searchInput.addEventListener('input', applyFiltersAndSearch);

        /**
         * Event listener filter buttons
         */
        filterContainer.addEventListener('click', (e) => {
            if (e.target.tagName === 'BUTTON') {
                filterContainer.querySelector('.active')?.classList.remove('active');

                e.target.classList.add('active');

                currentFilter = e.target.dataset.filter;

                applyFiltersAndSearch();
            }
        });

        // ========================================
        // COMMENT PREVIEW MODAL
        // ========================================

        /**
         * Event listener preview komentar
         */
        commentListBody.addEventListener('click', (e) => {
            if (e.target.classList.contains('comment-text')) {
                e.preventDefault();

                const row = e.target.closest('.comment-row');
                const authorName = row.querySelector('.author-name').textContent;
                const authorEmail = row.querySelector('.author-email').textContent;
                const articleTitle = row.querySelector('.article-title').textContent;
                const dateText = row.querySelector('.date-text').textContent.replace(/\s+/g, ' ');
                const commentContent = e.target.dataset.content;

                // Set modal content
                previewModalAuthor.textContent = authorName;
                previewModalEmail.textContent = authorEmail;
                previewModalArticle.textContent = articleTitle;
                previewModalDate.textContent = dateText;
                previewModalComment.textContent = commentContent;

                // Show modal
                const modal = new bootstrap.Modal(previewModal);
                modal.show();
            }
        });

        // ========================================
        // DROPDOWN TOGGLE MANAGEMENT
        // ========================================

        /**
         * Smart dropdown positioning
         */
        function positionDropdown(dropdown) {
            // Reset position classes
            dropdown.classList.remove('dropup');

            requestAnimationFrame(() => {
                const rect = dropdown.getBoundingClientRect();
                const viewportHeight = window.innerHeight;

                if (rect.bottom > viewportHeight - 20) {
                    dropdown.classList.add('dropup');
                }

                if (window.innerWidth <= 576) {
                    dropdown.style.position = 'static';
                    dropdown.style.marginTop = '8px';
                } else {
                    dropdown.style.position = 'absolute';
                    dropdown.style.marginTop = '';
                }
            });
        }

        /**
         * Event listener dropdown toggle
         */
        document.addEventListener('click', (e) => {
            const isDropdownButton = e.target.matches('.action-menu-toggle');

            document.querySelectorAll('.action-menu-dropdown.show').forEach(dropdown => {
                if (!dropdown.parentElement.contains(e.target)) {
                    dropdown.classList.remove('show');
                }
            });

            if (isDropdownButton) {
                e.stopPropagation();

                const dropdown = e.target.nextElementSibling;
                const isOpening = !dropdown.classList.contains('show');

                dropdown.classList.toggle('show');

                if (isOpening) {
                    positionDropdown(dropdown);
                }
            }
        });

        // ========================================
        // COMMENT ACTIONS HANDLER
        // ========================================

        /**
         * Event listener aksi komentar
         */
        commentListBody.addEventListener('click', async (e) => {
            if (!e.target.classList.contains('action-menu-item')) return;

            e.preventDefault();

            const commentId = e.target.dataset.id;
            const action = e.target.dataset.action;

            const dropdown = e.target.closest('.action-menu-dropdown');
            if (dropdown) dropdown.classList.remove('show');

            if (action === 'delete') {
                if (!confirm('Apakah Anda yakin ingin menghapus komentar ini?')) {
                    return;
                }
            }

            const success = await processCommentAction(commentId, action);

            if (success && action === 'delete') {
                const row = e.target.closest('.comment-row');
                row.style.opacity = '0.5';
                setTimeout(() => {
                    row.remove();
                    if (commentListBody.querySelectorAll('.comment-row').length === 0) {
                        location.reload();
                    }
                }, 500);
            }
        });

        // ========================================
        // SERVER COMMUNICATION
        // ========================================

        /**
         * Proses aksi komentar ke server
         */
        async function processCommentAction(commentId, action) {
            const formData = new FormData();
            formData.append('id', commentId);
            formData.append('action', action);

            try {
                const response = await fetch('process/process_comments.php', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    throw new Error(`Expected JSON response, got: ${text.substring(0, 100)}`);
                }

                const data = await response.json();

                if (data.status === 'success') {
                    const messages = {
                        'approve': 'Komentar berhasil disetujui',
                        'pending': 'Komentar dikembalikan ke status pending',
                        'spam': 'Komentar berhasil ditandai sebagai spam',
                        'delete': 'Komentar berhasil dihapus'
                    };

                    showNotification(messages[action] || data.message, 'success');

                    if (action !== 'delete') {
                        updateCommentUI(commentId, data.new_status);
                    }

                    return true;
                } else {
                    showNotification(data.message || 'Terjadi kesalahan', 'error');
                    return false;
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Gagal menghubungi server. Silakan coba lagi.', 'error');
                return false;
            }
        }

        /**
         * Update UI setelah aksi berhasil
         */
        function updateCommentUI(commentId, newStatus) {
            const row = document.querySelector(`[data-id="${commentId}"]`);
            if (!row) return;

            // Update status badge
            const statusBadge = row.querySelector('.status-badge');
            statusBadge.className = `status-badge ${newStatus}`;
            statusBadge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);

            // Update row data attribute
            row.dataset.status = newStatus;

            // Update action menu
            updateActionMenu(commentId, newStatus);

            // Reapply filters setelah delay singkat
            setTimeout(applyFiltersAndSearch, 100);
        }

        // ========================================
        // NOTIFICATION SYSTEM
        // ========================================

        /**
         * Tampilkan notifikasi toast
         */
        function showNotification(message, type = 'info') {
            const toastContainer = document.querySelector('.toast-container');
            if (!toastContainer) return;

            const bgClass = {
                'success': 'bg-success',
                'error': 'bg-danger',
                'warning': 'bg-warning',
                'info': 'bg-info'
            } [type] || 'bg-info';

            const toast = document.createElement('div');
            toast.className = `toast ${bgClass} text-white`;
            toast.innerHTML = `
            <div class="toast-header ${bgClass} text-white border-0">
                <strong class="me-auto">Komentar</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        `;

            toastContainer.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast, {
                delay: 4000
            });
            bsToast.show();

            toast.addEventListener('hidden.bs.toast', () => toast.remove());
        }

        // ========================================
        // RESPONSIVE HANDLING
        // ========================================

        /**
         * Handle responsive layout changes
         */
        function handleResponsiveLayout() {
            document.querySelectorAll('.action-menu-dropdown.show')
                .forEach(d => d.classList.remove('show'));
        }

        // ========================================
        // KEYBOARD NAVIGATION
        // ========================================

        /**
         * Handle keyboard events
         */
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                // Tutup semua dropdown dengan ESC
                document.querySelectorAll('.action-menu-dropdown.show')
                    .forEach(d => d.classList.remove('show'));
            }
        });

        // ========================================
        // INITIALIZATION
        // ========================================

        // Inisialisasi action menus
        initializeActionMenus();

        // Handle window resize
        window.addEventListener('resize', handleResponsiveLayout);

        // Initial responsive check
        handleResponsiveLayout();
    });
</script>

<?php include '../../includes/footer.php'; ?>