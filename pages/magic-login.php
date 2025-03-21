<?php
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/User.php';
require_once __DIR__ . '/../includes/Session.php';
require_once __DIR__ . '/../includes/layout.php';

$db = new Database();
$user = new User($db->conn);

$token = $_GET['token'] ?? '';

if ($user->unlockAndLogin($token)) {
    $userData = $user->unlockAndLogin($token); // Assuming this method exists to get user data by token
    $user->startSession($userData['id'], false);
    header('Location: /response?message=magic_login_success');
    exit;
}

echo "Invalid or expired magic login link.";

renderHeader('Magic Login');
?>

    <h1>Magic Login</h1>
    <p>Processing your login...</p>

<?php
renderFooter();
?>