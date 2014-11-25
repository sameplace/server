<?php

function spHeaderHack() {
header('Content-Type: application/json;charset=utf-8;');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
}

function spAuthLog($str) {
    $fp = fopen("spauth.log","a+");
    fputs($fp,$str."\n");
    fclose($fp);
}

function spError($str) {
    $dir = "errors/".gmdate("Y/m/d/H/i/s");
    mkdir($dir, 0777, true);
    $fp = fopen($dir."/errors.log","a+");
    fputs($fp,$str."\n");
    if (! empty($_SESSION['email']))
	foreach ($_SESSION as $key=>$val)
	    fputs($fp, '_SESSION['.$key.']='.$val."\n");
    foreach ($_POST as $key=>$val)
	fputs($fp, '_POST['.$key.']='.$val."\n");

    $serv = array("REMOTE_ADDR","HTTP_X_FORWARDED_FOR","REQUEST_URI");
    foreach ($serv as $val)
	if (! empty($_SERVER[$val]))
	    fputs($fp, '_SERVER['.$val.']='.$_SERVER[$val]."\n");
    fclose($fp);
}

function spAdminEmail($body, $subj = null) {
    if (empty($subj))
	$subj = "Sameplace Admin Message";
    $from = "From: Sameplace Archie <admin@arnie.sameplace.com>";
    $to = "admin@arnie.sameplace.com";
    mail($to,$subj,$body."\r\n".gmdate('Y-m-d H:i:s'),$from);
}

function spMenuBar($u) {
    echo spTable(
      spTableRow(
        spTableData(href2('profile.php','Profile')).
	spTableData(href2('logout.php','Logout')).
	spTableData('Logged in as '.$u->m_email)
      ,' border="1"'));
}

function spTrueFalse($bf) {
    return (0 == $bf) ? "false" : "true";
}

function spBold($str) {
    return '<b>'.$str.'</b>';
}

function spItalic($str) {
    return '<b>'.$str.'</b>';
}

function spTable($body, $attr = "") {
    return '<table'.$attr.'>'.$body.'</table>';
}

function spTableRow($body, $attr = "") {
    return '<tr'.$attr.'>'.$body.'</tr>';
}

function spTableData($body = "", $attr = "") {
    return '<td'.$attr.'>'.nbsp($body).'</td>';
}

function spTableHeader($body, $attr = "") {
    return '<th'.$attr.'>'.$body.'</th>';
}

function spInputCheckbox($name, $attr = "") {
    return '<input type="checkbox" name="'.$name.'"'.$attr.'/>';
}

function spInputRadio($name, $checked, $attr = "") {
    if ($checked)
	$attr .= ' checked';
    return '<input type="radio" name="'.$name.'"'.$attr.'/>';
}

function spInputText($name, $attr = "") {
    return '<input type="text" name="'.$name.'"'.$attr.'/>';
}

function spDateToHex($tStamp) {
    $bp = explode(' ', $tStamp);
    $dp = explode('-', $bp[0]);
    $tp = explode(':', $bp[1]);
    $ti = mktime($tp[0], $tp[1], $tp[2], $dp[1], $dp[2], $dp[0]);
    return strtoupper(dechex($ti));
}

?>
