<?php
/**
 * Opauth basic configuration file to quickly get you started
 * ==========================================================
 * To use: rename to opauth.conf.php and tweak as you like
 * If you require advanced configuration options, refer to opauth.conf.php.advanced
 */




 
$config_op = array(
/**
 * Path where Opauth is accessed.
 *  - Begins and ends with /
 *  - eg. if Opauth is reached via http://example.org/auth/, path is '/auth/'
 *  - if Opauth is reached via http://auth.example.org/, path is '/'
 */
	'path' => $opauth->path,

/**
 * Callback URL: redirected to after authentication, successful or otherwise
 */
 //	'callback_url' => '{path}callback.php',
	'callback_url' => $opauth->url.'\authentication.php',
/**
 * A random string used for signing of $auth response.
 * 
 * NOTE: PLEASE CHANGE THIS INTO SOME OTHER RANDOM STRING
 */
	'security_salt' => 'LDFmiiojlYf8Fyw5W10rx4W1KsVrieQCnpBzzpTBWA5vJidQKDx8pMJbmw28R1C4m',
		
/**
 * Strategy
 * Refer to individual strategy's documentation on configuration requirements.
 * 
 * eg.
 * 'Strategy' => array(
 * 
 *   'Facebook' => array(
 *      'app_id' => 'APP ID',
 *      'app_secret' => 'APP_SECRET'
 *    ),
 * 
 * )
 *
 */
	'Strategy' => array(
		// Define strategies and their respective configs here
		
		'Facebook' => array(
			'app_id' => ''.Tools::getValue('appid', Configuration::get($opauth->name.'appid')).'',
			'app_secret' => ''.Tools::getValue('appid', Configuration::get($opauth->name.'secret')).'',
            'scope' => 'email'
		),
		
		'Google' => array(
			'client_id' => ''.Configuration::get($opauth->name.'googleid').'',
			'client_secret' => ''.Configuration::get($opauth->name.'googlesecret').''
		),
		
		'Twitter' => array(
			'key' => ''.Configuration::get($opauth->name.'twitterconskey').'',
			'secret' => ''.Configuration::get($opauth->name.'twitterconssecret').''
		),
				
	),
);