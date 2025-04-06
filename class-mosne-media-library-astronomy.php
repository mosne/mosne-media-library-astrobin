<?php
/**
 * Plugin Name: Mosne Media Library Astronomy
 * Description: WordPress integration with AstroBin and NASA APIs for astronomy images
 * Version: 1.0.0
 * Author: Mosne
 * Text Domain: mosne-media-library-astronomy
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'MOSNE_ASTRONOMY_VERSION', '1.0.0' );
define( 'MOSNE_ASTRONOMY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MOSNE_ASTRONOMY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main plugin class
 */
class Mosne_Media_Library_Astronomy {

	/**
	 * Instance of this class
	 *
	 * @var Mosne_Media_Library_Astronomy
	 */
	private static $instance;

	/**
	 * Get the singleton instance of this class
	 *
	 * @return Mosne_Media_Library_Astronomy
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize the plugin
	 */
	private function __construct() {
		// Load plugin dependencies
		$this->load_dependencies();

		// Initialize text domain
		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );

		// Admin hooks
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Initialize the API
		Mosne_Astronomy_API::init();

		// Add hook for modifying attachment titles
		add_action( 'add_attachment', array( $this, 'modify_attachment_title' ) );
	}

	/**
	 * Load the plugin text domain for translation.
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'mosne-media-library-astronomy',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages/'
		);
	}

	/**
	 * Load dependencies
	 */
	private function load_dependencies() {
		// Settings helper class
		require_once MOSNE_ASTRONOMY_PLUGIN_DIR . 'includes/class-mosne-astronomy-settings.php';

		// API handler class
		require_once MOSNE_ASTRONOMY_PLUGIN_DIR . 'includes/class-mosne-astronomy-api.php';
	}

	/**
	 * Add menu item to WordPress admin
	 */
	public function add_admin_menu() {
		add_options_page(
			__( 'Mosne Media Library Astronomy', 'mosne-media-library-astronomy' ),
			__( 'Mosne Media Library Astronomy', 'mosne-media-library-astronomy' ),
			'manage_options',
			'mosne-media-library-astronomy',
			array( $this, 'display_settings_page' )
		);
	}

	/**
	 * Register plugin settings
	 */
	public function register_settings() {
		// Registration is handled by settings class
		Mosne_Astronomy_Settings::register_settings();
	}

	/**
	 * Display the settings page
	 */
	public function display_settings_page() {
		// Render through settings class
		Mosne_Astronomy_Settings::render_settings_page();
	}

	/**
	 * Enqueue admin scripts
	 */
	public function enqueue_admin_scripts( $hook ) {
		// Only load scripts in post editor - using Yoda conditions
		if ( 'post.php' === $hook || 'post-new.php' === $hook || 'site-editor.php' === $hook ) {
			$asset_file = include MOSNE_ASTRONOMY_PLUGIN_DIR . 'build/editor.asset.php';

			wp_enqueue_script(
				'mosne-astronomy-editor',
				MOSNE_ASTRONOMY_PLUGIN_URL . 'build/editor.js',
				$asset_file['dependencies'],
				$asset_file['version'],
				true
			);

			// Pass data to script
			wp_localize_script(
				'mosne-astronomy-editor',
				'mosneAstronomy',
				array(
					'apiUrl' => esc_url_raw( rest_url( 'mosne-media-library-astronomy/v1' ) ),
					'nonce'  => wp_create_nonce( 'wp_rest' ),
				)
			);
		}
	}

	/**
	 * Modify the attachment title when an image is uploaded
	 *
	 * @param int $attachment_id The ID of the newly uploaded attachment
	 */
	public function modify_attachment_title( $attachment_id ) {
		// Check if this is an image attachment
		if ( wp_attachment_is_image( $attachment_id ) ) {
			// Get attachment metadata
			$attachment = get_post( $attachment_id );

			// if the post_excerpt is empty, return
			if ( empty( $attachment->post_excerpt ) ) {
				return;
			}

			// Check if this is from an astronomy source
			$is_astrobin = false !== strpos( $attachment->post_excerpt, 'astrobin' );
			$is_nasa     = false !== strpos( $attachment->post_excerpt, 'nasa' );

			// If not an astronomy image, return
			if ( ! $is_astrobin && ! $is_nasa ) {
				return;
			}

			// Extract title from excerpt
			$title_part = explode( '©', $attachment->post_excerpt )[0];
			$new_title  = wp_strip_all_tags( $title_part );

			// You could also get info from API here if needed
			// if ($is_astrobin) {
			//    $astronomy_data = Mosne_Astronomy_API::get_astrobin_image_data($some_identifier);
			// } elseif ($is_nasa) {
			//    $astronomy_data = Mosne_Astronomy_API::get_nasa_image_data($some_identifier);
			// }
			// $new_title = $astronomy_data['title'];

			// Update the attachment title
			wp_update_post(
				array(
					'ID'         => $attachment_id,
					'post_title' => $new_title,
				)
			);
		}
	}
}

// Initialize the plugin
add_action(
	'plugins_loaded',
	function () {
		Mosne_Media_Library_Astronomy::get_instance();
	}
);
