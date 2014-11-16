<?php

function jError() {
    echo json_encode('Error: You do not belong here!');
    // var_dump(debug_backtrace());
    return null;
}

function jGetOid() {
    if (empty($_REQUEST['oid']))
	return jError();
    $ret = new stdClass;
    $ret->me = spUser::lookupMe();
    if (null == $ret->me)
	return jError();
    $ret->oid = $_REQUEST['oid'];
    return $ret;
}

function myInflate(&$me, &$obj, $oid) {
    if (! $obj->inflate($oid))
	return jError();
    if (! $me->isAdmin() && $obj->m_owner != $me->getOid())
	return jError();
    return $obj;
}

function jValidateObj(&$ret, &$obj, $args = null) {
    if (! empty($args)) {
	$p = explode(',', $args);
	foreach ($p as $arg) {
	    if (! isset($_REQUEST[$arg]))
		return jError();
	    $ret->$arg = $_REQUEST[$arg];
	}
    }

    if (null != myInflate($ret->me, $obj, $ret->oid))
	return $ret;
    return null;
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

// get-participant by oid, plus args
function jGetParty($args) {
    $ret = jGetOid();
    if (null == $ret)
	return $ret;
    $ret->party = new spParticipant;
    return jValidateObj($ret, $ret->party, $args);
}

?>
