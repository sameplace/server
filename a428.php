<?php

header('Content-Type: application/json;charset=utf-8;');

// start from scratch
if (isset($_SESSION))  {
    $_SESSION = array(); // clear the session vars
    session_destroy();   // destroy the session
}

// make a new session
session_start(); 

if (empty($_POST['email'])) {
    echo json_encode('Error: Missing email');
    return;
}
if (empty($_POST['pass'])) {
    echo json_encode('Error: Missing pass');
    return;
}
$e = $_POST['email'];
$p = $_POST['pass'];

$u = null;
try {
    $u = spUser::lookupEmail($email);
    if (empty($u)) {
	echo json_encode('Error: invalid login');
	return;
    }
} catch (PDOException $e) {
    echo json_encode('Error: invalid login');
    return;
}

if (0 == $u->isValidated()) {
    echo json_encode('Error: validate login first');
    return;
}

if (0 != $u->isLocked()) {
    echo json_encode(' Error: Your account is locked');
    return;
}

if (! password_verify($pass, $u->getPassword())) {
    echo json_encode('Error: invalid login');
    return;
}

$_SESSION['email'] = $u->m_email;
echo json_encode('OK');

?>
