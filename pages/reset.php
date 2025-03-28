<?php
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/User.php';
require_once __DIR__ . '/../includes/Mailer.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/CSRF.php';

$db = new Database();
$user = new User($db->conn);
$mailer = new Mailer();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate email
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    if (!$email) {
        $error = "Please enter a valid email address.";
    } else {
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
                $error = "Error: " . $user->getError();
            }
        } else {
            $error = "No user found with that email address.";
        }
    }
}

renderHeader('Reset Password');
?>

<?php include __DIR__ . '/reset-form.php'; ?>

<?php
renderFooter();
?>
