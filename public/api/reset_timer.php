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

// Validate and sanitize timer ID
$timerId = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;
$userId = $session->getUserId();

if (!$timerId) {
    echo json_encode(['success' => false, 'error' => 'Invalid timer ID']);
    exit;
}

if ($timer->isOwner($timerId, $userId)) {
    if ($timer->resetTimer($timerId)) {
        // Use getTimerById to fetch data for the specific timer.
        $timerData = $timer->getTimerById($timerId, $userId);
        echo json_encode(['success' => true, 'length' => $timerData['length'], 'timer' => $timerData]);
    } else {
        echo json_encode(['success' => false, 'error' => htmlspecialchars($timer->getError())]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to reset timer or unauthorized']);
}
?>
