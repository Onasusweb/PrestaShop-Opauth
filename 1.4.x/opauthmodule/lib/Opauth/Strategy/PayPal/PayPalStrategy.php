<?php
/**
 * Facebook strategy for Opauth
 * based on https://www.x.com/developers/paypal/documentation-tools/quick-start-guides/oauth-integration-paypal-access-getting-page-2
 * 
 * More information on Opauth: http://opauth.org
 * 
 * @copyright    Copyright © 2012 U-Zyn Chua (http://uzyn.com)
 * @link         http://opauth.org
 * @package      Opauth.PaypalStrategy
 * @license      MIT License
 */
class PaypalStrategy extends OpauthStrategy{
	
	/**
	 * Compulsory config keys, listed as unassociative arrays
	 * eg. array('app_id', 'app_secret');
	 */
	public $expects = array('app_id', 'app_secret');
	
	/**
	 * Optional config keys with respective default values, listed as associative arrays
	 * eg. array('scope' => 'email');
	 */
	public $defaults = array(
		'redirect_uri' => '{complete_url_to_strategy}int_callback'
	);

	/**
	 * Auth request
	 */
	public function request(){
		$url = 'https://identity.x.com/xidentity/resources/authorize';
		$params = array(
			'client_id' => $this->strategy['app_id'],
			'redirect_uri' => $this->strategy['redirect_uri'],
			'scope' => 'https://identity.x.com/xidentity/resources/profile/me',
			'response_type' => 'code'
		);
		if (!empty($this->strategy['scope'])) $params['scope'] = $this->strategy['scope'];
		if (!empty($this->strategy['state'])) $params['state'] = $this->strategy['state'];
		if (!empty($this->strategy['response_type'])) $params['response_type'] = $this->strategy['response_type'];
		if (!empty($this->strategy['display'])) $params['display'] = $this->strategy['display'];
		$this->clientGet($url, $params);
	}
	
	/**
	 * Internal callback, after Paypal's OAuth
	 */
	public function int_callback(){
		if (array_key_exists('code', $_GET) && !empty($_GET['code'])){
			$url = 'https://identity.x.com/xidentity/oauthtokenservice';
			$params = array(
				'client_id' =>$this->strategy['app_id'],
				'client_secret' => $this->strategy['app_secret'],
				'redirect_uri'=> $this->strategy['redirect_uri'],
				'code' => trim($_GET['code']),
				'grant_type' => 'authorization_code'
			);
			$response = $this->serverPost($url, $params, null, $headers);
			$results = json_decode($response, true);
			
			if (!empty($results) && !empty($results['access_token'])){

				$me = $this->me($results['access_token']);
				$this->auth = array(
					'provider' => 'Paypal',
					'uid' => $me['identity']['userId'],
					'info' => array(),
					'credentials' => array(
						'token' => $results['access_token'],
						'expires' => date('c', time() + $results['expires'])
					),
					'raw' => $results
				);
				
				/**
				 * TODO
				 * - I am unable to query any user detail from profile/me
				 */
				
				$this->callback();
			}
			else{
				$error = array(
					'provider' => 'Paypal',
					'code' => 'access_token_error',
					'message' => 'Failed when attempting to obtain access token',
					'raw' => $headers
				);

				$this->errorCallback($error);
			}
		}
		else{
			$error = array(
				'provider' => 'Paypal',
				'code' => $_GET['error'],
				'message' => $_GET['error_description'],
				'raw' => $_GET
			);
			
			$this->errorCallback($error);
		}
	}
	
	/**
	 * Queries PayPal Identity.x for user info
	 *
	 * @param string $access_token 
	 * @return array Parsed JSON results
	 */
	private function me($access_token){
		$me = $this->serverGet('https://identity.x.com/xidentity/resources/profile/me', array('oauth_token' => $access_token), null, $headers);
		if (!empty($me)){
			return json_decode($me, true);
		}
		else{
			$error = array(
				'provider' => 'PayPal',
				'code' => 'me_error',
				'message' => 'Failed when attempting to query for user information',
				'raw' => array(
					'response' => $me,
					'headers' => $headers
				)
			);

			$this->errorCallback($error);
		}
	}
}
