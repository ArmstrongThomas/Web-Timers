<?php
require_once __DIR__ . '/../includes/layout.php';

// Sanitize and validate message parameter
$message = isset($_GET['message']) ? trim($_GET['message']) : 'default';

// Define allowed messages and their data
$messages = [
    'registration_complete' => [
        'title' => "Registration Complete",
        'text' => "Check your email to confirm your account. If you don't see it within a few minutes, check your spam folder.",
        'next' => '/login',
        'type' => 'success'
    ],
    'account_confirmed' => [
        'title' => "Account Confirmed",
        'text' => "Thanks for confirming your account!",
        'next' => '/login',
        'type' => 'success'
    ],
    'password_reset' => [
        'title' => "Reset Email Sent",
        'text' => "Check your email for the password reset link. If you don't see it within a few minutes, check your spam folder.",
        'next' => '/login',
        'type' => 'success'
    ],
    'password_reset_success' => [
        'title' => "Password Reset",
        'text' => "Your password has been successfully reset.",
        'next' => '/login',
        'type' => 'success'
    ],
    'magic_login_success' => [
        'title' => "Account Unlocked",
        'text' => "Your account has been unlocked, and you are now logged in.",
        'next' => '/dashboard',
        'type' => 'success'
    ],
    'login_success' => [
        'title' => "Welcome Back",
        'text' => "You are now logged in.",
        'next' => '/dashboard',
        'type' => 'success'
    ],
    'default' => [
        'title' => "Error",
        'text' => "An unknown error occurred.",
        'next' => '/',
        'type' => 'error'
    ]
];

// Validate message is in allowed list
if (!array_key_exists($message, $messages)) {
    $message = 'default';
}

$messageData = $messages[$message];
$nextUrl = $messageData['next'];

renderHeader($messageData['title']);
?>

<div class="auth-container">
    <h1><?php echo htmlspecialchars($messageData['title']); ?></h1>
    <div class="message <?php echo htmlspecialchars($messageData['type']); ?>">
        <p><?php echo htmlspecialchars($messageData['text']); ?></p>
    </div>
    <div class="form-footer">
        <a href="<?php echo htmlspecialchars($nextUrl); ?>" class="submit-btn">Continue</a>
    </div>
</div>

<script>
    setTimeout(function() {
        window.location.href = "<?php echo htmlspecialchars($nextUrl); ?>";
    }, 3000);
</script>

<link rel="stylesheet" href="/utility.css">

<?php
renderFooter();
?>
