<?php

if (!defined('ABSPATH')) {
	exit;
}

/**
* Class for dealing with an Merchant Warrior Direct API payment
*/
class GFMWDirectAPI {

	// API endpoint
	const API_DIRECT_PAYMENT						= 'https://base.merchantwarrior.com/post/';
	
	// valid actions
	const METHOD_PAYMENT					= 'processCard'; //

	
	public $merchant_uuid; //

	public $apikey; //
	
	public $apipassphrase; //
	
	

	/**
	* Beagle: IP address of purchaser (from REMOTE_ADDR)
	* @var string max. 50 characters
	*/
	public $customerIP; //

	/**
	* ID of device or application processing the transaction
	* @var string max. 50 characters
	*/
	public $deviceID;

	#endregion // "connection specific members"

	#region "payment specific members"

	/**
	* action to perform: one of the METHOD_* values
	* @var string
	*/
	public $method;

	/**
	* a unique transaction number from your site
	* @var string max. 12 characters
	*/
	public $transactionNumber;

	/**
	* an invoice reference to track by (NB: see transactionNumber which is intended for invoice number or similar)
	* @var string max. 50 characters
	*/
	public $invoiceReference;

	/**
	* description of what is being purchased / paid for
	* @var string max. 64 characters
	*/
	public $invoiceDescription; //

	/**
	* total amount of payment, in dollars and cents as a floating-point number (will be converted to just cents for transmission)
	* @var float
	*/
	public $amount; //

	/**
	* ISO 4217 currency code
	* @var string 3 characters in uppercase
	*/
	public $currencyCode; //

	// customer and billing details

	/**
	* customer's title
	* @var string max. 5 characters
	*/
	public $title;

	/**
	* customer's first name
	* @var string max. 50 characters
	*/
	public $firstName; 

	/**
	* customer's last name
	* @var string max. 50 characters
	*/
	public $lastName;

	/**
	* customer's company name
	* @var string max. 50 characters
	*/
	public $companyName;

	/**
	* customer's job description (e.g. position)
	* @var string max. 50 characters
	*/
	public $jobDescription;

	/**
	* customer's address line 1
	* @var string max. 50 characters
	*/
	public $address1; //

	/**
	* customer's address line 2
	* @var string max. 50 characters
	*/
	public $address2; //

	/**
	* customer's suburb/city/town
	* @var string max. 50 characters
	*/
	public $suburb; //

	/**
	* customer's state/province
	* @var string max. 50 characters
	*/
	public $state; //

	/**
	* customer's postcode
	* @var string max. 30 characters
	*/
	public $postcode; //

	/**
	* customer's country code
	* @var string 2 characters lowercase
	*/
	public $country; //

	/**
	* customer's email address
	* @var string max. 50 characters
	*/
	public $emailAddress; //

	/**
	* customer's phone number
	* @var string max. 32 characters
	*/
	public $phone; //

	/**
	* customer's mobile phone number
	* @var string max. 32 characters
	*/
	public $mobile; //

	/**
	* customer's fax number
	* @var string max. 32 characters
	*/
	public $fax;

	/**
	* customer's website URL
	* @var string max. 512 characters
	*/
	public $website;

	/**
	* comments about the customer
	* @var string max. 255 characters
	*/
	public $comments;

	// card details

	/**
	* name on credit card
	* @var string max. 50 characters
	*/
	public $cardHoldersName; //

	/**
	* credit card number, with no spaces
	* @var string max. 50 characters
	*/
	public $cardNumber; //

	/**
	* month of expiry, numbered from 1=January
	* @var integer max. 2 digits
	*/
	public $cardExpiryMonth; //

	/**
	* year of expiry
	* @var integer will be truncated to 2 digits, can accept 4 digits
	*/
	public $cardExpiryYear; //

	/**
	* start month, numbered from 1=January
	* @var integer max. 2 digits
	*/
	public $cardStartMonth;

	/**
	* start year
	* @var integer will be truncated to 2 digits, can accept 4 digits
	*/
	public $cardStartYear;

	/**
	* card issue number
	* @var string
	*/
	public $cardIssueNumber;

	/**
	* CVN (Creditcard Verification Number) for verifying physical card is held by buyer
	* @var string max. 3 or 4 characters (depends on type of card)
	*/
	public $cardVerificationNumber; //

	/**
	* optional additional information for use in shopping carts, etc.
	* @var array[string] max. 254 characters each
	*/
	public $options = array();

	#endregion "payment specific members"

	#endregion "members"

	/**
	* populate members with defaults, and set account and environment information
	* @param string $merchant_uuid 
	* @param string $apikey 
	* @param boolean $apipassphrase 
	*/
	public function __construct($merchant_uuid, $apikey, $apipassphrase) {
		$this->merchant_uuid		= $merchant_uuid;
		$this->apikey	            = $apikey;
		$this->apipassphrase        = $apipassphrase;
	
	}

	/**
	* process a payment against Merchant Warrior; throws exception on error with error described in exception message.
	* @throws GFMWDirectAPIException
	*/
	public function processPayment() {
		$this->validate();
		$request_params = $this->getPayment();
		return $this->sendPaymentDirect($request_params);
	}

	/**
	* validate the data members to ensure that sufficient and valid information has been given
	*/
	protected function validate() {
		$errors = array();

		if (!is_numeric($this->amount) || $this->amount <= 0) {
			$errors[] = __('amount must be given as a number', 'merchantwarrior-directapi');
		}
		else if (!is_float($this->amount)) {
			$this->amount = (float) $this->amount;
		}
		if (strlen($this->cardHoldersName) === 0) {
			$errors[] = __('cardholder name cannot be empty', 'merchantwarrior-directapi');
		}
		if (strlen($this->cardNumber) === 0) {
			$errors[] = __('card number cannot be empty', 'merchantwarrior-directapi');
		}

		// make sure that card expiry month is a number from 1 to 12
		if (!is_int($this->cardExpiryMonth)) {
			if (strlen($this->cardExpiryMonth) === 0) {
				$errors[] = __('card expiry month cannot be empty', 'merchantwarrior-directapi');
			}
			elseif (!ctype_digit($this->cardExpiryMonth)) {
				$errors[] = __('card expiry month must be a number between 1 and 12', 'merchantwarrior-directapi');
			}
			else {
				$this->cardExpiryMonth = intval($this->cardExpiryMonth);
			}
		}
		if (is_int($this->cardExpiryMonth)) {
			if ($this->cardExpiryMonth < 1 || $this->cardExpiryMonth > 12) {
				$errors[] = __('card expiry month must be a number between 1 and 12', 'merchantwarrior-directapi');
			}
		}

		// make sure that card expiry year is a 2-digit or 4-digit year >= this year
		if (!is_int($this->cardExpiryYear)) {
			if (strlen($this->cardExpiryYear) === 0) {
				$errors[] = __('card expiry year cannot be empty', 'merchantwarrior-directapi');
			}
			elseif (!ctype_digit($this->cardExpiryYear)) {
				$errors[] = __('card expiry year must be a two or four digit year', 'merchantwarrior-directapi');
			}
			else {
				$this->cardExpiryYear = intval($this->cardExpiryYear);
			}
		}
		if (is_int($this->cardExpiryYear)) {
			$thisYear = intval(date_create()->format('Y'));
			if ($this->cardExpiryYear < 0 || $this->cardExpiryYear >= 100 && $this->cardExpiryYear < 2000 || $this->cardExpiryYear > $thisYear + 20) {
				$errors[] = __('card expiry year must be a two or four digit year', 'merchantwarrior-directapi');
			}
			else {
				if ($this->cardExpiryYear > 100 && $this->cardExpiryYear < $thisYear) {
					$errors[] = __("card expiry can't be in the past", 'merchantwarrior-directapi');
				}
				else if ($this->cardExpiryYear < 100 && $this->cardExpiryYear < ($thisYear - 2000)) {
					$errors[] = __("card expiry can't be in the past", 'merchantwarrior-directapi');
				}
			}
		}

		if (count($errors) > 0) {
			throw new GFMWDirectAPIException(implode("\n", $errors));
		}
	}

	/**
	* prepare request parameters array for payment
	* @return string
	*/
	public function getPayment() {
		$request = array();
		 
		$request['method']              = self::METHOD_PAYMENT;
		$request['merchantUUID']        = $this->merchant_uuid;
		$request['apiKey']              = $this->apikey;
		$request['transactionAmount']   = $this->getFormattedAmount();
		$request['transactionCurrency'] = $this->currencyCode;
		$request['transactionProduct']  = $this->invoiceDescription ? substr($this->invoiceDescription, 0, 34) : '';
		$request['customerName']        = $this->cardHoldersName; 
		$request['customerCountry']     = strtolower($this->country);
		$request['customerState']       = $this->state;
		$request['customerCity']        = $this->suburb;
		$request['customerAddress']     = $this->address1 . ', ' . $this->address2;
		$request['customerPostCode']    = $this->postcode;
		$request['customerPhone']       = $this->phone ? $this->phone : $this->mobile;
		$request['customerEmail']       = $this->emailAddress;
		$request['customerIP']          = $this->customerIP;
		$request['paymentCardNumber']   = $this->cardNumber;
		$request['paymentCardName']     = $this->cardHoldersName; 
		$request['paymentCardExpiry']   = sprintf('%02d', $this->cardExpiryMonth) . sprintf('%02d', $this->cardExpiryYear % 100);
		$request['paymentCardCSC']      = $this->cardVerificationNumber;
		$request['custom_1']            = '';
		$request['custom_2']            = '';
		$request['custom_3']            = '';
		$request['hash']                = $this->getPaymentHash( $request ); 

		return $request;
	}

	public function getFormattedAmount(){
		return  number_format((float)$this->amount, 2, '.', '');    
	}
	
	protected function getPaymentHash($data){
		$hash_text = md5( $this->apipassphrase );
		$hash_text .= $data['merchantUUID'];
		$hash_text .= $data['transactionAmount'];
		$hash_text .= $data['transactionCurrency'];

		$hash = md5( strtolower( $hash_text ) ); 
		
		return  $hash;  
	}


	/**
	* send the Merchant Warrior payment request and retrieve and parse the response
	* @param string $request_data Merchant Warrior payment request parameters as array, per Merchant Warrior specifications
	* @return GFWMDirectAPIResponse
	* @throws GFMWDirectAPIException
	*/
	protected function sendPaymentDirect($request_data) {
		
		// select endpoint
		$url	= self::API_DIRECT_PAYMENT;

		 // Setup CURL defaults
		$curl = curl_init();
		curl_setopt( $curl, CURLOPT_TIMEOUT, 60 );
		curl_setopt( $curl, CURLOPT_CONNECTTIMEOUT, 30 );
		curl_setopt( $curl, CURLOPT_FRESH_CONNECT, true );
		curl_setopt( $curl, CURLOPT_FORBID_REUSE, true );
		curl_setopt( $curl, CURLOPT_HEADER, false );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );

		// Setup CURL params for this request
		curl_setopt( $curl, CURLOPT_URL, $url );
		curl_setopt( $curl, CURLOPT_POST, true );
		curl_setopt( $curl, CURLOPT_POSTFIELDS, http_build_query( $request_data, '', '&' ) );

		// Run CURL
		$response = curl_exec( $curl );
		$error    = curl_error( $curl );

		// Check for CURL errors
		if ( isset( $error ) && strlen( $error ) ) {
			throw new GFMWDirectAPIException(sprintf(__('CURL Error: %s', 'merchantwarrior-directapi'), $error));
		}

		// Make sure the API returned something
		if ( ! isset( $response ) || strlen( $response ) < 1 ) {
			throw new GFMWDirectAPIException(sprintf(__('API response was empty')));
		}

		// Parse the XML
		$xml = simplexml_load_string( $response );
		// Convert the result from a SimpleXMLObject into an array
		$response_data = (array) $xml;
	
		$response = new GFWMDirectAPIResponse();
		$response->loadResponse($response_data);
		
		return $response;
	}

}
