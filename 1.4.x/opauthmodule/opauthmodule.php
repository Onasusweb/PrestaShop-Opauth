<?php

/*
 * OPAUTH Connexion
 *
 * 
 *
 * @package		Silbersaiten
 * @author		Achraf Hammami <hammami.achraf@gmail.com>
 * @copyright	Copyright (c) 2012, Open Presta <www.openpresta.com
 * @license		GNU General Public License (GPL)
 * @link		http://www.openpresta.com
 * @since		Version 1.0
 */

class opauthmodule extends Module
{
	function __construct()	
 	{
 	 	$this->name = 'opauthmodule';
 	 	$this->version = '1.0';
 	 	$this->tab = 'OPauth connexion';
 	 	$this->author = "openpresta.com";
		$this->module_key = '';		
		
		parent::__construct();
		$this->path = $this->_path;
        $this->url =  'http://'.$_SERVER['HTTP_HOST'];
		$this->page = basename(__FILE__, '.php');
		$this->displayName = $this->l('OPauth connexion');
		$this->description = $this->l('OPauth connexion');
 	}
 	
	function install()
	{
	 	if (!parent::install()     
                || !$this->registerHook('beforeAuthentication')
                || !Db::getInstance()->Execute('ALTER TABLE '._DB_PREFIX_.'customer ADD act_key CHAR(32)'))
	 		return false;
	 		
	 	Configuration::updateValue($this->name.'positionl', 'top');
		Configuration::updateValue($this->name.'BGCOLORL', '#9CC8D9');
	 		
	 	//connects
		Configuration::updateValue($this->name.'fbbutton', "on");
	 	Configuration::updateValue($this->name.'twbutton', "on");
	 	Configuration::updateValue($this->name.'gbutton', "on");
        Configuration::updateValue('emailverify_notifyadmin', 0);
		Configuration::updateValue('emailverify_adminmail', '');
	    
	 	if (!$this->registerHook('TOP') OR !$this->registerHook('FOOTER') OR
	 		!$this->createFacebookCustomerTbl() OR
	 		!$this->createUserTwitterTable() OR
			!$this->createUserGoogleTable()
	 		)
			return false;
	 	
	 	return true;
	}
	
	function uninstall()
	{
		
		
		if (!parent::uninstall()
			)
			return false;
		return true;
	}
	
	function createFacebookCustomerTbl()
	{
	
	$db = Db::getInstance();
	
	$query = 'CREATE TABLE IF NOT EXISTS '._DB_PREFIX_.'opauth_facebook_customer (
				   `id` int(11) NOT NULL AUTO_INCREMENT,
					  `facebook_id` varchar(255) NOT NULL,
					  `user_id` int(11) NOT NULL,
					  PRIMARY KEY (`id`)
					) ENGINE=MYISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1';			
	$db->Execute($query);
	return true;
	
	}
	
	public function createUserTwitterTable(){
		
		$db = Db::getInstance();
		$query = 'CREATE TABLE IF NOT EXISTS '._DB_PREFIX_.'opauth_twitter_customer (
				   `id` int(11) NOT NULL AUTO_INCREMENT,
					  `twitter_id` varchar(255) NOT NULL,
					  `user_id` int(11) NOT NULL,
					  PRIMARY KEY (`id`)
					) ENGINE=MYISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1';
		$db->Execute($query);
		return true;
		
	}
	public function createUserGoogleTable(){
		
		$db = Db::getInstance();
		$query = 'CREATE TABLE IF NOT EXISTS '._DB_PREFIX_.'opauth_google_customer (
				   `id` int(11) NOT NULL AUTO_INCREMENT,
					  `google_id` varchar(255) NOT NULL,
					  `user_id` int(11) NOT NULL,
					  PRIMARY KEY (`id`)
					) ENGINE=MYISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1';
		$db->Execute($query);
		return true;
		
	}
	
	
    	public function hookCreateAccount($params) {
                global  $smarty, $cookie, $cart;

                $customer=new Customer($params['newCustomer']->id);
                $customer->getFields();
                
                $err=2;
        	$id_lang = $cookie->id_lang;
                $actkey = md5($customer->email);           // La clé d'activation est un md5 de l'adresse mail du client

                $actlink = 'modules/emailverify/activate.php?id_lang='.$id_lang.'&actkey='.$actkey;

                // On rend le compte inactif et on enregistre la clé dans la base de donnée
                Db::getInstance()->Execute('UPDATE '._DB_PREFIX_.'customer SET active=0, act_key="'.$actkey.'" WHERE id_customer="'.$customer->id.'"') ;

                // Envoie du mail
                if (!Mail::Send((int)$cookie->id_lang,
                   'emailverify',
                   Mail::l('Welcome!', (int)$cookie->id_lang),
                   array('{firstname}' => $customer->firstname,
                   '{lastname}' => $customer->lastname,
                   '{email}' => $customer->email,
                   '{passwd}' =>  Tools::getValue('passwd'),
                   '{actlink}' => $actlink),
                   $customer->email, $customer->firstname.' '.$customer->lastname, NULL, NULL, NULL, NULL, dirname(__FILE__).'/mails/')
                   )
                     $err=1;   // si le mail n'est pas parti on le signalera au client

                $cart->id_customer = (int)($customer->id);          // Récupération du panier en lui affectant l'id du nouveau compte
                $cart->update();

        	$cookie->logout();                                   // On déconnecte le client puisque son compte n'est pas encore actif

                Tools::redirect('modules/emailverify/notify.php?id_lang='.$id_lang.'&err='.$err);
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
                elseif (!$cutomer_to_act = Db::getInstance()->getValue('SELECT id_customer FROM '._DB_PREFIX_.'customer WHERE act_key="'.$actkey.'"'))
		        $errors[] = $this->l('No account to activate. You may have already activated your account.');

                // Un compte correspond, alors on active le compte et on supprime la clé -> plus de réactivation ultérieur possible   
                elseif (Db::getInstance()->Execute('UPDATE '._DB_PREFIX_.'customer SET active=1, act_key=NULL WHERE act_key="'.$actkey.'"'))
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

                              if ($cart_id = Db::getInstance()->getValue('SELECT id_cart FROM '._DB_PREFIX_.'cart WHERE id_customer='.(int)($customer->id).' ORDER BY id_cart DESC'))
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
                             Mail::Send((int)$adminlang, 'notifyadmin', $this->l('New customer registered!', false, (int)$adminlang),
                                         array('{firstname}' => '', '{lastname}' => ''),
                                         $adminmail, NULL, NULL, NULL, NULL, NULL, dirname(__FILE__).'/mails/');
                       }

                }
                ob_end_flush();
                $smarty->assign('errors', $errors);
                $smarty->display(dirname(__FILE__).'/activate.tpl');
	}

	public function notify($err) {
                global $smarty, $cookie;
                $errors = array();
                
                if ($err == 1) $errors[] = $this->l('Validation email could not be sent. Maybe you typed a wrong address...');
		
                else if (isset($_POST['email']) AND $_POST['email']) {
                    $mailtoresend = $_POST['email'];
                    $actkey = md5($mailtoresend);
                    // On revérifie qu'il existe bien un compte : NON ACTIF, correspondant à CETTE ADRESSE, et CETTE CLÉ !
                    if ($cutomer_to_act = Db::getInstance()->getValue('SELECT id_customer FROM '._DB_PREFIX_.'customer WHERE email="'.$mailtoresend.'" AND active=0 AND act_key="'.$actkey.'"')) {
                        $actlink = 'modules/opauthmodule/activate.php?id_lang='.(int)$cookie->id_lang.'&actkey='.$actkey;
                        // Le compte existe, on renvoi la clé !
                        if (!Mail::Send((int)$cookie->id_lang, 'mailresend', $this->l('Account activation'),
                            array('{email}' => $mailtoresend, '{actlink}' => $actlink),
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
                     $result = Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'customer` WHERE `active` = 0
                     AND `email` = \''.pSQL($email).'\'
                     '.(isset($passwd) ? 'AND `passwd` = \''.md5(pSQL(_COOKIE_KEY_.$passwd)).'\'' : '').'
                     AND `act_key` = \''.md5(pSQL($email)).'\' AND `deleted` = 0 AND `is_guest` = 0');
                     if ($result) Tools::redirect('modules/emailverify/resend.php?email='.$email);
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
	
	
 public function getContent()
    {
        if (Tools::isSubmit('submit'))
        {
       
         Configuration::updateValue($this->name.'positionl', Tools::getValue('positionl'));
         Configuration::updateValue($this->name.'BGCOLORL', Tools::getValue($this->name.'BGCOLORL'));
        	
         Configuration::updateValue($this->name.'appid', Tools::getValue('appid'));
         Configuration::updateValue($this->name.'secret', Tools::getValue('secret'));
        
		 Configuration::updateValue($this->name.'twitterconskey', Tools::getValue('twitterconskey'));
	     Configuration::updateValue($this->name.'twitterconssecret', Tools::getValue('twitterconssecret'));
		 
		 Configuration::updateValue($this->name.'googleid', Tools::getValue('googleid'));
         Configuration::updateValue($this->name.'googlesecret', Tools::getValue('googlesecret'));
	    
	     //connects
		 Configuration::updateValue($this->name.'fbbutton', Tools::getValue('fbbutton'));
         Configuration::updateValue($this->name.'twbutton', Tools::getValue('twbutton'));
	     Configuration::updateValue($this->name.'gbutton', Tools::getValue('gbutton'));
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
        $this->_drawForm();
        return $this->_html;
    }


	private function _drawForm()
     {
       $this->_html = '';
		$this->_html .= $this->_jsandcss();
		
		$this->_html .= $this->_drawLoginForm();
       
       $this->_html .= $this->_drawSettingsForm(); 
		
       $this->_html .= $this->_drawTwitterForm();
       
 
     	$this->_html .= '<div style="clear:both"></div>';
    	
     	
     	$this->_html .= $this->_updateButton();
		
		
    }
    
    
	private function _drawLoginForm(){
    	$_html = '';
    	$_html .= '
        <form action="'.$_SERVER['REQUEST_URI'].'" method="post" >';
    	$_html .= '<fieldset>
					<legend><img src="../modules/'.$this->name.'/logo.gif"  />
						'.$this->displayName.'</legend>';

    		
    	
		$_html .= '<label>'.$this->l('Position Login Block:').'</label>
				<div class="margin-form">
					<select class="select" name="positionl" 
							id="positionl">
						<option '.(Tools::getValue('positionl', Configuration::get($this->name.'positionl'))  == "top" ? 'selected="selected" ' : '').' value="top">Top</option>
						<option '.(Tools::getValue('positionl', Configuration::get($this->name.'positionl')) == "bottom" ? 'selected="selected" ' : '').' value="bottom">Bottom</option>
						<option '.(Tools::getValue('positionl', Configuration::get($this->name.'positionl')) == "none" ? 'selected="selected" ' : '').' value="none">None</option>
					</select>
				</div>';
    	
	
		$_html .= $this->_colorpicker(array('name' => $this->name.'BGCOLORL',
											'color' => Configuration::get($this->name.'BGCOLORL'),
											'title' => 'Background'
											  ));	
		
		$_html .= '<label>'.$this->l('Enable or Disable Connects:').'</label>
				<div class="margin-form">';

		$_html .= '<table width="80%">';
		
		$_html .= '<tr>';
		$_html .= '<td align="center" width="20%"><img alt="Facebook" src="../modules/'.$this->name.'/img/icon/fb.png" /></td>';
		$_html .= '<td align="center" width="20%"><img alt="Twitter" src="../modules/'.$this->name.'/img/icon/tw.png" /></td>';
		$_html .= '<td align="center" width="20%"><img alt="Google" src="../modules/'.$this->name.'/img/icon/g.png" /></td>';
		
		$_html .= '</tr>';
		
		$_html .= '<tr>';
		$_html .= '<td align="center" style="padding:5px 0"><input type="checkbox" name="fbbutton" id="fbbutton" '.(Tools::getValue('fbbutton', Configuration::get($this->name.'fbbutton')) == "on" ? 'checked="checked" ' : '').'/></td>';
		$_html .= '<td align="center" style="padding:5px 0"><input type="checkbox" name="twbutton" id="twbutton" '.(Tools::getValue('twbutton', Configuration::get($this->name.'twbutton')) == "on" ? 'checked="checked" ' : '').'/></td>';
		$_html .= '<td align="center" style="padding:5px 0"><input type="checkbox" name="gbutton" id="gbutton" '.(Tools::getValue('gbutton', Configuration::get($this->name.'gbutton')) == "on" ? 'checked="checked" ' : '').'/></td>';
		
		$_html .= '</tr>';
		
		
		$_html .= '</table>';
		
		$_html .=	'</div>';					
											  
			$_html .= '</fieldset>';
    	
    	return $_html;
    }
    
	private function _drawTwitterForm(){
    	$_html = '';
    	
    	$_html .= '<fieldset style="margin-top:10px;">
					<legend><img src="../modules/'.$this->name.'/img/settings_tw.png"  />'.$this->l('Twitter Settings').'</legend>';

    		
    	
		$_html .= '<label>'.$this->l('Consumer key:').'</label>
    			
    				<div class="margin-form">
					<input type="text" name="twitterconskey"  style="width:274px"
			               value="'.Tools::getValue('twitterconskey', Configuration::get($this->name.'twitterconskey')).'"
			               >
			         <p class="clear"> 


										
					</p>
					
			       </div>';
		
		$_html .= '<label>'.$this->l('Consumer secret:').'</label>
    			
    				<div class="margin-form">
					<input type="text" name="twitterconssecret"  style="width:274px"
			               value="'.Tools::getValue('twitterconssecret', Configuration::get($this->name.'twitterconssecret')).'">
					 <p class="clear"
                     
                                          Twitter strategy for [Opauth][1], based on Opauth-OAuth.<br>
                     Create Twitter application at https://dev.twitter.com/apps<br>
   - Make sure to enter a Callback URL or callback will be disallowed.  <br>
      Callback URL can be a made up one as Opauth will explicitly provide the correct one as part of the OAuth process.<br>   
   - Register your domains at @Anywhere domains.  <br>
	   Twitter only allows authentication from authorized domains.<br>
										
					</p>
					
					
			       </div>';
		
		
			$_html .= '
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
                ';
    	
    	return $_html;
    }
    
	private function _drawSettingsForm(){
    	$_html = '';
    	$_html .= '<fieldset style="margin-top:10px;">
					<legend><img src="../modules/'.$this->name.'/img/settings_fb.png" />'.$this->l('Facebook Settings').'</legend>
					
					';
		// Facebook Application Id
    	$_html .= '<label>'.$this->l('Facebook Application Id:').'</label>
    			
    				<div class="margin-form">
					<input type="text" name="appid"  style="width:274px"
			                		value="'.Tools::getValue('appid', Configuration::get($this->name.'appid')).'">
				
				</div>';
    	
    	// Facebook Secret Key
		$_html .= '<label>'.$this->l('Facebook Secret Key:').'</label>
    			
    				<div class="margin-form">
					<input type="text" name="secret"  style="width:274px"
			                		value="'.Tools::getValue('secret', Configuration::get($this->name.'secret')).'">
					<p class="clear">
                    
                    Create Facebook application at https://developers.facebook.com/apps/ <br>
   - Remember to enter App Domains <br>
   - "Website with Facebook Login" must be checked, but for "Site URL", you can enter any landing URL. <br>
   </p>
				
				</div>';
		
		$_html .=	'</fieldset>'; 
        
        
            	$_html .= '<fieldset style="margin-top:10px;">
					<legend><img src="../modules/'.$this->name.'/img/settings_google.png" />'.$this->l('Google Settings').'</legend>
					
					';
		// Facebook Application Id
    	$_html .= '<label>'.$this->l('Google Client Id:').'</label>
    			
    				<div class="margin-form">
					<input type="text" name="googleid"  style="width:274px"
			                		value="'.Tools::getValue('googleid', Configuration::get($this->name.'googleid')).'">
				
				</div>';
    	
    	// Facebook Secret Key
		$_html .= '<label>'.$this->l('Google Client ID:').'</label>
    			
    				<div class="margin-form">
					<input type="text" name="googlesecret"  style="width:274px"
			                		value="'.Tools::getValue('googlesecret', Configuration::get($this->name.'googlesecret')).'">
					<p class="clear">
              2. Create a Google APIs project at https://code.google.com/apis/console/<br>
   - You do not have to enable any services from the Services tab.<br>
   - Make sure to go to **API Access** tab and **Create an OAuth 2.0 client ID**.<br>
   - Choose **Web application** for *Application type*<br>
   - Make sure that redirect URI is set to actual OAuth 2.0 callback URL, usually `http://path_to_opauth/google/oauth2callback` <br>

   </p>
				
				</div>';
		
		$_html .=	'</fieldset>'; 
		
		
    	
    	return $_html;
    }
    
   
   
    private function _updateButton(){
    	$_html = '';
    	$_html .= '<p class="center" style="background: none repeat scroll 0pt 0pt rgb(255, 255, 240); border: 1px solid rgb(223, 213, 195); padding: 10px; margin-top: 10px;">
					<input type="submit" name="submit" value="'.$this->l('Update settings').'" 
                		   class="button"  />
                	</p>';
    	
    	$_html .=	'</form>';
    	
    	return $_html;
    	
    }
    
 	function hookTOP($params){
    	global $smarty;
	
    	$smarty->assign($this->name.'appid', Configuration::get($this->name.'appid'));
    	$smarty->assign($this->name.'secret', Configuration::get($this->name.'secret'));
    	$smarty->assign($this->name.'positionl', Configuration::get($this->name.'positionl'));
    	
    	$smarty->assign($this->name.'BGCOLORL', Configuration::get($this->name.'BGCOLORL'));
    	//connects
    	$smarty->assign($this->name.'fbbutton', Configuration::get($this->name.'fbbutton'));
		$smarty->assign($this->name.'twbutton', Configuration::get($this->name.'twbutton'));
		$smarty->assign($this->name.'gbutton', Configuration::get($this->name.'gbutton'));
		$smarty->assign('url', $this->url);
    	
    	return $this->display(__FILE__, 'head.tpl');
    }
    
    
     	function hookFooter($params){
    	global $smarty;
		echo '
  <script type="text/javascript" src="http://jzaefferer.github.com/jquery-validation/jquery.validate.js"></script>

	<script type="text/javascript" src="js/jquery/jquery.fancybox-1.3.4.js"></script>
	<link rel="stylesheet" type="text/css" href="css/jquery.fancybox-1.3.4.css" media="screen" />';

		
	
	  include("callback.php");

			
    	
    
    }
    
  
    

    
 	
    
    private function _jsandcss(){
    	$_html = '';
    	$_html .= '<link rel="stylesheet" href="../modules/'.$this->name.'/css/colorpicker.css" type="text/css" />';
        $_html .=  '<link rel="stylesheet" media="screen" type="text/css" href="../modules/'.$this->name.'/css/layout.css" />';
    	$_html .= '<script type="text/javascript" src="../modules/'.$this->name.'/js/colorpicker.js"></script>';
    	$_html .= '<script type="text/javascript" src="../modules/'.$this->name.'/js/eye.js"></script>';
    	$_html .= '<script type="text/javascript" src="../modules/'.$this->name.'/js/utils.js"></script>';
    	$_html .= '<script type="text/javascript" src="../modules/'.$this->name.'/js/layout.js?ver=1.0.2"></script>';
    	return $_html;
    }
    
    private function _colorpicker($data){
    	
    	$name = $data['name'];
    	$color = $data['color'];
    	$title = $data['title'];
    	
    	$_html = '';
    	$_html .= '<label style="margin-top:6px">'.$this->l($title.':').'</label>
					<div class="margin-form">
						<input type="text" 
								id="'.$name.'_val"
							   value="'.Tools::getValue($name, Configuration::get($name)).'" 
								name="'.$name.'" style="float:left;margin-top:6px;margin-right:10px" >';
    	$_html .= '<div id="'.$name.'" style="float:left;"><div style="background-color: '.$color.';"></div></div>
    			  <div style="clear:both"></div>
						<script>$(\'#'.$name.'\').ColorPicker({
								color: \''.$color.'\',
								onShow: function (colpkr) {
									$(colpkr).fadeIn(500);
									return false;
								},
								onHide: function (colpkr) {
									$(colpkr).fadeOut(500);
									return false;
								},
								onChange: function (hsb, hex, rgb) {
									$(\'#'.$name.' div\').css(\'backgroundColor\', \'#\' + hex);
									$(\'#'.$name.'_val\').val(\'\');
									$(\'#'.$name.'_val\').val(\'#\' + hex);
								}
							});</script>';
    	$_html .= '</div>';
    	return $_html;
    }
  
	
}