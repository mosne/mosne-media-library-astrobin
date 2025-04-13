<?php
/**
 * Settings page for AstroBin integration
 *
 * @package Mosne_Media_Library_AstroBin
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Mosne_AstroBin_Settings
 * Handles settings page and secure API credential storage
 */
class Mosne_AstroBin_Settings {

	/**
	 * Option group name
	 */
	const OPTION_GROUP = 'mosne_astrobin_options';

	/**
	 * Option name in database
	 */
	const OPTION_NAME = 'mosne_astrobin_settings';

	/**
	 * Register settings fields
	 */
	public static function register_settings() {
		register_setting(
			self::OPTION_GROUP,
			self::OPTION_NAME,
			function ( $input ) {
				return self::sanitize_settings( $input );
			}
		);

		add_settings_section(
			'mosne_astrobin_main_section',
			__( 'AstroBin API Settings', 'mosne-media-library-astrobin' ),
			array( 'Mosne_AstroBin_Settings', 'section_callback' ),
			'mosne-media-library-astrobin'
		);

		add_settings_field(
			'username',
			__( 'Username', 'mosne-media-library-astrobin' ),
			array( 'Mosne_AstroBin_Settings', 'username_callback' ),
			'mosne-media-library-astrobin',
			'mosne_astrobin_main_section'
		);

		add_settings_field(
			'api_key',
			__( 'API Key', 'mosne-media-library-astrobin' ),
			array( 'Mosne_AstroBin_Settings', 'api_key_callback' ),
			'mosne-media-library-astrobin',
			'mosne_astrobin_main_section'
		);

		add_settings_field(
			'api_secret',
			__( 'API Secret', 'mosne-media-library-astrobin' ),
			array( 'Mosne_AstroBin_Settings', 'api_secret_callback' ),
			'mosne-media-library-astrobin',
			'mosne_astrobin_main_section'
		);
	}

	/**
	 * Render the settings page
	 */
	public static function render_settings_page() {
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
			<?php
				settings_fields( self::OPTION_GROUP );
				do_settings_sections( 'mosne-media-library-astrobin' );
				submit_button();
			?>
			</form>
			<p>
				<?php echo esc_html__( 'Since "All rights reserved" is the most commonly used license, I\'d like to remind you that this means you need the author\'s permission before publishing any images on your website.', 'mosne-media-library-astrobin' ); ?>
			</p>
			<p>
				<?php echo esc_html__( 'This plugin uses the AstroBin API but is not endorsed or certified by AstroBin.', 'mosne-media-library-astrobin' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Section description callback
	 */
	public static function section_callback() {
		printf(
			'<p>%s</p><p><a href="https://www.astrobin.com/api/request-key/" target="_blank">%s</a></p>',
			esc_html__( 'Enter your AstroBin API credentials. These are required to connect to AstroBin services.', 'mosne-media-library-astrobin' ),
			esc_html__( 'Request you api key creditial', 'mosne-media-library-astrobin' )
		);
	}

	/**
	 * Username field callback
	 */
	public static function username_callback() {
		$options  = get_option( self::OPTION_NAME );
		$username = isset( $options['username'] ) ? $options['username'] : '';

		echo '<input type="text" id="username" name="' . esc_attr( self::OPTION_NAME ) . '[username]" value="' . esc_attr( $username ) . '" class="regular-text">';
	}

	/**
	 * API Key field callback
	 */
	public static function api_key_callback() {
		$options = get_option( self::OPTION_NAME );
		$api_key = isset( $options['api_key'] ) ? $options['api_key'] : '';

		echo '<input type="text" id="api_key" name="' . esc_attr( self::OPTION_NAME ) . '[api_key]" value="' . esc_attr( $api_key ) . '" class="regular-text">';
	}

	/**
	 * API Secret field callback
	 */
	public static function api_secret_callback() {
		$api_secret = self::get_api_secret();

		echo '<input type="password" id="api_secret" name="' . esc_attr( self::OPTION_NAME ) . '[api_secret]" value="' . esc_attr( $api_secret ) . '" class="regular-text">';
	}

	/**
	 * Sanitize settings before saving
	 *
	 * @param array $input The input array.
	 * @return array
	 */
	public static function sanitize_settings( $input ) {
		$sanitized_input = array();

		if ( isset( $input['username'] ) ) {
			$sanitized_input['username'] = sanitize_text_field( $input['username'] );
		}

		if ( isset( $input['api_key'] ) ) {
			$sanitized_input['api_key'] = sanitize_text_field( $input['api_key'] );
		}

		if ( isset( $input['api_secret'] ) ) {
			// Save API secret securely
			$api_secret = sanitize_text_field( $input['api_secret'] );

			// Only update if the value has changed
			if ( ! empty( $api_secret ) ) {
				// Store the API secret using WordPress options
				update_option( 'mosne_astrobin_api_secret', $api_secret, false );
				// Set a placeholder in the main options
				$sanitized_input['api_secret'] = 'STORED_SECURELY';
			} else {
				// Keep the placeholder if empty (to prevent clearing on empty submissions)
				$sanitized_input['api_secret'] = 'STORED_SECURELY';
			}
		}

		return $sanitized_input;
	}

	/**
	 * Get API secret
	 *
	 * @return string
	 */
	private static function get_api_secret() {
		return get_option( 'mosne_astrobin_api_secret', '' );
	}

	/**
	 * Get API credentials - for use in API requests
	 *
	 * @return array
	 */
	public static function get_api_credentials() {
		$options = get_option( self::OPTION_NAME, array() );

		$credentials = array(
			'username'   => isset( $options['username'] ) ? $options['username'] : '',
			'api_key'    => isset( $options['api_key'] ) ? $options['api_key'] : '',
			'api_secret' => self::get_api_secret(),
		);

		return $credentials;
	}
}
