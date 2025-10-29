<?php

declare(strict_types=1);

// Include required files
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'includes/functions.php';

// Require authentication
requireAuth();

// Get database connection
$db = Database::getInstance()->getConnection();

// Get dashboard statistics
try {
    // Total articles
    $stmt = $db->query("SELECT COUNT(*) as total FROM articles WHERE status = 'published'");
    $totalArticles = $stmt->fetch()['total'];

    // Total products
    $stmt = $db->query("SELECT COUNT(*) as total FROM products");
    $totalProducts = $stmt->fetch()['total'];

    // Unread messages
    $stmt = $db->query("SELECT COUNT(*) as total FROM contact_messages WHERE status = 'unread'");
    $unreadMessages = $stmt->fetch()['total'];

    // Pending comments
    $stmt = $db->query("SELECT COUNT(*) as total FROM comments WHERE status = 'pending'");
    $pendingComments = $stmt->fetch()['total'];

    // Recent articles
    $stmt = $db->prepare("
        SELECT a.id, a.title, a.created_at, u.username as author 
        FROM articles a 
        JOIN users u ON a.author_id = u.id 
        ORDER BY a.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $recentArticles = $stmt->fetchAll();

    // Recent messages
    $stmt = $db->prepare("
        SELECT id, name, email, subject, submitted_at, status 
        FROM contact_messages 
        ORDER BY submitted_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $recentMessages = $stmt->fetchAll();

    // Low stock products count
    $stmt = $db->query("SELECT COUNT(*) as total FROM products WHERE stock <= 10");
    $lowStockCount = $stmt->fetch()['total'];
} catch (PDOException $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
    $totalArticles = $totalProducts = $unreadMessages = $pendingComments = $lowStockCount = 0;
    $recentArticles = $recentMessages = [];
}

$pageTitle = "Dashboard";
include 'includes/header.php';

?>

<!-- Page Header -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </h1>
    <div class="d-sm-flex">
        <span class="text-muted me-3">
            Selamat datang, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
        </span>
        <small class="text-muted">
            <?php echo date('l, d F Y'); ?>
        </small>
    </div>
</div>

<!-- Statistics Cards Row -->
<div class="row mb-4">
    <!-- Articles Card -->
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total Artikel
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo number_format($totalArticles); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-newspaper fa-2x text-primary"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <a href="modules/articles/articles.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-eye"></i> Lihat Detail
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Products Card -->
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Total Produk
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo number_format($totalProducts); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-box fa-2x text-success"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <a href="modules/products/products.php" class="btn btn-success btn-sm">
                        <i class="fas fa-eye"></i> Lihat Detail
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Messages Card -->
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Pesan Belum Dibaca
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo number_format($unreadMessages); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-envelope fa-2x text-warning"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <a href="modules/messages/messages.php" class="btn btn-warning btn-sm">
                        <i class="fas fa-eye"></i> Lihat Detail
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Low Stock Card -->
    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-4">
        <div class="card border-left-danger shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                            Stok Rendah
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo number_format($lowStockCount); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-exclamation-triangle fa-2x text-danger"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <a href="modules/products/products.php?filter=low_stock" class="btn btn-danger btn-sm">
                        <i class="fas fa-eye"></i> Lihat Detail
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Content Row -->
<div class="row">
    <!-- Chart Column -->
    <div class="col-xl-8 col-lg-12 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3">
                    <div class="d-flex align-items-center flex-wrap gap-3 mb-3 mb-lg-0">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-chart-line" id="chart-icon"></i>
                            <span id="chart-title">Statistik Artikel</span>
                        </h6>
                        <div class="btn-group btn-group-sm" role="group">
                            <input type="radio" class="btn-check" name="chartType" id="articlesChart" value="articles" checked>
                            <label class="btn btn-outline-primary" for="articlesChart">
                                <i class="fas fa-newspaper"></i> Artikel
                            </label>
                            <input type="radio" class="btn-check" name="chartType" id="productsChart" value="products">
                            <label class="btn btn-outline-success" for="productsChart">
                                <i class="fas fa-box"></i> Produk
                            </label>
                        </div>
                        <!-- Export Dropdown -->
                        <div class="dropdown no-arrow">
                            <button class="btn btn-primary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-download"></i> Export
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
                                <li>
                                    <h6 class="dropdown-header">Export Chart:</h6>
                                </li>
                                <li><a class="dropdown-item" href="#" onclick="exportChart('pdf')">
                                        <i class="fas fa-file-pdf text-danger"></i> Export PDF
                                    </a></li>
                                <li><a class="dropdown-item" href="#" onclick="exportChart('excel')">
                                        <i class="fas fa-file-excel text-success"></i> Export Excel
                                    </a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="#" onclick="exportChart('image')">
                                        <i class="fas fa-image text-info"></i> Export Gambar
                                    </a></li>
                            </ul>
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center">
                        <!-- Timeline Controls -->
                        <div class="timeline-controls mb-2 mb-md-0">
                            <div class="btn-group btn-group-sm" role="group" aria-label="Timeline selector">
                                <button type="button" class="btn btn-outline-secondary timeline-btn" data-timeline="1week">1 Minggu</button>
                                <button type="button" class="btn btn-outline-secondary timeline-btn" data-timeline="1month">1 Bulan</button>
                                <button type="button" class="btn btn-outline-secondary timeline-btn" data-timeline="3months">3 Bulan</button>
                                <button type="button" class="btn btn-outline-secondary timeline-btn active" data-timeline="6months">6 Bulan</button>
                                <button type="button" class="btn btn-outline-secondary timeline-btn" data-timeline="1year">1 Tahun</button>
                                <button type="button" class="btn btn-outline-info timeline-btn" data-timeline="custom">
                                    <i class="fas fa-calendar"></i> Kustom
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Custom Date Range Controls -->
                <div id="custom-date-range" class="mt-3" style="display: none;">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label for="customStartDate" class="form-label small">Tanggal Mulai</label>
                            <input type="date" class="form-control form-control-sm" id="customStartDate">
                        </div>
                        <div class="col-md-4">
                            <label for="customEndDate" class="form-label small">Tanggal Akhir</label>
                            <input type="date" class="form-control form-control-sm" id="customEndDate">
                        </div>
                        <div class="col-md-4">
                            <button type="button" class="btn btn-info btn-sm w-100" id="applyCustomRange">
                                <i class="fas fa-check"></i> Terapkan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="chart-area">
                    <canvas id="mainChart"></canvas>
                </div>
                <div class="mt-3" id="chart-legend"></div>
                <div class="mt-2" id="chart-period-info">
                    <small class="text-muted"><i class="fas fa-info-circle"></i> Memuat data...</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions Column -->
    <div class="col-xl-4 col-lg-12 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-bolt"></i> Aksi Cepat
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2" style="grid-template-columns: 1fr;">
                    <a href="modules/articles/add.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Tambah Artikel Baru
                    </a>
                    <a href="modules/products/add.php" class="btn btn-success">
                        <i class="fas fa-plus"></i> Tambah Produk Baru
                    </a>
                    <a href="modules/settings/settings.php" class="btn btn-secondary">
                        <i class="fas fa-cog"></i> Pengaturan Website
                    </a>
                </div>
            </div>
        </div>

        <!-- System Info Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-secondary">
                    <i class="fas fa-info-circle"></i> Informasi Sistem
                </h6>
            </div>
            <div class="card-body">
                <div class="small">
                    <div class="mb-2">
                        <strong>PHP Version:</strong> <?php echo PHP_VERSION; ?>
                    </div>
                    <div class="mb-2">
                        <strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?>
                    </div>
                    <div class="mb-2">
                        <strong>Free Space:</strong>
                        <?php
                        $freeBytes = disk_free_space('.');
                        if ($freeBytes !== false) {
                            echo number_format($freeBytes / (1024 * 1024 * 1024), 2) . ' GB';
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </div>
                    <div>
                        <strong>Last Login:</strong>
                        <?php echo isset($_SESSION['last_login']) ? date('d/m/Y H:i', strtotime($_SESSION['last_login'])) : 'First time'; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity Row -->
<div class="row">
    <!-- Recent Articles -->
    <div class="col-xl-6 col-lg-12 col-md-12 mb-4">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-newspaper"></i> Artikel Terbaru
                </h6>
            </div>
            <div class="card-body">
                <?php if (empty($recentArticles)): ?>
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-file-alt fa-3x mb-3"></i>
                        <p>Belum ada artikel.</p>
                        <a href="modules/articles/add.php" class="btn btn-primary btn-sm">
                            Tambah Artikel Pertama
                        </a>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recentArticles as $article): ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">
                                        <a href="modules/articles/articles.php?id=<?php echo $article['id']; ?>"
                                            class="text-decoration-none">
                                            <?php echo htmlspecialchars($article['title']); ?>
                                        </a>
                                    </h6>
                                    <small class="text-muted">
                                        <?php echo date('d M Y, H:i', strtotime($article['created_at'])); ?>
                                    </small>
                                </div>
                                <small class="text-muted">
                                    Oleh: <?php echo htmlspecialchars($article['author']); ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="card-footer">
                        <a href="modules/articles/articles.php" class="btn btn-primary btn-sm">
                            Lihat Semua Artikel
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Messages -->
    <div class="col-xl-6 col-lg-12 col-md-12 mb-4">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-warning">
                    <i class="fas fa-envelope"></i> Pesan Terbaru
                </h6>
            </div>
            <div class="card-body">
                <?php if (empty($recentMessages)): ?>
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-envelope-open fa-3x mb-3"></i>
                        <p>Belum ada pesan masuk.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recentMessages as $message): ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">
                                        <a href="modules/messages/view.php?id=<?php echo $message['id']; ?>"
                                            class="text-decoration-none">
                                            <?php echo htmlspecialchars($message['subject']); ?>
                                        </a>
                                        <?php if ($message['status'] === 'unread'): ?>
                                            <span class="badge bg-warning text-dark">Baru</span>
                                        <?php endif; ?>
                                    </h6>
                                    <small class="text-muted">
                                        <?php echo date('d M Y, H:i', strtotime($message['submitted_at'])); ?>
                                    </small>
                                </div>
                                <small class="text-muted">
                                    Dari: <?php echo htmlspecialchars($message['name']); ?>
                                    (<?php echo htmlspecialchars($message['email']); ?>)
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="card-footer">
                        <a href="modules/messages/messages.php" class="btn btn-warning btn-sm">
                            Lihat Semua Pesan
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js and Export Libraries -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>

<script>
    (function() {
        'use strict';

        let currentChart = null;
        let currentChartType = 'articles';
        let currentTimeline = '6months';
        let customStartDate = '';
        let customEndDate = '';
        let currentChartData = {};

        const timelineLabels = {
            '1week': '1 Minggu Terakhir',
            '1month': '1 Bulan Terakhir',
            '3months': '3 Bulan Terakhir',
            '6months': '6 Bulan Terakhir',
            '1year': '1 Tahun Terakhir',
            'custom': 'Rentang Kustom'
        };

        document.addEventListener('DOMContentLoaded', () => {
            setupEventListeners();
            updateChartData(); // Initial load
            toggleTimelineControls(); // Set initial visibility
        });

        function setupEventListeners() {
            document.querySelectorAll('input[name="chartType"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    currentChartType = this.value;
                    updateChartData();
                    toggleTimelineControls();
                });
            });

            document.querySelectorAll('.timeline-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    if (currentChartType === 'products') {
                        return;
                    }

                    currentTimeline = this.dataset.timeline;
                    document.querySelectorAll('.timeline-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');

                    const customDateRange = document.getElementById('custom-date-range');
                    if (currentTimeline === 'custom') {
                        customDateRange.style.display = 'block';
                    } else {
                        customDateRange.style.display = 'none';
                        updateChartData();
                    }
                });
            });

            document.getElementById('applyCustomRange').addEventListener('click', function() {
                if (currentChartType === 'products') return;

                customStartDate = document.getElementById('customStartDate').value;
                customEndDate = document.getElementById('customEndDate').value;

                if (!customStartDate || !customEndDate) {
                    alert('Silakan pilih tanggal mulai dan tanggal akhir.');
                    return;
                }
                if (new Date(customStartDate) >= new Date(customEndDate)) {
                    alert('Tanggal mulai harus lebih kecil dari tanggal akhir.');
                    return;
                }
                updateChartData();
            });
        }

        function toggleTimelineControls() {
            const timelineControls = document.querySelector('.timeline-controls');
            const customDateRange = document.getElementById('custom-date-range');

            if (currentChartType === 'products') {
                timelineControls.style.display = 'none';
                customDateRange.style.display = 'none';
            } else {
                timelineControls.style.display = 'block';
            }
        }

        async function updateChartData() {
            showLoadingState(true);

            let url = `api/chart-data.php?chartType=${currentChartType}`;

            if (currentChartType === 'articles') {
                url += `&timeline=${currentTimeline}`;
                if (currentTimeline === 'custom' && customStartDate && customEndDate) {
                    url += `&custom_start=${customStartDate}&custom_end=${customEndDate}`;
                }
            }

            try {
                const response = await fetch(url);
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                const result = await response.json();

                if (result.success) {
                    currentChartData = result;
                    renderChart(result.data);
                    updateUI(result.dateRange);
                } else {
                    console.error('API Error:', result.message);
                    showError('Gagal memuat data chart: ' + result.message);
                }
            } catch (error) {
                console.error('Fetch error:', error);
                showError('Terjadi kesalahan saat memuat data chart');
            } finally {
                showLoadingState(false);
            }
        }

        function renderChart(data) {
            const ctx = document.getElementById('mainChart').getContext('2d');
            if (currentChart) {
                currentChart.destroy();
            }

            let labels, chartData;

            if (currentChartType === 'articles') {
                if (currentTimeline === '1week' || currentTimeline === '1month') {
                    labels = data.map(item => new Date(item.period).toLocaleDateString('id-ID', {
                        day: 'numeric',
                        month: 'short'
                    }));
                } else {
                    labels = data.map(item => new Date(item.period + '-02').toLocaleDateString('id-ID', {
                        month: 'long',
                        year: 'numeric'
                    }));
                }
                chartData = data.map(item => parseInt(item.total));
            } else {
                labels = data.map(item => {
                    return item.period.length > 15 ? item.period.substring(0, 15) + '...' : item.period;
                });
                chartData = data.map(item => parseInt(item.total));
            }

            const chartConfig = {
                type: currentChartType === 'articles' ? 'line' : 'bar',
                data: {
                    labels: labels,
                    datasets: [createDataset(chartData, data)]
                },
                options: getChartOptions()
            };

            currentChart = new Chart(ctx, chartConfig);
        }

        function createDataset(data, rawData = []) {
            if (currentChartType === 'articles') {
                return {
                    label: 'Artikel Dipublikasi',
                    data: data,
                    borderColor: '#4e73df',
                    backgroundColor: 'rgba(78, 115, 223, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                };
            } else {
                const backgroundColors = rawData.map(item => {
                    switch (item.stock_status) {
                        case 'Habis':
                            return '#e74a3b';
                        case 'Rendah':
                            return '#f39c12';
                        case 'Sedang':
                            return '#3498db';
                        case 'Tinggi':
                            return '#2ecc71';
                        default:
                            return '#95a5a6';
                    }
                });

                return {
                    label: 'Stok Produk',
                    data: data,
                    backgroundColor: backgroundColors,
                    borderColor: backgroundColors.map(color => color),
                    borderWidth: 1,
                    borderRadius: 4
                };
            }
        }

        function getChartOptions() {
            const baseOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += context.parsed.y;
                                label += currentChartType === 'articles' ? ' artikel' : ' unit';
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            };

            if (currentChartType === 'products') {
                baseOptions.scales.x.ticks = {
                    maxRotation: 45,
                    minRotation: 45
                };
            }

            return baseOptions;
        }

        function updateUI(dateRange) {
            const titleElement = document.getElementById('chart-title');
            const iconElement = document.getElementById('chart-icon');

            if (currentChartType === 'articles') {
                titleElement.textContent = `Statistik Artikel - ${timelineLabels[currentTimeline]}`;
                iconElement.className = 'fas fa-chart-line';
            } else {
                titleElement.textContent = 'Data Stok Produk';
                iconElement.className = 'fas fa-chart-bar';
            }

            const periodInfo = document.getElementById('chart-period-info');

            if (currentChartType === 'articles' && dateRange && dateRange.start && dateRange.end) {
                const startDate = new Date(dateRange.start).toLocaleDateString('id-ID', {
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric'
                });
                const endDate = new Date(dateRange.end).toLocaleDateString('id-ID', {
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric'
                });
                periodInfo.innerHTML = `<small class="text-muted"><i class="fas fa-info-circle"></i> Periode: <strong>${startDate}</strong> - <strong>${endDate}</strong></small>`;
            } else if (currentChartType === 'products') {
                const currentDate = new Date().toLocaleDateString('id-ID', {
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric'
                });
                periodInfo.innerHTML = `<small class="text-muted"><i class="fas fa-info-circle"></i> Data per: <strong>${currentDate}</strong> (15 produk dengan stok terendah)</small>`;
            }

            document.getElementById('chart-legend').innerHTML = '';
        }

        function showLoadingState(isLoading) {
            const chartArea = document.querySelector('.chart-area');
            const periodInfo = document.getElementById('chart-period-info');

            if (isLoading) {
                chartArea.classList.add('loading');
                periodInfo.innerHTML = '<small class="text-muted"><i class="fas fa-spinner fa-spin"></i> Memuat data...</small>';
            } else {
                chartArea.classList.remove('loading');
            }
        }

        function showError(message) {
            const periodInfo = document.getElementById('chart-period-info');
            periodInfo.innerHTML = `<small class="text-danger"><i class="fas fa-exclamation-triangle"></i> ${message}</small>`;
        }

        // Export functions
        window.exportChart = function(format) {
            if (!currentChart || !currentChartData.data) {
                alert('Tidak ada data untuk di-export');
                return;
            }

            const chartTitle = document.getElementById('chart-title').textContent;
            const dataToExport = currentChartData.data;

            switch (format) {
                case 'image':
                    exportAsImage(chartTitle);
                    break;
                case 'pdf':
                    exportAsPDF(chartTitle, dataToExport);
                    break;
                case 'excel':
                    exportAsExcel(chartTitle, dataToExport);
                    break;
                default:
                    alert('Format export tidak didukung');
            }
        };

        function exportAsImage(title) {
            const canvas = document.getElementById('mainChart');
            const link = document.createElement('a');
            link.download = `${title.replace(/\s+/g, '_')}.png`;
            link.href = canvas.toDataURL();
            link.click();
        }

        function exportAsPDF(title, data) {
            const {
                jsPDF
            } = window.jspdf;
            const canvas = document.getElementById('mainChart');
            const imgData = canvas.toDataURL('image/png');

            const pdf = new jsPDF('landscape', 'mm', 'a4');
            const imgWidth = 280;
            const imgHeight = (canvas.height * imgWidth) / canvas.width;

            // Add title
            pdf.setFontSize(16);
            pdf.text(title, 20, 20);

            // Add date
            pdf.setFontSize(10);
            pdf.text(`Generated: ${new Date().toLocaleDateString('id-ID')}`, 20, 30);

            // Add chart
            pdf.addImage(imgData, 'PNG', 10, 40, imgWidth, Math.min(imgHeight, 160));

            // Add data table
            if (currentChartType === 'articles') {
                addArticleTableToPDF(pdf, data, imgHeight + 50);
            } else {
                addProductTableToPDF(pdf, data, imgHeight + 50);
            }

            pdf.save(`${title.replace(/\s+/g, '_')}.pdf`);
        }

        function exportAsExcel(title, data) {
            let worksheetData;
            let filename;

            if (currentChartType === 'articles') {
                worksheetData = [
                    ['Periode', 'Jumlah Artikel'],
                    ...data.map(item => {
                        if (currentTimeline === '1week' || currentTimeline === '1month') {
                            return [
                                new Date(item.period).toLocaleDateString('id-ID'),
                                parseInt(item.total)
                            ];
                        } else {
                            const date = new Date(item.period + '-01');
                            return [
                                date.toLocaleDateString('id-ID', {
                                    month: 'long',
                                    year: 'numeric'
                                }),
                                parseInt(item.total)
                            ];
                        }
                    })
                ];
                filename = `statistik_artikel_${new Date().toISOString().slice(0, 10)}.xlsx`;
            } else {
                worksheetData = [
                    ['Nama Produk', 'Stok', 'Status Stok'],
                    ...data.map(item => [
                        item.period,
                        parseInt(item.total),
                        item.stock_status
                    ])
                ];
                filename = `stok_produk_${new Date().toISOString().slice(0, 10)}.xlsx`;
            }

            const ws = XLSX.utils.aoa_to_sheet(worksheetData);
            const wb = XLSX.utils.book_new();

            ws['!cols'] = [{
                    wch: 30
                }, // Column A
                {
                    wch: 15
                }, // Column B
                {
                    wch: 15
                } // Column C
            ];

            XLSX.utils.book_append_sheet(wb, ws, currentChartType === 'articles' ? 'Artikel' : 'Produk');
            XLSX.writeFile(wb, filename);
        }

        function addArticleTableToPDF(pdf, data, startY) {
            pdf.setFontSize(12);
            pdf.text('Data Artikel:', 20, startY);

            let y = startY + 10;
            pdf.setFontSize(10);

            pdf.text('Periode', 20, y);
            pdf.text('Jumlah Artikel', 100, y);
            y += 7;

            data.forEach(item => {
                let periodStr;
                if (currentTimeline === '1week' || currentTimeline === '1month') {
                    periodStr = new Date(item.period).toLocaleDateString('id-ID');
                } else {
                    const date = new Date(item.period + '-01');
                    periodStr = date.toLocaleDateString('id-ID', {
                        month: 'long',
                        year: 'numeric'
                    });
                }

                pdf.text(periodStr, 20, y);
                pdf.text(item.total.toString(), 100, y);
                y += 5;

                if (y > 280) {
                    pdf.addPage();
                    y = 20;
                }
            });
        }

        function addProductTableToPDF(pdf, data, startY) {
            pdf.setFontSize(12);
            pdf.text('Data Stok Produk:', 20, startY);

            let y = startY + 10;
            pdf.setFontSize(10);

            pdf.text('Nama Produk', 20, y);
            pdf.text('Stok', 150, y);
            pdf.text('Status', 200, y);
            y += 7;

            data.forEach(item => {
                const productName = item.period.length > 35 ? item.period.substring(0, 35) + '...' : item.period;

                pdf.text(productName, 20, y);
                pdf.text(item.total.toString(), 150, y);
                pdf.text(item.stock_status, 200, y);
                y += 5;

                if (y > 280) {
                    pdf.addPage();
                    y = 20;
                }
            });
        }

        // Add custom styles
        const styles = document.createElement('style');
        styles.textContent = `
        .chart-area {
            position: relative;
            height: 400px;
            width: 100%;
        }

        .chart-area.loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            animation: spin 2s linear infinite;
        }

        @keyframes spin {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }

        .timeline-btn.active {
            background-color: #007bff !important;
            border-color: #007bff !important;
            color: white !important;
        }

        @media (max-width: 768px) {
            .timeline-controls .btn-group {
                flex-wrap: wrap;
            }
            
            .timeline-controls .btn-group .btn {
                flex: 1 1 auto;
                margin-bottom: 0.25rem;
            }
            
            #custom-date-range .row {
                flex-direction: column;
            }
            
            #custom-date-range .col-md-4 {
                margin-bottom: 0.5rem;
            }
        }
    `;
        document.head.appendChild(styles);

    })();
</script>

</div>
</div>

<?php include 'includes/footer.php'; ?>