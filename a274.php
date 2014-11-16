<?php

// a274 oid=[dealspace] p=[partys]

include("json-config.php");

$c = jGetDS("p");
if (null == $c)
    return;

$which = array();
$parts = explode(',', $c->p);
foreach ($parts as $part)
    $which[$part] = $part;
$which = array_keys($which);
$ret = array();
$ps = spParticipant::lookupAll($c->ds->getOid());
foreach ($ps as $party)
    if (in_array($party->m_oStr, $which))
	$ret[] = $party->toJson();

echo json_encode($ret);

?>
