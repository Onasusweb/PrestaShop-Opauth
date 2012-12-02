<?php
 
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
	'callback_url' => $opauth->url.'/authentication.php',
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
			'app_id' => ''.Configuration::get($opauth->name.'_facebook_app_id').'',
			'app_secret' => ''.Configuration::get($opauth->name.'_facebook_app_secret').'',
            'scope' => 'email'
		),
		
		'Twitter' => array(
			'key' => ''.Configuration::get($opauth->name.'_twitter_consumer_key').'',
			'secret' => ''.Configuration::get($opauth->name.'_twitter_consumer_secret').''
		),

		'Google' => array(
			'client_id' => ''.Configuration::get($opauth->name.'_google_client_id').'',
			'client_secret' => ''.Configuration::get($opauth->name.'_google_client_secret').''
		),

		'LinkedIn' => array(
			'api_key' => ''.Configuration::get($opauth->name.'_linkedin_api_key').'',
			'secret_key' => ''.Configuration::get($opauth->name.'_linkedin_secret_key').'',
		    'scope' => 'r_emailaddress'
		),				
		
		'PayPal' => array(
			'app_id' => ''.Configuration::get($opauth->name.'_paypal_app_id').'',
			'app_secret' => ''.Configuration::get($opauth->name.'_paypal_app_secret').''
		)
	
	),
);