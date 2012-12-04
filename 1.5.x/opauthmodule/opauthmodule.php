<?php
/*
* @author		Achraf HAMMAMI <hammami.achraf@gmail.com>
* @author		Amaury PLANÇON <contact@amaury-plancon.com>
* @copyright	Copyright (c) 2012, Open Presta <www.openpresta.com> & Amaury PLANÇON <www.amaury-plancon.com>
* @license		GNU General Public License (GPL)
* @link			http://www.openpresta.com/
* @link			http://www.amaury-plancon.com/
* @since		Version 1.0
*/

if (!defined('_PS_VERSION_'))
	exit;

class opauthmodule extends Module
{
	function __construct()	
 	{
 	 	$this->name = 'opauthmodule';
 	 	$this->version = '1.1';
 	 	$this->author = "Erico, Mellow971, Achraf HAMMAMI & Amaury PLANÇON";
		$this->module_key = '';		
		
		parent::__construct();

		$this->path = $this->_path;
		$this->page = basename(__FILE__, '.php');
        $this->url =  'http://'.$_SERVER['HTTP_HOST'];

		$this->displayName = $this->l('Opauth for PrestaShop');
		$this->description = $this->l('Opauth plugin for PrestaShop v1.4.x, allowing simple plug-n-play 3rd-party authentication with PrestaShop 1.4.x Version ');
 	}	

	public function install() {
		Configuration::updateValue('emailverify_notifyadmin', 0);
		Configuration::updateValue('emailverify_adminmail', '');

		if (!parent::install()
			|| !$this->registerHook('top')
			|| !$this->registerHook('footer')
			|| !$this->registerHook('beforeAuthentication')
			|| !Db::getInstance()->Execute('
				CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'customer_opauth_google` (
				  `id_customer` int(10) unsigned NOT NULL,
				  `uid` varchar(255) NOT NULL,
				  `image_profil_link` varchar(255) NOT NULL,
				  `active` tinyint(1) unsigned NOT NULL default,
				  `date_add` datetime NOT NULL,
				  `date_upd` datetime NOT NULL,
				  PRIMARY KEY `customer_opauth_google_index` (`id_customer`,`uid`),
				  KEY `id_customer` (`id_customer`)
				) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8')
			|| !Db::getInstance()->Execute('
				CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'customer_opauth_twitter`(
				  `id_customer` int(10) unsigned NOT NULL,
				  `uid` varchar(255) NOT NULL,
				  `image_profil_link` varchar(255) NOT NULL,
				  `active` tinyint(1) unsigned NOT NULL,
				  `date_add` datetime NOT NULL,
				  `date_upd` datetime NOT NULL,
				  PRIMARY KEY `customer_opauth_twitter_index` (`id_customer`,`uid`),
				  KEY `id_customer` (`id_customer`)
				) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8')
			|| !Db::getInstance()->Execute('
				CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'customer_opauth_facebook`(
				  `id_customer` int(10) unsigned NOT NULL,
				  `uid` varchar(255) NOT NULL,
				  `image_profil_link` varchar(255) NOT NULL,
				  `active` tinyint(1) unsigned NOT NULL,
				  `date_add` datetime NOT NULL,
				  `date_upd` datetime NOT NULL,
				  PRIMARY KEY `customer_opauth_facebook_index` (`id_customer`,`uid`),
				  KEY `id_customer` (`id_customer`)
				) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8')
			|| !Db::getInstance()->Execute('
				CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'customer_opauth_linkedin`(
				  `id_customer` int(10) unsigned NOT NULL,
				  `uid` varchar(255) NOT NULL,
				  `image_profil_link` varchar(255) NOT NULL,
				  `active` tinyint(1) unsigned NOT NULL,
				  `date_add` datetime NOT NULL,
				  `date_upd` datetime NOT NULL,
				  PRIMARY KEY `customer_opauth_linkedin_index` (`id_customer`,`uid`),
				  KEY `id_customer` (`id_customer`)
				) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8')
			|| !Db::getInstance()->Execute('
				CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'customer_opauth_paypal`(
				  `id_customer` int(10) unsigned NOT NULL,
				  `uid` varchar(255) NOT NULL,
				  `image_profil_link` varchar(255) NOT NULL,
				  `active` tinyint(1) unsigned NOT NULL,
				  `date_add` datetime NOT NULL,
				  `date_upd` datetime NOT NULL,
				  PRIMARY KEY `customer_opauth_paypal_index` (`id_customer`,`uid`),
				  KEY `id_customer` (`id_customer`)
				) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8')
			|| !Db::getInstance()->Execute('ALTER TABLE '._DB_PREFIX_.'customer ADD act_key CHAR(32) NOT NULL AFTER `active`'))
			return false;
		return true;
	}

	public function uninstall() {
		Configuration::deleteByName('emailverify_notifyadmin');
		Configuration::deleteByName('emailverify_adminmail');

		if (!parent::uninstall()
			|| !$this->unregisterHook('beforeAuthentication')
			|| !$this->unregisterHook('footer')
			|| !$this->unregisterHook('top')
			|| !Db::getInstance()->Execute('
				DROP TABLE `'._DB_PREFIX_.'customer_opauth_google` , 
				  `'._DB_PREFIX_.'customer_opauth_twitter`, 
				  `'._DB_PREFIX_.'customer_opauth_facebook`, 
				  `'._DB_PREFIX_.'customer_opauth_linkedin`, 
				  `'._DB_PREFIX_.'customer_opauth_paypal`')
			|| !Db::getInstance()->Execute('
				ALTER TABLE '._DB_PREFIX_.'customer 
				  DROP act_key')
			)
				return false;
		return true;
	}
       
	private function isValidMd5($str) {
		return !empty($str) && preg_match('/^[a-f0-9]{32}$/', $str);
	}

	public function activate() {
		global $smarty, $cookie;
	
		$errors = array();
	
		$actkey=Tools::getValue('actkey');
		$id_lang=Tools::getValue('id_lang');
		
		// On vérifie que la clé du lien est bien un md5 valide
		if (!$this->isValidMd5($actkey))
			$errors[] = $this->l('Invalid activation key');
		
		// On vérifie qu'il y a bien un compte avec la clé d'activation correspondante
		elseif (!$cutomer_to_act = Db::getInstance()->getValue('SELECT id_customer FROM '._DB_PREFIX_.'customer WHERE act_key = "'.$actkey.'"'))
			$errors[] = $this->l('No account to activate. You may have already activated your account.');
		
		// Un compte correspond, alors on active le compte et on supprime la clé -> plus de réactivation ultérieur possible   
		elseif (Db::getInstance()->Execute('UPDATE '._DB_PREFIX_.'customer SET active = 1, act_key = NULL WHERE act_key = "'.$actkey.'"'))
		{
			// Le compte a été activé, maintenant on va connecter le client et récupéré son panier
			$customer = new Customer($cutomer_to_act);
			$customer->getFields();
			if ( $customer->id ) {
			      $cookie->logged = 1;
			      $cookie->id_customer = (int)($customer->id);
			      $cookie->customer_lastname = $customer->lastname;
			      $cookie->customer_firstname = $customer->firstname;
			      $cookie->passwd = $customer->passwd;
			      $cookie->email = $customer->email;
			
			      if ($cart_id = Db::getInstance()->getValue('SELECT id_cart FROM '._DB_PREFIX_.'cart WHERE id_customer = '.(int)($customer->id).' ORDER BY id_cart DESC'))
			          $cookie->id_cart = $cart_id;
			      
			      Module::hookExec('authentication');
			      
			      // Ici on éfface le header chargé avant la connexion (pas encore affiché) pour le recharger avec les infos du client connecté
			      ob_clean();
	
			      include(dirname(__FILE__).'/../../header.php');
			}
		
			// Le compte a été activé, notification à l'admin si l'option à été choisi
			if(Configuration::get('emailverify_notifyadmin') == 1) {
			     $adminmail = ( Configuration::get('emailverify_adminmail') == '' ? Configuration::get('PS_SHOP_EMAIL') : Configuration::get('emailverify_adminmail'));
			     $adminlang = Configuration::get('PS_LANG_DEFAULT');
			
			     // Envoie du mail
			     Mail::Send((int)$adminlang, 'notifyadmin', $this->l('New customer registered!', false, (int)$adminlang), array(
			     	'{firstname}' => '', 
			     	'{lastname}' => ''),
			     	$adminmail, NULL, NULL, NULL, NULL, NULL, dirname(__FILE__).'/mails/');
			}
		
		}
	
		ob_end_flush();
	
		$smarty->assign('errors', $errors);
	
		return $this->display(__FILE__, 'activate.tpl');
	}

	public function notify($err) {
		global $smarty, $cookie;
	
		$errors = array();
	
		if ($err == 1) $errors[] = $this->l('Validation email could not be sent. Maybe you typed a wrong address...');
	
		else if (isset($_POST['email']) AND $_POST['email']) {
		    $mailtoresend = $_POST['email'];
		    $actkey = md5($mailtoresend);
	
		    // On revérifie qu'il existe bien un compte : NON ACTIF, correspondant à CETTE ADRESSE, et CETTE CLÉ !
		    if ($cutomer_to_act = Db::getInstance()->getValue('
		    						SELECT id_customer FROM '._DB_PREFIX_.'customer 
		    						WHERE email = "'.$mailtoresend.'" 
		    						AND active = 0
		    						AND act_key = "'.$actkey.'"')) {
	
		        $actlink = 'modules/opauthmodule/activate.php?id_lang='.(int)$cookie->id_lang.'&actkey='.$actkey;
	
		        // Le compte existe, on renvoi la clé !
		        if (!Mail::Send((int)$cookie->id_lang, 'mailresend', $this->l('Account activation'), array(
		            	'{email}' => $mailtoresend, 
		            	'{actlink}' => $actlink),
		            	$mailtoresend, NULL, NULL, NULL, NULL, NULL, dirname(__FILE__).'/mails/'))
		
		              // le mail n'est pas parti on le signale au client
		              $errors[] = $this->l('Validation email could not be sent. Maybe you typed a wrong address...');
		
		        // le mail est bien parti
		        else $smarty->assign('mailresended', $_POST['email']);
		
		    } 
		    // aucun compte ne correspond
		    else $errors[] = $this->l('No account matches your request !');
		}
	
		// pas d'erreur et pas d'email posté
		else if (!($err == 2)) $errors[] = $this->l('No account matches your request !');
	
		$smarty->assign('errors', $errors);
	
		return $this->display(__FILE__, 'notify.tpl');
	}

	public function hookBeforeAuthentication() {
		global  $cookie;

		$passwd = trim(Tools::getValue('passwd'));
		$email = trim(Tools::getValue('email'));

		if (empty($email) OR empty($passwd) OR !Validate::isEmail($email) OR !Validate::isPasswd($passwd))
			return;
        else {
             $result = Db::getInstance()->getRow('
             	SELECT * FROM `'._DB_PREFIX_.'customer` WHERE `active` = 0
             	AND `email` = \''.pSQL($email).'\''.(isset($passwd) ? '
             	AND `passwd` = \''.md5(pSQL(_COOKIE_KEY_.$passwd)).'\'' : '').'
             	AND `act_key` = \''.md5(pSQL($email)).'\' 
             	AND `deleted` = 0 
             	AND `is_guest` = 0');

             if ($result) Tools::redirect('modules/opauthmodule/resend.php?email='.$email);
        }
	}

	public function resendForm($email) {
		global  $smarty, $cookie;

		$this->_html .='
			<div class="error">
				<p>'.$this->l('Your account is not active yet !').'</p>
				'.$this->l('Please check your mailbox, you should have received a link to activate your account.').'<br />
				'.$this->l('If not, you can use the form below to resend the activation link at your mail address.').'<br />
				'.$this->l('If you still doesn\'t receive it, your mail address may be wrong or unreachable.').'<br /><br />

				<form action="notify.php" method="post">
					<label class="t" for="email">E-mail :</label>
					<input type="text" name="email" value="'.$email.'" size="30" /><br /><br />
					<input type="submit" name="submitResend" value="'.$this->l('Resend actvation link').'" class="button_large" />
				</form>
			</div>';

		return $this->_html;
	}
	
	public function getContent() {
	    if (Tools::isSubmit('submit')) {
		    Configuration::updateValue($this->name.'_facebook_app_id', Tools::getValue('facebook_app_id'));
		    Configuration::updateValue($this->name.'_facebook_app_secret', Tools::getValue('facebook_app_secret'));

		    Configuration::updateValue($this->name.'_twitter_consumer_key', Tools::getValue('twitter_consumer_key'));
		    Configuration::updateValue($this->name.'_twitter_consumer_secret', Tools::getValue('twitter_consumer_secret'));

		    Configuration::updateValue($this->name.'_google_client_id', Tools::getValue('google_client_id'));
		    Configuration::updateValue($this->name.'_google_client_secret', Tools::getValue('google_client_secret'));

		    Configuration::updateValue($this->name.'_linkedin_api_key', Tools::getValue('linkedin_api_key'));
		    Configuration::updateValue($this->name.'_linkedin_secret_key', Tools::getValue('linkedin_secret_key'));

		    Configuration::updateValue($this->name.'_paypal_app_id', Tools::getValue('paypal_app_id'));
		    Configuration::updateValue($this->name.'_paypal_app_secret', Tools::getValue('paypal_app_secret'));

		    $email = trim(Tools::getValue('emailverify_adminmail'));

		    if (!Configuration::updateValue('emailverify_notifyadmin', Tools::getValue('emailverify_notifyadmin')))
		    	$this->_html .= '<div class="alert error">'.$this->l('Cannot update settings').'</div>';
		    elseif (!empty($email) && !Validate::isEmail($email))
		    	$this->_html .= '<div class="alert error">'.$this->l('Invalid e-mail:').' '.$email.'</div>';
		    elseif (!Configuration::updateValue('emailverify_adminmail', Tools::getValue('emailverify_adminmail')))
		    	$this->_html .= '<div class="alert error">'.$this->l('Cannot update settings').'</div>';
		    else
		    	$this->_html .= '<div class="conf confirm"><img src="../img/admin/ok.gif" alt="" />'.$this->l('Settings updated').'</div>';
		    }

		$this->_html .= '
			<form action="'.$_SERVER['REQUEST_URI'].'" method="post" >
				<fieldset style="margin-top:10px;">
					<legend class="zocial icon facebook"><img src="../modules/'.$this->name.'/img/settings_facebook.png" />'.$this->l('Facebook Settings').'</legend>

					<label>'.$this->l('Facebook App ID:').'</label>
    				<div class="margin-form">
						<input type="text" name="facebook_app_id"  style="width:274px" value="'.Tools::getValue('facebook_app_id', Configuration::get($this->name.'_facebook_app_id')).'">
					</div>

					<label>'.$this->l('Facebook App Secret Key:').'</label>
    				<div class="margin-form">
						<input type="text" name="facebook_app_secret"  style="width:274px" value="'.Tools::getValue('facebook_app_secret', Configuration::get($this->name.'_facebook_app_secret')).'">
						<p class="clear">
	                    	Create Facebook application at <a href="https://developers.facebook.com/apps/" target="_blank">https://developers.facebook.com/apps/</a><br>
	                    	- Remember to enter App Domains <br>
	                    	- "Website with Facebook Login" must be checked, but for "Site URL", you can enter any landing URL.
	                    </p>
                    </div>
				</fieldset>
                    
				<fieldset style="margin-top:10px;">
					<legend><img src="../modules/'.$this->name.'/img/settings_twitter.png"  />'.$this->l('Twitter Settings').'</legend>

					<label>'.$this->l('Consumer key:').'</label>
					<div class="margin-form">
						<input type="text" name="twitter_consumer_key"  style="width:274px" value="'.Tools::getValue('twitter_consumer_key', Configuration::get($this->name.'_twitter_consumer_key')).'">
					</div>

					<label>'.$this->l('Consumer secret:').'</label>
					<div class="margin-form">
						<input type="text" name="twitter_consumer_secret"  style="width:274px" value="'.Tools::getValue('twitter_consumer_secret', Configuration::get($this->name.'_twitter_consumer_secret')).'">
						<p class="clear">
							Twitter strategy for [Opauth][1], based on Opauth-OAuth.<br>
							Create Twitter application at <a href="https://dev.twitter.com/apps" target="_blank">https://dev.twitter.com/apps</a><br>
							- Make sure to enter a Callback URL or callback will be disallowed.  <br>
							Callback URL can be a made up one as Opauth will explicitly provide the correct one as part of the OAuth process.<br>   
							- Register your domains at @Anywhere domains.  <br>
							Twitter only allows authentication from authorized domains.
						</p>
					</div>
				</fieldset>

				<fieldset style="margin-top:10px;">
					<legend><img src="../modules/'.$this->name.'/img/settings_google.png" />'.$this->l('Google Settings').'</legend>

					<label>'.$this->l('Google Client ID:').'</label>
					<div class="margin-form">
						<input type="text" name="google_client_id"  style="width:274px" value="'.Tools::getValue('google_client_id', Configuration::get($this->name.'_google_client_id')).'">
					</div>
					
					<label>'.$this->l('Google Client secret:').'</label>
					<div class="margin-form">
						<input type="text" name="google_client_secret"  style="width:274px" value="'.Tools::getValue('google_client_secret', Configuration::get($this->name.'_google_client_secret')).'">
						<p class="clear">
							2. Create a Google APIs project at <a href="https://code.google.com/apis/console/" target="_blank">https://code.google.com/apis/console/</a><br>
							- You do not have to enable any services from the Services tab.<br>
							- Make sure to go to **API Access** tab and **Create an OAuth 2.0 client ID**.<br>
							- Choose **Web application** for *Application type*<br>
							- Make sure that redirect URI is set to actual OAuth 2.0 callback URL, usually `http://path_to_opauth/google/oauth2callback`
						</p>
					</div>
				</fieldset>

				<fieldset style="margin-top:10px;">
					<legend><img src="../modules/'.$this->name.'/img/settings_linkedin.png" />'.$this->l('LinkedIn Settings').'</legend>

					<label>'.$this->l('LinkedIn API Key:').'</label>
					<div class="margin-form">
						<input type="text" name="linkedin_api_key"  style="width:274px" value="'.Tools::getValue('linkedin_api_key', Configuration::get($this->name.'_linkedin_api_key')).'">
					</div>
					
					<label>'.$this->l('LinkedIn Secret Key:').'</label>
					<div class="margin-form">
						<input type="text" name="linkedin_secret_key"  style="width:274px" value="'.Tools::getValue('linkedin_secret_key', Configuration::get($this->name.'_linkedin_secret_key')).'">
						<p class="clear">
							2. Create LinkedIn application at <a href="https://www.linkedin.com/secure/developer" target="_blank">https://www.linkedin.com/secure/developer</a><br>
						    - Enter your domain at JavaScript API Domain<br>
						    - There is no need to enter OAuth Redirect URL
						</p>
					</div>
				</fieldset>

				<fieldset style="margin-top:10px;">
					<legend><img src="../modules/'.$this->name.'/img/settings_paypal.png" />'.$this->l('PayPal Settings').'</legend>

					<label>'.$this->l('PayPal App ID:').'</label>
					<div class="margin-form">
						<input type="text" name="paypal_app_id"  style="width:274px" value="'.Tools::getValue('paypal_app_id', Configuration::get($this->name.'_paypal_app_id')).'">
					</div>
					
					<label>'.$this->l('PayPal App Secret:').'</label>
					<div class="margin-form">
						<input type="text" name="paypal_app_secret"  style="width:274px" value="'.Tools::getValue('paypal_app_secret', Configuration::get($this->name.'_paypal_app_secret')).'">
						<p class="clear">
							2. Create Paypal application at <a href="https://devportal.x.com/" target="_blank">https://devportal.x.com/</a><br>
							- Select [Paypal Access] on API Scope<br>
						    - Select [OAuth 2.0 / Open Id Connect] on Protocols<br>
						    - Set the value of "Return URL" to http://path_to_opauth/paypal/int_callback
						</p>
					</div>
				</fieldset>

				<fieldset style="margin-top:10px;">
					<legend><img src="../modules/'.$this->name.'/img/settings_paypal.png" />'.$this->l('Notification Settings').'</legend>

					<label>'.$this->l('Admin notification :').'</label>
					<div class="margin-form" style="padding-top:5px;">
						<input type="radio" name="emailverify_notifyadmin" id="emailverify_notifyadmin_on" value="1" '.(Tools::getValue('emailverify_notifyadmin', Configuration::get('emailverify_notifyadmin')) ? 'checked="checked" ' : '').'/>
						<label class="t" for="emailverify_notifyadmin_on"> <img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Enabled').'" /></label>
						<input type="radio" name="emailverify_notifyadmin" id="emailverify_notifyadmin_off" value="0" '.(!Tools::getValue('emailverify_notifyadmin', Configuration::get('emailverify_notifyadmin')) ? 'checked="checked" ' : '').'/>
						<label class="t" for="emailverify_notifyadmin_off"> <img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('Disabled').'" /></label>
						<p class="clear">'.$this->l('Enable to notify admin of new customer registration (mail is sent once account is activated)').'</p>
					</div>
				
					<div class="margin-form">
						<input type="text" name="emailverify_adminmail" value="'.Configuration::get('emailverify_adminmail').'" size="50" />
						<p class="clear">'.$this->l('E-mail address for admin notification (leave blanck to send to shop address)').'</p>
					</div>
				</fieldset>

				<p class="center" style="background: none repeat scroll 0pt 0pt rgb(255, 255, 240); border: 1px solid rgb(223, 213, 195); padding: 10px; margin-top: 10px;">
				<input type="submit" name="submit" value="'.$this->l('Update settings').'" class="button"  />
				</p>
			</form>';
    	
    	return $this->_html;
    	
    }

    public function hookTop($params){
	    global $smarty;
		    	
    	return $this->display(__FILE__, 'head.tpl');
    }
    
    public function hookFooter($params){
    	global $smarty;

		echo '
			<script type="text/javascript" src="/js/jquery/jquery.fancybox-1.3.4.js"></script>
			<script type="text/javascript" src="/modules/opauthmodule/js/jquery.validate-1.11.0.js"></script>
			
			<link rel="stylesheet" type="text/css" href="/css/jquery.fancybox-1.3.4.css" media="screen" />
			<link rel="stylesheet" type="text/css" href="/modules/opauthmodule/css/zocial.css" media="screen" />';

		include("callback.php");    
    }
}