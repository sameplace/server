<?php

// a542 oid=[dealspace] d=[doc]

include("json-config.php");

$c = jGetDS("d");
if (null == $c)
    return;

$doc = new spMimeDoc;
if (null == myInflate($ret->me, $doc, $ret->d))
    return;

// already done?
if ($doc->m_deal == $c->ds->getOid())
    return jError();

// hang on to this
$oldDeal = $doc->m_deal;

$doc->setDeal($c->ds->getOid());
spParticipant::reset($ds);

// patch up loser
$dsd = new spDealSpace;
$dsd->inflate($dsd->encode($oldDeal));
spParticipant::reset($dsd);

echo json_encode($c->ds->toJsonUpdated());

?>
