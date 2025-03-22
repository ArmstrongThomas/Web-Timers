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
    // Use a function that fetches a specific timer for this user.
    $timerData = $timer->getTimerById($timerId, $userId);
    $status = $timerData['status'];

    if ($status === 'active') {
        if ($timer->pauseTimer($timerId)) {
            $timerData = $timer->getTimerById($timerId, $userId);
            echo json_encode(['success' => true, 'status' => 'paused', 'timer' => $timerData]);
        } else {
            echo json_encode(['success' => false, 'error' => $timer->getError()]);
        }
    } elseif ($status === 'paused') {
        if ($timer->resumeTimer($timerId)) {
            $timerData = $timer->getTimerById($timerId, $userId);
            echo json_encode(['success' => true, 'status' => 'active', 'timer' => $timerData]);
        } else {
            echo json_encode(['success' => false, 'error' => $timer->getError()]);
        }
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to pause/resume timer or unauthorized']);
}
?>
