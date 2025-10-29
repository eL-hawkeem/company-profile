<?php
// api/chart-data.php

declare(strict_types=1);

header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../includes/functions.php';

requireAuth();

$db = Database::getInstance()->getConnection();

$chartType = isset($_GET['chartType']) ? $_GET['chartType'] : 'articles';
$timeline = isset($_GET['timeline']) ? $_GET['timeline'] : '6months';
$customStart = isset($_GET['custom_start']) ? $_GET['custom_start'] : '';
$customEnd = isset($_GET['custom_end']) ? $_GET['custom_end'] : '';

function getDateRange($timeline, $customStart = '', $customEnd = '')
{
    $end = date('Y-m-d');
    switch ($timeline) {
        case '1week':
            $start = date('Y-m-d', strtotime('-1 week'));
            break;
        case '1month':
            $start = date('Y-m-d', strtotime('-1 month'));
            break;
        case '3months':
            $start = date('Y-m-d', strtotime('-3 months'));
            break;
        case '6months':
            $start = date('Y-m-d', strtotime('-6 months'));
            break;
        case '1year':
            $start = date('Y-m-d', strtotime('-1 year'));
            break;
        case 'custom':
            return (!empty($customStart) && !empty($customEnd)) ? ['start' => $customStart, 'end' => $customEnd] : ['start' => date('Y-m-d', strtotime('-6 months')), 'end' => $end];
        default:
            $start = date('Y-m-d', strtotime('-6 months'));
    }
    return ['start' => $start, 'end' => $end];
}

$dateRange = getDateRange($timeline, $customStart, $customEnd);
$response = ['success' => true, 'data' => [], 'dateRange' => $dateRange, 'chartType' => $chartType];

try {
    if ($chartType === 'articles') {
        $grouping_format = ($timeline === '1week' || $timeline === '1month') ? "DATE(created_at)" : "DATE_FORMAT(created_at, '%Y-%m')";
        $sql = "
            SELECT {$grouping_format} as period, COUNT(*) as total
            FROM articles 
            WHERE created_at >= ? AND created_at <= ? AND status = 'published'
            GROUP BY period
            ORDER BY period
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([$dateRange['start'], $dateRange['end'] . ' 23:59:59']);
        $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($chartType === 'products') {
        $sql = "
            SELECT 
                p.name as period,
                p.stock as total,
                CASE 
                    WHEN p.stock = 0 THEN 'Habis'
                    WHEN p.stock <= 10 THEN 'Rendah'
                    WHEN p.stock <= 50 THEN 'Sedang'
                    ELSE 'Tinggi'
                END as stock_status
            FROM products p 
            ORDER BY p.stock ASC
            LIMIT 15
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response['dateRange'] = [
            'start' => date('Y-m-d'),
            'end' => date('Y-m-d')
        ];
    }
} catch (PDOException $e) {
    $response['success'] = false;
    $response['message'] = 'Database error: ' . $e->getMessage();
}

echo json_encode($response);
