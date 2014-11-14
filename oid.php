<?php

class spOid {
    protected $m_oid;	// real
    public function getOid() { return $this->m_oid; }
    public function setOid($o) { $this->m_oid=$o; }
    protected $m_nonce;

    public $m_oStr;	// encoded
    public $m_oType;
    public $m_owner;

    public function toJson() {
	return array(
	  "oid"		=> $this->m_oStr,
	  "type"	=> $this->oTypeStr($this->m_oType),
	  "owner"	=> $this->encode($this->m_owner),
	  "cTime"	=> $this->m_cTime,
	  "mTime"	=> $this->m_mTime);
    }

    public function toJsonUpdated() {
	return array(
	  "oid"		=> $this->m_oStr,
	  "mTime"	=> $this->m_mTime);
    }

    // internal stuff
    protected static $oMax;
    protected static $oMap;
    protected static $oTypeMap1, $oTypeMap2;

    // see gen-oid-map.php
    protected static $oStrMap = array(
      "0123456789abcdefghijklmnopqrstuvwxyz",	// source
      "hbwnem2y6EtoXaVZklRzsNOWA0HgT4C3dQFq",
      "82YUqljBOAWLgm1MG3u9aFwPtJCvy.KVTI4D",
      "AmUMPuOgdFiQ3H.B6VYN4eZqroa97XDKTbSR",
      "psfqC2jbMD14udSOBZ9zNJHlcIKXyoaek5t3",
      "KmUnedx3R.EoDHwMWAL2XZGcq9SPvBspt06V",
      "7TgwraxXFv.COIyQ1RefqNspjD3bPVzG28Sm",
      "wjFQfdNalZpo7XuL.xBntTP4VvYGOg1e30zb",
      "YlOZ9teczM06bkQWuN2aP3opGCTqi7fvmHEg",
      "ET5pW4Cl2RYFMLXfDPIt3xNgSz8B.VQbKniH",
    );

    public function __construct() {
	// by default archie owns everything
	$this->m_owner = 1;

	// auto-init
	if (isset(self::$oMap))
	    return;

	self::$oMax = base_convert("zzzzzzzzz",36,10);
	self::$oMap = array();
	for ($i = 0; $i < 10; $i++)
	    self::$oMap[$i] = str_split(self::$oStrMap[$i]);

	$spdb = spGetDB();

	$q = "SELECT * FROM OidType";
	$s = queryOrDie($spdb,$q);
	self::$oTypeMap1 = array();
	self::$oTypeMap2 = array();
	while ($r = $s->fetch(PDO::FETCH_ASSOC)) {
	    self::$oTypeMap1[$r['oTypeID']] = $r['oKind'];
	    self::$oTypeMap2[$r['oKind']] = $r['oTypeID'];
	}
    }

    public static function oTypeStr($id) {
	if (! empty(self::$oTypeMap1[$id]))
	    return self::$oTypeMap1[$id];
	return "[unk:".$id."]";
    }

    protected function inflateHelper($q, $v) {
	$s = queryOrDie(spGetDB(), $q, $v);
	while ($r = $s->fetch(PDO::FETCH_ASSOC)) {
	    foreach ($r as $key=>$val) {
		$tag = "m_".$key;
		$this->$tag = $val;
	    }
	    $this->mergeAttrs();
	    return true;
	}
	return false;
    }

    public function inflate($oStr = null) {
	if (! empty($oStr))
	    return $this->inflateHelper("SELECT * FROM Oid WHERE oStr=?",
	      array($oStr));
	return $this->inflateHelper("SELECT * FROM Oid WHERE oid=?",
	  array($this->getOid()));
    }

    public static function zPad($str) { return substr("000000000".$str,-9); }

    public function encode($oid) {
	if ($oid >= self::$oMax)
	    throw new Exception("encode: oid $oid too big!");
	$b1 = base_convert($oid,10,36);
	$b2 = $this->zPad($b1);
	$b3 = "";
	$p = str_split($b2);
	foreach ($p as $k=>$c) {
	    $ref = self::$oMap[$k+1];
	    $i = array_search($c,self::$oMap[0]);
	    $b3 .= $ref[$i];
	}
	return $b3;
    }

    protected function allocate() {
	if (empty(self::$oTypeMap1[$this->m_oType]))
	    throw new Exception("$this->m_oType not valid type");

	for (;;) {
	    $spdb = spGetDB();
	    $nonce = mt_rand(1,self::$oMax);
	    $this->setOid(mt_rand(1,self::$oMax));
	    $this->m_oStr = $this->encode($this->getOid());
	    $q = "INSERT INTO Oid (oStr,owner,cTime,mTime,nonce,oType)"
	      ." VALUES (?,?,?,?,?,?)";
	    $v = array();
	    $v[] = $this->m_oStr;
	    $v[] = $this->m_owner;
	    $tStamp = gmdate('Y-m-d H:i:s');
	    $v[] = $tStamp;
	    $v[] = $tStamp;
	    $v[] = $nonce;
	    $v[] = $this->m_oType;
	    executeOrDie($spdb,$q,$v);
	    // make sure it worked
	    $q = "SELECT * from Oid WHERE nonce=?";
	    $v = array($nonce);
	    $s = queryOrDie($spdb,$q,$v);
	    $worked = false;
	    while ($r = $s->fetch(PDO::FETCH_ASSOC))
		if ($nonce == $r['nonce'] && $this->m_oStr == $r['oStr']) {
		    $this->setOid($r['oid']);
		    $worked = true;
		}
	    if ($worked)
		break;
	}
    }
    public function remove() {
	$q = "DELETE FROM Oid WHERE oid=?";
	$v = array($this->getOid());
	executeOrDie(spGetDB(), $q, $v);
    }

    public function updateMTime() {
	$q = "UPDATE Oid SET mTime=? WHERE Oid=?";
	$v = array();
	$v[] = gmdate('Y-m-d H:i:s');
	$v[] = $this->getOid();
	executeOrDie(spGetDB(), $q, $v);
    }

    public static function updateMTimes($oids) {
	if (empty($oids) || 0 == count($oids))
	    return;
	$q = "UPDATE Oid SET mTime=? WHERE Oid IN (";
	$v = array_merge(array(gmdate('Y-m-d H:i:s')), $oids);
	prepArray($q, $oids);
	executeOrDie(spGetDB(), $q, $v);
    }

    public function mergeAttrs() {
	$q = "SELECT * FROM Attribute WHERE referant=?";
	$v = array($this->getOid());
	$s = queryOrDie(spGetDB(), $q, $v);
	while ($r = $s->fetch(PDO::FETCH_ASSOC)) {
	    $key = $r['aKey'];
	    $val = $r['aValue'];
	    $tag = "m_".$key;
	    $this->$tag = $val;
	}
    }
};

class spUser extends spOid {
    public function toJson() {
	$me = array(
	  "realname"	=> $this->m_realname,
	  "email"	=> $this->m_email,
	  "question"	=> $this->m_question);
	return array_merge(parent::toJson(), $me);
    }

    protected $m_password;
    public function getPassword() { return $this->m_password; }
    public function setPassword($p) { $this->m_password = $p; }

    protected $m_validated;
    public function isValidated() { return $this->m_validated; }

    protected $m_badpass;
    public function getBadpass() { return $this->m_badpass; }

    protected $m_isadmin;
    public function isAdmin() { return $this->m_isadmin; }

    protected $m_locked;
    public function isLocked() { return $this->m_locked; }

    public function __construct() {
	parent::__construct();
	$this->m_oType = self::$oTypeMap2['user'];

	// defaults
	$this->m_question = "";
	$this->m_answer = "";
	$this->m_validated = 0;
	$this->m_isadmin = 0;
	$this->m_locked = 0;
	$this->m_badpass = 0;
    }
    public function inflate($oStr = null) {
	if (! parent::inflate($oStr))
	    return false;
	$q = "SELECT * FROM User WHERE oid=?";
	$v = array($this->getOid());
	$s = queryOrDie(spGetDB(), $q, $v);
	while ($r = $s->fetch(PDO::FETCH_ASSOC)) {
	    foreach ($r as $key=>$val) {
		$tag = "m_".$key;
		$this->$tag = $val;
	    }
	    return true;
	}
	return false;
    }
    public function create() {
	parent::allocate();
	$q = "INSERT INTO User"
	  ." (oid,realname,email,password,question,answer"
	  .",isadmin,validated,locked,badpass)"
	  ." VALUES (?,?,?,?,?,?,?,?,?,?)";
	$v = array();
	$v[] = $this->getOid();
	$v[] = $this->m_realname;
	$v[] = $this->m_email;
	$v[] = password_hash($this->getPassword(),PASSWORD_DEFAULT);

	$v[] = $this->m_question;	// blank by default
	$v[] = $this->m_answer;		// blank by default
	$v[] = $this->m_isadmin;	// not admin by default
	$v[] = $this->m_validated;	// not validated
	$v[] = $this->m_locked;		// not locked
	$v[] = $this->m_badpass;	// no bad passwords yet
	return executeDoNotDie(spGetDB(), $q, $v);
    }
    public function createDefDealSpace() {
	$ds = $this->getDefDeal();
	if (empty($ds)) {
	    global $defDealSpace;
	    $ds = new spDealSpace;
	    $ds->m_name = $defDealSpace;
	    $ds->m_editable = 0;
	    $ds->m_owner = $this->getOid();
	    $ds->create();
	}
	return $ds;
    }
    public static function lookupAll() {
	$ret = array();
	$q = "SELECT * FROM Oid o, User u WHERE o.oType=? AND o.oid=u.oid";
	$v = array(self::$oTypeMap2['user']);
	$s = queryOrDie(spGetDB(), $q, $v);
	while ($r = $s->fetch(PDO::FETCH_ASSOC)) {
	    $u = new spUser();
	    foreach ($r as $key=>$val) {
		$tag = "m_".$key;
		$u->$tag = $val;
	    }
	    $u->mergeAttrs();
	    $ret[] = $u;
	}
	return $ret;
    }
    public static function lookupMe() {
	if (empty($_SESSION['email']))
	    return null;
	return spUser::lookupEmail($_SESSION['email']);
    }
    public static function lookupEmail($e) {
	$ret = null;
	$q = "SELECT * FROM Oid o, User u WHERE u.email=? AND o.oid=u.oid";
	$v = array($e);
	$s = queryOrDie(spGetDB(), $q, $v);
	while ($r = $s->fetch(PDO::FETCH_ASSOC)) {
	    $ret = new spUser();
	    foreach ($r as $key=>$val) {
		$tag = "m_".$key;
		$ret->$tag = $val;
	    }
	}
	if (null == $ret) {
	    $a = spAddress::Lookup($e);
	    if (null != $a) {
		$u = new spUser;
		$u->setOid($a->m_owner);
		if ($u->inflate())
		    $ret = $u;
	    }
	}
	return $ret;
    }
    public function badPass() {
	$this->m_badpass += 1;
	$q = "UPDATE User SET badpass=?";
	$v = array($this->m_badpass);
	if (3 <= $this->m_badpass) {
	    $this->m_locked = 1;
	    $q .= ",locked=?";
	    $v[] = $this->m_locked;
	}
	$q .= " WHERE Oid=?";
	$v[] = $this->getOid();
	executeOrDie(spGetDB(), $q, $v);
	$this->updateMTime();
    }
    public function validate() {
	if (0 != $this->m_validated && 0 == $this->m_badpass)
	    return;
	$this->m_badpass = 0;
	$this->m_validated = 1;
	$q = "UPDATE User SET badpass=?,validated=?";
	$v = array($this->m_badpass, $this->m_validated);
	$q .= " WHERE Oid=?";
	$v[] = $this->getOid();
	executeOrDie(spGetDB(), $q, $v);
	$this->updateMTime();
    }
    public function getDefDeal() {
	global $defDealSpace;

	return spDealSpace::lookupOwnerName($this->getOid(), $defDealSpace);
    }
};

class spAddress extends spOid {
    public function toJson() {
	$me = array(
	  "email"	=> $this->m_email);
	return array_merge(parent::toJson(), $me);
    }
    public function __construct() {
	parent::__construct();
	$this->m_oType = self::$oTypeMap2['address'];
    }
    public function inflate($oStr = null) {
	if (! parent::inflate($oStr))
	    return false;
	$q = "SELECT * FROM Address WHERE oid=?";
	$v = array($this->getOid());
	$s = queryOrDie(spGetDB(), $q, $v);
	while ($r = $s->fetch(PDO::FETCH_ASSOC)) {
	    foreach ($r as $key=>$val) {
		$tag = "m_".$key;
		$this->$tag = $val;
	    }
	    return true;
	}
	return false;
    }
    public static function lookup($a) {
	$q = "SELECT * FROM Oid o, Address a WHERE o.oType=? AND o.oid=a.oid";
	$v = array(self::$oTypeMap2['address']);
	$s = queryOrDie(spGetDB(), $q, $v);
	$ret = null;
	while ($r = $s->fetch(PDO::FETCH_ASSOC)) {
	    $obj = new spAddress();
	    foreach ($r as $key=>$val) {
		$tag = "m_".$key;
		$obj->$tag = $val;
	    }
	    $ret = $obj;
	}
	return $ret;
    }
};

class spAttribute {
    public function __construct() {
	$this->m_aKey = "";
	$this->m_aValue = "";
    }
    public function create() {
	$q = "INSERT INTO Attribute"
	  ." (referant,aKey,aValue)"
	  ." VALUES (?,?,?)";
	$v = array();
	$v[] = $this->m_referant;
	$v[] = $this->m_aKey;
	$v[] = $this->m_aValue;
	return executeDoNotDie(spGetDB(), $q, $v);
    }
    public static function lookupAll($o = null, $r = null) {
	$ret = array();
	$q = "SELECT * FROM Attribute";
	if (!empty($o)) {
	    $q .= " WHERE referant IN (SELECT oid FROM Oid WHERE owner=?)";
	    $v[] = $o;
	}
	if (!empty($r)) {
	    $q .= " AND referant=?";
	    $v[] = $r;
	}
	$q .= " ORDER BY referant ASC";
	$s = queryOrDie(spGetDB(), $q, $v);
	while ($r = $s->fetch(PDO::FETCH_ASSOC)) {
	    $a = new spAttribute();
	    foreach ($r as $key=>$val) {
		$tag = "m_".$key;
		$a->$tag = $val;
	    }
	    $ret[] = $a;
	}
	return $ret;
    }
    public static function createAttr($o, $k, $v) {
	$a = new spAttribute;
	$a->m_referant = $o;
	$a->m_aKey = $k;
	$a->m_aValue = $v;
	return $a->create();
    }
};

class spDealSpace extends spOid {
    public function toJson() {
	$me = array(
	  "name"	=> $this->m_name);
	$ps = spParticipant::lookupAll($this->getOid());
	if (0 != count($ps)) {
	    $ja = array();
	    foreach ($ps as $p) {
		$tag = "party".count($ja);
		$ja[$tag] = $p->m_oStr;
	    }
	    $me = array_merge($me, $ja);
	}
	return array_merge(parent::toJson(), $me);
    }
    public function __construct() {
	parent::__construct();
	$this->m_oType = self::$oTypeMap2['dealspace'];
	$this->m_editable = 1;
	$this->m_hidden = 0;
    }
    public function inflate($oStr = null) {
	if (! parent::inflate($oStr))
	    return false;
	$q = "SELECT * FROM DealSpace WHERE oid=?";
	$v = array($this->getOid());
	return $this->inflateHelper($q, $v);
    }
    public function create() {
	parent::allocate();
	$q = "INSERT INTO DealSpace"
	  ." (oid,editable,hidden,name)"
	  ." VALUES (?,?,?,?)";
	$v = array();
	$v[] = $this->getOid();
	$v[] = $this->m_editable;
	$v[] = $this->m_hidden;
	$v[] = $this->m_name;
	return executeDoNotDie(spGetDB(), $q, $v);
    }
    public static function lookupAll($o = null) {
	$ret = array();
	$q = "SELECT * FROM Oid o, DealSpace d WHERE o.oType=? AND o.oid=d.oid";
	$v = array(self::$oTypeMap2['dealspace']);
	if (! empty($o)) {
	    $q .= " AND o.owner=?";
	    $v[] = $o;
	}
	$s = queryOrDie(spGetDB(), $q, $v);
	while ($r = $s->fetch(PDO::FETCH_ASSOC)) {
	    $u = new spDealSpace();
	    foreach ($r as $key=>$val) {
		$tag = "m_".$key;
		$u->$tag = $val;
	    }
	    $u->mergeAttrs();
	    $ret[] = $u;
	}
	return $ret;
    }
    public static function lookupOwnerName($o, $n) {
	$ret = null;
	$q = "SELECT * FROM DealSpace d, Oid o"
	  . " WHERE o.owner=? AND d.name=? AND d.oid=o.oid";
	$v = array($o, $n);
	$s = queryOrDie(spGetDB(), $q, $v);
	while ($r = $s->fetch(PDO::FETCH_ASSOC)) {
	    $ret = new spDealSpace();
	    foreach ($r as $key=>$val) {
		$tag = "m_".$key;
		$ret->$tag = $val;
	    }
	    $ret->mergeAttrs();
	}
	return $ret;
    }
    public function changeName($n) {
	// can't set it to null
	if (empty($n))
	    return false;

	// already set
	if ($n == $this->m_name)
	    return false;

	// can't change default name
	global $defDealSpace;

	if ($defDealSpace == $this->m_name)
	    return false;

	// can't change name to magic token
	if ($defDealSpace == $n)
	    return false;

	// make sure we don't already have one with this name
	$db = spGetDB();
	$q = "SELECT * FROM DealSpace WHERE name=? AND oid IN"
	  ." (SELECT oid FROM Oid WHERE owner=? AND oType=?)";
	$v = array($n, $this->m_owner, self::$oTypeMap2['dealspace']);
	$s = queryOrDie($db, $q, $v);
	$found = false;
	while ($r = $s->fetch(PDO::FETCH_ASSOC))
	    $found = true;
	if ($found)
	    return false;

	// do it in a transaction
	beginTranOrDie($db);
	$q = "UPDATE DealSpace SET name=? WHERE oid=?";
	$v = array($n, $this->getOid());
	executeDoNotDie($db, $q, $v);
	$this->updateMTime();
	commitOrDie($db);

	return true;
    }
};

class spMimeDoc extends spOid {
    public function toJson() {
	$me = array(
	  "MessageId"	=> $this->m_MessageId,
	  "Deal"	=> $this->encode($this->m_deal),
	  "FromAddr"	=> $this->m_FromAddr,
	  "Date"	=> $this->m_Date,
	  "Subject"	=> $this->m_Subject,
	  "InReplyTo"	=> $this->m_InReplyTo,
	  "References"	=> $this->m_References);
	if (! empty($this->m_To))
	    $me['To'] = $this->m_To;
	if (! empty($this->m_Cc))
	    $me['Cc'] = $this->m_Cc;

	// XXX find content
	$what = "";
	$wkey = "";
	foreach ($this as $key=>$val) {
	    if (endsWith($key, ":content-type")
	      && startsWith($val, "text/plain")) {
		$what = $val;
		$wkey = $key;
	    } else if (endsWith($key, ":content-type")
	      && startsWith($val, "text/html")) {
		if (empty($what)) {
		    $what = $val;
		    $wkey = $key;
		}
	    }
	}
	if (! empty($what)) {
	    $parts = explode(':',$wkey);
	    $part = $parts[0];
	    $tag = $part.':body';
	    $body = $this->$tag;
	    $me = array_merge($me, array("Content"=>utf8_encode($body)));
	}

	return array_merge(parent::toJson(), $me);
    }
    public function __construct() {
	parent::__construct();
	$this->m_oType = self::$oTypeMap2['mimedoc'];
	$this->m_MessageId = "";
	$this->m_hidden = 0;
	$this->m_private = 1;
	$this->m_FromAddr = "";

	// attributes
	$this->m_From = "";
	$this->m_Subject = "";
	$this->m_Date = "";
	$this->m_InReplyTo = "";
	$this->m_References = "";
    }
    public function inflate($oStr = null) {
	if (! parent::inflate($oStr))
	    return false;
	$q = "SELECT * FROM MimeDoc WHERE oid=?";
	$v = array($this->getOid());
	return $this->inflateHelper($q, $v);
    }
    public function create() {
	parent::allocate();
	$q = "INSERT INTO MimeDoc"
	  ." (oid,deal,MessageId,FromAddr,hidden,private)"
	  ." VALUES (?,?,?,?,?,?)";
	$v = array();
	$v[] = $this->getOid();
	$v[] = $this->m_deal;
	$v[] = $this->m_MessageId;
	$v[] = $this->m_FromAddr;
	$v[] = $this->m_hidden;
	$v[] = $this->m_private;
	return executeDoNotDie(spGetDB(), $q, $v);
    }
    public function setDeal($did) {
	$db = spGetDB();
	beginTranOrDie($db);
	$q = "UPDATE MimeDoc SET deal=? WHERE oid=?";
	$v = array($did, $this->getOid());
	executeDoNotDie(spGetDB(), $q, $v);
	$this->updateMTime();
	commitOrDie($db);
    }
    public function allMsgIds() {
	$ret = array();
	if (! empty($this->m_MessageId))
	    $ret[] = $this->m_MessageId;
	if (! empty($this->m_InReplyTo))
	    $ret[] = $this->m_InReplyTo;
	if (! empty($this->m_References)) {
	    $refs = explode(' ',$this->m_References);
	    $ret = array_merge($ret, $refs);
	}
	return $ret;
    }
    public static function moveToDeal($docIds, $deal, $owner = -1) {
	if (0 == count($docIds))
	    return;
	$db = spGetDB();
	beginTranOrDie($db);
	$q = "UPDATE MimeDoc SET deal=? WHERE oid IN (";
	prepArray($q, $docIds);
	$v = array_merge(array($deal), $docIds);
	executeDoNotDie($db, $q, $v);
	// optionally change the owner
	if (-1 != $owner) {
	    $q = "UPDATE Oid SET owner=? WHERE oid IN (";
	    prepArray($q, $docIds);
	    $v = array_merge(array($owner), $docIds);
	    executeDoNotDie($db, $q, $v);
	}
	spOid::updateMTimes($docIds);
	commitOrDie($db);
    }
    public static function lookupAll($d = null, $from = null) {
	$ret = array();
	$q = "SELECT * FROM Oid o, MimeDoc md WHERE o.oType=?"
	  ." AND o.oid=md.oid";
	$v = array(self::$oTypeMap2['mimedoc']);
	if (! empty($d)) {
	    $q .= " AND md.deal=?";
	    $v[] = $d;
	}
	if (! empty($from)) {
	    $q .= " AND md.FromAddr=?";
	    $v[] = $from;
	}
	$q .= " ORDER BY oid ASC";
	$s = queryOrDie(spGetDB(), $q, $v);
	while ($r = $s->fetch(PDO::FETCH_ASSOC)) {
	    $d = new spMimeDoc();
	    foreach ($r as $key=>$val) {
		$tag = "m_".$key;
		$d->$tag = $val;
	    }
	    $d->mergeAttrs();
	    $ret[] = $d;
	}
	return $ret;
    }
    public static function lookupMessageId($mid) {
	$q = "SELECT * FROM Oid o, MimeDoc m WHERE m.MessageId=?"
	  ." AND o.oid=m.oid";
	$v = array($mid);
	$s = queryOrDie(spGetDB(), $q, $v);
	$ret = null;
	while ($r = $s->fetch(PDO::FETCH_ASSOC)) {
	    $ret = new spMimeDoc();
	    foreach ($r as $key=>$val) {
		$tag = "m_".$key;
		$ret->$tag = $val;
	    }
	    $ret->mergeAttrs();
	}
	return $ret;
    }
};

class spAttachment extends spOid {
    public function toJson() {
	$me = array(
	  "MimeType"	=> $this->m_mType,
	  "Name"	=> $this->m_name);
	return array_merge(parent::toJson(), $me);
    }
    public function __construct() {
	parent::__construct();
	$this->m_oType = self::$oTypeMap2['attachment'];
	$this->m_mType = "";
	$this->m_name = "";
	$this->m_path = "";
    }
    public function inflate($oStr = null) {
	if (! parent::inflate($oStr))
	    return false;
	$q = "SELECT * FROM Attachment WHERE oid=?";
	$v = array($this->getOid());
	return $this->inflateHelper($q, $v);
    }
    public function create() {
	parent::allocate();
	$q = "INSERT INTO Attachment"
	  ." (oid,mDoc,mType,name,path)"
	  ." VALUES (?,?,?,?,?)";
	$v = array();
	$v[] = $this->getOid();
	$v[] = $this->m_mDoc;
	$v[] = $this->m_mType;
	$v[] = $this->m_name;
	$v[] = $this->m_path;
	return executeDoNotDie(spGetDB(), $q, $v);
    }
    public static function lookupAll($md) {
	$ret = array();
	$q = "SELECT * FROM Oid o, Attachment a WHERE o.oType=?"
	  ." AND a.mDoc=? AND o.oid=a.oid";
	$v = array(self::$oTypeMap2['attachment'], $md);
	$s = queryOrDie(spGetDB(), $q, $v);
	while ($r = $s->fetch(PDO::FETCH_ASSOC)) {
	    $a = new spAttachment();
	    foreach ($r as $key=>$val) {
		$tag = "m_".$key;
		$a->$tag = $val;
	    }
	    $a->mergeAttrs();
	    $ret[] = $a;
	}
	return $ret;
    }
    public function setPath($p) {
	$db = spGetDB();
	beginTranOrDie($db);
	$q = "UPDATE Attachment SET path=? WHERE oid=?";
	$v = array($p, $this->getOid());
	executeDoNotDie(spGetDB(), $q, $v);
	$this->updateMTime();
	commitOrDie($db);
	$this->m_path = $p;
    }
};

class spParticipant extends spOid {
    public function toJson() {
	$me = array(
	  "Addr"	=> $this->m_Addr,
	  "Role"	=> $this->m_Role);
	if (! empty($this->m_Name))
	    $me["Name"] = $this->m_Name;
	return array_merge(parent::toJson(), $me);
    }
    public function __construct() {
	parent::__construct();
	$this->m_oType = self::$oTypeMap2['participant'];
	$this->m_Addr = "";
	$this->m_Role = 0;
    }
    public function inflate($oStr = null) {
	if (! parent::inflate($oStr))
	    return false;
	$q = "SELECT * FROM Participant WHERE oid=?";
	$v = array($this->getOid());
	return $this->inflateHelper($q, $v);
    }
    public function create() {
	parent::allocate();
	$q = "INSERT INTO Participant"
	  ." (oid,deal,Addr,Name,Role)"
	  ." VALUES (?,?,?,?,?)";
	$v = array();
	$v[] = $this->getOid();
	$v[] = $this->m_deal;
	$v[] = $this->m_Addr;
	$v[] = $this->m_Name;
	$v[] = $this->m_Role;
	return executeDoNotDie(spGetDB(), $q, $v);
    }
    public static function lookupAll($md) {
	$ret = array();
	$q = "SELECT * FROM Oid o, Participant p WHERE o.oType=?"
	  ." AND p.deal=? AND o.oid=p.oid";
	$v = array(self::$oTypeMap2['participant'], $md);
	$s = queryOrDie(spGetDB(), $q, $v);
	while ($r = $s->fetch(PDO::FETCH_ASSOC)) {
	    $p = new spParticipant();
	    foreach ($r as $key=>$val) {
		$tag = "m_".$key;
		$p->$tag = $val;
	    }
	    $p->mergeAttrs();
	    $ret[$p->m_Addr] = $p;
	}
	return $ret;
    }
    public static function reset(&$md) {
	// what's the list look like so far?
	$cur = spParticipant::lookupAll($md->getOid());
	// grab all the docs, make a list
	$ps = array();
	$docs = spMimeDoc::lookupAll($md->getOid());
	foreach ($docs as $doc) {
	    $which = array("From","To","Cc");
	    foreach ($which as $h) {
		$tag = "m_".$h;
		if (empty($doc->$tag))
		    continue;
		$hdr = $doc->$tag;
		$whom = mailparse_rfc822_parse_addresses($hdr);
		foreach ($whom as $who) {
		    if (empty($who['address']))
			continue;
		    $addr = $who['address'];
		    if ("assist@arnie.sameplace.com" == $addr)
			continue;
		    $party = new stdClass;
		    $party->addr = $addr;
		    $party->name = "";
		    if ($who['display'] != $who['address'])
			$party->name = $who['display'];
		    $ps[$addr] = $party;
		}
	    }
	}
	// already have 'em?
	$done = array();
	foreach ($ps as $addr=>$party)
	    if (! empty($cur[$addr])) {
		unset($cur[$addr]);
		$done[] = $addr;
	    }
	foreach ($done as $who)
	    unset($ps[$who]);
	foreach ($cur as $who)
	    $who->remove();
	// make what's left
	foreach ($ps as $addr=>$party) {
	    $p = new spParticipant();
	    $p->m_owner = $md->m_owner;
	    $p->m_deal = $md->getOid();
	    $p->m_Addr = $party->addr;
	    $p->m_Name = $party->name;
	    $p->create();
	}
    }
};

function spEncodeAttachmentPath($o) {
    $o = str_replace('.','_',$o);
    $path = 'attach/'
      .substr($o,0,1).'/'
      .substr($o,1,1).'/'
      .substr($o,2,1).'/'
      .substr($o,3,1).'/';
    @mkdir($path, 0777, true);
    $path .= substr($o,4);
    return $path;
}

function spGetDB() {
    static $spdb;

    if (! empty($spdb))
	return $spdb;

    global $spDbName;

    $spdb = new PDO($spDbName);
    $spdb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $q = "pragma foreign_keys=ON";
    try {
	executeOrDie($spdb,$q);
    } catch (PDOException $e) {
	print_r($e);
    }
    return $spdb;
}

?>
