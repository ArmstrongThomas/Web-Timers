<h1>Reset Password</h1>
<form method="POST">
    <?php echo CSRF::tokenField(); ?>
    <input type="password" name="password" placeholder="New Password" required>
    <button type="submit">Reset Password</button>
</form>
