<html>
<head>
<link rel="stylesheet" href="style.css" type="text/css">
<title>register</title>
</head><body>
<?php

include("config.php");

if (empty($_REQUEST['u'])) {
    echo '<h4>Nothing to validate!</h4>';
    echo '</body></html>';
    return;
}

$u = new spUser();
if ($u->inflate($_REQUEST['u'])) {
    if ($u->isValidated()) {
	echo '<h4>Already validated!</h4></body></html>';
	return;
    }

    $u->validate();
    $ds = $u->createDefDealSpace();
    $deal = $ds->getOid();
    $user = $u->getOid();
    $admin = spUser::lookupEmail("archie@sameplace.com");
    $ads = $admin->getDefDeal();
    // find all docs "owned" by new user
    $docs = spMimeDoc::lookupAll($ads->getOid(), $u->m_email);
    if (! empty($docs)) {
	// make a list of references
	$which = array();
	foreach ($docs as $d) {
	    $some = $d->allMsgIds();
	    foreach ($some as $id)
		$which[$id] = $id;
	}
	$which = array_keys($which);
	$adocs = spMimeDoc::lookupAll($ads->getOid());
	$move = array();
	foreach ($adocs as $d) {
	    $some = $d->allMsgIds();
	    foreach ($some as $id)
		if (in_array($id, $which)) {
		    $move[$d->getOid()] = $d->getOid();
		    continue;
		}
	    }
	if (0 != count($move))
	    spMimeDoc::moveToDeal(array_keys($move), $ds->getOid(),
	      $u->getOid());
    }
    echo '<h4>Thanks for validating!  Now you can '
      .href2('login.php','Log in');
}

?>
</body></html>
