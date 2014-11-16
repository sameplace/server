<?php

// a851 oid=[user] op=[old-password] np=[new-password]

include("json-config.php");

$c = jGetUser("op","np");
if (null == $c)
    return;

if (! password_verify($c->op, $c->user->getPassword()))
    return jError();

$c->user->updatePassword($c->np);

echo json_encode($c->user->toJsonUpdated());

?>
