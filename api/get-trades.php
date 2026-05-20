<?php
/**
 * API: Load trades for a given sector
 * Path: /api/get-trades.php
 */
require_once '../config/database.php';

$sector = $_GET['sector'] ?? '';
if (empty($sector)) {
    echo json_encode(['success' => false, 'message' => 'Sector missing']);
    exit;
}

// Join trades with sectors using sector_id (adjust names if needed)
$stmt = $pdo->prepare("
    SELECT t.trade_name 
    FROM trades t
    JOIN sectors s ON t.sector_id = s.sector_id
    WHERE s.sector_name = ? AND t.status = 'active'
    ORDER BY t.trade_name
");
$stmt->execute([$sector]);
$trades = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'trades' => $trades]);