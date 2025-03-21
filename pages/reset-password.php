<?php
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/User.php';
require_once __DIR__ . '/../includes/layout.php';

$db = new Database();
$user = new User($db->conn);

$token = $_GET['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    if ($user->resetPassword($token, $new_password)) {
        header('Location: /response?message=password_reset_success');
        exit;
    } else {
        echo "Invalid or expired reset token.";
    }
}

renderHeader('Reset Password');
?>

    <h1>Reset Password</h1>
    <form method="POST">
        <input type="password" name="password" placeholder="New Password" required>
        <button type="submit">Reset Password</button>
    </form>

<?php
renderFooter();
?>