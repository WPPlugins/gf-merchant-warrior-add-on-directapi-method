<?php

if (!defined('ABSPATH')) {
	exit;
}

/**
* custom exception types
*/
class GFMWDirectAPIException extends Exception {}
class GFMWDirectAPICurlException extends Exception {}

/**
* class for managing the plugin
*/
class GFMWDirectAPIPlugin {
	public $options;                                    // array of plugin options

	// minimum versions required
	const MIN_VERSION_GF	= '1.9.15';

	/**
	* static method for getting the instance of this singleton object
	* @return self
	*/
	public static function getInstance() {
		static $instance = NULL;

		if (is_null($instance)) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	* initialise plugin
	*/
	private function __construct() {
		spl_autoload_register(array(__CLASS__, 'autoload'));

		add_action('plugins_loaded', array($this, 'load'));
		add_action('init', array($this, 'loadTextDomain'));
	}

	/**
	* handle the plugins_loaded action
	*/
	public function load() {

		// grab options, setting new defaults for any that are missing
		$defaults = array (
			'customerID'            => '87654321',
			'merchant_uuid'                => '',
			'apikey'            => '',
			'apipassphrase'                => '',
		);
		$this->options = wp_parse_args(get_option(GFMWDirectAPI_PLUGIN_OPTIONS, array()), $defaults);

		// do nothing if Gravity Forms isn't enabled or doesn't meet required minimum version
		if (self::hasMinimumGF()) {
			//add_action('wp_enqueue_scripts', array($this, 'registerScripts'), 20);
		   // add_action('gform_preview_footer', array($this, 'registerScripts'), 5);

			// hook into Gravity Forms to enable credit cards and trap form submissions
			add_action('gform_enqueue_scripts', array($this, 'gformEnqueueScripts'), 20, 2); //todo
			add_filter('gform_logging_supported', array($this, 'enableLogging')); 
			add_filter('gform_pre_render', array($this, 'ecryptModifyForm')); //todo ?
			add_filter('gform_pre_render', array($this, 'gformPreRenderSniff')); 
			add_filter('gform_admin_pre_render', array($this, 'gformPreRenderSniff')); 
			add_action('gform_enable_credit_card_field', '__return_true'); 
			add_filter('gform_pre_validation', array($this, 'ecryptPreValidation')); //todo ?
			add_filter('gform_validation', array($this, 'gformValidation')); 
			add_action('gform_entry_post_save', array($this, 'gformEntryPostSave'), 10, 2); 
			add_filter('gform_custom_merge_tags', array($this, 'gformCustomMergeTags'), 10, 4); // todo ?
			add_filter('gform_replace_merge_tags', array($this, 'gformReplaceMergeTags'), 10, 7); //todo ?
			add_filter('gform_entry_meta', array($this, 'gformEntryMeta'), 10, 2);

		}
		
		if (is_admin()) {
			// kick off the admin handling
			require GFMWDirectAPI_PLUGIN_ROOT . 'includes/class.GFMWDirectAPIAdmin.php';
			new GFMWDirectAPIAdmin($this);
		}
	}
	
	
	/**
	* load text translations
	*/
	public function loadTextDomain() {
		load_plugin_textdomain('merchantwarrior-directapi');
	}
	
	/**
	* compare Gravity Forms version against minimum required version
	* @return bool
	*/
	public static function hasMinimumGF() {
		return self::versionCompareGF(self::MIN_VERSION_GF, '>=');
	}
	
	/**
	* check current form for information (front-end and admin)
	* @param array $form
	* @return array
	*/
	public function gformPreRenderSniff($form) {
		// test whether form has a credit card field
		$this->formHasCcField = self::isMWForm($form['id'], $form['fields']);

		return $form;
	}
	
	
	/**
	* see if form is an MWDirectAPI credit card form
	* @param int $form_id
	* @param array $fields
	* @return bool
	*/
	public static function isMWForm($form_id, $fields) {
		static $mapFormsHaveCC = array();

		// see whether we've already checked
		if (isset($mapFormsHaveCC[$form_id])) {
			return $mapFormsHaveCC[$form_id];
		}

		$isMWForm = self::hasFieldType($fields, 'creditcard');

		$isMWForm = apply_filters('GFMWDirectAPI_form_is_mw', $isMWForm, $form_id);
		$isMWForm = apply_filters('GFMWDirectAPI_form_is_mw_' . $form_id, $isMWForm);

		$mapFormsHaveCC[$form_id] = $isMWForm;

		return $isMWForm;
	}
	
	/**
	* check form to see if it has a field of specified type
	* @param array $fields array of fields
	* @param string $type name of field type
	* @return boolean
	*/
	public static function hasFieldType($fields, $type) {
		if (is_array($fields)) {
			foreach ($fields as $field) {
				if (RGFormsModel::get_input_type($field) === $type) {
					return true;
				}
			}
		}
		return false;
	}
	
	/**
	* compare Gravity Forms version against target
	* @param string $target
	* @param string $operator
	* @return bool
	*/
	public static function versionCompareGF($target, $operator) {
		if (class_exists('GFCommon')) {
			return version_compare(GFCommon::$version, $target, $operator);
		}

		return false;
	}
	
	/**
	* enable Gravity Forms Logging Add-On support for this plugin
	* @param array $plugins
	* @return array
	*/
	public function enableLogging($plugins){
		$plugins['GFMWDirectAPI'] = __('Gravity Forms Merchant Warrior', 'merchantwarrior-directapi');

		return $plugins;
	}
	
	/**
	* autoload classes as/when needed
	*
	* @param string $class_name name of class to attempt to load
	*/
	public static function autoload($class_name) {
		
		static $classMap = array (
			'GFMWDirectAPI'                        => 'includes/class.GFMWDirectAPI.php',
			'GFWMDirectAPIResponse'            => 'includes/class.GFWMDirectAPIResponse.php',
		);

		if (isset($classMap[$class_name])) {
			require GFMWDirectAPI_PLUGIN_ROOT . $classMap[$class_name];
		}
	}
	
	/**
	* set form modifiers for Merachant Warrior client side encryption
	* @param array $form
	* @return array
	*/
	public function ecryptModifyForm($form) {
		//todo ?

		return $form;
	}
	
	/**
	* enqueue additional scripts if required by form
	* @param array $form
	* @param boolean $ajax
	*/
	public function gformEnqueueScripts($form, $ajax) {
		$a=1;  //todo
	}
	
	
	/**
	* put something back into Credit Card field inputs, to enable validation when using Merchant Warrior Client Side Encryption
	* @param array $form
	* @return array
	*/
	public function ecryptPreValidation($form) {
	   
		//todo

		return $form;
	}
	
	/**
	* process a form validation filter hook; if last page and has credit card field and total, attempt to bill it
	* @param array $data an array with elements is_valid (boolean) and form (array of form elements)
	* @return array
	*/
	public function gformValidation($data) {
		//debugbreak();
		// make sure all other validations passed
		if ($data['is_valid'] && self::isMWForm($data['form']['id'], $data['form']['fields'])) {
			require GFMWDirectAPI_PLUGIN_ROOT . 'includes/class.GFMWDirectAPIFormData.php';
			$formData = new GFMWDirectAPIFormData($data['form']);

			// make sure form hasn't already been submitted / processed
			if ($this->hasFormBeenProcessed($data['form'])) {
				$data['is_valid'] = false;
				$formData->ccField['failed_validation']        = true;
				$formData->ccField['validation_message']    = $this->getErrMsg(GFMWDirectAPI_ERROR_ALREADY_SUBMITTED);
			}

			// make that this is the last page of the form and that we have a credit card field and something to bill
			// and that credit card field is not hidden (which indicates that payment is being made another way)
			else if (!$formData->isCcHidden() && $formData->isLastPage() && $formData->ccField !== false) {
				if (!$formData->hasPurchaseFields()) {
					$data['is_valid'] = false;
					$formData->ccField['failed_validation']        = true;
					$formData->ccField['validation_message']    = $this->getErrMsg(GFMWDirectAPI_ERROR_NO_AMOUNT);
				}
				else {
					// only check credit card details if we've got something to bill
					if ($formData->total > 0) {
						// check for required fields
						$required = array(
							'ccName'    => $this->getErrMsg(GFMWDirectAPI_ERROR_REQ_CARD_HOLDER),
							'ccNumber'    => $this->getErrMsg(GFMWDirectAPI_ERROR_REQ_CARD_NAME),
							'address_country' => $this->getErrMsg(GFMWDirectAPI_ERROR_REQ_ADDRESS_COUNTRY),
							'address_state'    => $this->getErrMsg(GFMWDirectAPI_ERROR_REQ_ADDRESS_STATE),
							'address_suburb'    => $this->getErrMsg(GFMWDirectAPI_ERROR_REQ_ADDRESS_SUBURB),
							'address_street1'    => $this->getErrMsg(GFMWDirectAPI_ERROR_REQ_ADDRESS),
							'postcode'    => $this->getErrMsg(GFMWDirectAPI_ERROR_REQ_POSTCODE),
						);
						foreach ($required as $name => $message) {
							if (empty($formData->$name)) {
								$data['is_valid'] = false;
								$formData->ccField['failed_validation'] = true;
								if (!empty($formData->ccField['validation_message'])) {
									$formData->ccField['validation_message'] .= '<br />';
								}
								$formData->ccField['validation_message'] .= $message;
							}
						}

						// if no errors, try to bill it
						if ($data['is_valid']) {
							$data = $this->processSinglePayment($data, $formData);  
						}
					}
				}
			}

			// if errors, send back to credit card page
			if (!$data['is_valid']) {
				GFFormDisplay::set_current_page($data['form']['id'], $formData->ccField['pageNumber']);
			}
		}

		return $data;
	}
	
	
	/**
	* process regular one-off payment
	* @param array $data an array with elements is_valid (boolean) and form (array of form elements)
	* @param GFMWDirectAPIFormData $formData pre-parsed data from $data
	* @return array
	*/
	protected function processSinglePayment($data, $formData) {
		
		try {
			$mw_direct_api = $this->getPaymentRequestor();


			$mw_direct_api->customerIP                = self::getCustomerIP();
			$mw_direct_api->invoiceDescription        = get_bloginfo('name') . " -- {$data['form']['title']}";
			$mw_direct_api->invoiceReference            = $data['form']['id']; //todo
			$mw_direct_api->currencyCode                = GFCommon::get_currency();
			if (empty($formData->firstName) && empty($formData->lastName)) {
				$mw_direct_api->lastName                = $formData->ccName;                // pick up card holder's name for last name
			}
			else {
				$mw_direct_api->firstName            = $formData->firstName;
				$mw_direct_api->lastName                = $formData->lastName;
			}
			$mw_direct_api->cardHoldersName            = $formData->ccName;
			$mw_direct_api->cardNumber                = $formData->ccNumber;
			$mw_direct_api->cardExpiryMonth            = $formData->ccExpMonth;
			$mw_direct_api->cardExpiryYear            = $formData->ccExpYear;
			$mw_direct_api->emailAddress                = $formData->email;
			$mw_direct_api->address1                    = $formData->address_street1;
			$mw_direct_api->address2                    = $formData->address_street2;
			$mw_direct_api->suburb                    = $formData->address_suburb;
			$mw_direct_api->state                    = $formData->address_state;
			$mw_direct_api->postcode                    = $formData->postcode;
	
			$mw_direct_api->country                    = $formData->address_country ? GFCommon::get_country_code($formData->address_country) : '';
			$mw_direct_api->cardVerificationNumber    = $formData->ccCVN;

			// generate a unique transaction ID to avoid collisions, e.g. between different installations using the same Merchant Warrior account
			$mw_direct_api->transactionNumber = substr(uniqid(), -12); //todo

			// allow plugins/themes to modify invoice description and reference, and set option fields
			$mw_direct_api->invoiceDescription        = apply_filters('gfewdirectapi_invoice_desc', $mw_direct_api->invoiceDescription, $data['form']);
			$mw_direct_api->invoiceReference            = apply_filters('gfewdirectapi_invoice_ref', $mw_direct_api->invoiceReference, $data['form']);
			$mw_direct_api->transactionNumber        = apply_filters('gfewdirectapi_invoice_trans_number', $mw_direct_api->transactionNumber, $data['form']);
		
	
			$mw_direct_api->amount = $formData->total;
		

			self::log_debug(sprintf('%s: %s gateway, invoice ref: %s, transaction: %s, amount: %s, currency: %s, cc: %s',
				__FUNCTION__, 'live', $mw_direct_api->invoiceReference, $mw_direct_api->transactionNumber,
				$mw_direct_api->amount, $mw_direct_api->currencyCode, $mw_direct_api->cardNumber));

			// record basic transaction data, for updating the entry with later
			$this->txResult = array (
				'payment_gateway'        => 'gfmwdirectapi',
				'gfmwdirectapi_unique_id'        => GFFormsModel::get_form_unique_id($data['form']['id']),    // reduces risk of double-submission
			);

			$response = $mw_direct_api->processPayment();
			if ($response->TransactionStatus) {
				// transaction was successful, so record details and continue
				$this->txResult['payment_status']    =  'Approved';
				$this->txResult['payment_date']        = date('Y-m-d H:i:s');
				$this->txResult['payment_amount']    = $mw_direct_api->getFormattedAmount();
				$this->txResult['transaction_id']    = $response->TransactionID;
				$this->txResult['transaction_type']    = 1;
				$this->txResult['authcode']            = $response->AuthorisationCode;
			

				self::log_debug(sprintf('%s: success, date = %s, id = %s, status = %s, amount = %s, authcode = %s',
					__FUNCTION__, $this->txResult['payment_date'], $response->TransactionID, $this->txResult['payment_status'],
					$mw_direct_api->getFormattedAmount(), $response->AuthorisationCode));
				if (!empty($response->ResponseMessage)) {
					self::log_debug(sprintf('%s: %s', __FUNCTION__, implode('; ', $response->ResponseMessage)));
				}
			}
			else {
				$data['is_valid'] = false;
				$formData->ccField['failed_validation']        = true;
				$formData->ccField['validation_message']    = $this->getErrMsg(GFMWDirectAPI_ERROR_MW_FAIL);
				$this->txResult['payment_status']            = 'Failed';
				$this->txResult['authcode']                    = '';            // empty bank authcode, for conditional logic

				if (!empty($response->Errors)) {
					$formData->ccField['validation_message'] .= ':<br/>' . nl2br(esc_html(implode("\n", $response->Errors)));
				}
				elseif (!empty($response->ResponseMessage)) {
					$formData->ccField['validation_message'] .= ' (' . esc_html(implode(',', array_values($response->ResponseMessage))) . ')';
				}

				self::log_debug(sprintf('%s: failed; %s', __FUNCTION__, implode('; ', array_merge($response->Errors, $response->ResponseMessage))));
				
			}
		}
		catch (GFMWDirectAPIException $e) {
			$data['is_valid'] = false;
			$formData->ccField['failed_validation']            = true;
			$formData->ccField['validation_message']        = nl2br($this->getErrMsg(GFMWDirectAPI_ERROR_MW_FAIL) . esc_html(":\n{$e->getMessage()}"));
			$this->txResult['payment_status']                = 'Failed';
			$this->txResult['authcode']                        = '';            // empty bank authcode, for conditional logic

			self::log_error(__METHOD__ . ": " . $e->getMessage());
		}

		return $data;
	}
	
	/**
	* get payment object
	* @return object
	* @throws GFMWDirectAPIException
	*/
	protected function getPaymentRequestor() {
		$mw_direct_api = null;
	
		$creds = $this->getMWDirectAPICredentials();

		// Direct API
		$mw_direct_api = new GFMWDirectAPI($creds['merchant_uuid'], $creds['apikey'], $creds['apipassphrase']);
	
		return $mw_direct_api;
	}
	
	/**
	* get Merchant Wairrior direct API credentials
	* @return string
	*/
	protected function getMWDirectAPICredentials() {
		// get defaults from add-on settings
		$creds = array(
			'merchant_uuid'        => $this->options['merchant_uuid'],
			'apikey'        => $this->options['apikey'],
			'apipassphrase'        => $this->options['apipassphrase'],
		);

		return $creds;
	}
	
	/**
	* check whether this form entry's unique ID has already been used; if so, we've already done a payment attempt.
	* @param array $form
	* @return boolean
	*/
	protected function hasFormBeenProcessed($form) {
		global $wpdb;

		$unique_id = RGFormsModel::get_form_unique_id($form['id']);

		$sql = "select lead_id from {$wpdb->prefix}rg_lead_meta where meta_key='gfmwdirectapi_unique_id' and meta_value = %s";
		$lead_id = $wpdb->get_var($wpdb->prepare($sql, $unique_id));

		return !empty($lead_id);
	}
	
	/**
	* form entry post-submission processing
	* @param array $entry
	* @param array $form
	* @return array
	*/
	public function gformEntryPostSave($entry, $form) {
		
		if (!empty($this->txResult['payment_status'])) {

			foreach ($this->txResult as $key => $value) {
				switch ($key) {
					case 'payment_status':
					case 'payment_date':
					case 'payment_amount':
					case 'transaction_id':
					case 'transaction_type':
					case 'payment_gateway':                // custom entry meta must be saved with entry
					case 'authcode':                    // custom entry meta must be saved with entry
						// update entry
						$entry[$key] = $value;
						break;

					default:
						// update entry meta
						gform_update_meta($entry['id'], $key, $value);
						break;
				}
			}

			GFAPI::update_entry($entry);

		}

		return $entry;

		return $entry;
	}
	
		/**
	* add custom merge tags
	* @param array $merge_tags
	* @param int $form_id
	* @param array $fields
	* @param int $element_id
	* @return array
	*/
	public function gformCustomMergeTags($merge_tags, $form_id, $fields, $element_id) {
		//todo ?

		return $merge_tags;
	}
	
	/**
	* replace custom merge tags
	* @param string $text
	* @param array $form
	* @param array $lead
	* @param bool $url_encode
	* @param bool $esc_html
	* @param bool $nl2br
	* @param string $format
	* @return string
	*/
	public function gformReplaceMergeTags($text, $form, $lead, $url_encode, $esc_html, $nl2br, $format) {
		
		//todo ?

		return $text;
	}
	
	/**
	* activate and configure custom entry meta
	* @param array $entry_meta
	* @param int $form_id
	* @return array
	*/
	public function gformEntryMeta($entry_meta, $form_id) {

		$entry_meta['payment_gateway'] = array(
			'label'                    => _x('Payment Gateway!!!', 'entry meta label', 'gravityforms-merchantwarrior-directapi'),
			'is_numeric'            => false,
			'is_default_column'        => false,
			'filter'                => array(
											'operators' => array('is', 'isnot')
										),
		);

		$entry_meta['authcode'] = array(
			'label'                    => _x('AuthCode', 'entry meta label', 'gravityforms-merchantwarrior-directapi'),
			'is_numeric'            => false,
			'is_default_column'        => false,
			'filter'                => array(
											'operators' => array('is', 'isnot')
										),
		);

		return $entry_meta;
	}
	
	
	/**
	* get nominated error message, checking for custom error message in WP options
	* @param string $errName the fixed name for the error message (a constant)
	* @param boolean $useDefault whether to return the default, or check for a custom message
	* @return string
	*/
	public function getErrMsg($errName, $useDefault = false) {
		static $messages = false;

		if ($messages === false) {
			$messages = array (
				GFMWDirectAPI_ERROR_ALREADY_SUBMITTED  => __('Payment already submitted and processed - please close your browser window', 'merchantwarrior-directapi'),
				GFMWDirectAPI_ERROR_NO_AMOUNT          => __('This form has credit card fields, but no products or totals', 'merchantwarrior-directapi'),
				GFMWDirectAPI_ERROR_REQ_CARD_HOLDER    => __('Card holder name is required for credit card processing', 'merchantwarrior-directapi'),
				GFMWDirectAPI_ERROR_REQ_CARD_NAME      => __('Card number is required for credit card processing', 'merchantwarrior-directapi'),
				GFMWDirectAPI_ERROR_MW_FAIL          => __('Transaction failed', 'merchantwarrior-directapi'),
				GFMWDirectAPI_ERROR_REQ_ADDRESS_COUNTRY => __('Country is required for credit card processing', 'merchantwarrior-directapi'),
				GFMWDirectAPI_ERROR_REQ_ADDRESS_STATE => __('State is required for credit card processing', 'merchantwarrior-directapi'),
				GFMWDirectAPI_ERROR_REQ_ADDRESS_SUBURB => __('Suburb is required for credit card processing', 'merchantwarrior-directapi'),
				GFMWDirectAPI_ERROR_REQ_ADDRESS => __('Address is required for credit card processing', 'merchantwarrior-directapi'),
				GFMWDirectAPI_ERROR_REQ_POSTCODE => __('Postcode is required for credit card processing', 'merchantwarrior-directapi'),
			);
		}

		// default
		$msg = isset($messages[$errName]) ? $messages[$errName] : __('Unknown error', 'merchantwarrior-directapi');

		// check for custom message
		if (!$useDefault) {
			// check that messages are stored in options array; only since v1.8.0
			if (isset($this->options[$errName])) {
				if (!empty($this->options[$errName])) {
					$msg = $this->options[$errName];
				}
			}
			else {
				// pre-1.8.0 settings stored individually, not using settings API
				$msg = get_option($errName, $msg);
			}
		}

		return $msg;
	}
	
	/**
	* get the customer's IP address dynamically from server variables
	* @return string
	*/
	public static function getCustomerIP() {
		// if test mode and running on localhost, then kludge to an Aussie IP address
		$plugin = self::getInstance();
		if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] === '127.0.0.1') {
			return '210.1.199.10';
		}

		// check for remote address, ignore all other headers as they can be spoofed easily
		if (isset($_SERVER['REMOTE_ADDR']) && self::isIpAddress($_SERVER['REMOTE_ADDR'])) {
			return $_SERVER['REMOTE_ADDR'];
		}

		return '';
	}
	
	
	/**
	* check whether a given string is an IP address
	* @param string $maybeIP
	* @return bool
	*/
	protected static function isIpAddress($maybeIP) {
		if (function_exists('inet_pton')) {
			// check for IPv4 and IPv6 addresses
			return !!inet_pton($maybeIP);
		}

		// just check for IPv4 addresses
		return !!ip2long($maybeIP);
	}
	
	/**
	* write an debug message log via the Gravity Forms Logging Add-On
	* @param string $message
	*/
	public static function log_debug($message){
		if (class_exists('GFLogging')) {
			GFLogging::include_logger();
			GFLogging::log_message('gfmwdirectapi', self::sanitiseLog($message), KLogger::DEBUG);
		}
	}
	
	/**
	* sanitise a logging message to obfuscate credit card details before storing in plain text!
	* @param string $message
	* @return string
	*/
	protected static function sanitiseLog($message) {
		// credit card number, a string of at least 12 numeric digits
		$message = preg_replace('#[0-9]{8,}([0-9]{4})#', '************$1', $message);

		return $message;
	}

}
