<h1>Login</h1>
<form method="POST">
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
