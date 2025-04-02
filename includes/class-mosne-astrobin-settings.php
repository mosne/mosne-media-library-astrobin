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
            array( 'Mosne_AstroBin_Settings', 'sanitize_settings' )
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
        </div>
        <?php
    }

    /**
     * Section description callback
     */
    public static function section_callback() {
        echo '<p>' . esc_html__( 'Enter your AstroBin API credentials. These are required to connect to AstroBin services.', 'mosne-media-library-astrobin' ) . '</p>';
    }

    /**
     * Username field callback
     */
    public static function username_callback() {
        $options = get_option( self::OPTION_NAME );
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
        $options = get_option( self::OPTION_NAME );
        $api_secret = isset( $options['api_secret'] ) ? self::decrypt_data( $options['api_secret'] ) : '';
        
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
            // Encrypt sensitive data before saving to database
            $sanitized_input['api_secret'] = self::encrypt_data( sanitize_text_field( $input['api_secret'] ) );
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
            $iv = substr( $combined, 0, $iv_length );
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
        $key = get_option( 'mosne_astrobin_encryption_key' );
        
        if ( ! $key ) {
            // Generate a random key
            $key = bin2hex( openssl_random_pseudo_bytes( 32 ) );
            update_option( 'mosne_astrobin_encryption_key', $key );
        }
        
        return $key;
    }

    /**
     * Get API credentials - for use in API requests
     *
     * @return array
     */
    public static function get_api_credentials() {
        $options = get_option( self::OPTION_NAME, array() );
        
        $credentials = array(
            'username' => isset( $options['username'] ) ? $options['username'] : '',
            'api_key' => isset( $options['api_key'] ) ? $options['api_key'] : '',
            'api_secret' => isset( $options['api_secret'] ) ? self::decrypt_data( $options['api_secret'] ) : '',
        );
        
        return $credentials;
    }
} 