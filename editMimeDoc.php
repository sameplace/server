<?php include("spauth.php"); ?>
<?php
include("config.php");
echo "<html>\n<head>\n <title>edit mimedoc</title>\n";
echo ' <link rel="stylesheet" href="style.css" type="text/css">'."\n";
echo "</head><body>\n";

$me = spUser::lookupMe();
if (null == $me) {
    echo 'You do not belong here!</body></html>';
    return;
}

if (empty($_REQUEST['oid'])) {
    echo 'Error: must specify an oid!</body></html>';
    return;
}
$oid = $_REQUEST['oid'];

$doc = new spMimeDoc();
if (! $doc->inflate($oid)) {
    echo "Error: cannot find MimeDoc='".htmlentities($oid)."'</body></html>";
    return;
}

if ($doc->m_owner != $me->getOid() && !$me->isAdmin()) {
    echo "Error: cannot find MimeDoc='".htmlentities($oid)."'</body></html>";
    return;
}

spMenuBar($me);
?>
<form action="createDealSpace.php" method="POST">
Create new DealSpace with this message as anchor:
<input type="text" name="name"/>
<input name="oid" type="hidden" value="<?php echo $oid;?>"/>
<input name="action" type="submit" value="Create"/>
</form>
<?php

echo '<table border="1">';
echo '<tr><td colspan="2" align="center">Headers</td></tr>';
echo '<tr><th>From</th><td>'.htmlentities($doc->m_From).'</td>';
echo '<tr><th>Subject</th><td>'.htmlentities($doc->m_Subject).'</td>';
echo '<tr><th>Date</th><td>'.htmlentities($doc->m_Date).'</td>';

$who = array();
$tag = "m_1:headers:from";
if (!empty($doc->$tag)) {
    $parts = mailparse_rfc822_parse_addresses($doc->$tag);
    foreach ($parts as $key=>$val)
	$who[$val['address']] = $val;
}
$tag = "m_1:headers:to";
if (!empty($doc->$tag)) {
    echo '<tr><th>To</th><td>'.htmlentities($doc->$tag).'</td>';
    $parts = mailparse_rfc822_parse_addresses($doc->$tag);
    foreach ($parts as $key=>$val)
	$who[$val['address']] = $val;
}
$tag = "m_1:headers:cc";
if (!empty($doc->$tag)) {
    echo '<tr><th>Cc</th><td>'.htmlentities($doc->$tag).'</td>';
    $parts = mailparse_rfc822_parse_addresses($doc->$tag);
    foreach ($parts as $key=>$val)
	$who[$val['address']] = $val;
}
unset($who['assist@arnie.sameplace.com']);
echo '<tr><td colspan="2" align="center">Participants</td></tr>';
foreach ($who as $key=>$val) {
    $uStr = htmlentities($key).'&nbsp;'
      .href2('invite.php?email='.rawurlencode($key), '(invite)');
    $u = spUser::lookupEmail($key);
    if ($u) {
	$fn = $val['display'];
	if ($key != $fn)
	    $fn .= ' <'.$key.'>';
	$uStr = href2('profile.php?oid='.$u->m_oStr, htmlentities($fn));
    }
    echo "\n".spTableRow(spTableData($uStr, ' colspan="2"'))."\n";
}

$as = spAttachment::lookupAll($doc->getOid());
if (!empty($as) && 0 != count($as)) {
    echo '<tr><td colspan="2" align="center">Attachments</td></tr>';
    foreach ($as as $a) {
	echo spTableRow(
	  spTableData(href2('dispAttach.php?oid='.$a->m_oStr,
	  htmlentities($a->m_name)), ' colspan="2"'));
    }
}

echo '<tr><td colspan="2" align="center">Content</td></tr>';
$what = "";
$wkey = "";
foreach ($doc as $key=>$val) {
    if (endsWith($key, ":content-type")
      && startsWith($val, "text/plain")) {
	$what = $val;
	$wkey = $key;
    } else if (endsWith($key, ":content-type")
      && startsWith($val, "text/html")) {
	if (empty($what)) {
	    $what = $val;
	    $wkey = $key;
	}
    }
}
if (! empty($what)) {
    $parts = explode(':',$wkey);
    $part = $parts[0];
    $tag = $part.':body';
    $body = $doc->$tag;
    echo spTableRow(spTableData('<pre>'.$body.'</pre>', ' colspan="2"'));

    /*
    if ("text/plain" == $info['content-type']) {
	$start = $info['starting-pos-body'];
	$end = $info['ending-pos-body'];
	echo substr($txt, $start, $end - $start)."\n";
    }
    */
}
echo '</table>';

?>
</body></html>
