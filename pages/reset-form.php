<div class="auth-container">
    <h1>Reset Password</h1>
    <?php if (isset($error) && $error): ?>
        <div class="message error">
            <p><?php echo htmlspecialchars($error); ?></p>
        </div>
    <?php endif; ?>
    <form method="POST" class="reset-form">
        <?php echo CSRF::tokenField(); ?>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="Email" required>
        </div>
        <button type="submit" class="submit-btn">Send Reset Link</button>
    </form>
    <div class="form-footer">
        <a href="/login">Have an Account?</a>
        <a href="/register">Register</a>
    </div>
</div>
<link rel="stylesheet" href="/utility.css">
