<?php

include('config.php');

session_start();  // get the existing session

$logEntry = date("Y M d H:i:s");
if (! empty($_SERVER["REMOTE_ADDR"]))
    $logEntry .= " ".$_SERVER["REMOTE_ADDR"];

// redirect to login page if no session exists
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    spAuthLog($logEntry." LOGOUT: no session?");
    exit();
}

spAuthLog($logEntry." LOGOUT ".escapeshellarg($_SESSION['email']));

// otherwise clear out session and display logout message
$_SESSION = array(); // clear the session vars
session_destroy();   // destroy the session

?>
<html>
<head>
<link rel="stylesheet" href="style.css" type="text/css">
<title>logout</title>
</head><body>
<h1>You have logged out!</h1><p><a href="login.php">Login</a></p>
</body></html>
