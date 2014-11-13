<?php

function jError() {
    echo json_encode('Error: You do not belong here!');
    return null;
}

function jGetOid() {
    if (empty($_REQUEST['oid']))
	return jError();
    $ret = new stdClass;
    $ret->me = spUser::lookupMe();
    if (null == $me)
	return jError();
    $ret->oid = $_REQUEST['oid'];
    return $ret;
}

function jValidateObj(&$ret, &$obj, $args) {
    if (! $obj->inflate($ret->oid))
	return jError();
    if (! $ret->me->isAdmin() && $obj->m_owner != $ret->me->getOid())
	return jError();

    $p = explode(',', $args);
    foreach ($p as $arg) {
	if (empty($_REQUEST[$arg]))
	    return jError();
	$ret->$arg = $_REQUEST[$arg];
    }
    return $ret;
}

// get-dealspace by oid, plus args
function jGetDS($args) {
    $ret = jGetOid();
    if (null == $ret)
	return $ret;
    $ret->ds = new spDealSpace;
    return jValidateObj($ret, $ret->ds, $args);
}

// get-mimedoc by oid, plus args
function jGetMD($args) {
    $ret = jGetOid();
    if (null == $ret)
	return $ret;
    $ret->doc = new spMimeDoc;
    return jValidateObj($ret, $ret->doc, $args);
}

?>