<?php

include('config.php');

$errors = array();
$logEntry = date("Y M d H:i:s");
if (! empty($_SERVER["REMOTE_ADDR"]))
    $logEntry .= " ".$_SERVER["REMOTE_ADDR"];

function validateLogin($email, $pass) {
    global $errors, $logEntry;

    // are we registered?
    if (empty($email)) {
	$errors[] = "Please enter an email address";
	$logEntry .= " NOEMAIL";
	return null;
    }

    if (empty($pass)) {
	$errors[] = "Please enter a password";
	$logEntry .= " NOPASS";
	return null;
    }

    $u = null;
    try {
	$logEntry .= " email='".$email."'";
	$u = spUser::lookupEmail($email);
	if (empty($u)) {
	    $errors[] = "Invalid Login";
	    $logEntry .= " NOENTRY";
	    return null;
	}
    } catch (PDOException $e) {
	$errors[] = $e->getMessage();
	return null;
    }

    if (0 == $u->isValidated()) {
	$errors[] = "Must validate your account first";
	$logEntry .= " NOVALIDATE";
	return null;
    }

    if (0 != $u->isLocked()) {
	$errors[] = "Your account is locked.";
	$logEntry .= " LOCKED";
	return null;
    }

    if (! password_verify($pass, $u->getPassword())) {
	$errors[] = "Invalid Login";
	$logEntry .= " BADPASS";
	$u->badPass();
	return null;
    }

    return $u;
}

$email = "";
$pass = "";

// process the login attempt
if (isset($_POST['action'])) {
    if (! empty($_POST['email']))
	$email = $_POST['email'];
    if (! empty($_POST['pass']))
	$pass = $_POST['pass'];

    // validate the login info
    $u = validateLogin($email, $pass);
    spAuthLog($logEntry);
    if (empty($errors)) {
	session_start();      // start the session
	$_SESSION['email'] = $u->m_email;

	// redirect to start page
	$next = "profile.php";
	if ($u && $u->isAdmin())
	    $next = "admin.php";
	if (! empty($_POST['next']))
	    $next = $_POST['next'];
	header("Location: ".$next);
	exit();
    }
}

echo <<<_EOD
<html>
<head>
<link rel="stylesheet" href="style.css" type="text/css">
<title>login</title>
</head><body>

_EOD;

foreach ($errors as $error)
    echo '<br/><font color="red">'.htmlentities($error)."</font>\n";

$next = "";
if (! empty($_REQUEST["next"]))
    $next = $_REQUEST["next"];

$uLock = "";

echo <<<_EOD
<h4>Enter your email address and password to login</h4>
<form action="" method="POST">
<table border="0">
 <tr>
   <td rowspan="3"><img src="images/sp.png"></td>
   <td>Email:</td>
   <td><input type="text" name="email" value="$email"/></td>
 </tr>
 <tr>
   <td>Password:</td>
   <td><input type="password" name="pass" value="$pass"/></td>
 </tr>
 <tr>
   <td>$uLock</td>
   <td><input name="action" type="submit" value="Login"/></td>
 </tr>
</table>
<input name="next" type="hidden" value="$next"/>
</form>

_EOD;

?>
</body></html>
