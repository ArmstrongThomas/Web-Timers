<?php
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/User.php';
require_once __DIR__ . '/../includes/Mailer.php';
require_once __DIR__ . '/../includes/layout.php';

$db = new Database();
$user = new User($db->conn);
$mailer = new Mailer();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $creation_ip = $_SERVER['REMOTE_ADDR'];
    $verification_code = bin2hex(random_bytes(16));
    $code_expiry = date('Y-m-d H:i:s', strtotime('+8 hours'));

    if ($user->register($name, $email, $password, $creation_ip, $verification_code, $code_expiry)) {
        $subject = "Registration Confirmation";
        $body = "Thank you for registering. Please confirm your email by clicking the link below:\n";
        $body .= "https://timers.dotting.page/verify?code=$verification_code";
        $mailer->sendMail($email, $subject, $body);
        header('Location: /response?message=registration_complete');
        exit;
    } else {
        $error = $user->getError();
    }
}

renderHeader('Register');
?>

    <h1>Register</h1>
<?php if ($error): ?>
    <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>
    <form method="POST">
        <input type="text" name="name" placeholder="Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Register</button>
    </form>
    <a href="/login">Have an Account?</a>
    <a href="/reset">Forgot Password?</a>

<?php
renderFooter();
?>