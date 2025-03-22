<?php
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Session.php';
require_once __DIR__ . '/../../includes/Timer.php';

$session = new Session();
if (!$session->isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$db = new Database();
$timer = new Timer($db->conn);

$timerId = $_GET['id'] ?? null;
$status = $_GET['status'] ?? null;
$userId = $session->getUserId();

if ($timerId && $status && $timer->isOwner($timerId, $userId)) {
    if ($timer->updateStatus($timerId, $status)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $timer->getError()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to update timer status or unauthorized']);
}
?>