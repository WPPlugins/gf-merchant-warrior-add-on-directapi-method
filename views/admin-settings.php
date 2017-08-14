
<?php settings_errors(); ?>

<h3><span><i class="fa fa-credit-card"></i> <?php echo esc_html_x('Merchant Warrior Payments', 'settings page', 'merchantwarrior-directapi'); ?></span></h3>

<form action="<?php echo admin_url('options.php'); ?>" method="POST" id="mw-settings-form">
	<?php settings_fields(GFMWDirectAPI_PLUGIN_OPTIONS); ?>

	<h4 class="gf_settings_subgroup_title"><?php esc_html_e('Main settings', 'merchantwarrior-directapi'); ?></h4>

	<table class="form-table gforms_form_settings">

		<tr>
			<th scope="row">
				<label for="GFMWDirectAPI_plugin_merchant_uuid"><?php echo esc_html_x('Merchant UUID', 'settings field', 'merchantwarrior-directapi'); ?></label>
				<?php gform_tooltip(esc_html__('Your Merchant UUID.', 'merchantwarrior-directapi')); ?>
			</th>
			<td>
				<input type="text" class="large-text" name="GFMWDirectAPI_plugin[merchant_uuid]" id="GFMWDirectAPI_plugin_merchant_uuid" value="<?php echo esc_attr($options['merchant_uuid']); ?>" />
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="GFMWDirectAPI_plugin_apikey"><?php echo esc_html_x('API KEY', 'settings field', 'merchantwarrior-directapi'); ?></label>
				<?php gform_tooltip(esc_html__('Your API Key.', 'merchantwarrior-directapi')); ?>
			</th>
			<td>
				<input type="text" class="regular-text" name="GFMWDirectAPI_plugin[apikey]" id="GFMWDirectAPI_plugin_apikey" value="<?php echo esc_attr($options['apikey']); ?>" />
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="GFMWDirectAPI_plugin_apipassphrase"><?php echo esc_html_x('API PASS PHRASE', 'settings field', 'merchantwarrior-directapi'); ?></label>
				<?php gform_tooltip(esc_html__('Your API Pass Phrase.', 'merchantwarrior-directapi')); ?>
			</th>
			<td>
				<input type="text" class="regular-text" name="GFMWDirectAPI_plugin[apipassphrase]" id="GFMWDirectAPI_plugin_apipassphrase" value="<?php echo esc_attr($options['apipassphrase']); ?>" />
			</td>
		</tr>

		
	</table>

	<?php submit_button(); ?>

</form>