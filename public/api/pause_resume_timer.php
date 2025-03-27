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
    // Use a function that fetches a specific timer for this user.
    $timerData = $timer->getTimerById($timerId, $userId);
    
    if (!$timerData) {
        echo json_encode(['success' => false, 'error' => 'Timer not found']);
        exit;
    }
    
    $status = $timerData['status'];

    if ($status === 'active') {
        if ($timer->pauseTimer($timerId)) {
            $timerData = $timer->getTimerById($timerId, $userId);
            echo json_encode(['success' => true, 'status' => 'paused', 'timer' => $timerData]);
        } else {
            echo json_encode(['success' => false, 'error' => htmlspecialchars($timer->getError())]);
        }
    } elseif ($status === 'paused') {
        if ($timer->resumeTimer($timerId)) {
            $timerData = $timer->getTimerById($timerId, $userId);
            echo json_encode(['success' => true, 'status' => 'active', 'timer' => $timerData]);
        } else {
            echo json_encode(['success' => false, 'error' => htmlspecialchars($timer->getError())]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Timer cannot be paused/resumed in its current state']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to pause/resume timer or unauthorized']);
}
?>
