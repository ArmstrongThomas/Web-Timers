<?php
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Session.php';
require_once __DIR__ . '/../../includes/Timer.php';

header('Content-Type: application/json');

$session = new Session();
if (!$session->isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$db = new Database();
$timer = new Timer($db->conn);

// Validate and sanitize inputs
$timerId = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;
$status = isset($_GET['status']) ? trim($_GET['status']) : null;
$userId = $session->getUserId();

// Validate timer ID
if (!$timerId) {
    echo json_encode(['success' => false, 'error' => 'Invalid timer ID']);
    exit;
}

// Validate status
$validStatuses = ['active', 'paused', 'completed'];
if (!in_array($status, $validStatuses, true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid status value']);
    exit;
}

if ($timer->isOwner($timerId, $userId)) {
    if ($timer->updateStatus($timerId, $status)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => htmlspecialchars($timer->getError())]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to update timer status or unauthorized']);
}
?>