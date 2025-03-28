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
$user_id = $session->getUserId();

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (empty($data['timers'])) {
    echo json_encode(['success' => false, 'error' => 'No timers provided']);
    exit;
}

foreach ($data['timers'] as $timerData) {
    $timer_id = filter_var($timerData['id'], FILTER_VALIDATE_INT);
    $position = filter_var($timerData['position'], FILTER_VALIDATE_INT);

    if (!$timer_id || !$position || !$timer->isOwner($timer_id, $user_id)) {
        echo json_encode(['success' => false, 'error' => 'Invalid data']);
        exit;
    }

    if (!$timer->updateTimerPosition($timer_id, $position, $user_id)) {
        echo json_encode(['success' => false, 'error' => 'Update failed']);
        exit;
    }
}

echo json_encode(['success' => true]);
?>
