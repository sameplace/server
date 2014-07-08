<?php

// grab this here, why not?
require_once 'utils.php';
require_once 'spfuncs.php';
require_once 'oid.php';

$spDbName = "sqlite:/ext/www/public/archie.sqb";

@include("config-local.php");

// should not have to change anything below, but you might want to

$defDealSpace = '_____defaultDealSpace_____';

?>
