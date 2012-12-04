<?php

$opauth = new opauthmodule();
define('CONF_FILE', dirname(__file__) . '/' . 'opauth.conf.php');
define('OPAUTH_LIB_DIR', dirname(__file__) . '/lib/Opauth/');
/**
 * Load config
 */
if (!file_exists(CONF_FILE)) {
    trigger_error('Config file missing at ' . CONF_FILE, E_USER_ERROR);
    exit();
}
require CONF_FILE;

/**
 * Instantiate Opauth with the loaded config but not run automatically
 */
require OPAUTH_LIB_DIR . 'Opauth.php';
$Opauth = new Opauth($config_op, false);
/**
 * Fetch auth response, based on transport configuration for callback
 */
if (isset($_POST['email_opthmodule'])) {


    $id_default_group = 1;


    // generate passwd
    srand((double)microtime() * 1000000);
    $passwd = substr(uniqid(rand()), 0, 12);
    $real_passwd = $passwd;
    $passwd = md5(pSQL(_COOKIE_KEY_ . $passwd));

    $last_passwd_gen = date('Y-m-d H:i:s', strtotime('-' . Configuration::get('PS_PASSWD_TIME_FRONT') .
        'minutes'));
    $secure_key = md5(uniqid(rand(), true));
    $active = 1;
    $date_add = date('Y-m-d H:i:s'); //'2011-04-04 18:29:15';
    $date_upd = $date_add;
    $_data_user_exist = checkExist($_POST['email_opthmodule']);
    $_customer_id_exits = (int)$_data_user_exist['customer_id'];
    if (!$_customer_id_exits) {
        mysql_query("SET NAMES UTF8");
        $sql = 'insert into `' . _DB_PREFIX_ . 'customer` SET 
						   id_gender = ' . $_POST["gender"] . ', id_default_group = ' . $id_default_group .
            ',
						   firstname = \'' . utf8_encode(html_entity_decode(($_POST["firstname"]))) .
            '\', lastname = \'' . utf8_encode(html_entity_decode(($_POST["lastname"]))) . '\',
						   email = \'' . $_POST['email_opthmodule'] . '\', passwd = \'' . $passwd .
            '\',
						   last_passwd_gen = \'' . $last_passwd_gen . '\',
						   secure_key = \'' . $secure_key . '\', active = ' . $active . ',
						   date_add = \'' . $date_add . '\', date_upd = \'' . $date_upd . '\' ';

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($sql);
        $insert_id = Db::getInstance()->Insert_ID();
        $id_group = 1;
        $sql = 'INSERT into `' . _DB_PREFIX_ . 'customer_group` SET 
						   id_customer = ' . $insert_id . ', id_group = ' . $id_group . ' ';
        defined('_MYSQL_ENGINE_') ? $result = Db::getInstance()->ExecuteS($sql) : $result =
            Db::getInstance()->Execute($sql);


        $insert = Db::getInstance()->Execute('
					INSERT INTO  `' . _DB_PREFIX_ . 'opauth_' . strtolower($_POST['provider']) .
            '_customer` 
					(`' . strtolower($_POST['provider']) . '_id`,`user_id`) 
					VALUES (' . $_POST["id"] . ',' . $insert_id . ')');

    } else {
        $insert_id = $_customer_id_exits;
        $insert = Db::getInstance()->Execute('
					INSERT INTO  `' . _DB_PREFIX_ . 'opauth_' . strtolower($_POST['provider']) .
            '_customer` 
					(`' . strtolower($_POST['provider']) . '_id`,`user_id`) 
					VALUES (' . $_POST["id"] . ',' . $insert_id . ')');
    }


    ////// Envoie de mail /////

    global $cookie;
    $err = 2;
    $id_lang = $cookie->id_lang;
    $actkey = md5($_POST['email_opthmodule']); // La clé d'activation est un md5 de l'adresse mail du client

    $actlink = 'modules/opauthmodule/activate.php?id_lang=' . $id_lang . '&actkey=' .
        $actkey;

    // On rend le compte inactif et on enregistre la clé dans la base de donnée
    Db::getInstance()->Execute('UPDATE ' . _DB_PREFIX_ .
        'customer SET active=0, act_key="' . $actkey . '" WHERE id_customer="' . $insert_id .
        '"');

    // Envoie du mail

    if (!Mail::Send((int)$cookie->id_lang, 'opauthmodule', Mail::l('Welcome!', (int)
        $cookie->id_lang), array(
        '{firstname}' => $_POST["firstname"],
        '{lastname}' => $_POST["lastname"],
        '{email}' => $_POST['email_opthmodule'],
        '{passwd}' => $real_passwd,
        '{actlink}' => $actlink), $_POST['email_opthmodule'], $_POST["firstname"] . ' ' .
        $_POST["lastname"], null, null, null, null, dirname(__file__) . '/mails/'))
        $err = 1; // si le mail n'est pas parti on le signalera au client


    $cookie->logout(); // On déconnecte le client puisque son compte n'est pas encore actif

    Tools::redirect('modules/opauthmodule/notify.php?id_lang=' . $id_lang . '&err=' .
        $err);


    /***********/
} else {
    $response = null;

    switch ($Opauth->env['callback_transport']) {
        case 'session':
            session_start();
            $response = $_SESSION['opauth'];
            unset($_SESSION['opauth']);
            break;
        case 'post':
            $response = unserialize(base64_decode($_POST['opauth']));
            break;
        case 'get':
            $response = unserialize(base64_decode($_GET['opauth']));
            break;
        default:
            echo '<strong style="color: red;">Error: </strong>Unsupported callback_transport.' .
                "<br>\n";
            break;
    }

    /**
     * Check if it's an error callback
     */

    if (array_key_exists('error', $response)) {
        echo '<strong style="color: red;">Authentication error: </strong> Opauth returns error auth response.' .
            "<br>\n";
    }

    /**
     * Auth response validation
     * 
     * To validate that the auth response received is unaltered, especially auth response that 
     * is sent through GET or POST.
     */  else {
        if (empty($response['auth']) || empty($response['timestamp']) || empty($response['signature']) ||
            empty($response['auth']['provider']) || empty($response['auth']['uid'])) {
            //	echo '<strong style="color: red;">Invalid auth response: </strong>Missing key auth response components.'."<br>\n";
        } elseif (!$Opauth->validate(sha1(print_r($response['auth'], true)), $response['timestamp'],
        $response['signature'], $reason)) {
            //	echo '<strong style="color: red;">Invalid auth response: </strong>'.$reason.".<br>\n";
        } else {
            //	echo '<strong style="color: green;">OK: </strong>Auth response is validated.'."<br>\n";

            /**
             * It's all good. Go ahead with your application-specific authentication logic
             */
        }
    }

    $_data = array();
    $_data['provider'] = $response['auth']['provider'];
    if ($response['auth']['provider'] == 'Twitter') {
        $_data['person/gender'] = 'male';
        $_data['namePerson/first'] = $response['auth']['info']['name'];
        $_data['namePerson/last'] = $response['auth']['info']['nickname'];
        $_data['contact/email'] = "nothing"; //$response['auth']['info']['nickname'];
        $_data['id'] = $response['auth']['uid'];
    } elseif ($response['auth']['provider'] == 'Facebook') {
        $_data['person/gender'] = $response['auth']['raw']['gender'];
        $_data['namePerson/first'] = $response['auth']['raw']['first_name'];
        $_data['namePerson/last'] = $response['auth']['raw']['last_name'];
        isset($response['auth']['raw']['email']) ? $_data['contact/email'] = $response['auth']['raw']['email'] :
            $_data['contact/email'] = "nothing";
        $_data['id'] = $response['auth']['uid'];

    } elseif ($response['auth']['provider'] == 'Google') {
        $_data['person/gender'] = $response['auth']['raw']['gender'];
        $_data['namePerson/first'] = $response['auth']['raw']['given_name'];
        $_data['namePerson/last'] = $response['auth']['raw']['family_name'];
        $_data['contact/email'] = $response['auth']['raw']['email'];
        $_data['id'] = $response['auth']['uid'];
    }
}

createUser($_data);

function createUser($_data)
{
    $result1 = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('select * FROM ' .
        _DB_PREFIX_ . 'opauth_' . strtolower($_data['provider']) . '_customer   WHERE  ' .
        strtolower($_data['provider']) . '_id="' . $_data["id"] . '"  ');
    if (sizeof($result1)) {
        foreach ($result1 as $res) {

            global $cookie;
            // authentication
            $result = Db::getInstance()->GetRow('
		        	SELECT * FROM `' . _DB_PREFIX_ . 'customer` 
			        WHERE `active` = 1 AND `id_customer` = ' . $res["user_id"]);

            if ($result) {
                $customer = new Customer();

                $customer->id = $result['id_customer'];
                foreach ($result as $key => $value)
                    if (key_exists($key, $customer))
                        $customer->{$key} = $value;
            } else {
                $result3 = Db::getInstance()->GetRow('
		        	SELECT email FROM `' . _DB_PREFIX_ . 'customer` 
			        WHERE `id_customer` = ' . $res["user_id"]);
                Tools::redirect('modules/opauthmodule/resend.php?email=' . $result3['email']);
            }

            $cookie->id_customer = intval($customer->id);
            $cookie->customer_lastname = $customer->lastname;
            $cookie->customer_firstname = $customer->firstname;
            $cookie->logged = 1;
            $cookie->passwd = $customer->passwd;
            $cookie->email = $customer->email;
            if (Configuration::get('PS_CART_FOLLOWING') and (empty($cookie->id_cart) or Cart::
                getNbProducts($cookie->id_cart) == 0))
                $cookie->id_cart = intval(Cart::lastNoneOrderedCart(intval($customer->id)));
            Module::hookExec('authentication');
            Tools::redirect('index.php');
        }
    } else {

        //// create new user ////

        $gender = (isset($_data['person/gender']) && $_data['person/gender'] == 'male') ?
            1 : 2;
        $id_default_group = 1;

        if (isset($_data['namePerson/first']) && isset($_data['namePerson/last'])) {
            $firstname = deldigit(pSQL($_data['namePerson/first']));
            $lastname = deldigit(pSQL($_data['namePerson/last']));
        }

        $email = $_data['contact/email'];

        // generate passwd
        srand((double)microtime() * 1000000);
        $passwd = substr(uniqid(rand()), 0, 12);
        $real_passwd = $passwd;
        $passwd = md5(pSQL(_COOKIE_KEY_ . $passwd));

        $last_passwd_gen = date('Y-m-d H:i:s', strtotime('-' . Configuration::get('PS_PASSWD_TIME_FRONT') .
            'minutes'));
        $secure_key = md5(uniqid(rand(), true));
        $active = 1;
        $date_add = date('Y-m-d H:i:s'); //'2011-04-04 18:29:15';
        $date_upd = $date_add;


        $_data_user_exist = checkExist($email);
        $_customer_id_exits = (int)$_data_user_exist['customer_id'];
        if ($_customer_id_exits) {

            global $cookie;
            // authentication
            $result = Db::getInstance()->GetRow('
		        	SELECT * FROM `' . _DB_PREFIX_ . 'customer` 
			        WHERE `active` = 1 AND `email` = \'' . pSQL($email) . '\'  
			        AND `deleted` = 0 ' . (defined('_MYSQL_ENGINE_') ?
                "AND `is_guest` = 0" : "") . '
			        ');

            if ($result) {
                $customer = new Customer();

                $customer->id = $result['id_customer'];
                foreach ($result as $key => $value)
                    if (key_exists($key, $customer))
                        $customer->{$key} = $value;
            }

            $cookie->id_customer = intval($customer->id);
            $cookie->customer_lastname = $customer->lastname;
            $cookie->customer_firstname = $customer->firstname;
            $cookie->logged = 1;
            $cookie->passwd = $customer->passwd;
            $cookie->email = $customer->email;
            if (Configuration::get('PS_CART_FOLLOWING') and (empty($cookie->id_cart) or Cart::
                getNbProducts($cookie->id_cart) == 0))
                $cookie->id_cart = intval(Cart::lastNoneOrderedCart(intval($customer->id)));
            Module::hookExec('authentication');
            Tools::redirect('index.php');

        } else {
            if ($email == "nothing") {
                echo "<script>
$(document).ready(function() {
$('#add_email').fancybox().trigger('click');({


                });
				});
</script>";
                echo '<a href="'.$opauth->url.'/modules/opauthmodule/email.php?gender=' .
                    $gender . '&firstname=' . $firstname . '&lastname=' . $lastname . '&provider=' .
                    $_data['provider'] . '&idu=' . $_data["id"] . '" id="add_email"></a>';
            }
            if ($email != "nothing") {

                if (!isset($_POST['email_opthmodule'])) {
                    $gender = (isset($_data['person/gender']) && $_data['person/gender'] == 'male') ?
                        1 : 2;
                    $id_default_group = 1;

                    if (isset($_data['namePerson/first']) && isset($_data['namePerson/last'])) {
                        $firstname = deldigit(pSQL($_data['namePerson/first']));
                        $lastname = deldigit(pSQL($_data['namePerson/last']));
                    }
                    $email = $_data['contact/email'];
                    // generate passwd
                    srand((double)microtime() * 1000000);
                    $passwd = substr(uniqid(rand()), 0, 12);
                    $real_passwd = $passwd;
                    $passwd = md5(pSQL(_COOKIE_KEY_ . $passwd));

                    $last_passwd_gen = date('Y-m-d H:i:s', strtotime('-' . Configuration::get('PS_PASSWD_TIME_FRONT') .
                        'minutes'));
                    $secure_key = md5(uniqid(rand(), true));
                    $active = 1;
                    $date_add = date('Y-m-d H:i:s'); //'2011-04-04 18:29:15';
                    $date_upd = $date_add;
                    $_data_user_exist = checkExist($email);
                    $_customer_id_exits = (int)$_data_user_exist['customer_id'];
                    if (!$_customer_id_exits) {
                        $sql = 'insert into `' . _DB_PREFIX_ . 'customer` SET 
						   id_gender = ' . $gender . ', id_default_group = ' . $id_default_group .
                            ',
						   firstname = \'' . $firstname . '\', lastname = \'' . $lastname . '\',
						   email = \'' . $email . '\', passwd = \'' . $passwd . '\',
						   last_passwd_gen = \'' . $last_passwd_gen . '\',
						   secure_key = \'' . $secure_key . '\', active = ' . $active . ',
						   date_add = \'' . $date_add . '\', date_upd = \'' . $date_upd . '\' ';

                        defined('_MYSQL_ENGINE_') ? $result = Db::getInstance()->ExecuteS($sql) : $result =
                            Db::getInstance()->Execute($sql);
                        $insert_id = Db::getInstance()->Insert_ID();
                        $id_group = 1;
                        $sql = 'INSERT into `' . _DB_PREFIX_ . 'customer_group` SET 
						   id_customer = ' . $insert_id . ', id_group = ' . $id_group . ' ';
                        defined('_MYSQL_ENGINE_') ? $result = Db::getInstance()->ExecuteS($sql) : $result =
                            Db::getInstance()->Execute($sql);


                        $insert = Db::getInstance()->Execute('
					INSERT INTO  `' . _DB_PREFIX_ . 'opauth_' . strtolower($_data['provider']) .
                            '_customer` 
					(`' . strtolower($_data['provider']) . '_id`,`user_id`) 
					VALUES (' . $_data["id"] . ',' . $insert_id . ')');

                    } else {
                        $insert_id = $_customer_id_exits;
                        $insert = Db::getInstance()->Execute('
					INSERT INTO  `' . _DB_PREFIX_ . 'opauth_' . strtolower($_data['provider']) .
                            '_customer` 
					(`' . strtolower($_data['provider']) . '_id`,`user_id`) 
					VALUES (' . $_data["id"] . ',' . $insert_id . ')');
                    }
                }


                // auth customer
                global $cookie;
                $customer = new Customer();
                $authentication = $customer->getByEmail(trim($email), trim($real_passwd));
                if (!$authentication or !$customer->id) {
                    $status = 'error';
                    echo 'Authentication failed!';
                } else {
                    $cookie->id_customer = intval($customer->id);
                    $cookie->customer_lastname = $customer->lastname;
                    $cookie->customer_firstname = $customer->firstname;
                    $cookie->logged = 1;
                    $cookie->passwd = $customer->passwd;
                    $cookie->email = $customer->email;
                    if (Configuration::get('PS_CART_FOLLOWING') and (empty($cookie->id_cart) or Cart::
                        getNbProducts($cookie->id_cart) == 0))
                        $cookie->id_cart = intval(Cart::lastNoneOrderedCart(intval($customer->id)));
                    Module::hookExec('authentication');

                    Mail::Send(intval($cookie->id_lang), 'account', 'Welcome!', array(
                        '{firstname}' => $customer->firstname,
                        '{lastname}' => $customer->lastname,
                        '{email}' => $customer->email,
                        '{passwd}' => $real_passwd), $customer->email, $customer->firstname . ' ' . $customer->
                        lastname);
                    Tools::redirect('index.php');
                }


            }
        }


    }
}


function checkExist($email)
{

    $sql = '
	        	SELECT * FROM `' . _DB_PREFIX_ . 'customer` 
		        WHERE `active` = 1 AND `email` = \'' . pSQL($email) . '\'  
		        AND `deleted` = 0 ' . (defined('_MYSQL_ENGINE_') ?
        "AND `is_guest` = 0" : "") . '
		        ';
    $result = Db::getInstance()->GetRow($sql);

    $_customer = $result['id_customer'];
    return array('customer_id' => $_customer, 'result' => $result);
}
function deldigit($str)
{
    $arr_out = array('');
    $arr_in = array(
        0,
        1,
        2,
        3,
        4,
        5,
        6,
        7,
        8,
        9,
        '_',
        '(',
        ')',
        ',',
        '«',
        '»',
        '.',
        '-',
        '+',
        '&');

    $textout = str_replace($arr_in, $arr_out, $str);

    return $textout;

}



?>
	