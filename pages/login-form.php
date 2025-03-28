<div class="auth-container">
    <h1>Login</h1>
    <?php if (isset($error) && $error): ?>
        <div class="message error">
            <p><?php echo htmlspecialchars($error); ?></p>
        </div>
    <?php endif; ?>
    <form method="POST" class="login-form" action="/login">
        <?php echo CSRF::tokenField(); ?>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="Email" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Password" required>
        </div>
        <div class="form-group">
            <label>
                <input type="checkbox" name="remember_me"> Remember Me
            </label>
        </div>
        <button type="submit" class="submit-btn">Login</button>
    </form>
    <div class="form-footer">
        <a href="/reset">Forgot Password?</a>
        <a href="/register">Register</a>
    </div>
</div>
<link rel="stylesheet" href="/utility.css">
