<?php
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/User.php';
require_once __DIR__ . '/../includes/layout.php';

$db = new Database();
$user = new User($db->conn);

// Sanitize and validate verification code
$code = isset($_GET['code']) ? trim($_GET['code']) : '';

// Verify code format (should be a 32-character hex string)
if (empty($code) || !preg_match('/^[a-f0-9]{32}$/i', $code)) {
    renderHeader('Verification Failed');
    echo "<p style='color: red;'>Invalid verification code format.</p>";
    echo "<p><a href='/'>Return to home page</a></p>";
    renderFooter();
    exit;
}

if ($user->verifyUser($code)) {
    header('Location: /response?message=account_confirmed');
} else {
    renderHeader('Verification Failed');
    echo "<p style='color: red;'>Invalid or expired verification code.</p>";
    echo "<p><a href='/'>Return to home page</a></p>";
    renderFooter();
    exit;
}

renderHeader('Verify');
?>

    <h1>Verify</h1>
    <p>Processing your verification...</p>

<?php
renderFooter();
?>