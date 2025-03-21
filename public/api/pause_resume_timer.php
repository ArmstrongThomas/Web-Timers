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
$userId = $session->getUserId();

if ($timerId && $timer->isOwner($timerId, $userId)) {
    $timerData = $timer->getTimersByUserId($userId);
    $status = $timerData[0]['status'];
    if ($status === 'active') {
        if ($timer->pauseTimer($timerId)) {
            echo json_encode(['success' => true, 'status' => 'paused', 'remaining_time' => $timerData[0]['remaining_time']]);
        } else {
            echo json_encode(['success' => false, 'error' => $timer->getError()]);
        }
    } elseif ($status === 'paused') {
        if ($timer->resumeTimer($timerId)) {
            echo json_encode(['success' => true, 'status' => 'active', 'length' => $timerData[0]['length']]);
        } else {
            echo json_encode(['success' => false, 'error' => $timer->getError()]);
        }
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to pause/resume timer or unauthorized']);
}
?>