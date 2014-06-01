<html>
<head>
<link rel="stylesheet" href="style.css" type="text/css">
<title>register</title>
</head><body>
<?php

include("config.php");

$real = "";
if (! empty($_REQUEST['real']))
    $real = $_REQUEST['real'];

$pass = "";
if (! empty($_REQUEST['pass']))
    $pass = $_REQUEST['pass'];

$email = "";
if (! empty($_REQUEST['email']))
    $email = $_REQUEST['email'];

$gotSome = false;
$gotAll = true;
$errors = array();

if (! empty($real))
    $gotSome = true;
else {
    $gotAll = false;
    $errors[] = "Real name field is required";
}
if (! empty($pass))
    $gotSome = true;
else {
    $gotAll = false;
    $errors[] = "Password field is required";
}
if (! empty($email)) {
    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
	$email = "";
	$errors[] = "Simplify your email please";
	$gotAll = false;
    }
    $gotSome = true;
} else
    $gotAll = false;

if ($gotAll) {
    if (spUser::lookupEmail($email)) {
	$errors[] = 'Email already exists';
	$email = "";
    } else {
	$u = new spUser();
	$u->setPassword($pass);
	$u->m_realname = $real;
	$u->m_email = $email;
	if ($u->create()) {
	    $subj = 'Please validate your SamePlace account';
	    $msg = "Click this link to validate your SamePlace account\r\n"
	      ."\r\nhttps://secure.bitway.com/sp"
	      ."/validate.php?u=$u->m_oStr\r\n";
	    $from = "From: SamePlace Archie <admin@arnie.sameplace.com>";
	    if (mail($email,$subj,$msg,$from))
		echo '<h4>Check your email for validation instructions!</h4>';
	    else
		echo '<h4>Something went wrong sending mail!</h4>';
	    spAdminEmail('Sent mail to '.$email.' to validate',
	      'New email '.$email);
	} else
	    echo '<h4>Something went wrong creating user!</h4>';
	echo '</body></html>';
	return;
    }
}

if ($gotSome)
    foreach ($errors as $error)
	echo '<br/><font color="red">'.htmlentities($error)."</font>\n";

echo<<<_EOD
<h4>Enter your real name, password, and email to register</h4>
<form action="" method="POST">
<table border="0">
 <tr>
   <td rowspan="3"><img src="images/sp.png"></td>
   <td>Real name:</td>
   <td><input type="text" name="real" value="$real"/></td>
 </tr>
 <tr>
   <td>Password:</td>
   <td><input type="password" name="pass" value="$pass"/></td>
 </tr>
 <tr>
   <td>Email:</td>
   <td><input type="text" name="email" value="$email"/></td>
 </tr>
 <tr>
   <td></td>
   <td><input name="action" type="submit" value="Register"/></td>
 </tr>
</table>
</form>
</body></html>

_EOD;
?>
