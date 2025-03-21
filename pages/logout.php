<?php
require_once __DIR__ . '/../includes/Session.php';

$session = new Session();
$session->logout();
header('Location: /');
exit;
