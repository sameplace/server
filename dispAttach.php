<?php include("spauth.php"); ?>
<?php
include("config.php");

function showError($str) {
    echo "<html>\n<head>\n <title>display attachment</title>\n";
    echo ' <link rel="stylesheet" href="style.css" type="text/css">'."\n";
    echo "</head><body>\n";
    echo $str."</body></html>";
    return false;
}

$me = spUser::lookupMe();
if (null == $me)
    return showError('You do not belong here!');

if (empty($_REQUEST['oid']))
    return showError('Error: must specify an oid!');
$oStr = $_REQUEST['oid'];

$a = new spAttachment();
if (! $a->inflate($oStr))
    return showError("Error: cannot find Attachment='".htmlentities($oStr));

$doc = new spMimeDoc();
$doc->getOid() = $a->m_mDoc;
if (! $doc->inflate())
    return showError("Error: cannot find Attachment='".htmlentities($oStr));

if ($doc->m_owner != $me->getOid() && !$me->isAdmin())
    return showError("Error: cannot find Attachment='".htmlentities($oStr));

header('Content-Type: '.$a->m_mType);
header('Content-Disposition: attachment; filename="'.$a->m_name.'"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Content-Length: '.filesize($a->m_path));
flush();
readfile($a->m_path);
?>
