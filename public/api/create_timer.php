<?php
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/Session.php';
require_once __DIR__ . '/../../includes/Timer.php';

$db = new Database();
$session = new Session();
$timer = new Timer($db->conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $length = (int)$_POST['length'];
    $sound = $_POST['sound'];
    $user_id = $session->getUserId();

    if ($timer->createTimer($user_id, $name, $length, $sound)) {
        // Retrieve the ID of the newly inserted timer
        $newTimerId = $db->conn->insert_id;
        // Fetch the new timer's data using getTimerById
        $newTimer = $timer->getTimerById($newTimerId, $user_id);
        echo json_encode(['success' => true, 'timer' => $newTimer]);
    } else {
        echo json_encode(['success' => false, 'error' => $timer->getError()]);
    }
}
?>
