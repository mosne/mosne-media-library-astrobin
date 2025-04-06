<?php
/**
 * Settings page for Astronomy integration
 *
 * @package Mosne_Media_Library_Astronomy
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Mosne_Astronomy_Settings
 * Handles settings page and secure API credential storage
 */
class Mosne_Astronomy_Settings {

	/**
	 * Option group name
	 */
	const OPTION_GROUP = 'mosne_astronomy_options';

	/**
	 * Option name in database
	 */
	const OPTION_NAME = 'mosne_astronomy_settings';

	/**
	 * Register settings fields
	 */
	public static function register_settings() {
		register_setting(
			self::OPTION_GROUP,
			self::OPTION_NAME,
			array( 'Mosne_Astronomy_Settings', 'sanitize_settings' )
		);

		// AstroBin Settings Section
		add_settings_section(
			'mosne_astronomy_astrobin_section',
			__( 'AstroBin API Settings', 'mosne-media-library-astronomy' ),
			array( 'Mosne_Astronomy_Settings', 'astrobin_section_callback' ),
			'mosne-media-library-astronomy'
		);

		add_settings_field(
			'astrobin_username',
			__( 'AstroBin Username', 'mosne-media-library-astronomy' ),
			array( 'Mosne_Astronomy_Settings', 'astrobin_username_callback' ),
			'mosne-media-library-astronomy',
			'mosne_astronomy_astrobin_section'
		);

		add_settings_field(
			'astrobin_api_key',
			__( 'AstroBin API Key', 'mosne-media-library-astronomy' ),
			array( 'Mosne_Astronomy_Settings', 'astrobin_api_key_callback' ),
			'mosne-media-library-astronomy',
			'mosne_astronomy_astrobin_section'
		);

		add_settings_field(
			'astrobin_api_secret',
			__( 'AstroBin API Secret', 'mosne-media-library-astronomy' ),
			array( 'Mosne_Astronomy_Settings', 'astrobin_api_secret_callback' ),
			'mosne-media-library-astronomy',
			'mosne_astronomy_astrobin_section'
		);

		// NASA API Settings Section
		add_settings_section(
			'mosne_astronomy_nasa_section',
			__( 'NASA API Settings', 'mosne-media-library-astronomy' ),
			array( 'Mosne_Astronomy_Settings', 'nasa_section_callback' ),
			'mosne-media-library-astronomy'
		);

		add_settings_field(
			'nasa_api_key',
			__( 'NASA API Key', 'mosne-media-library-astronomy' ),
			array( 'Mosne_Astronomy_Settings', 'nasa_api_key_callback' ),
			'mosne-media-library-astronomy',
			'mosne_astronomy_nasa_section'
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
				do_settings_sections( 'mosne-media-library-astronomy' );
				submit_button();
			?>
			</form>
			<p>
				<?php echo esc_html__( 'This plugin uses the AstroBin and NASA APIs but is not endorsed or certified by AstroBin or NASA.', 'mosne-media-library-astronomy' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * AstroBin section description callback
	 */
	public static function astrobin_section_callback() {
		printf(
			'<p>%s</p><p><a href="https://www.astrobin.com/api/request-key/" target="_blank">%s</a></p>',
			esc_html__( 'Enter your AstroBin API credentials. These are required to connect to AstroBin services.', 'mosne-media-library-astronomy' ),
			esc_html__( 'Request your AstroBin API key credentials', 'mosne-media-library-astronomy' )
		);
	}

	/**
	 * NASA section description callback
	 */
	public static function nasa_section_callback() {
		printf(
			'<p>%s</p><p><a href="https://api.nasa.gov/" target="_blank">%s</a></p>',
			esc_html__( 'Enter your NASA API key. This is required to connect to NASA services.', 'mosne-media-library-astronomy' ),
			esc_html__( 'Request your NASA API key', 'mosne-media-library-astronomy' )
		);
	}

	/**
	 * AstroBin username field callback
	 */
	public static function astrobin_username_callback() {
		$options  = get_option( self::OPTION_NAME );
		$username = isset( $options['astrobin_username'] ) ? $options['astrobin_username'] : '';

		echo '<input type="text" id="astrobin_username" name="' . esc_attr( self::OPTION_NAME ) . '[astrobin_username]" value="' . esc_attr( $username ) . '" class="regular-text">';
	}

	/**
	 * AstroBin API Key field callback
	 */
	public static function astrobin_api_key_callback() {
		$options = get_option( self::OPTION_NAME );
		$api_key = isset( $options['astrobin_api_key'] ) ? $options['astrobin_api_key'] : '';

		echo '<input type="text" id="astrobin_api_key" name="' . esc_attr( self::OPTION_NAME ) . '[astrobin_api_key]" value="' . esc_attr( $api_key ) . '" class="regular-text">';
	}

	/**
	 * AstroBin API Secret field callback
	 */
	public static function astrobin_api_secret_callback() {
		$options    = get_option( self::OPTION_NAME );
		$api_secret = isset( $options['astrobin_api_secret'] ) ? self::decrypt_data( $options['astrobin_api_secret'] ) : '';

		echo '<input type="password" id="astrobin_api_secret" name="' . esc_attr( self::OPTION_NAME ) . '[astrobin_api_secret]" value="' . esc_attr( $api_secret ) . '" class="regular-text">';
	}

	/**
	 * NASA API Key field callback
	 */
	public static function nasa_api_key_callback() {
		$options = get_option( self::OPTION_NAME );
		$api_key = isset( $options['nasa_api_key'] ) ? $options['nasa_api_key'] : '';

		echo '<input type="text" id="nasa_api_key" name="' . esc_attr( self::OPTION_NAME ) . '[nasa_api_key]" value="' . esc_attr( $api_key ) . '" class="regular-text">';
		echo '<p class="description">' . esc_html__( 'If you do not have a NASA API key, you can use DEMO_KEY for testing with limited usage.', 'mosne-media-library-astronomy' ) . '</p>';
	}

	/**
	 * Sanitize settings before saving
	 *
	 * @param array $input The input array.
	 * @return array
	 */
	public static function sanitize_settings( $input ) {
		$sanitized_input = array();

		// AstroBin settings
		if ( isset( $input['astrobin_username'] ) ) {
			$sanitized_input['astrobin_username'] = sanitize_text_field( $input['astrobin_username'] );
		}

		if ( isset( $input['astrobin_api_key'] ) ) {
			$sanitized_input['astrobin_api_key'] = sanitize_text_field( $input['astrobin_api_key'] );
		}

		if ( isset( $input['astrobin_api_secret'] ) ) {
			// Encrypt sensitive data before saving to database
			$sanitized_input['astrobin_api_secret'] = self::encrypt_data( sanitize_text_field( $input['astrobin_api_secret'] ) );
		}

		// NASA settings
		if ( isset( $input['nasa_api_key'] ) ) {
			$sanitized_input['nasa_api_key'] = sanitize_text_field( $input['nasa_api_key'] );
		}

		return $sanitized_input;
	}

	/**
	 * Encrypt data for secure storage
	 *
	 * @param string $data Data to encrypt.
	 * @return string
	 */
	private static function encrypt_data( $data ) {
		// Skip encryption if data is empty
		if ( empty( $data ) ) {
			return '';
		}

		// Get or generate encryption key
		$encryption_key = self::get_encryption_key();

		// Generate an initialization vector
		$iv = openssl_random_pseudo_bytes( openssl_cipher_iv_length( 'AES-256-CBC' ) );

		// Encrypt the data
		$encrypted = openssl_encrypt( $data, 'AES-256-CBC', $encryption_key, 0, $iv );

		// Combine the IV and encrypted data
		return base64_encode( $iv . $encrypted );
	}

	/**
	 * Decrypt data
	 *
	 * @param string $encrypted_data Data to decrypt.
	 * @return string
	 */
	private static function decrypt_data( $encrypted_data ) {
		// Skip decryption if data is empty
		if ( empty( $encrypted_data ) ) {
			return '';
		}

		try {
			// Get encryption key
			$encryption_key = self::get_encryption_key();

			// Decode the combined string
			$combined = base64_decode( $encrypted_data );

			// Extract the initialization vector and encrypted data
			$iv_length = openssl_cipher_iv_length( 'AES-256-CBC' );
			$iv        = substr( $combined, 0, $iv_length );
			$encrypted = substr( $combined, $iv_length );

			// Decrypt the data
			$decrypted = openssl_decrypt( $encrypted, 'AES-256-CBC', $encryption_key, 0, $iv );

			return $decrypted;
		} catch ( Exception $e ) {
			return '';
		}
	}

	/**
	 * Get or generate encryption key
	 *
	 * @return string
	 */
	private static function get_encryption_key() {
		$key = get_option( 'mosne_astronomy_encryption_key' );

		if ( ! $key ) {
			// Generate a random key
			$key = bin2hex( openssl_random_pseudo_bytes( 32 ) );
			update_option( 'mosne_astronomy_encryption_key', $key );
		}

		return $key;
	}

	/**
	 * Get AstroBin API credentials - for use in API requests
	 *
	 * @return array
	 */
	public static function get_astrobin_credentials() {
		$options = get_option( self::OPTION_NAME, array() );

		$credentials = array(
			'username'   => isset( $options['astrobin_username'] ) ? $options['astrobin_username'] : '',
			'api_key'    => isset( $options['astrobin_api_key'] ) ? $options['astrobin_api_key'] : '',
			'api_secret' => isset( $options['astrobin_api_secret'] ) ? self::decrypt_data( $options['astrobin_api_secret'] ) : '',
		);

		return $credentials;
	}

	/**
	 * Get NASA API key - for use in API requests
	 *
	 * @return string
	 */
	public static function get_nasa_api_key() {
		$options = get_option( self::OPTION_NAME, array() );

		// Return the NASA API key or DEMO_KEY if not set
		return isset( $options['nasa_api_key'] ) && ! empty( $options['nasa_api_key'] )
			? $options['nasa_api_key']
			: 'DEMO_KEY';
	}
}
