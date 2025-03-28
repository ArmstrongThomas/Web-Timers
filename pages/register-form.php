<div class="auth-container">
    <h1>Register</h1>
    <?php if (isset($error) && $error): ?>
        <div class="message error">
            <p><?php echo htmlspecialchars($error); ?></p>
        </div>
    <?php endif; ?>
    <form method="POST" class="register-form">
        <?php echo CSRF::tokenField(); ?>
        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" placeholder="Name" required>
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="Email" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Enter new password" required minlength="8">
            <p class="form-hint">Password must be at least 8 characters long</p>
        </div>
        <div class="form-group">
            <label for="password_confirm">Confirm Password</label>
            <input type="password" id="password_confirm" name="password_confirm" placeholder="Confirm new password" required minlength="8">
        </div>
        <button type="submit" class="submit-btn">Register</button>
    </form>
    <div class="form-footer">
        <a href="/login">Have an Account?</a>
        <a href="/reset">Forgot Password?</a>
    </div>
</div>
<link rel="stylesheet" href="/utility.css">
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.register-form');
    const password = document.getElementById('password');
    const passwordConfirm = document.getElementById('password_confirm');

    form.addEventListener('submit', function(e) {
        if (password.value !== passwordConfirm.value) {
            e.preventDefault();
            alert('Passwords do not match. Please try again.');
        }
    });
});
</script>
