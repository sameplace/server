<?php

// a274 oid=[dealspace] p=[partys]

require_once 'json-config.php';

$c = jGetDS("p");
if (null == $c)
    return;

$w = jParseOidList($c->p);
if (null == $w)
    return;

$ps = spParticipant::lookupAll($c->ds->getOid());
$ret = jFilterOids($w, $ps);
if (null == $ret)
    return;

echo json_encode($ret);

?>
