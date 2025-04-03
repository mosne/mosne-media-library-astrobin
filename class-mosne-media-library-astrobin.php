<?php
/**
 * Plugin Name: Mosne Media Library AstroBin
 * Description: WordPress integration with AstroBin API
 * Version: 1.0.0
 * Author: Mosne
 * Text Domain: mosne-media-library-astrobin
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

define( 'MOSNE_ASTROBIN_VERSION', '1.0.0' );
define( 'MOSNE_ASTROBIN_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MOSNE_ASTROBIN_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main plugin class
 */
class Mosne_Media_Library_AstroBin {

    /**
     * Instance of this class
     *
     * @var Mosne_Media_Library_AstroBin
     */
    private static $instance;

    /**
     * Get the singleton instance of this class
     *
     * @return Mosne_Media_Library_AstroBin
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

        // Admin hooks
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        
        // Initialize the API
        Mosne_AstroBin_API::init();
    }

    /**
     * Load dependencies
     */
    private function load_dependencies() {
        // Settings helper class
        require_once MOSNE_ASTROBIN_PLUGIN_DIR . 'includes/class-mosne-astrobin-settings.php';
        
        // API handler class
        require_once MOSNE_ASTROBIN_PLUGIN_DIR . 'includes/class-mosne-astrobin-api.php';
    }

    /**
     * Add menu item to WordPress admin
     */
    public function add_admin_menu() {
        add_options_page(
            __( 'Mosne Media Library AstroBin', 'mosne-media-library-astrobin' ),
            __( 'Mosne Media Library AstroBin', 'mosne-media-library-astrobin' ),
            'manage_options',
            'mosne-media-library-astrobin',
            array( $this, 'display_settings_page' )
        );
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        // Registration is handled by settings class
        Mosne_AstroBin_Settings::register_settings();
    }

    /**
     * Display the settings page
     */
    public function display_settings_page() {
        // Render through settings class
        Mosne_AstroBin_Settings::render_settings_page();
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts( $hook ) {
        // Only load scripts in post editor
        if ( $hook == 'post.php' || $hook == 'post-new.php' || $hook == 'site-editor.php' ) {
            $asset_file = include MOSNE_ASTROBIN_PLUGIN_DIR . 'build/editor.asset.php';
            
            wp_enqueue_script(
                'mosne-astrobin-editor',
                MOSNE_ASTROBIN_PLUGIN_URL . 'build/editor.js',
                $asset_file['dependencies'],
                $asset_file['version'],
                true
            );
            
            // Pass data to script
            wp_localize_script(
                'mosne-astrobin-editor',
                'mosneAstroBin',
                array(
                    'apiUrl' => esc_url_raw( rest_url( 'mosne-astrobin/v1' ) ),
                    'nonce' => wp_create_nonce( 'wp_rest' ),
                )
            );
        }
    }
}

// Initialize the plugin
function mosne_media_library_astrobin_init() {
    Mosne_Media_Library_AstroBin::get_instance();
}
add_action( 'plugins_loaded', 'mosne_media_library_astrobin_init' ); 