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
    if (empty($args))
	return $ret;
    $p = explode(',', $args);
    foreach ($p as $arg) {
	if (! isset($_REQUEST[$arg]))
	    return jError("required arg '".$arg."' missing");
	$ret->$arg = $_REQUEST[$arg];
    }
    return $ret;
}

function jValidateObj(&$ret, &$obj, $args) {
    if (! empty($args))
	if (null == jMergeArgs($ret, $args))
	    return jError();
    if (null != myInflate($ret->me, $obj, $ret->oid))
	return $ret;
    return null;
}

function jParseOidList($ol) {
    if (empty($ol))
	return jError("jParseOidList: required oidlist missing");

    // grab the list, de-duped
    $which = array();
    $parts = explode(',', $ol);
    foreach ($parts as $part)
	$which[$part] = $part;

    // track possible cacheing
    $ret = array();
    foreach ($which as $key=>$val) {
	$wp = explode(':',$key);
	$k = $wp[0]; $v = "";
	switch (count($wp)) {
	case 2:
	    $v = $wp[1];
	    break;
	case 1:
	    break;
	default:
	    return jError("syntax error oid='".$key."'");
	}
	if (isset($ret[$k]))
	    return jError("unsynced duplicate oid=".$k);
	$ret[$k] = $v;
    }

    if (0 == count($ret))
	return jError("jParseOidList: no oids found in '".$ol."'");

    return $ret;
}

function jFilterOids(&$ol, &$oids) {
    $ret = array();
    $keys = array_keys($ol);
    foreach ($oids as $oid)
	if (in_array($oid->m_oStr, $keys)) {
	    if (! empty($ol[$oid->m_oStr])) {
		$mt = spDateToHex($oid->m_mTime);
		$gt = $ol[$oid->m_oStr];
		if ($gt > $mt)
		    return jError("bad cache val: '".$oid->m_oStr.':'.$gt."'");
		if ($mt > $gt)
		    $ret[] = $oid->toJson();
		else
		    $ret[] = $oid->toJsonUpdated();
	    } else
		$ret[] = $oid->toJson();
	}
    return $ret;
}

// get-dealspace by oid, plus args
function jGetDS($args = null) {
    $ret = jGetOid();
    if (null == $ret)
	return $ret;
    $ret->ds = new spDealSpace;
    return jValidateObj($ret, $ret->ds, $args);
}

// get-mimedoc by oid, plus args
function jGetMD($args = null) {
    $ret = jGetOid();
    if (null == $ret)
	return $ret;
    $ret->doc = new spMimeDoc;
    return jValidateObj($ret, $ret->doc, $args);
}

// get-participant by oid, plus args
function jGetParty($args = null) {
    $ret = jGetOid();
    if (null == $ret)
	return $ret;
    $ret->party = new spParticipant;
    return jValidateObj($ret, $ret->party, $args);
}

// get-user by optional oid, plus args
function jGetUser($args = null) {
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
