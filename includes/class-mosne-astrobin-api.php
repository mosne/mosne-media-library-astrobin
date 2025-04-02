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
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'page_size' => array(
                        'required'          => false,
                        'sanitize_callback' => 'absint',
                        'default'           => 20,
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
        return current_user_can( 'edit_posts' );
    }

    /**
     * Search AstroBin images
     *
     * @param WP_REST_Request $request The request object.
     * @return WP_REST_Response
     */
    public static function search_images( $request ) {
        $search_term = $request->get_param( 'term' );
        $page_size = $request->get_param( 'page_size' );
        
        // Get API credentials
        $credentials = Mosne_AstroBin_Settings::get_api_credentials();
        
        if ( empty( $credentials['username'] ) || empty( $credentials['api_key'] ) || empty( $credentials['api_secret'] ) ) {
            return new WP_REST_Response(
                array(
                    'error' => __( 'AstroBin API credentials are not configured.', 'mosne-media-library-astrobin' ),
                ),
                400
            );
        }
        
        $api_response = self::make_api_request(
            'image/',
            array(
                'title__icontains' => $search_term,
                'user'             => $credentials['username'],
                'limit'            => $page_size,
            )
        );
        
        if ( is_wp_error( $api_response ) ) {
            return new WP_REST_Response(
                array(
                    'error' => $api_response->get_error_message(),
                ),
                500
            );
        }
        
        $formatted_results = array(
            'objects' => array(),
        );
        
        if ( ! empty( $api_response->objects ) ) {
            foreach ( $api_response->objects as $image ) {
                $formatted_results['objects'][] = array(
                    'id'           => $image->id,
                    'title'        => $image->title,
                    'user'         => $image->user,
                    'hash'         => $image->hash,
                    'url_regular'  => $image->url_regular,
                    'url_thumb'    => $image->url_thumb,
                    'url_duckduckgo' => isset($image->url_duckduckgo) ? $image->url_duckduckgo : $image->url_thumb,
                    'url_real'     => isset($image->url_real) ? $image->url_real : $image->url_regular,
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
        
        $response = wp_remote_get(
            $request_url,
            array(
                'timeout'     => 15,
                'httpversion' => '1.1',
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
} 