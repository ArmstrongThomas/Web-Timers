<?php
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/User.php';
require_once __DIR__ . '/../includes/Session.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/CSRF.php';

$db = new Database();
$user = new User($db->conn);
$session = new Session();

// Initialize $error as null
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
        $error = "Invalid form submission. Please try again.";
    } else {
        // Sanitize and validate email
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        if (!$email) {
            $error = "Please enter a valid email address.";
        } else {
            // Validate password (not empty)
            $password = $_POST['password'] ?? '';
            if (empty($password)) {
                $error = "Password is required.";
            } else {
                $userData = $user->getUserByEmail($email);

                if ($userData && password_verify($password, $userData['password'])) {
                    if ($userData['locked']) {
                        $error = "You've been locked out of your account for failing to login too many times. Please check your email for a magic login link or try again later.";
                    } else {
                        $user->updateLoginDetails($userData['id'], $_SERVER['REMOTE_ADDR'], session_id(), isset($_POST['remember_me']), $_SERVER['HTTP_USER_AGENT']);
                        $user->startSession($userData['id'], isset($_POST['remember_me']));
                        header('Location: /response?message=login_success');
                        exit;
                    }
                } else {
                    if ($userData) {
                        $user->incrementFailedLogins($userData['id']);
                        if ($userData['failed_logins'] >= 2) {
                            $user->sendMagicLoginLink($userData['id'], $userData['email']);
                            $error = "Your account is locked. Please check your email for a magic login link.";
                        } else {
                            $error = "Invalid email or password.";
                        }
                    } else {
                        $error = "Invalid email or password.";
                    }
                }
            }
        }
    }
}

if ($session->isLoggedIn() || $session->validateSession()) {
    header('Location: /dashboard');
    exit;
}

renderHeader('Login');
?>

<?php include __DIR__ . '/login-form.php'; ?>

<?php
renderFooter();
?>
