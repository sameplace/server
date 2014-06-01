<?php include("spauth.php"); ?>
<?php
include("config.php");
echo "<html>\n<head>\n <title>profile</title>\n";
echo ' <link rel="stylesheet" href="style.css" type="text/css">'."\n";
echo "</head><body>\n";

$me = spUser::lookupMe();
if (null == $me) {
    echo 'You do not belong here!</body></html>';
    return;
}

if (empty($_REQUEST['oid']))
    $oid = $me->m_oStr;
else {
    $oid = $_REQUEST['oid'];
    if (! $me->isAdmin() && $oid != $me->m_oStr) {
	echo 'You do not belong here!</body></html>';
	return;
    }
}

$u = new spUser();
if (! $u->inflate($oid)) {
    echo "Error: cannot find oid='".htmlentities($oid)."'</body></html>";
    return;
}

function doAdminTextField($u, $name) {
    global $me;
    if ($me->isAdmin())
	return doTextField($u, $name);

    $tag = "m_".$name;
    echo spTableRow("\n".
      " ".spTableHeader($name)."\n".
      " ".spTableData(" ").
      " ".spTableData(htmlentities($u->$tag))."\n"
    )."\n";
}

function doTextField($u, $name, $val = null) {
    $tag = "m_".$name;
    if (null == $val)
	$val = $u->$tag;

    echo spTableRow("\n".
      " ".spTableHeader($name)."\n".
      " ".spTableData(spInputCheckbox("clear_".$name))."\n".
      " ".spTableData(spInputText($name,
        ' size="50" value="'.$val.'"'))."\n"
    )."\n";
    return true;
}

function doBoolField($u, $name, $val) {
    $tag = "m_".$name;
    echo spTableRow("\n".
      " ".spTableHeader($name)."\n".
      " ".spTableData(spInputRadio($name,$val != 0).'Yes'."\n     ".
        spInputRadio($name,$val == 0).'No')."\n".
      " ".spTableData()."\n"
    )."\n";
}

spMenuBar($me);
echo '<form action="" method="POST">'."\n".'<table border="1">'."\n";
echo spTableRow("\n".
  " ".spTableHeader("Field")."\n".
  " ".spTableHeader("CLR?")."\n".
  " ".spTableHeader("Created: ".$u->m_cTime
     ."<br/>LastUpdate: ".$u->m_mTime)."\n"
)."\n";

doTextField($u, "realname");
doAdminTextField($u, "email");
doTextField($u, "question");
doTextField($u, "answer");
if ($me->isAdmin()) {
    doBoolField($u, "isadmin", $u->isAdmin());
    doBoolField($u, "validated", $u->isValidated());
    doBoolField($u, "locked", $u->isLocked());
    doTextField($u, "badpass", $u->getBadpass());
}

echo "</form></table>\n";

$deals = spDealSpace::lookupAll($u->getOid());
echo "<table>".
  spTableRow("\n"
    ." ".spTableHeader("DealSpace")
    ." ".spTableHeader("Name")
    ." ".spTableHeader("Created")
    ." ".spTableHeader("LastUpdate")
  );
foreach ($deals as $deal) {
    $name = $deal->m_name;
    global $defDealSpace;
    if ($name == $defDealSpace)
	$name = "(default)";
    echo spTableRow(
      spTableData('<a href="editDealSpace.php?oid='.$deal->m_oStr.'">'
      .$deal->getOid().'</a>')
      .spTableData(htmlentities($name))
      .spTableData($deal->m_cTime)
      .spTableData($deal->m_mTime));
}

?>
</body></html>
