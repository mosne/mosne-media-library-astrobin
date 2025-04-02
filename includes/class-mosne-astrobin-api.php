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
                        'default'           => 20,
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
        // Check if user is logged in and can edit posts
        if (current_user_can('edit_posts')) {
            return true;
        }
        
        // For debugging purposes - Allow all requests temporarily
        return true;
        
        // Note: Remove the line above and use one of these more secure approaches for production:
        
        // Option 1: Check for a valid nonce for authenticated requests
        // if (isset($_SERVER['HTTP_X_WP_NONCE'])) {
        //     $nonce = sanitize_text_field($_SERVER['HTTP_X_WP_NONCE']);
        //     if (wp_verify_nonce($nonce, 'wp_rest')) {
        //         return true;
        //     }
        // }
        
        // Option 2: Allow read-only access for all users
        // return true;
        
        // Option 3: Custom capability check
        // return apply_filters('mosne_astrobin_api_permission', current_user_can('read'));
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
        switch ( $type ) {
            case 'my_pictures':
                // Search by current user and optional title
                $params['user'] = $credentials['username'];
                $endpoint = 'image/';
                break;
                
            case 'public_pictures':
                // Search public pictures
                $endpoint = 'image/';
                break;
                
            case 'user_gallery':
                // Search by specified username
                if ( ! empty( $username ) ) {
                    $params['user'] = $username;
                }
                $endpoint = 'image/';
                break;
                
            case 'imageoftheday':
                // Image of the Day
                $endpoint = 'imageoftheday/';
                break;
                
            case 'by_subject':
                // Search by astronomical subject
                $endpoint = 'image/';
                if ( ! empty( $search_term ) ) {
                    $params['subjects'] = $search_term;
                }
                break;
                
            default:
                $endpoint = 'image/';
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
            error_log('AstroBin API error: ' . $api_response->get_error_message());
            return new WP_REST_Response(
                array(
                    'error' => $api_response->get_error_message(),
                ),
                500
            );
        }
        
        // Process results based on endpoint type
        if ( $endpoint === 'imageoftheday/' ) {
            // Handle Image of the Day response format
            if ( ! empty( $api_response->objects ) && is_array( $api_response->objects ) ) {
                error_log('IOTD entries found: ' . count($api_response->objects));
                
                foreach ( $api_response->objects as $iotd ) {
                    if ( ! empty( $iotd->image ) ) {
                        // Extract the image ID from the path
                        $image_path = $iotd->image;
                        // Handle both formats: "/api/v1/image/1ucvr7/" and "1ucvr7"
                        if (preg_match('|/api/v1/image/([^/]+)|', $image_path, $matches)) {
                            $image_id = $matches[1];
                        } else {
                            $image_id = basename(rtrim($image_path, '/'));
                        }
                        
                        // Get the image details
                        $image_response = self::make_api_request( 'image/' . $image_id . '/', array() );
                        
                        if ( ! is_wp_error( $image_response ) && is_object( $image_response ) ) {
                            // Add the image to results with IOTD date
                            $formatted_results['objects'][] = array(
                                'id'            => $image_response->id,
                                'title'         => isset( $image_response->title ) ? $image_response->title : '',
                                'user'          => isset( $image_response->user ) ? $image_response->user : '',
                                'hash'          => isset( $image_response->hash ) ? $image_response->hash : '',
                                'date'          => isset( $iotd->date ) ? $iotd->date : '',
                                'url_regular'   => isset( $image_response->url_regular ) ? $image_response->url_regular : '',
                                'url_thumb'     => isset( $image_response->url_thumb ) ? $image_response->url_thumb : '',
                                'url_real'      => isset( $image_response->url_real ) ? $image_response->url_real : 
                                                    (isset( $image_response->url_regular ) ? $image_response->url_regular : ''),
                                'url_hd'        => isset( $image_response->url_hd ) ? $image_response->url_hd : '',
                            );
                        }
                    }
                }
            }
        } else {
            // Process results for other API endpoints
            if ( isset( $api_response->objects ) && is_array( $api_response->objects ) ) {
                $objects = $api_response->objects;
            } else {
                $objects = array( $api_response );
            }
            
            foreach ( $objects as $image ) {
                // Skip if no data
                if ( ! isset( $image->id ) ) {
                    continue;
                }
                
                $formatted_results['objects'][] = array(
                    'id'           => $image->id,
                    'title'        => isset( $image->title ) ? $image->title : '',
                    'user'         => isset( $image->user ) ? $image->user : '',
                    'hash'         => isset( $image->hash ) ? $image->hash : '',
                    'url_regular'  => isset( $image->url_regular ) ? $image->url_regular : '',
                    'url_thumb'    => isset( $image->url_thumb ) ? $image->url_thumb : '',
                    'url_real'     => isset( $image->url_real ) ? $image->url_real : 
                                      (isset( $image->url_regular ) ? $image->url_regular : ''),
                    'url_hd'       => isset( $image->url_hd ) ? $image->url_hd : '',
                );
            }
        }
        
        return new WP_REST_Response( $formatted_results );
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
        
        // Log the URL for debugging (remove sensitive data in production)
        $log_url = preg_replace('/api_secret=[^&]*/', 'api_secret=REDACTED', $request_url);
        error_log('AstroBin API request: ' . $log_url);
        
        $response = wp_remote_get(
            $request_url,
            array(
                'timeout'     => 15,
                'httpversion' => '1.1',
            )
        );
        
        if ( is_wp_error( $response ) ) {
            error_log('AstroBin API wp_error: ' . $response->get_error_message());
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code( $response );
        
        if ( 200 !== $response_code ) {
            error_log('AstroBin API error code: ' . $response_code);
            error_log('AstroBin API error body: ' . wp_remote_retrieve_body($response));
            
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
        $decoded = json_decode( $body );
        
        // Log response summary
        if (is_object($decoded)) {
            error_log('AstroBin API response received successfully');
        } else {
            error_log('AstroBin API response could not be decoded: ' . substr($body, 0, 100) . '...');
        }
        
        return $decoded;
    }
} 