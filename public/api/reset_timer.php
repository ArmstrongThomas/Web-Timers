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
    if ($timer->resetTimer($timerId)) {
        // Use getTimerById to fetch data for the specific timer.
        $timerData = $timer->getTimerById($timerId, $userId);
        echo json_encode(['success' => true, 'length' => $timerData['length'], 'timer' => $timerData]);
    } else {
        echo json_encode(['success' => false, 'error' => $timer->getError()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to reset timer or unauthorized']);
}
?>
