<?php

if (!defined('ABSPATH')) {
	exit;
}

/**
* class for admin screens
*/
class GFMWDirectAPIAdmin {

	private $plugin;

	/**
	* @param GFMWDirectAPIPlugin $plugin
	*/
	public function __construct($plugin) {
		$this->plugin = $plugin;

		// handle basic plugin actions and filters
		add_action('admin_init', array($this, 'adminInit'));

		add_filter('plugin_row_meta', array($this, 'addPluginDetailsLinks'), 10, 2);

		
	}

	/**
	* handle admin init action
	*/
	public function adminInit() {
		
		if (isset($_GET['page'])) {
			switch ($_GET['page']) {
				case 'gf_settings':
					// add our settings page to the Gravity Forms settings menu
					RGForms::add_settings_page(_x('Merchant Warrior Payments', 'settings page', 'merchantwarrior-directapi'), array($this, 'settingsPage'));
					break;
			}
		}

		add_settings_section(GFMWDirectAPI_PLUGIN_OPTIONS, false, false, GFMWDirectAPI_PLUGIN_OPTIONS);
		register_setting(GFMWDirectAPI_PLUGIN_OPTIONS, GFMWDirectAPI_PLUGIN_OPTIONS, array($this, 'settingsValidate'));

	}



	/**
	* add plugin action links
	*/
	public function addPluginActionLinks($links) {
		$url = esc_url(admin_url('admin.php?page=gf_settings&subview=merchantwarrior-directapi'));
		$settings_link = sprintf('<a href="%s">%s</a>', $url, _x('Settings', 'plugin details links', 'merchantwarrior-directapi'));
		array_unshift($links, $settings_link);

		return $links;
	}

	/**
	* add plugin details links
	*/
	public static function addPluginDetailsLinks($links, $file) {
		if ($file === GFMWDirectAPI_PLUGIN_NAME) {
			$links[] = sprintf('<a href="https://www.upwork.com/fl/mikhailp4" target="_blank">%s</a>', _x('Get help', 'plugin details links', 'merchantwarrior-directapi'));
		}

		return $links;
	}

	/**
	* settings admin
	*/
	public function settingsPage() {
		$options = $this->plugin->options;
		require GFMWDirectAPI_PLUGIN_ROOT . 'views/admin-settings.php';
	}

	/**
	* validate settings on save
	* @param array $input
	* @return array
	*/
	public function settingsValidate($input) {
		$output = array();
		
		$output['merchant_uuid']            = trim($input['merchant_uuid']);
		$output['apikey']                = trim($input['apikey']);
		$output['apipassphrase']            = trim($input['apipassphrase']);
		
		$errNames = array (
			GFMWDirectAPI_ERROR_ALREADY_SUBMITTED,
			GFMWDirectAPI_ERROR_NO_AMOUNT,
			GFMWDirectAPI_ERROR_REQ_CARD_HOLDER,
			GFMWDirectAPI_ERROR_REQ_CARD_NAME,
			GFMWDirectAPI_ERROR_MW_FAIL,
		);
		foreach ($errNames as $name) {
			$output[$name] = trim(sanitize_text_field($input[$name]));
		}
		
			
		return $output;
	}

	

}
