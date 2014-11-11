<?php

// a923 oid=[dealspace] n=[new name]

include("json-config.php");

$me = spUser::lookupMe();
if (null == $me) {
    echo json_encode('Error: You do not belong here!');
    return;
}

if (empty($_REQUEST['n'])) {
    echo json_encode('Error: syntax!');
    return;
}
$n = $_REQUEST['n'];

if (empty($_REQUEST['oid'])) {
    echo json_encode('Error: syntax!');
    return;
}

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

if ($ds->changeName($n))
    echo json_encode($ds->toJsonUpdated());
else
    echo json_encode('Error: You do not belong here!');

?>
