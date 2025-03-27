<?php
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/User.php';
require_once __DIR__ . '/../includes/Session.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/CSRF.php';

$db = new Database();
$user = new User($db->conn);
$session = new Session();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    $session->logout();
    header('Location: /');
    exit;
}

if ($session->isLoggedIn() || $session->validateSession()) {
    header('Location: /dashboard');
    exit;
}

renderHeader('Home');

// Use direct PHP output instead of heredoc with PHP code inside
?>

<h1>Login</h1>
<form method="POST" action="/login">
    <?php echo CSRF::tokenField(); ?>
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
