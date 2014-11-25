<?php

function jError($str = null) {
    echo json_encode('Error: You do not belong here!');
    if (empty($_SERVER['REMOTE_ADDR'])) {
	if (! empty($str))
	    echo 'Error: '.$str."\n";
	var_dump(debug_backtrace());
    } else if (! empty($str))
	spError($str);
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

function jMergeArgs(&$ret, $args) {
    $p = explode(',', $args);
    foreach ($p as $arg) {
	if (! isset($_REQUEST[$arg]))
	    return jError();
	$ret->$arg = $_REQUEST[$arg];
    }
    return $ret;
}

function jValidateObj(&$ret, &$obj, $args = null) {
    if (! empty($args))
	if (null == jMergeArgs($ret, $args))
	    return jError();
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

// get-user by optional oid, plus args
function jGetUser($args) {
    $ret = new stdClass;
    $ret->user = spUser::lookupMe();
    if (empty($_REQUEST['oid'])) {
	if (null == $ret->user)
	    return jError();
	return jMergeArgs($ret, $args);
    }
    $me = $ret->user->m_oStr;
    $isAdmin = $ret->user->isAdmin();
    $ret->oid = $_REQUEST['oid'];
    if (! $ret->user->inflate($ret->oid))
	return jError();
    if (! $isAdmin && $me != $ret->oid)
	return jError();
    return jMergeArgs($ret, $args);
}

?>
