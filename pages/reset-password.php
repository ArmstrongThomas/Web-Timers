<?php
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/User.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/CSRF.php';

$db = new Database();
$user = new User($db->conn);

$token = $_GET['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
        echo "<p style='color: red;'>Invalid form submission. Please try again.</p>";
        renderHeader('Reset Password');
        include(__DIR__ . '/reset-password-form.php');
        renderFooter();
        exit;
    }
    
    // Validate token
    $token = filter_var(trim($token), FILTER_SANITIZE_STRING);
    if (empty($token) || strlen($token) != 32) {
        echo "<p style='color: red;'>Invalid reset token.</p>";
        renderHeader('Reset Password');
        echo "<p>Please request a new password reset link.</p>";
        echo "<p><a href='/reset'>Back to reset password page</a></p>";
        renderFooter();
        exit;
    }
    
    // Validate password strength
    $password = $_POST['password'] ?? '';
    if (strlen($password) < 8) {
        echo "<p style='color: red;'>Password must be at least 8 characters long.</p>";
        renderHeader('Reset Password');
        include(__DIR__ . '/reset-password-form.php');
        renderFooter();
        exit;
    }
    
    $new_password = password_hash($password, PASSWORD_BCRYPT);

    if ($user->resetPassword($token, $new_password)) {
        header('Location: /response?message=password_reset_success');
        exit;
    } else {
        echo "<p style='color: red;'>Invalid or expired reset token.</p>";
    }
}

renderHeader('Reset Password');
?>

    <h1>Reset Password</h1>
    <form method="POST">
        <?php echo CSRF::tokenField(); ?>
        <input type="password" name="password" placeholder="New Password" required>
        <button type="submit">Reset Password</button>
    </form>

<?php
renderFooter();
?>