<?php
require_once __DIR__ . '/../includes/layout.php';

// Sanitize and validate message parameter
$message = isset($_GET['message']) ? trim($_GET['message']) : 'default';

// Define allowed messages and their data
$messages = [
    'registration_complete' => [
        'text' => "Registration complete, check your email to confirm your account. If you don't see it within a few minutes, check your spam folder.",
        'next' => '/login'
    ],
    'account_confirmed' => [
        'text' => "Thanks for confirming your account!",
        'next' => '/login'
    ],
    'password_reset' => [
        'text' => "Password reset email sent! Check your email. If you don't see it within a few minutes, check your spam folder.",
        'next' => '/login'
    ],
    'password_reset_success' => [
        'text' => "Your password has been successfully reset.",
        'next' => '/login'
    ],
    'magic_login_success' => [
        'text' => "Your account has been unlocked and you are now logged in.",
        'next' => '/dashboard'
    ],
    'login_success' => [
        'text' => "Welcome back!",
        'next' => '/dashboard'
    ],
    'default' => [
        'text' => "An unknown error occurred.",
        'next' => '/'
    ]
];

// Validate message is in allowed list
if (!array_key_exists($message, $messages)) {
    $message = 'default';
}

$messageData = $messages[$message];
$nextUrl = $messageData['next'];

renderHeader('Response');
?>

    <h1>Response</h1>
    <p><?php echo htmlspecialchars($messageData['text']); ?></p>
    <p><a href="<?php echo htmlspecialchars($nextUrl); ?>">Click here to continue</a></p>

    <script>
        setTimeout(function() {
            window.location.href = "<?php echo htmlspecialchars($nextUrl); ?>";
        }, 5000);
    </script>

<?php
renderFooter();
?>