<?php include("spauth.php"); ?>
<?php
include("json-config.php");

$me = spUser::lookupMe();
if (null == $me) {
    echo json_encode('Error: You do not belong here!');
    return;
}

if (empty($_REQUEST['oid']))
    $ds = $u->getDefDeal();
else {
    $oid = $_REQUEST['oid'];
    $ds = new spDealSpace;
    if (! $ds->inflate($oid)) {
	echo json_encode('Error: You do not belong here!');
	return;
    }
    if (! $me->isAdmin() && $ds->m_owner != $me->getOid()) {
	echo json_encode('Error: You do not belong here!');
	return;
    }
}

$ret = array();
$docs = spMimeDoc::lookupAll($ds->getOid());
foreach ($docs as $d)
    $ret[] = $d->toJson();

echo json_encode($ret);

?>
