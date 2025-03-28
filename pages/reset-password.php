<?php
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/User.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/CSRF.php';
require_once __DIR__ . '/../includes/Session.php';

$db = new Database();
$user = new User($db->conn);
$session = new Session();

// Get and sanitize token from URL
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

// Check if token format is valid
if (empty($token) || !preg_match('/^[a-f0-9]{32}$/i', $token)) {
    renderHeader('Invalid Reset Link');
    echo "<div class='message error'>";
    echo "<h1>Invalid Reset Link</h1>";
    echo "<p>The password reset link is invalid or has expired.</p>";
    echo "<p><a href='/reset' class='button'>Request a new reset link</a></p>";
    echo "</div>";
    renderFooter();
    exit;
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
        $error = "Invalid form submission. Please try again.";
    } else {
        // Validate password strength
        $password = $_POST['password'] ?? '';
        if (strlen($password) < 8) {
            $error = "Password must be at least 8 characters long.";
        } else {
            // Hash password for storage
            $new_password = password_hash($password, PASSWORD_BCRYPT);

            // Attempt to reset the password
            if ($user->resetPassword($token, $new_password)) {
                $success = true;
                // Redirect after successful password reset
                header('Location: /response?message=password_reset_success');
                exit;
            } else {
                $error = "The password reset link is invalid or has expired. Please request a new one.";
            }
        }
    }
}

renderHeader('Reset Password');

?>

<?php include __DIR__ . '/reset-password-form.php'; ?>


<?php
renderFooter();
?>
