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
        
        $params = array(
            'limit' => $page_size,
        );
        
        // Calculate offset for pagination (if not IOTD)
        if ( $page > 1 && $type !== 'image_of_the_day' ) {
            $params['offset'] = ( $page - 1 ) * $page_size;
        }
        
        // Add search parameters based on request type
        switch ( $type ) {
            case 'my_pictures':
                // Search by current user and optional title
                $params['user'] = $credentials['username'];
                if ( ! empty( $search_term ) ) {
                    $params['title__icontains'] = $search_term;
                }
                $endpoint = 'image/';
                break;
                
            case 'public_pictures':
                // Search public pictures by title
                if ( ! empty( $search_term ) ) {
                    $params['title__icontains'] = $search_term;
                }
                $endpoint = 'image/';
                break;
                
            case 'user_gallery':
                // Search by specified username
                if ( ! empty( $username ) ) {
                    $params['user'] = $username;
                }
                if ( ! empty( $search_term ) ) {
                    $params['title__icontains'] = $search_term;
                }
                $endpoint = 'image/';
                break;
                
            case 'image_of_the_day':
                // Get Image of the Day
                // For Image of the Day, offset and limit works differently
                // limit=1 gets today's image, offset=1 gets yesterday's, etc.
                if ( $page > 1 ) {
                    $params['offset'] = $page - 1;
                }
                // We need to get multiple images but not too many as each requires an extra API call
                $params['limit'] = min( $page_size, 10 );
                $endpoint = 'imageoftheday/';
                break;
                
            case 'top_picks':
                // Get Top Picks
                $endpoint = 'toppick/';
                break;
                
            default:
                $endpoint = 'image/';
        }
        
        $api_response = self::make_api_request( $endpoint, $params );
        
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
        
        // Handle both single objects and collections
        if ( isset( $api_response->objects ) && is_array( $api_response->objects ) ) {
            $objects = $api_response->objects;
            
            // Special handling for imageoftheday
            if ( $type === 'image_of_the_day' ) {
                // For image of the day, we need to extract the actual image data
                $iotd_objects = array();
                foreach ( $objects as $iotd ) {
                    if ( isset( $iotd->image ) ) {
                        $image_response = self::make_api_request( 'image/' . $iotd->image . '/' );
                        if ( ! is_wp_error( $image_response ) ) {
                            // Add some IOTD specific data to the image
                            $image_response->date = isset( $iotd->date ) ? $iotd->date : '';
                            $iotd_objects[] = $image_response;
                        }
                    }
                }
                $objects = $iotd_objects;
            }
        } elseif ( $type !== 'image_of_the_day' ) {
            $objects = array( $api_response );
        } else {
            $objects = array();
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
                // Date field only for IOTD
                'date'         => isset( $image->date ) ? $image->date : '',
                'url_regular'  => isset( $image->url_regular ) ? $image->url_regular : '',
                'url_thumb'    => isset( $image->url_thumb ) ? $image->url_thumb : '',
                'url_duckduckgo' => isset( $image->url_duckduckgo ) ? $image->url_duckduckgo : (isset( $image->url_thumb ) ? $image->url_thumb : ''),
                'url_real'     => isset( $image->url_real ) ? $image->url_real : (isset( $image->url_regular ) ? $image->url_regular : ''),
            );
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