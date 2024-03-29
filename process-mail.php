<?php

include("config.php");

require_once('PHPMailer/PHPMailerAutoload.php');

$fp = fopen("php://stdin","r");
$fn = tempnam(".","assist_");
$tmp = fopen($fn,"w");
while (! feof($fp))
    fwrite($tmp,fread($fp,8192));
fclose($tmp);

function errorOut($str) {
    spAdminEmail($str, 'mail botch');

    global $fn;
    readfile($fn);
    return 0;
}

function setupDemo(&$who) {
    $u = new spUser();
    $u->m_realname = $who['display'];
    $u->m_email = $who['address'];
    if ($u->create()) {
	$u->validate();
	$ds = $u->createDefDealSpace();
	if (empty($ds))
	    return 0;

	$url = 'http://tonic.sameplace.com/'
	  ."demo.php?u=$u->m_oStr";

	$pm = new PHPMailer;
	$pm->isSMTP();
	$pm->Host = "secure.bitway.com";
	$pm->SMTPAuth = true;
	$pm->Username = 'jordan';
	$pm->SMTPSecure = 'tls';
	$pm->Port = 587;

	$pm->From = 'admin@arnie.sameplace.com';
	$pm->FromName = 'Arnie Stumple';
	if ($u->m_email == $u->m_realname)
	    $pm->AddAddress($u->m_email);
	else
	    $pm->AddAddress($u->m_email, $u->m_realname);
	$pm->AddBCC('jordan@sameplace.com');
	$pm->Subject = 'Sameplace Demo Activation';
	$pm->IsHTML(true);
	$pm->Body = "Thanks for trying Sameplace!"
	  .' Click <a href="'.$url.'">here</a> to see what your buyers'
	  .' will see when you use Sameplace for real.';

	$pm->send();

	return $ds->getOid();
    }
    return 0;
}

function findTheDeal(&$headers) {
    // find the deal
    if (! empty($headers['in-reply-to'])) {
	$irt = $headers['in-reply-to'];
	$doc = spMimeDoc::lookupMessageId($irt);
	if ($doc)
	    return $doc->m_deal;
    }

    if (! empty($headers['references'])) {
	$refs = $headers['references'];
	$refp = explode(' ',$refs);
	foreach ($refp as $ref) {
	    $doc = spMimeDoc::lookupMessageId($ref);
	    if ($doc)
		return $doc->m_deal;
	}
    }

    if (! empty($headers['from'])) {
	$from = $headers['from'];
	$whom = mailparse_rfc822_parse_addresses($from);
	if (! empty($whom) && 1 == count($whom)) {
	    $who = $whom[0];
	    if (! empty($who['address'])) {
		$email = $who['address'];
		$owner = spUser::lookupEmail($email);
		if (! empty($owner)) {
		    $ds = $owner->getDefDeal();
		    if (! empty($ds))
			return $ds->getOid();
		}

		/* XXX DEMOMODE */
		$demoMode = true;
		if ($demoMode)
		    $ret = setupDemo($who);
		if (0 != $ret)
		    return $ret;
	    }
	}
    }

    // stick it into the admin account for now
    $admin = spUser::lookupEmail("archie@sameplace.com");
    $ds = $admin->getDefDeal();
    return $ds->getOid();
}

$msg = mailparse_msg_parse_file($fn);
$txt = file_get_contents($fn);
$ms = mailparse_msg_get_structure($msg);

$nDoc = new spMimeDoc;

foreach ($ms as $part) {
    $sect = mailparse_msg_get_part($msg, $part);
    $info = mailparse_msg_get_part_data($sect);

    // does this ever happen?
    $headers = $info['headers'];

    if (empty($nDoc->m_MessageId)) {

	// duplicate?
	$msgId = $headers['message-id'];
	$doc = spMimeDoc::lookupMessageId($msgId);
	if (null != $doc)
	    return errorOut('duplicate message-id: '.$msgId);

	$deal = findTheDeal($headers);
	if (0 == $deal)
	    return errorOut("can't determine deal");

	$ds = new spDealSpace();
	$ds->setOid($deal);
	$ds->inflate();

	$nDoc->m_owner = $ds->m_owner;
	$nDoc->m_deal = $deal;
	$nDoc->m_MessageId = $msgId;

	// from is different
	if (! empty($headers['from'])) {
	    $from = $headers['from'];
	    $whom = mailparse_rfc822_parse_addresses($from);
	    if (! empty($whom) && 1 == count($whom)) {
		$who = $whom[0];
		if (! empty($who['address']))
		    $nDoc->m_FromAddr = $who['address'];
	    }
	}
	if (! $nDoc->create())
	    return errorOut("something went wrong creating a doc");

	// handy duplicates
	$snag = array(
	  "From"	=> "from",
	  "To"		=> "to",
	  "InReplyTo"	=> "in-reply-to",
	  "References"	=> "references",
	  "Subject"	=> "subject",
	  "Cc"		=> "cc",
	  "Date"	=> "date"
	);
	foreach ($snag as $key=>$val)
	    if (! empty($headers[$val]))
		spAttribute::createAttr($nDoc->getOid(), $key, $headers[$val]);
	spParticipant::reset($ds);
    }

    attrArray($nDoc->getOid(), $info, $part);
    if (! empty($headers['content-disposition'])) {
	$cd = $headers['content-disposition'];
	if (startsWith($cd, "attachment") || startsWith($cd, "inline")) {
	    $start = $info['starting-pos-body'];
	    $end = $info['ending-pos-body'];
	    $ntxt = substr($txt, $start, $end - $start);
	    if (! empty($headers['content-transfer-encoding'])) {
		$dtxt = mb_convert_encoding($ntxt, "UTF-8",
		  strtoupper($headers['content-transfer-encoding']));
	    } else
		$dtxt = $ntxt;

	    $a = new spAttachment;
	    $a->m_owner = $nDoc->m_owner;
	    $a->m_mDoc = $nDoc->getOid();
	    $a->m_mType = $headers['content-type'];
	    $cdp = explode(';',$cd);
	    foreach ($cdp as $pt) {
		$pt = trim($pt);
		if (startsWith($pt, "filename")) {
		    $a->m_name = trim(substr($pt,9));
		    if (startsWith($a->m_name, '"') &&
		      endsWith($a->m_name, '"'))
			$a->m_name = substr($a->m_name, 1, -1);
		}
	    }
	    $a->create();
	    $a->setPath(spEncodeAttachmentPath($a->m_oStr));
	    if (false === ($fp = fopen($a->m_path, "w")))
		logIt("fopen('".$a->m_path."'): failed\n");
	    else {
		fwrite($fp, $dtxt);
		fclose($fp);
	    }
	}
    }

    if (! empty($info['starting-pos-body'])) {
	$start = $info['starting-pos-body'];
	$end = $info['ending-pos-body'];
	$key = $part.':body';
	switch ($info['content-type']) {
	case 'text/plain':
	    $dtxt = substr($txt, $start, $end - $start);
	    spAttribute::createAttr($nDoc->getOid(), $key, $dtxt);
	    break;
	case 'text/html':
	    $ntxt = substr($txt, $start, $end - $start);
	    $dtxt = $ntxt;
	    switch ($headers['content-transfer-encoding']) {
	    case '8bit':
		break;
	    case 'base64':
		$dtxt = base64_decode($ntxt);
		break;
	    case 'quoted-printable':
		$dtxt = quoted_printable_decode($ntxt);
		break;
	    }
	    spAttribute::createAttr($nDoc->getOid(), $key, $dtxt);
	    break;
	}
    }
}

function attrArray($o, $a, $pre) {
    foreach ($a as $key=>$val) {
	$k = $pre.':'.$key;
	if (is_array($val))
	    attrArray($o, $val, $k);
	else {
	    if ("content-type" == $key)
		spAttribute::createAttr($o, $k, $val);
	}
    }
}

readfile($fn);
exit(0);

?>
