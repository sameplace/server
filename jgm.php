<?php

include("json-config.php");

$me = spUser::lookupMe();
if (null == $me) {
    echo json_encode('Error: You do not belong here! 1');
    return;
}

if (empty($_REQUEST['oid'])) {
    echo json_encode('Error: You do not belong here! 2');
    return;
}
$oid = $_REQUEST['oid'];

$md = new spMimeDoc;
if (! $md->inflate($oid)) {
    echo json_encode('Error: You do not belong here! 3');
    return;
}
if (! $me->isAdmin() && $md->m_owner != $me->getOid()) {
    echo json_encode('Error: You do not belong here! 4');
    return;
}

$ret = array();
$ret[] = $md->toJson();

$as = spAttachment::lookupAll($md->getOid());
if (!empty($as) && 0 != count($as))
    foreach ($as as $a)
	$ret[] = $a->toJson();

echo json_encode($ret);

?>
