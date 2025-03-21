<?php
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/User.php';
require_once __DIR__ . '/../includes/Session.php';
require_once __DIR__ . '/../includes/layout.php';

$db = new Database();
$user = new User($db->conn);
$session = new Session();

if ($session->isLoggedIn() || $session->validateSession()) {
    header('Location: /dashboard');
    exit;
}

renderHeader('Account Settings');
?>

    <h1>Account Settings</h1>
    <!-- Account settings form -->

<?php
renderFooter();
?>