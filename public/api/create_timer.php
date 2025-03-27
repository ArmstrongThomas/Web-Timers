<?php
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Session.php';
require_once __DIR__ . '/../../includes/Timer.php';

$db = new Database();
$session = new Session();
$timer = new Timer($db->conn);

// Check if user is logged in
if (!$session->isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $length = isset($_POST['length']) ? filter_var($_POST['length'], FILTER_VALIDATE_INT) : 0;
    $sound = isset($_POST['sound']) ? trim($_POST['sound']) : '';
    $user_id = $session->getUserId();

    // Additional validation before passing to Timer class
    if (empty($name)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Timer name is required']);
        exit;
    }

    if ($length <= 0 || $length > 157680000) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Timer length must be between 1 and 157680000 seconds']);
        exit;
    }

    if (!preg_match('/^\/sounds\/[a-zA-Z0-9_\-\.]+\.(mp3|wav|ogg)$/i', $sound)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid sound format']);
        exit;
    }

    if ($timer->createTimer($user_id, $name, $length, $sound)) {
        // Retrieve the ID of the newly inserted timer
        $newTimerId = $db->conn->insert_id;
        // Fetch the new timer's data using getTimerById
        $newTimer = $timer->getTimerById($newTimerId, $user_id);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'timer' => $newTimer]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => htmlspecialchars($timer->getError())]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>
