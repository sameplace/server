<?php

include("config.php");

header('Content-Type: application/json;charset=utf-8;');
spHeaderHack();

// start from scratch
if (isset($_SESSION))  {
    $_SESSION = array(); // clear the session vars
    session_destroy();   // destroy the session
}

if (empty($_REQUEST['u'])) {
    spError("no u argument");
    echo json_encode('NOPE');
    return;
}

$u = new spUser;
if (! $u->inflate($_REQUEST['u'])) {
    spError("problem with u");
    echo json_encode('NOPE');
    return;
}
if ($u->isLocked()) {
    spError("u is locked");
    echo json_encode('NOPE');
    return;
}
$p = $u->getPassword();
if (! empty($p)) {
    spError("u has a password");
    echo json_encode('NOPE');
    return;
}

// make a new session
session_start(); 

$_SESSION['email'] = $u->m_email;
echo json_encode('OK');

?>
