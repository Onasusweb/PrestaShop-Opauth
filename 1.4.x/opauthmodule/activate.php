<?php

/* SSL Management */
$useSSL = true;

include(dirname(__FILE__).'/../../config/config.inc.php');
ob_start();
include(dirname(__FILE__).'/../../header.php');


$ev = new Opauthmodule();
$ev->activate();

include_once(dirname(__FILE__).'/../../footer.php');
?>
