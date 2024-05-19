<?php
//add action
add_action( 'admin_menu', 'woo_sync_admin_menu' );
add_action( 'admin_init', 'ebook_store_settings_init' );

function woo_sync_admin_menu(): void {
	add_menu_page(
		'General Options',
		'Sync Woo Products',
		'manage_options',
		'sync-woo-setting',
		'sync_woo_option_page',
		'dashicons-list-view',
		27
	);
}

//Register new setting page
function ebook_store_settings_init() {
	add_settings_section(
		'api_config_section',
		'',
		'',
		'ven_sync_woo'
	);

	add_settings_field(
		'ven_woo_sync_target',
		'Target host name',
		'target_host_name_cb',
		'ven_sync_woo',
		'api_config_section',
		array(
			'label_for' => 'ven_woo_sync_target',
		)
	);

	add_settings_field(
		'ven_woo_sync_consumer_key',
		'Woocommerce consumer key',
		'ven_woo_sync_consumer_key_cb',
		'ven_sync_woo',
		'api_config_section',
		array(
			'label_for' => 'ven_woo_sync_consumer_key',
			'type' => 'password'
		)
	);

	add_settings_field(
		'ven_woo_sync_secret_key',
		'Woocommerce secret key',
		'ven_woo_sync_secret_key_cb',
		'ven_sync_woo',
		'api_config_section',
		array(
			'label_for' => 'ven_woo_sync_secret_key',
			'type' => 'password'
		)
	);


	register_setting( 'ven_sync_woo', 'ven_woo_sync_target' );
	register_setting( 'ven_sync_woo', 'ven_woo_sync_consumer_key' );
	register_setting( 'ven_sync_woo', 'ven_woo_sync_secret_key' );

}


/**
 * callback function
 */
function target_host_name_cb( $args ) {
	$options = get_option( 'ven_woo_sync_target');
	?>
	<input type="<?php echo esc_attr( $args['type'] ); ?>" id="<?php echo esc_attr( $args['label_for'] ); ?>" name="<?php echo esc_attr( $args['label_for'] ); ?>"
	       value="<?php echo $options;?>" placeholder="https://example.com" size="40">
	<?php
}

/**
 * callback function
 */
function ven_woo_sync_consumer_key_cb( $args ) {
	$options = get_option( 'ven_woo_sync_consumer_key');
	?>
	<input type="<?php echo esc_attr( $args['type'] ); ?>" id="<?php echo esc_attr( $args['label_for'] ); ?>" name="<?php echo esc_attr( $args['label_for'] ); ?>"
	       value="<?php echo $options;?>" placeholder="https://example.com" size="50">
	<p>Woocommerce consumer key of target host</p>
	<?php
}

/**
 * callback function
 */
function ven_woo_sync_secret_key_cb( $args ) {
	$options = get_option( 'ven_woo_sync_secret_key');
	?>
	<input type="<?php echo esc_attr( $args['type'] ); ?>" id="<?php echo esc_attr( $args['label_for'] ); ?>" name="<?php echo esc_attr( $args['label_for'] ); ?>"
	       value="<?php echo $options;?>" placeholder="https://example.com" size="50">
	<p>Woocommerce secret key of target host</p>
	<?php
}


/**
 * sync_woo_option_page callback function
 */
function sync_woo_option_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// add error/update messages
	if ( isset( $_GET['settings-updated'] ) ) {
		// add settings saved message with the class of "updated"
		add_settings_error( 'woo_sync_messages', 'woo_sync_messages', 'Settings Saved', 'updated' );
	}

	// show error/update messages
	settings_errors( 'woo_sync_messages' );
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<form action="options.php" method="post">
			<?php
			// output security fields for the registered setting
			settings_fields( 'ven_sync_woo' );
			// output setting sections and their fields
			do_settings_sections( 'ven_sync_woo' );
			// output save settings button
			submit_button( 'Save Settings' );
			?>
		</form>
	</div>
	<?php
}
