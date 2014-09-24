<?php

include("config.php");
spHeaderHack();

if (empty($_REQUEST['oid'])) {
    header('Content-Type: application/json;charset=utf-8;');
    echo json_encode('Error: must specify an oid!');
    return;
}
$oStr = $_REQUEST['oid'];

$a = new spAttachment();
if (! $a->inflate($oStr)) {
    header('Content-Type: application/json;charset=utf-8;');
    echo json_encode("Error: cannot find Attachment='".htmlentities($oStr)."'");
    return;
}

header('Content-Type: '.$a->m_mType);
header('Content-Disposition: attachment; filename="'.$a->m_name.'"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Content-Length: '.filesize($a->m_path));
flush();
readfile($a->m_path);

?>
