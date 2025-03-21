<?php
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/User.php';
require_once __DIR__ . '/../includes/Session.php';
require_once __DIR__ . '/../includes/layout.php';

$db = new Database();
$user = new User($db->conn);
$session = new Session();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $userData = $user->getUserByEmail($email);

    if ($userData && password_verify($password, $userData['password'])) {
        if ($userData['locked']) {
            echo "You've been locked out of your account for failing to login too many times. Please check your email for a magic login link or try again later. If you've forgotten your password, use the Forgotten Password link below.";
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
                echo "Your account is locked. Please check your email for a magic login link.";
            } else {
                echo "Invalid email or password.";
            }
        } else {
            echo "Invalid email or password.";
        }
    }
}

if ($session->isLoggedIn() || $session->validateSession()) {
    header('Location: /dashboard');
    exit;
}

renderHeader('Login');
?>

    <h1>Login</h1>
    <form method="POST">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <label>
            <input type="checkbox" name="remember_me"> Remember Me
        </label>
        <button type="submit">Login</button>
    </form>
    <a href="/reset">Forgot Password?</a>
    <a href="/register">Register</a>

<?php
renderFooter();
?>