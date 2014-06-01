<?php

// $Id: utils.php,v 1.1 2014/02/16 20:11:36 jordan Exp jordan $

date_default_timezone_set('UTC');

include("password.php");

function logIt($str) {
    echo date("Y M d H:i:s") . " " . $str . "\n";
}

// html comments
function htmlComment($name, $str) {
    if (! empty($str))
	echo "<!-- " . $name . " is '" . htmlentities($str) . "' -->\n";
}
function htmlCommentArray($name, $val) {
    echo "<!-- " . $name . " is :\n";
    print_r($val);
    echo "-->\n";
}
function htmlTimestamp($msg) {
    htmlComment(gmdate("Y/m/d H:i:s"), $msg);
}

function startsWith($str, $pre) { return ($str == strstr($str,$pre)); }
function endsWith($str, $p) {
    $i = strlen($p);
    if ($p == substr($str,-$i))
	return true;
    return false;
}

// check for non-ASCII
function isAllASCII($str) {
    if (empty($str))
	return true;
    $v = str_split($str);
    foreach ($v as $c)
	if ($c != trim($c,"\x80..\xFF"))
	    return false;
    return true;
}

// check a whole array
function isAllASCIIV($v) {
    foreach ($v as $str)
	if (! isAllASCII($str))
	    return false;
    return true;
}

function asASCII($str) {
    if (empty($str))
	return $str;
    $ns = "";
    $v = str_split($str);
    foreach ($v as $c) {
	$c = str_replace("\x85"," ",$c);
	$c = str_replace("\xA0"," ",$c);
	$ns .= trim($c,"\x80..\xFF");
    }
    return $ns;
}

function getTomorrow($day) {
    $p = explode("-",$day);
    $yr= $p[0];
    $mn= $p[1];
    $dy= $p[2];
    return date('Y-m-d', mktime(0,0,0,$mn,($dy+1),$yr)); 
}

// figure out today, yesterday, and tomorrow
function getDates(&$tDay, &$yDay, &$tMro) {
    if (empty($_REQUEST['day']))
	$tDay = date('Y-m-d');
    else
	$tDay = $_REQUEST['day'];

    $p = explode("-",$tDay);
    $mn= $p[1];
    $dy= $p[2];
    $yr= $p[0];
    $yDay= date('Y-m-d', mktime(0,0,0,$mn,($dy-1),$yr)); 
    $tMro= date('Y-m-d', mktime(0,0,0,$mn,($dy+1),$yr)); 
}

// figure out last month, this month and next month
function getMonths(&$tDay, &$lMonth, &$tMonth, &$nMonth) {
    if (empty($tDay)) {
	if (! empty($_REQUEST['month'])) {
	    $mp = explode('-',$_REQUEST['month']);
	    $tDay = date('Y-m-d', mktime(0,0,0,$mp[1],1,$mp[0]));
	} else if (empty($_REQUEST['day']))
	    $tDay = date('Y-m-d');
	else
	    $tDay = $_REQUEST['day'];
    }

    $p = explode("-",$tDay);
    $mn= $p[1];
    $dy= $p[2];
    $yr= $p[0];

    $lMonth= date('Y-m', mktime(0,0,0,($mn-1),1,$yr)); 
    $tMonth= date('Y-m', mktime(0,0,0,$mn,1,$yr)); 
    $nMonth= date('Y-m', mktime(0,0,0,($mn+1),1,$yr)); 
}

function nbsp($str) {
    if (empty($str))
	return "&nbsp;";
    return $str;
}

function code($str) {
    return '<code>'.$str.'</code>';
}

function small($str) {
    return '<small>'.$str.'</small>';
}

function hrefKeyVal($key,$val) {
    return $key . '=' . rawurlencode($val);
}
function hrefKeyVal1($key,$val) {
    return hrefKeyVal("?" . $key, $val);
}
function hrefKeyValN($key,$val) {
    return hrefKeyVal("&" . $key, $val);
}
function href($prog,&$keyVals) {
    $s = '<a href="' . $prog;
    $count = 0;
    foreach ($keyVals as $key=>$val) {
	if (0 == $count)
	    $s .= hrefKeyVal1($key,$val);
	else
	    $s .= hrefKeyValN($key,$val);
	$count++;
    }
    return $s;
}
function href2($prog,$tag) {
    $s = '<a href="'.$prog.'">'.$tag.'</a>';
    return $s;
}

function mightDie(&$q, &$v, &$stmt, &$res) {
    if (false === $res) {
	echo "<code>\n";
	echo $q . "\n";
	if (null != $v)
	    print_r($v);
	echo "\n";
	print_r($stmt->errorInfo());
	echo "</code>\n";
	exit(0);
    }
    return $res;
}

function executeDoNotDie(&$dbh, &$q, &$v = null) {
    try {
	$stmt = $dbh->prepare($q);
	$stmt->execute($v);
	return true;
    } catch (PDOException $e) {
	echo "Error ...\n";
	echo "Query: " . $q . "\n";
	print_r($v);
	print_r($e->errorInfo);
	return false;
    }
}

function executeOrDie(&$dbh, &$q, &$v = null) {
    $stmt = $dbh->prepare($q);
    return mightDie($q, $v, $stmt, $stmt->execute($v));
}

function queryOrDie(&$dbh, &$q, &$v = null) {
    $stmt = $dbh->prepare($q);
    if (null == $v)
	$res = $stmt->execute();
    else
	$res = $stmt->execute($v);
    if (false === $res)
	mightDie($q, $v, $stmt, $res);
    return $stmt;
}

function beginTranOrDie($dbh) {
    if (! $dbh->beginTransaction()) {
	print_r($dbh->errorInfo());
	exit(0);
    }
    // make sure it's really running ...
    try {
	$dbh->beginTransaction();
	logIt("beginTransaction broken on MySQL/MyISAM");
	exit(0);
    } catch (PDOException $e) {
    }
}

function commitOrDie($dbh) {
    if (! $dbh->commit()) {
	print_r($dbh->errorInfo());
	exit(0);
    }
}

function prepArray(&$q, $v) {
    $sep = "";
    foreach ($v as $val) {
	$q .= $sep."?";
	$sep = ",";
    }
    $q .= ")";
}

function useTableData() {
echo<<<_EOD
 <link rel="stylesheet" type="text/css" href="js/jquery.dataTables.css">
 <script type="text/javascript" charset="utf-8" src="js/jquery-1.8.2.min.js"></script>
 <script type="text/javascript" charset="utf-8" src="js/jquery.dataTables.min.js"></script>

_EOD;
}

function tableTail($name,$rows,$dcv=null,$nlv=null,$plv=null) {
    if (empty($dcv))
	$dcv = "'&nbsp;'";
    if (empty($nlv))
	$nlv = "'&nbsp;'";
    if (empty($plv))
	$plv = "'&nbsp;'";

    $pl = $name."_prevLink";
    $rc = $name."_rowCount";
    $dc = $name."_detailCount";
    $nl = $name."_nextLink";

    $idl = '   "iDisplayLength": '.$rows;
    $bpg = "\n".'   "bPaginate": false,';
    if ($rows > 100) {
	$idl = '   "iDisplayLength": 100';
	$bpg = "\n".'   "bPaginate": true,';
    }

echo<<<EOD
</tbody>
</table>
<script type="text/javascript" charset="utf-8">
//<![CDATA[
var pElem = document.getElementById('$pl');
pElem.innerHTML = $plv;
pElem = document.getElementById('$rc');
pElem.innerHTML = $rows;
pElem = document.getElementById('$dc');
pElem.innerHTML = $dcv;
pElem = document.getElementById('$nl');
pElem.innerHTML = $nlv;
$(document).ready(function() {
 $('#$name').dataTable( {
   "bLengthChange": false,$bpg
   "bInfo": false,
   "aaSorting": [],
$idl
 } );
} );
//]]>
</script>    

EOD;
}

function tableHead($name,$cols,$head=null) {
    $gmd = gmdate("Y-m-d H:i:s");
    $num = count($cols) - 2;
    if ($num < 1)
	$num = 1;

    $pl = $name."_prevLink";
    $rc = $name."_rowCount";
    $dc = $name."_detailCount";
    $nl = $name."_nextLink";

    echo '<table id="'.$name.'" border="1">'."\n";
    echo " <thead>\n";
    if (! empty($head))
	echo $head;

    echo "<tr>\n";
    echo ' <td align="left"><b><span id="'.$pl.'"></span></b></td>'."\n";
    echo ' <td colspan="'.$num.'" align="center"><b><span id="'
      . $rc.'"></span>&nbsp;total rows&nbsp;<span id="'.$dc
      . '"></span>&nbsp;at '.$gmd."</b></td>\n";
    echo ' <td align="right"><b><span id="'.$nl.'"></span></b></td>'."\n";
    echo "</tr>\n<tr>\n";

    $cs = "";
    if (count($cols) == 1)
	$cs = ' colspan="3"';
    foreach ($cols as $key=>$val)
	echo ' <th'.$cs.'>'.$val."</th>\n";
    echo "</tr>\n</thead><tbody>";
}

?>
