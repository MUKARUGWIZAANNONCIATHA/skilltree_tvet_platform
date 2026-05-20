<?php
/**
 * Admin API Endpoint
 * Path: /api/v1/admin.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once '../../config/database.php';
require_once '../../includes/functions/common.php';

$response = ['success' => false, 'message' => 'Invalid action'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = intval($_POST['user_id'] ?? 0);
    
    if ($userId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        exit();
    }
    
    try {
        switch ($action) {
            case 'approve_user':
                // Approve teacher
                $stmt = $pdo->prepare("UPDATE users SET is_approved = 1 WHERE user_id = ? AND role = 'teacher'");
                $stmt->execute([$userId]);
                if ($stmt->rowCount() > 0) {
                    logActivity($_SESSION['user_id'], 'approve', "Approved teacher ID: $userId");
                    $response = ['success' => true, 'message' => 'Teacher approved successfully'];
                } else {
                    $response = ['success' => false, 'message' => 'User not found or already approved'];
                }
                break;
                
            case 'verify_company':
                // Verify company
                $stmt = $pdo->prepare("UPDATE users SET is_verified = 1 WHERE user_id = ? AND role = 'company'");
                $stmt->execute([$userId]);
                if ($stmt->rowCount() > 0) {
                    logActivity($_SESSION['user_id'], 'verify', "Verified company ID: $userId");
                    $response = ['success' => true, 'message' => 'Company verified successfully'];
                } else {
                    $response = ['success' => false, 'message' => 'Company not found or already verified'];
                }
                break;
                
            case 'reject_user':
                // Reject/delete user
                $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND role IN ('teacher', 'company')");
                $stmt->execute([$userId]);
                if ($stmt->rowCount() > 0) {
                    logActivity($_SESSION['user_id'], 'reject', "Rejected user ID: $userId");
                    $response = ['success' => true, 'message' => 'User rejected and deleted'];
                } else {
                    $response = ['success' => false, 'message' => 'User not found'];
                }
                break;
                
            case 'activate':
                $stmt = $pdo->prepare("UPDATE users SET is_active = 1 WHERE user_id = ?");
                $stmt->execute([$userId]);
                $response = ['success' => true, 'message' => 'User activated'];
                break;
                
            case 'deactivate':
                $stmt = $pdo->prepare("UPDATE users SET is_active = 0 WHERE user_id = ?");
                $stmt->execute([$userId]);
                $response = ['success' => true, 'message' => 'User deactivated'];
                break;
                
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
                $stmt->execute([$userId]);
                $response = ['success' => true, 'message' => 'User deleted'];
                break;
                
            default:
                $response = ['success' => false, 'message' => 'Unknown action'];
        }
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

echo json_encode($response);
?> 
