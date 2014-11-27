<?php

// a923 oid=[dealspace] n=[new name]

require_once 'json-config.php';

$c = jGetDS("n");
if (null == $c)
    return;

if ($c->ds->changeName($c->n))
    echo json_encode($c->ds->toJsonUpdated());
else
    return jError();

?>
