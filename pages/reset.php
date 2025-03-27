<?php
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/User.php';
require_once __DIR__ . '/../includes/Mailer.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/CSRF.php';

$db = new Database();
$user = new User($db->conn);
$mailer = new Mailer();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate email
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    if (!$email) {
        echo "<p style='color: red;'>Please enter a valid email address.</p>";
        renderHeader('Reset Password');
        include(__DIR__ . '/reset-form.php');
        renderFooter();
        exit;
    }
    
    $userData = $user->getUserByEmail($email);

    if ($userData) {
        $reset_token = bin2hex(random_bytes(16));
        $token_expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

        if ($user->setResetToken($userData['id'], $reset_token, $token_expiry)) {
            $subject = "Password Reset Request";
            $body = "Please reset your password by clicking the link below:\n";
            $body .= "https://timers.dotting.page/reset-password?token=$reset_token";
            $mailer->sendMail($email, $subject, $body);
            header('Location: /response?message=password_reset');
            exit;
        } else {
            echo "Error: " . $user->getError();
        }
    } else {
        echo "No user found with that email address.";
    }
}

renderHeader('Reset Password');
?>

    <h1>Reset Password</h1>
    <form method="POST">
        <?php echo CSRF::tokenField(); ?>
        <input type="email" name="email" placeholder="Email" required>
        <button type="submit">Send Reset Link</button>
    </form>
    <a href="/login">Have an Account?</a>
    <a href="/register">Register</a>

<?php
renderFooter();
?>