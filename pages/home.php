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
?>

<?php include __DIR__ . '/login-form.php'; ?>


<?php
renderFooter();
?>
