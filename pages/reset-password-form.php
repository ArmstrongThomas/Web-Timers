<div class="auth-container">
    <h1>Reset Your Password</h1>

    <?php if ($error): ?>
    <div class="message error">
        <p><?php echo htmlspecialchars($error); ?></p>
    </div>
    <?php endif; ?>

    <form method="POST" class="reset-password-form">
        <?php echo CSRF::tokenField(); ?>

        <div class="form-group">
            <label for="password">New Password</label>
            <input type="password" id="password" name="password" placeholder="Enter new password"
                   required minlength="8">
            <p class="form-hint">Password must be at least 8 characters long</p>
        </div>

        <div class="form-group">
            <label for="password_confirm">Confirm Password</label>
            <input type="password" id="password_confirm" name="password_confirm"
                   placeholder="Confirm new password" required minlength="8">
        </div>

        <button type="submit" class="submit-btn">Reset Password</button>
    </form>

    <div class="form-footer">
        <a href="/login">Return to login</a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.reset-password-form');
    const password = document.getElementById('password');
    const passwordConfirm = document.getElementById('password_confirm');

    form.addEventListener('submit', function(e) {
        // Check if passwords match
        if (password.value !== passwordConfirm.value) {
            e.preventDefault();
            alert('Passwords do not match. Please try again.');
        }
    });
});
</script>
<link rel="stylesheet" href="/utility.css">
