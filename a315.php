<?php

// a315 oid=[sentinel] n=[name]

include("json-config.php");

$c = jGetMD("n");
if (null == $c)
    return;

$ds = spDealSpace::lookupOwnerName($c->me->getOid(), $c->n);
if (! empty($ds))
    return jError();

$ds = new spDealSpace;
$ds->m_name = $c->n;
$ds->m_editable = 1;
$ds->m_owner = $c->me->getOid();
$ds->create();

$c->doc->setDeal($ds->getOid());
$which = $c->doc->allMsgIds();

$dsd = $c->me->getDefDeal();
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
spParticipant::reset($dsd);

?>
