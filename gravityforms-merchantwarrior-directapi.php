<?php
/*
Plugin Name: Gravity Forms Merchant Warrior Add-On - DirectAPI Method
Plugin URI: http://www.gravityforms.com
Description: Integrates Gravity Forms with Merchant Warrior Payments, enabling end users to purchase goods and services through Gravity Forms using DirectAPI Method 
Version: 1.0.2
Author: Mikhail Portnyagin
Author URI: https://www.upwork.com/fl/mikhailp4
Text Domain: merchantwarrior-directapi
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
	exit;
}

define('GFMWDirectAPI_PLUGIN_FILE', __FILE__);
define('GFMWDirectAPI_PLUGIN_ROOT', dirname(__FILE__) . '/');
define('GFMWDirectAPI_PLUGIN_NAME', basename(dirname(__FILE__)) . '/' . basename(__FILE__));
define('GFMWDirectAPI_PLUGIN_OPTIONS', 'GFMWDirectAPI_plugin');
define('GFMWDirectAPI_PLUGIN_VERSION', '1.0.2');

// error message names
define('GFMWDirectAPI_ERROR_ALREADY_SUBMITTED',    'gfmwdirectapi_err_already');
define('GFMWDirectAPI_ERROR_NO_AMOUNT',            'gfmwdirectapi_err_no_amount');
define('GFMWDirectAPI_ERROR_REQ_CARD_HOLDER',        'gfmwdirectapi_err_req_card_holder');
define('GFMWDirectAPI_ERROR_REQ_CARD_NAME',        'gfmwdirectapi_err_req_card_name');
define('GFMWDirectAPI_ERROR_MW_FAIL',            'gfmwdirectapi_err_mw_fail');

define('GFMWDirectAPI_ERROR_REQ_ADDRESS_COUNTRY',            'gfmwdirectapi_err_address_country');
define('GFMWDirectAPI_ERROR_REQ_ADDRESS_STATE',            'gfmwdirectapi_err_address_state');
define('GFMWDirectAPI_ERROR_REQ_ADDRESS_SUBURB',            'gfmwdirectapi_err_address_suburb');
define('GFMWDirectAPI_ERROR_REQ_ADDRESS',            'gfmwdirectapi_err_address');
define('GFMWDirectAPI_ERROR_REQ_POSTCODE',            'gfmwdirectapi_err_postcode');


// custom fields


// instantiate the plug-in
require GFMWDirectAPI_PLUGIN_ROOT . 'includes/class.GFMWDirectAPIPlugin.php';
GFMWDirectAPIPlugin::getInstance();
