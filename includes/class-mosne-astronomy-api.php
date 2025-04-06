<?php
/**
 * Astronomy API Integration
 *
 * @package Mosne_Media_Library_Astronomy
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Mosne_Astronomy_API
 * Base class for astronomy API integrations
 */
class Mosne_Astronomy_API {
	/**
	 * Initialize the API integrations
	 */
	public static function init() {
		// Load the API classes
		require_once MOSNE_ASTRONOMY_PLUGIN_DIR . 'includes/class-mosne-astrobin-api.php';
		require_once MOSNE_ASTRONOMY_PLUGIN_DIR . 'includes/class-mosne-nasa-api.php';

		// Initialize the specific APIs
		Mosne_AstroBin_API::init();
		Mosne_NASA_API::init();
	}

	/**
	 * Check if user has permission to access the API
	 *
	 * @return bool
	 */
	public static function api_permissions_check() {
		// Only allow authenticated users with proper capabilities
		return current_user_can( 'edit_posts' );
	}
}
