<?php

if (!defined('ABSPATH')) {
	exit;
}

/**
* Class for dealing with an Merchant Warrior Direct API response
*/
class GFWMDirectAPIResponse {


	/**
	* bank authorisation code
	* @var string
	*/
	public $AuthorisationCode;
	
	/**
	* @var string
	*/
	public $ResponseCode;
	
	
	/**
	* array of codes describing the result 
	* @var array
	*/
	public $ResponseMessage;

	/**
	* array of original response data 
	* @var array
	*/
	public $ResponseData;

	/**
	* Merchant Warrior transaction ID
	* @var string
	*/
	public $TransactionID;
	
	
	/**
	* Merchant Warrior transaction status: true for success
	* @var boolean
	*/
	public $TransactionStatus;


	/**
	* a list of errors
	* @var array
	*/
	public $Errors;

	/**
	* load Merchant Warrior response data as array
	* @param string $json Merchant Warrior response as a array 
	*/
	public function loadResponse($response_data) {
		
		// Check for a valid response code
		if ( ! isset( $response_data['responseCode'] ) || strlen( $response_data['responseCode'] ) < 1 ) {
			throw new GFMWDirectAPIException(sprintf(__('Invalid response from Merchant Warrior for Direct payment', 'merchantwarrior-directapi')));
		}

		// Validate the response - the only successful code is 0
		$status = ( (int) $response_data['responseCode'] === 0 ) ? true : false;

		if (!$status){
			$this->Errors = $this->getResponseCodeDetails($response_data['responseCode']); 
		}
		
		$this->TransactionStatus = $status;
		
		if (isset($response_data['authCode'])) $this->AuthorisationCode = $response_data['authCode'];
		
		$this->ResponseCode = (int) $response_data['responseCode'];
		$this->ResponseMessage = array($response_data['responseMessage']);
		
		$this->TransactionID = ( isset( $response_data['transactionID'] ) ? $response_data['transactionID'] : null );
		$this->ResponseData = $response_data;
		
	}
	
	/**
	* Get Merchant Warrior Response code details
	* 
	* @param int $code Merchant Warrior Response code
	*/
	protected function getResponseCodeDetails($code) {
		
		$messages = array();
		if ( $code === -4 ) 
			$messages[] = _x('%s: Internal MWE error (contact MWE support).', 'Merchant Warrior coded response', 'merchantwarrior-directapi');
	
		if ( $code === -3 ) 
		   $messages[] = _x('%s: One of the required fields was not submitted.', 'Merchant Warrior coded response', 'merchantwarrior-directapi');
		   
		if ( $code === -2 ) 
		   $messages[] = _x('%s: One of the submitted fields was invalid.', 'Merchant Warrior coded response', 'merchantwarrior-directapi');
		   
		if ( $code === -1 ) 
		   $messages[] = _x('%s: Invalid authentication credentials supplied.', 'Merchant Warrior coded response', 'merchantwarrior-directapi');
		   
		if ( $code === 1 ) 
		   $messages[] = _x('%s: Transaction could not be processed (server error).', 'Merchant Warrior coded response', 'merchantwarrior-directapi');
		   
		if ( $code === 2 ) 
		   $messages[] = _x('%s: Transaction declined – contact issuing bank.', 'Merchant Warrior coded response', 'merchantwarrior-directapi');
		   
		if ( $code === 3 ) 
		   $messages[] = _x('%s: No reply from processing host (timeout).', 'Merchant Warrior coded response', 'merchantwarrior-directapi');
		
		if ( $code === 4 ) 
		   $messages[] = _x('%s: Card has expired.', 'Merchant Warrior coded response', 'merchantwarrior-directapi');
		   
		if ( $code === 5 ) 
		   $messages[] = _x('%s: Insufficient Funds.', 'Merchant Warrior coded response', 'merchantwarrior-directapi');
		   
		if ( $code === 6 ) 
		   $messages[] = _x('%s: Error communicating with bank.', 'Merchant Warrior coded response', 'merchantwarrior-directapi');
		   
		if ( $code === 7 ) 
		   $messages[] = _x('%s: Bank rejected request.', 'Merchant Warrior coded response', 'merchantwarrior-directapi');
		
		if ( $code === 8 ) 
		   $messages[] = _x('%s: Bank declined transaction – type not supported.', 'Merchant Warrior coded response', 'merchantwarrior-directapi');
		
		if ( $code === 9 ) 
		   $messages[] = _x('%s: Bank declined transaction – do not contact bank.', 'Merchant Warrior coded response', 'merchantwarrior-directapi'); 
		
		if ( $code === 10 ) 
		   $messages[] = _x('%s: Transaction pending.', 'Merchant Warrior coded response', 'merchantwarrior-directapi');        
		return $messages;
	}


}
