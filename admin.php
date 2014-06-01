<?php include("spauth.php"); ?>
<?php
include("config.php");
echo "<html>\n<head>\n <title>admin</title>\n";
echo ' <link rel="stylesheet" href="style.css" type="text/css">'."\n";
useTableData();
echo "</head><body>\n";

$me = spUser::lookupMe();
if (null == $me || !$me->isAdmin()) {
    echo 'You do not belong here!</body></html>';
    return;
}

$users = spUser::lookupAll();
$cols = array("oid","realname","email","question"
  ,"answer","created","modified","isAdmin","validated","locked","badpass");
tableHead("users",$cols);
foreach ($users as $u) {
    echo "<tr>\n";
    echo spTableData('<a href="profile.php?oid='.$u->m_oStr.'">'
      .$u->getOid().'</a>');
    echo " <td>".$u->m_realname."</td>";
    echo " <td>".$u->m_email."</td>";
    echo " <td>".$u->m_question."</td>";
    echo " <td>".$u->m_answer."</td>";
    echo " <td>".$u->m_cTime."</td>";
    echo " <td>".$u->m_mTime."</td>";
    echo " <td>".spTrueFalse($u->isAdmin())."</td>";
    echo " <td>".spTrueFalse($u->isValidated())."</td>";
    echo " <td>".spTrueFalse($u->isLocked())."</td>";
    echo " <td>".$u->getBadpass()."</td>";
    echo "</tr>\n";
}
tableTail("users",count($users),"'[USERS]'");

?>
</body></html>
