<?php include("spauth.php"); ?>
<?php
include("config.php");
echo "<html>\n<head>\n <title>create dealspace</title>\n";
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

if (empty($_REQUEST['name'])) {
    echo 'Error: must specify a name for the DealSpace!</body></html>';
    return;
}
$name = $_REQUEST['name'];

$ds = spDealSpace::lookupOwnerName($me->getOid(), $name);
if (! empty($ds)) {
    echo 'Error: a DealSpace named "'.htmlentities($name)
      .'" already exists!</body></html>';
    return;
}

$ds = new spDealSpace;
$ds->m_name = $name;
$ds->m_editable = 1;
$ds->m_owner = $me->getOid();
$ds->create();

$doc->setDeal($ds->getOid());
$which = $doc->allMsgIds();

$dsd = $me->getDefDeal();
$docs = spMimeDoc::lookupAll($dsd->getOid());
$move = array();
foreach ($docs as $d) {
    $some = $d->allMsgIds();
    foreach ($some as $id)
	if (in_array($id, $which)) {
	    $move[$d->getOid()] = $d->getOid();
	    continue;
	}
}
spMimeDoc::moveToDeal(array_keys($move), $ds->getOid());
spParticipant::reset($ds);

echo 'Moved '.(1+count($move)).' messages to '
  .href2('editDealSpace.php?oid='.$ds->m_oStr, 'DealSpace named "'
  .htmlentities($ds->m_name).'"');

?>
</body></html>
