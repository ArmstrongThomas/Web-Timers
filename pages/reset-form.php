<h1>Reset Password</h1>
<form method="POST">
    <?php echo CSRF::tokenField(); ?>
    <input type="email" name="email" placeholder="Email" required>
    <button type="submit">Send Reset Link</button>
</form>
<a href="/login">Have an Account?</a>
<a href="/register">Register</a>
