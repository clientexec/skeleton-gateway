<?php

$_GET['fuse']='billing';
$_GET['action'] = 'gatewaycallback';
$_GET['plugin'] = 'skeletongateway';           //replace 'skeletongateway' with the respective plugin folder name

chdir('../../..');

require_once dirname(__FILE__).'/../../../library/front.php';

?>
