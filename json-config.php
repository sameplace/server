<?php

require_once 'config.php';
require_once 'jutils.php';

if (! isset($_SESSION)) {
    $ok = @session_start();
    if (! $ok) {
	session_regenerate_id(true);
	session_start();
    }
}

if (! isset($_SESSION['email'])) {
    header("Location: a428.php");
    return;
}

header('Content-Type: application/json;charset=utf-8;');
spHeaderHack();

?>
