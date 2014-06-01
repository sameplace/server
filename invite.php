<?php include("spauth.php"); ?>
<?php
include("config.php");
echo "<html>\n<head>\n <title>invite</title>\n";
echo ' <link rel="stylesheet" href="style.css" type="text/css">'."\n";
echo "</head><body>\n";

$me = spUser::lookupMe();
if (null == $me) {
    echo 'You do not belong here!</body></html>';
    return;
}

function showForm() {
    echo '<form action="" method="POST">';
    echo '<input type="text" name="email"/>';
    echo '<input name="action" type="submit" value="Invite"/>';
    echo '</form></body></html>';
    return;
}

if (empty($_REQUEST['email']))
    return showForm();

$email = $_REQUEST['email'];
$aValid = array('-','.','@','+');
if (! ctype_alnum(str_replace($aValid,'',$email))) {
    echo "Simplify the email address please";
    return showForm();
}

$subj = 'Please create your Sameplace account';
$msg = $me->m_realname.' <'.$me->m_email."> is inviting you to Sameplace!\r\n"
."\r\nClick this link to register for your SamePlace account\r\n"
."\r\nhttps://secure.bitway.com/sp/register.php\r\n";
$from = "From: Arnie Stumple <admin@arnie.sameplace.com>";
if (mail($email,$subj,$msg,$from))
    echo '<h4>Email has been sent!</h4>';
else
    echo '<h4>Something went wrong sending mail!</h4>';

?>
</body></html>
