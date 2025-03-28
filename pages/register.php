<?php
require_once __DIR__ . "/../includes/Database.php";
require_once __DIR__ . "/../includes/User.php";
require_once __DIR__ . "/../includes/Mailer.php";
require_once __DIR__ . "/../includes/layout.php";
require_once __DIR__ . "/../includes/CSRF.php";
require_once __DIR__ . "/../includes/Session.php";

$db = new Database();
$user = new User($db->conn);
$mailer = new Mailer();
$session = new Session();

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Validate CSRF token
    if (
        !isset($_POST["csrf_token"]) ||
        !CSRF::validateToken($_POST["csrf_token"])
    ) {
        $error = "Invalid form submission. Please try again.";
        renderHeader("Register");
        include __DIR__ . "/register-form.php";
        renderFooter();
        exit();
    }

    // Sanitize and validate name
    $name = trim($_POST["name"] ?? "");
    if (empty($name) || strlen($name) > 255) {
        $error = "Name must be between 1 and 255 characters.";
        renderHeader("Register");
        include __DIR__ . "/register-form.php";
        renderFooter();
        exit();
    }

    // Sanitize and validate email
    $email = filter_var(trim($_POST["email"] ?? ""), FILTER_VALIDATE_EMAIL);
    if (!$email) {
        $error = "Please enter a valid email address.";
        renderHeader("Register");
        include __DIR__ . "/register-form.php";
        renderFooter();
        exit();
    }

    // Validate password strength
    $password_input = $_POST["password"] ?? "";
    if (strlen($password_input) < 8) {
        $error = "Password must be at least 8 characters long.";
        renderHeader("Register");
        include __DIR__ . "/register-form.php";
        renderFooter();
        exit();
    }

    // Hash password after validation
    $password = password_hash($password_input, PASSWORD_BCRYPT);

    // Sanitize IP address
    $creation_ip = filter_var($_SERVER["REMOTE_ADDR"], FILTER_VALIDATE_IP)
        ? $_SERVER["REMOTE_ADDR"]
        : "0.0.0.0";

    $verification_code = bin2hex(random_bytes(16));
    $code_expiry = date("Y-m-d H:i:s", strtotime("+8 hours"));

    if (
        $user->register(
            $name,
            $email,
            $password,
            $creation_ip,
            $verification_code,
            $code_expiry
        )
    ) {
        $subject = "Registration Confirmation";
        $body =
            "Thank you for registering. Please confirm your email by clicking the link below:\n";
        $body .= "https://timers.dotting.page/verify?code=$verification_code";
        $mailer->sendMail($email, $subject, $body);
        header("Location: /response?message=registration_complete");
        exit();
    } else {
        $error = $user->getError();
    }
}

renderHeader("Register");
?>

<?php include __DIR__ . '/register-form.php'; ?>

<?php renderFooter();
?>
