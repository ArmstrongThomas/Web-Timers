<?php
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/User.php';
require_once __DIR__ . '/../includes/Session.php';
require_once __DIR__ . '/../includes/layout.php';

$db = new Database();
$user = new User($db->conn);

// Sanitize and validate token
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

// Verify token format (should be a 32-character hex string)
if (empty($token) || !preg_match('/^[a-f0-9]{32}$/i', $token)) {
    renderHeader('Invalid Login Link');
    echo "<p style='color: red;'>Invalid magic login link format.</p>";
    echo "<p><a href='/login'>Return to login page</a></p>";
    renderFooter();
    exit;
}

if ($user->unlockAndLogin($token)) {
    $userData = $user->unlockAndLogin($token); 
    $user->startSession($userData['id'], false);
    header('Location: /response?message=magic_login_success');
    exit;
}

renderHeader('Invalid Login Link');
echo "<p style='color: red;'>Invalid or expired magic login link.</p>";
echo "<p><a href='/login'>Return to login page</a></p>";
renderFooter();
exit;

renderHeader('Magic Login');
?>

    <h1>Magic Login</h1>
    <p>Processing your login...</p>

<?php
renderFooter();
?>