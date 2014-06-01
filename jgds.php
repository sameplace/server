<?php include("spauth.php"); ?>
<?php
include("config.php");

header('Content-Type: application/json;charset=utf-8;');

$me = spUser::lookupMe();
if (null == $me) {
    echo json_encode('Error: You do not belong here!');
    return;
}

if (empty($_REQUEST['oid']))
    $oid = $me->m_oStr;
else {
    $oid = $_REQUEST['oid'];
    if (! $me->isAdmin() && $oid != $me->m_oStr) {
	echo json_encode('Error: You do not belong here!');
	return;
    }
}

$u = new spUser();
if (! $u->inflate($oid)) {
    echo json_encode("Error: cannot find oid='".$oid."'");
    return;
}

$ds = spDealSpace::lookupAll($u->getOid());
$ret = array();
foreach ($ds as $d)
    $ret[] = $d->toJson();

echo json_encode($ret);

?>
