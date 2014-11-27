<?php

// a695 oid=[participant] r=[role]

require_once 'json-config.php';

$c = jGetParty("r");
if (null == $c)
    return;

if (spParticipant::isValidRole($c->r))
    echo json_encode($c->party->setRole($c->r));
else
    jError();

?>
