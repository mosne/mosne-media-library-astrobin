<?php
/**
 * AstroBin API Integration
 *
 * @package Mosne_Media_Library_AstroBin
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Class Mosne_AstroBin_API
 * Handles AstroBin API requests and REST API endpoints
 */
class Mosne_AstroBin_API {

    /**
     * AstroBin API base URL
     */
    const API_BASE_URL = 'https://www.astrobin.com/api/v1/';

    /**
     * Initialize the API
     */
    public static function init() {
        add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );
    }

    /**
     * Register REST API routes
     */
    public static function register_rest_routes() {
        register_rest_route(
            'mosne-astrobin/v1',
            '/search',
            array(
                'methods'             => 'GET',
                'callback'            => array( __CLASS__, 'search_images' ),
                'permission_callback' => array( __CLASS__, 'api_permissions_check' ),
                'args'                => array(
                    'term' => array(
                        'required'          => false,
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'username' => array(
                        'required'          => false,
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'page_size' => array(
                        'required'          => false,
                        'sanitize_callback' => 'absint',
                        'default'           => 30,
                    ),
                    'page' => array(
                        'required'          => false,
                        'sanitize_callback' => 'absint',
                        'default'           => 1,
                    ),
                    'type' => array(
                        'required'          => false,
                        'sanitize_callback' => 'sanitize_text_field',
                        'default'           => 'my_pictures',
                    ),
                ),
            )
        );
    }

    /**
     * Check if user has permission to access the API
     *
     * @return bool
     */
    public static function api_permissions_check() {
        // Only allow authenticated users with proper capabilities
        return current_user_can( 'edit_posts' );
        
        // For development environments, you can use filters to modify this behavior:
        // return apply_filters( 'mosne_astrobin_api_permission', current_user_can( 'edit_posts' ) );
    }

    /**
     * Search AstroBin images
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response
     */
    public static function search_images( $request ) {
        $search_term = $request->get_param( 'term' );
        $username = $request->get_param( 'username' );
        $page_size = $request->get_param( 'page_size' );
        $page = $request->get_param( 'page' );
        $type = $request->get_param( 'type' );
        
        // Get API credentials
        $credentials = Mosne_AstroBin_Settings::get_api_credentials();
        
        if ( empty( $credentials['api_key'] ) || empty( $credentials['api_secret'] ) ) {
            return new WP_REST_Response(
                array(
                    'error' => __( 'AstroBin API credentials are not configured.', 'mosne-media-library-astrobin' ),
                ),
                400
            );
        }
        
        $params = array();
        $formatted_results = array(
            'objects' => array(),
        );
        
        // Determine endpoint based on type
        $endpoint = 'image/';
        
        switch ( $type ) {
            case 'my_pictures':
                // Search by current user
                $params['user'] = $credentials['username'];
                break;
                
            case 'user_gallery':
                // Search by specified username
                if ( ! empty( $username ) ) {
                    $params['user'] = $username;
                }
                break;
                
            case 'imageoftheday':
                // Image of the Day
                $endpoint = 'imageoftheday/';
                break;
                
            case 'by_subject':
                // Search by astronomical subject
                if ( ! empty( $search_term ) ) {
                    $params['subjects'] = $search_term;
                }
                break;
        }
        
        // Add search term if provided (for endpoints that support it)
        if ( ! empty( $search_term ) && $endpoint === 'image/' ) {
            $params['title__icontains'] = $search_term;
        }
        
        // Add pagination parameters
        $params['limit'] = $page_size;
        if ( $page > 1 ) {
            $params['offset'] = ( $page - 1 ) * $page_size;
        }
        
        // Make the API request
        $api_response = self::make_api_request( $endpoint, $params );
        
        if ( is_wp_error( $api_response ) ) {
            return new WP_REST_Response(
                array(
                    'error' => $api_response->get_error_message(),
                ),
                500
            );
        }
        
        // Process results based on endpoint type
        if ( $endpoint === 'imageoftheday/' ) {
            self::process_imageoftheday_results( $api_response, $formatted_results );
        } else {
            self::process_standard_results( $api_response, $formatted_results );
        }
        
        return new WP_REST_Response( $formatted_results );
    }
    
    /**
     * Process Image of the Day results
     *
     * @param object $api_response The API response.
     * @param array  $formatted_results Results array to populate.
     */
    private static function process_imageoftheday_results( $api_response, &$formatted_results ) {
        if ( empty( $api_response->objects ) || ! is_array( $api_response->objects ) ) {
            return;
        }
        
        // Limit the number of IOTD entries to 2 for better performance
        $api_response->objects = array_slice( $api_response->objects, 0, 2 );

        foreach ( $api_response->objects as $iotd ) {
            if ( empty( $iotd->image ) ) {
                continue;
            }
            
            // Extract the image ID from the path
            $image_id = self::extract_image_id( $iotd->image );
            if ( ! $image_id ) {
                continue;
            }
            
            // Get the image details
            $image_response = self::make_api_request( 'image/' . $image_id . '/', array() );
            
            if ( ! is_wp_error( $image_response ) && is_object( $image_response ) ) {
                $formatted_results['objects'][] = self::format_image_data( $image_response, $iotd->date ?? '' );
            }
        }
    }
    
    /**
     * Process standard image results
     *
     * @param object $api_response The API response.
     * @param array  $formatted_results Results array to populate.
     */
    private static function process_standard_results( $api_response, &$formatted_results ) {
        $objects = isset( $api_response->objects ) && is_array( $api_response->objects ) 
            ? $api_response->objects 
            : array( $api_response );
        
        foreach ( $objects as $image ) {
            // Skip if no data
            if ( ! isset( $image->id ) ) {
                continue;
            }
            
            $formatted_results['objects'][] = self::format_image_data( $image );
        }
    }
    
    /**
     * Format image data for consistent output
     *
     * @param object $image The image data.
     * @param string $date Optional date for IOTD.
     * @return array Formatted image data.
     */
    private static function format_image_data( $image, $date = '' ) {
        return array(
            'id'          => $image->id,
            'title'       => isset( $image->title ) ? $image->title : '',
            'user'        => isset( $image->user ) ? $image->user : '',
            'hash'        => isset( $image->hash ) ? $image->hash : '',
            'date'        => $date,
            'url_regular' => isset( $image->url_regular ) ? $image->url_regular : '',
            'url_thumb'   => isset( $image->url_thumb ) ? $image->url_thumb : '',
            'url_real'    => isset( $image->url_real ) ? $image->url_real : 
                             (isset( $image->url_regular ) ? $image->url_regular : ''),
            'url_hd'      => isset( $image->url_hd ) ? $image->url_hd : '',

            // add copyright license
            'license'      => isset( $image->license ) ? $image->license : 0,
            'license_name'   => self::get_license_name( isset( $image->license ) ? $image->license : 0 ),
            'license_url'    => self::get_license_url( isset( $image->license ) ? $image->license : 0, isset( $image->hash ) ? $image->hash : '' ),
        );

        
    }
    
    /**
     * Extract image ID from AstroBin path
     *
     * @param string $image_path The image path.
     * @return string|null The extracted image ID or null.
     */
    private static function extract_image_id( $image_path ) {
        if ( preg_match( '|/api/v1/image/([^/]+)|', $image_path, $matches ) ) {
            return $matches[1];
        } 
        
        return basename( rtrim( $image_path, '/' ) );
    }

    /**
     * Make request to AstroBin API
     *
     * @param string $endpoint API endpoint.
     * @param array  $params   Request parameters.
     * @return mixed|WP_Error
     */
    private static function make_api_request( $endpoint, $params = array() ) {
        $credentials = Mosne_AstroBin_Settings::get_api_credentials();
        
        // Add authentication params
        $params['api_key']    = $credentials['api_key'];
        $params['api_secret'] = $credentials['api_secret'];
        $params['format']     = 'json';
        
        $request_url = self::API_BASE_URL . $endpoint . '?' . http_build_query( $params );
        
        // Remove sensitive data for logging
        $log_url = preg_replace( '/api_secret=[^&]*/', 'api_secret=REDACTED', $request_url );
        
        $response = wp_remote_get(
            $request_url,
            array(
                'timeout'     => 15,
                'httpversion' => '1.1',
                'headers'     => array(
                    'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' ),
                ),
            )
        );
        
        if ( is_wp_error( $response ) ) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code( $response );
        
        if ( 200 !== $response_code ) {
            return new WP_Error(
                'astrobin_api_error',
                sprintf(
                    /* translators: %d: HTTP response code */
                    __( 'AstroBin API error: %d', 'mosne-media-library-astrobin' ),
                    $response_code
                )
            );
        }
        
        $body = wp_remote_retrieve_body( $response );
        
        return json_decode( $body );
    }

    /**
     * Map numeric license type to human-readable text
     *
     * @param int $license_type The numeric license type from AstroBin API.
     * @return string The human-readable license text.
     */
    public static function get_license_name( $license_type ) {
        $license_types = array(
            0 => esc_html__( 'All rights reserved', 'mosne-media-library-astrobin' ),
            1 => esc_html__( 'Attribution-NonCommercial-ShareAlike Creative Commons', 'mosne-media-library-astrobin' ),
            2 => esc_html__( 'Attribution-NonCommercial Creative Commons', 'mosne-media-library-astrobin' ),
            3 => esc_html__( 'Attribution-NonCommercial-NoDerivs Creative Commons', 'mosne-media-library-astrobin' ),
            4 => esc_html__( 'Attribution Creative Commons', 'mosne-media-library-astrobin' ),
            5 => esc_html__( 'Attribution-ShareAlike Creative Commons', 'mosne-media-library-astrobin' ),
            6 => esc_html__( 'Attribution-NoDerivs Creative Commons', 'mosne-media-library-astrobin' ),
        );
        
        return isset( $license_types[ $license_type ] ) 
            ? $license_types[ $license_type ] 
            : __( 'Unknown license', 'mosne-media-library-astrobin' );
    }
    
    /**
     * Get license URL based on license type
     *
     * @param int $license_type The numeric license type from AstroBin API.
     * @param string $image_hash The hash of the image.
     * @return string The license URL or empty string if proprietary.
     */
    public static function get_license_url( $license_type, $image_hash ) {
        $license_urls = array(
            0 => 'https://app.astrobin.com/i/' . $image_hash . '/',  // link to astrobin image page
            1 => 'https://creativecommons.org/licenses/by-nc-sa/4.0/',
            2 => 'https://creativecommons.org/licenses/by-nc/4.0/',
            3 => 'https://creativecommons.org/licenses/by-nc-nd/4.0/',
            4 => 'https://creativecommons.org/licenses/by/4.0/',
            5 => 'https://creativecommons.org/licenses/by-sa/4.0/',
            6 => 'https://creativecommons.org/licenses/by-nd/4.0/',
        );
        
        return isset( $license_urls[ $license_type ] ) ? $license_urls[ $license_type ] : '';
    }
} 