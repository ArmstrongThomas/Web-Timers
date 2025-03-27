<h1>Register</h1>
<?php if (isset($error) && $error): ?>
    <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>
<form method="POST">
    <?php echo CSRF::tokenField(); ?>
    <input type="text" name="name" placeholder="Name" required>
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit">Register</button>
</form>
<a href="/login">Have an Account?</a>
<a href="/reset">Forgot Password?</a>
