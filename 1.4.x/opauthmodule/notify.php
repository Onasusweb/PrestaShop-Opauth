<?php

/* SSL Management */
$useSSL = true;

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');


$err=Tools::getValue('err');
$ev = new Opauthmodule();
echo $ev->notify($err);

include_once(dirname(__FILE__).'/../../footer.php');
?>
