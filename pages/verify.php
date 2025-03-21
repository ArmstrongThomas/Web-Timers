<?php
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/User.php';
require_once __DIR__ . '/../includes/layout.php';

$db = new Database();
$user = new User($db->conn);

$code = $_GET['code'] ?? '';

if ($user->verifyUser($code)) {
    header('Location: /response?message=account_confirmed');
} else {
    echo "Invalid or expired verification code.";
}

renderHeader('Verify');
?>

    <h1>Verify</h1>
    <p>Processing your verification...</p>

<?php
renderFooter();
?>