<?php include("spauth.php"); ?>
<?php
include("config.php");
echo "<html>\n<head>\n <title>edit dealspace</title>\n";
echo ' <link rel="stylesheet" href="style.css" type="text/css">'."\n";
useTableData();
echo "</head><body>\n";

$me = spUser::lookupMe();
if (null == $me) {
    echo 'You do not belong here!</body></html>';
    return;
}

if (empty($_REQUEST['oid'])) {
    echo 'Error: must specify an oid!</body></html>';
    return;
}
$oid = $_REQUEST['oid'];

$ds = new spDealSpace();
if (! $ds->inflate($oid)) {
    echo "Error: cannot find DealSpace='".htmlentities($oid)."'</body></html>";
    return;
}

if ($ds->m_owner != $me->getOid() && !$me->isAdmin()) {
    echo "Error: cannot find DealSpace='".htmlentities($oid)."'</body></html>";
    return;
}

spMenuBar($me);
$cols = array("Oid","Date","Hidden","Private","From","IRT","Subject");
tableHead("msgs",$cols);
$docs = spMimeDoc::lookupAll($ds->getOid());

foreach ($docs as $doc) {
    $iStr = "";
    $irt = spMimeDoc::lookupMessageId($doc->m_InReplyTo);
    if (! empty($irt))
	$iStr = href2('editMimeDoc.php?oid='.$irt->m_oStr, $irt->getOid());
    echo spTableRow(
      spTableData('<a href="editMimeDoc.php?oid='.$doc->m_oStr.'">'
      .$doc->getOid().'</a>')
      .spTableData(htmlentities($doc->m_Date))
      .spTableData(spTrueFalse($doc->m_hidden))
      .spTableData(spTrueFalse($doc->m_private))
      .spTableData(htmlentities($doc->m_From))
      .spTableData($iStr)
      .spTableData(htmlentities($doc->m_Subject)));
}
tableTail("msgs",count($docs));

?>
