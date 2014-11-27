<?php

// a851 oid=[user] op=[old-password] np=[new-password]

require_once 'json-config.php';

$c = jGetUser("op,np");
if (null == $c)
    return;

if (! password_verify($c->op, $c->user->getPassword()))
    return jError("password didn't verify");

if (false === password_hash($c->np, PASSWORD_DEFAULT))
    return jError("password didn't hash");

$c->user->updatePassword($c->np);

echo json_encode($c->user->toJsonUpdated());

?>
