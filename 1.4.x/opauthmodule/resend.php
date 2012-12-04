<?php

/* SSL Management */
$useSSL = true;

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');

$email=Tools::getValue('email');
$ev = new Opauthmodule();
echo $ev->resendForm($email);

include_once(dirname(__FILE__).'/../../footer.php');
?>
