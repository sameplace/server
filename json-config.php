<?php

require_once 'config.php';
require_once 'jutils.php';

if (! isset($_SESSION))
    session_start();

if (! isset($_SESSION['email'])) {
    header("Location: a428.php");
    return;
}

header('Content-Type: application/json;charset=utf-8;');
spHeaderHack();

?>
