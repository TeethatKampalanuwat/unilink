<?php
require_once '../includes/functions.php';
$toAdmin = !empty($_GET['admin']);
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
redirect($toAdmin ? 'admin_login.php' : 'home.php');
