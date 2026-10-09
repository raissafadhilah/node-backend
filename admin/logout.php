<?php

require_once '../includes/session.php';

$_SESSION['admin_id'] = null;
$_SESSION['admin_username'] = null;

unset(
    $_SESSION['admin_id'],
    $_SESSION['admin_username']
);

session_regenerate_id(true);

header('Location: login.php');
exit;