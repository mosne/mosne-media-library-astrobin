<?php
/**
 * AstroBin API Integration
 *
 * @package Mosne_Media_Library_Astronomy
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
	const ASTROBIN_API_BASE_URL = 'https://www.astrobin.com/api/v1/';

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
		// AstroBin Search Endpoint
		register_rest_route(
			'mosne-media-library-astronomy/v1',
			'/astrobin/search',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'search_astrobin_images' ),
				'permission_callback' => array( 'Mosne_Astronomy_API', 'api_permissions_check' ),
				'args'                => array(
					'term'      => array(
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'username'  => array(
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'page_size' => array(
						'required'          => false,
						'sanitize_callback' => 'absint',
						'default'           => 30,
					),
					'page'      => array(
						'required'          => false,
						'sanitize_callback' => 'absint',
						'default'           => 1,
					),
					'type'      => array(
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
						'default'           => 'my_pictures',
					),
				),
			)
		);
	}

	/**
	 * Search AstroBin images
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public static function search_astrobin_images( $request ) {
		// Debug logging
		error_log( 'AstroBin search request received: ' . print_r( $request->get_params(), true ) );

		$search_term = $request->get_param( 'term' );
		$username    = $request->get_param( 'username' );
		$page_size   = $request->get_param( 'page_size' );
		$page        = $request->get_param( 'page' );
		$type        = $request->get_param( 'type' );

		// Get API credentials
		$credentials = Mosne_Astronomy_Settings::get_astrobin_credentials();
		error_log( 'AstroBin credentials: ' . print_r( $credentials, true ) );

		if ( empty( $credentials['api_key'] ) || empty( $credentials['api_secret'] ) ) {
			error_log( 'AstroBin API credentials missing' );
			return new WP_REST_Response(
				array(
					'error' => __( 'AstroBin API credentials are not configured.', 'mosne-media-library-astronomy' ),
				),
				400
			);
		}

		$params            = array();
		$formatted_results = array(
			'objects' => array(),
		);

		// Determine endpoint based on type
		$endpoint = 'image/';

		switch ( $type ) {
			case 'astrobin_my_pictures':
				// Search by current user
				$params['user'] = $credentials['username'];
				break;

			case 'astrobin_user_gallery':
				// Search by specified username
				if ( ! empty( $username ) ) {
					$params['user'] = $username;
				}
				break;

			case 'astrobin_imageoftheday':
				// Image of the Day
				$endpoint = 'imageoftheday/';
				break;

			case 'astrobin_by_subject':
				// Search by astronomical subject
				if ( ! empty( $search_term ) ) {
					$params['subjects'] = $search_term;
				}
				break;

			case 'astrobin_by_hash':
				// Search by image hash
				if ( ! empty( $search_term ) ) {
					$endpoint = 'image/' . $search_term . '/';
				}
				break;
		}

		// Add search term if provided (for endpoints that support it)
		if ( ! empty( $search_term ) && 'image/' === $endpoint ) {
			$params['title__icontains'] = $search_term;
		}

		// Add pagination parameters
		$params['limit'] = $page_size;
		if ( $page > 1 ) {
			$params['offset'] = ( $page - 1 ) * $page_size;
		}

		// Make the API request
		$api_response = self::make_astrobin_request( $endpoint, $params );

		if ( is_wp_error( $api_response ) ) {
			return new WP_REST_Response(
				array(
					'error' => $api_response->get_error_message(),
				),
				500
			);
		}

		// Process results based on endpoint type
		if ( 'imageoftheday/' === $endpoint ) {
			self::process_astrobin_imageoftheday_results( $api_response, $formatted_results );
		} elseif ( 'astrobin_by_hash' === $type && ! empty( $search_term ) ) {
			self::process_astrobin_by_hash_results( $api_response, $formatted_results );
		} else {
			self::process_astrobin_standard_results( $api_response, $formatted_results );
		}

		return new WP_REST_Response( $formatted_results );
	}

	/**
	 * Process AstroBin Image of the Day results
	 *
	 * @param object $api_response The API response.
	 * @param array  $formatted_results Results array to populate.
	 */
	private static function process_astrobin_imageoftheday_results( $api_response, &$formatted_results ) {
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
			$image_response = self::make_astrobin_request( 'image/' . $image_id . '/', array() );

			if ( ! is_wp_error( $image_response ) && is_object( $image_response ) ) {
				$formatted_results['objects'][] = self::format_astrobin_image_data( $image_response, $iotd->date ?? '' );
			}
		}
	}

	/**
	 * Process AstroBin standard image results
	 *
	 * @param object $api_response The API response.
	 * @param array  $formatted_results Results array to populate.
	 */
	private static function process_astrobin_standard_results( $api_response, &$formatted_results ) {
		$objects = isset( $api_response->objects ) && is_array( $api_response->objects )
			? $api_response->objects
			: array( $api_response );

		foreach ( $objects as $image ) {
			// Skip if no data
			if ( ! isset( $image->id ) ) {
				continue;
			}

			$formatted_results['objects'][] = self::format_astrobin_image_data( $image );
		}
	}

	/**
	 * Process AstroBin by hash results
	 *
	 * @param object $api_response The API response.
	 * @param array  $formatted_results Results array to populate.
	 */
	private static function process_astrobin_by_hash_results( $api_response, &$formatted_results ) {
		if ( empty( $api_response->id ) ) {
			return;
		}

		$formatted_results['objects'][] = self::format_astrobin_image_data( $api_response );
	}

	/**
	 * Format AstroBin image data for consistent output
	 *
	 * @param object $image The image data from API.
	 * @param string $date  Optional date for IOTD.
	 * @return array
	 */
	private static function format_astrobin_image_data( $image, $date = '' ) {
		// Extract username from resource URI
		$username = '';
		if ( ! empty( $image->user ) ) {
			$username_parts = explode( '/', rtrim( $image->user, '/' ) );
			$username       = end( $username_parts );
		}

		// Format data consistently
		$formatted = array(
			'id'            => $image->id,
			'hash'          => $image->hash,
			'title'         => $image->title ?? esc_html__( 'Untitled', 'mosne-media-library-astronomy' ),
			'description'   => $image->description ?? '',
			'url'           => 'https://www.astrobin.com/' . $image->hash . '/',
			'thumbnail_url' => $image->url_regular ?? '',
			'url_real'      => $image->url_real ?? '',
			'url_hd'        => $image->url_hd ?? '',
			'source'        => 'astrobin',
			'username'      => $username ?? '',
			'license'       => self::get_astrobin_license_name( $image->license ?? null ),
			'license_url'   => self::get_astrobin_license_url( $image->license ?? null, $image->hash ),
		);

		// Add IOTD date if available
		if ( ! empty( $date ) ) {
			$formatted['iotd_date'] = $date;
		}

		return $formatted;
	}

	/**
	 * Extract image ID from AstroBin path
	 *
	 * @param string $image_path Path from API.
	 * @return string|false
	 */
	private static function extract_image_id( $image_path ) {
		if ( preg_match( '#/(\d+)/$#', $image_path, $matches ) ) {
			return $matches[1];
		}
		return false;
	}

	/**
	 * Make AstroBin API request
	 *
	 * @param string $endpoint API endpoint.
	 * @param array  $params   Request parameters.
	 * @return mixed
	 */
	private static function make_astrobin_request( $endpoint, $params = array() ) {
		// Get API credentials
		$credentials = Mosne_Astronomy_Settings::get_astrobin_credentials();

		// Add authentication parameters
		$params['api_key']    = $credentials['api_key'];
		$params['api_secret'] = $credentials['api_secret'];
		$params['format']     = 'json';

		// Build request URL
		$url = self::ASTROBIN_API_BASE_URL . $endpoint;
		if ( ! empty( $params ) ) {
			$url = add_query_arg( $params, $url );
		}

		// Log the request URL (without credentials)
		$log_url = preg_replace( '/api_key=[^&]+&api_secret=[^&]+/', 'api_key=HIDDEN&api_secret=HIDDEN', $url );
		error_log( 'AstroBin API request URL: ' . $log_url );

		// Make request
		$response = wp_remote_get(
			$url,
			array(
				'timeout'     => 30,
				'redirection' => 5,
				'httpversion' => '1.1',
				'user-agent'  => 'MosneMediaLibraryAstronomy/1.0.0',
				'headers'     => array(
					'Accept' => 'application/json',
				),
			)
		);

		// Check for errors
		if ( is_wp_error( $response ) ) {
			error_log( 'AstroBin API error: ' . $response->get_error_message() );
			return $response;
		}

		// Check response code
		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $response_code ) {
			error_log( 'AstroBin API HTTP error: ' . $response_code . ' - ' . wp_remote_retrieve_body( $response ) );
			return new WP_Error(
				'astrobin_api_error',
				sprintf(
					/* translators: %d: HTTP response code */
					__( 'AstroBin API request failed with code %d', 'mosne-media-library-astronomy' ),
					$response_code
				)
			);
		}

		// Parse response
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body );

		if ( JSON_ERROR_NONE !== json_last_error() ) {
			return new WP_Error( 'json_parse_error', __( 'Failed to parse AstroBin API response', 'mosne-media-library-astronomy' ) );
		}

		return $data;
	}

	/**
	 * Get AstroBin license name
	 *
	 * @param int|null $license_type License type ID.
	 * @return string
	 */
	public static function get_astrobin_license_name( $license_type ) {
		$licenses = array(
			0 => __( 'All rights reserved', 'mosne-media-library-astronomy' ),
			1 => __( 'Attribution-NonCommercial-ShareAlike Creative Commons', 'mosne-media-library-astronomy' ),
			2 => __( 'Attribution-NonCommercial Creative Commons', 'mosne-media-library-astronomy' ),
			3 => __( 'Attribution-NonCommercial-NoDerivs Creative Commons', 'mosne-media-library-astronomy' ),
			4 => __( 'Attribution Creative Commons', 'mosne-media-library-astronomy' ),
			5 => __( 'Attribution-ShareAlike Creative Commons', 'mosne-media-library-astronomy' ),
			6 => __( 'Attribution-NoDerivs Creative Commons', 'mosne-media-library-astronomy' ),
		);

		if ( is_null( $license_type ) || ! isset( $licenses[ $license_type ] ) ) {
			return __( 'Unknown license', 'mosne-media-library-astronomy' );
		}

		return $licenses[ $license_type ];
	}

	/**
	 * Get AstroBin license URL
	 *
	 * @param int|null $license_type License type ID.
	 * @param string   $image_hash   Image hash for fallback URL.
	 * @return string
	 */
	public static function get_astrobin_license_url( $license_type, $image_hash ) {
		$license_urls = array(
			0 => '',
			1 => 'https://creativecommons.org/licenses/by-nc-sa/4.0/',
			2 => 'https://creativecommons.org/licenses/by-nc/4.0/',
			3 => 'https://creativecommons.org/licenses/by-nc-nd/4.0/',
			4 => 'https://creativecommons.org/licenses/by/4.0/',
			5 => 'https://creativecommons.org/licenses/by-sa/4.0/',
			6 => 'https://creativecommons.org/licenses/by-nd/4.0/',
		);

		if ( is_null( $license_type ) || ! isset( $license_urls[ $license_type ] ) || empty( $license_urls[ $license_type ] ) ) {
			// Return the image URL if no license URL is available
			return 'https://www.astrobin.com/' . $image_hash . '/';
		}

		return $license_urls[ $license_type ];
	}
}
