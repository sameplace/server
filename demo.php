<?php

if (empty($_REQUEST['u'])) {
    header('Location: login.php');
    return;
}

include("config.php");

$u = new spUser;
if (! $u->inflate($_REQUEST['u']) || $u->isLocked()) {
    header('Location: login.php');
    return;
}
$p = $u->getPassword();
if (! empty($p)) {
    header('Location: login.php');
    return;
}

header('Content-Type: application/json;charset=utf-8;');
spHeaderHack();

// start from scratch
if (isset($_SESSION))  {
    $_SESSION = array(); // clear the session vars
    session_destroy();   // destroy the session
}

// make a new session
session_start(); 

$_SESSION['email'] = $u->m_email;
echo json_encode('OK');

?>
