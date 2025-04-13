<?php
/**
 * NASA API Integration
 *
 * @package Mosne_Media_Library_Astronomy
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Mosne_NASA_API
 * Handles NASA API requests and REST API endpoints
 */
class Mosne_NASA_API {

	/**
	 * NASA API base URL
	 */
	const NASA_API_BASE_URL = 'https://api.nasa.gov/';

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
		// NASA Search Endpoint
		register_rest_route(
			'mosne-media-library-astronomy/v1',
			'/nasa/search',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'search_nasa_images' ),
				'permission_callback' => array( 'Mosne_Astronomy_API', 'api_permissions_check' ),
				'args'                => array(
					'term'       => array(
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'page_size'  => array(
						'required'          => false,
						'sanitize_callback' => 'absint',
						'default'           => 30,
					),
					'page'       => array(
						'required'          => false,
						'sanitize_callback' => 'absint',
						'default'           => 1,
					),
					'media_type' => array(
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
						'default'           => 'image',
					),
					'type'       => array(
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * Search NASA images
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public static function search_nasa_images( $request ) {
		// Debug logging
		error_log( 'NASA search request received: ' . print_r( $request->get_params(), true ) );

		$search_term = $request->get_param( 'term' );
		$page_size   = $request->get_param( 'page_size' );
		$page        = $request->get_param( 'page' );
		$media_type  = $request->get_param( 'media_type' );
		$type        = $request->get_param( 'type' );

		// Get API credentials
		$nasa_api_key = Mosne_Astronomy_Settings::get_nasa_api_key();

		if ( empty( $nasa_api_key ) ) {
			return new WP_REST_Response(
				array(
					'error' => __( 'NASA API key is not configured.', 'mosne-media-library-astronomy' ),
				),
				400
			);
		}

		// Handle different NASA API endpoints based on type
		if ( 'nasa_imageoftheday' === $type ) {
			return self::get_nasa_image_of_the_day( $nasa_api_key, $page_size, $page );
		}

		$params = array(
			'api_key'    => $nasa_api_key,
			'media_type' => $media_type,
		);

		if ( ! empty( $search_term ) ) {
			$params['q'] = $search_term;
		}

		// Add pagination parameters
		if ( $page_size ) {
			$params['page_size'] = $page_size;
		}

		if ( $page ) {
			$params['page'] = $page;
		}

		// Make the API request to NASA Images API
		$api_response = self::make_nasa_request( 'search', $params );

		if ( is_wp_error( $api_response ) ) {
			return new WP_REST_Response(
				array(
					'error' => $api_response->get_error_message(),
				),
				500
			);
		}

		// Process NASA results
		$formatted_results = array(
			'objects' => self::process_nasa_results( $api_response ),
		);

		return new WP_REST_Response( $formatted_results );
	}

	/**
	 * Get NASA Image of the Day
	 *
	 * @param string $api_key   NASA API key.
	 * @param int    $page_size Number of images to retrieve.
	 * @param int    $page      Page number.
	 * @return WP_REST_Response
	 */
	private static function get_nasa_image_of_the_day( $api_key, $page_size = 10, $page = 1 ) {
		// Calculate date range based on page size and page number
		$end_date   = new DateTime();
		$start_date = clone $end_date;

		// Calculate start date based on pagination
		$offset = ( $page - 1 ) * $page_size;
		$start_date->modify( '-' . ( $offset + $page_size - 1 ) . ' days' );

		// Get one day at a time for the requested range
		$results = array();

		for ( $i = 0; $i < $page_size; $i++ ) {
			$current_date = clone $start_date;
			$current_date->modify( '+' . $i . ' days' );

			// Skip future dates
			if ( $current_date > $end_date ) {
				break;
			}

			$date_str = $current_date->format( 'Y-m-d' );

			$params = array(
				'api_key' => $api_key,
				'date'    => $date_str,
			);

			$apod = self::make_nasa_request( 'apod', $params );

			if ( ! is_wp_error( $apod ) && isset( $apod['url'] ) ) {
				// Only include image media types
				if ( isset( $apod['media_type'] ) && 'image' !== $apod['media_type'] ) {
					continue;
				}

				// Format the APOD data
				$results[] = array(
					'id'            => md5( $apod['url'] ),
					'title'         => $apod['title'] ?? 'NASA Image of the Day',
					'description'   => $apod['explanation'] ?? '',
					'thumbnail_url' => $apod['url'],
					'url'           => $apod['url'] ?? $apod['url'],
					'date_created'  => $date_str,
					'nasa_id'       => 'apod_' . str_replace( '-', '', $date_str ),
					'source'        => 'nasa',
					'photographer'  => $apod['copyright'] ?? 'NASA',
					'license'       => 'Public Domain',
					'license_url'   => 'https://www.nasa.gov/multimedia/guidelines/index.html',
					'center'        => 'APOD',
					'keywords'      => array( 'APOD', 'Astronomy Picture of the Day' ),
				);
			}
		}

		$formatted_results = array(
			'objects' => $results,
		);

		return new WP_REST_Response( $formatted_results );
	}

	/**
	 * Process NASA API results
	 *
	 * @param array $api_response The API response.
	 * @return array Formatted results.
	 */
	private static function process_nasa_results( $api_response ) {
		$results = array();

		if ( empty( $api_response['collection']['items'] ) ) {
			return $results;
		}

		foreach ( $api_response['collection']['items'] as $item ) {
			// Basic data validation
			if ( empty( $item['data'][0] ) || empty( $item['links'][0]['href'] ) ) {
				continue;
			}

			$data      = $item['data'][0];
			$thumbnail = $item['links'][0]['href'];

			// Format NASA image data
			$result = array(
				'id'            => md5( $thumbnail ), // Generate an ID from the thumbnail URL
				'title'         => $data['title'] ?? '',
				'description'   => $data['description'] ?? '',
				'thumbnail_url' => $thumbnail,
				'url'           => $thumbnail,
				'date_created'  => $data['date_created'] ?? '',
				'nasa_id'       => $data['nasa_id'] ?? '',
				'source'        => 'nasa',
				'photographer'  => $data['photographer'] ?? 'NASA',
				'license'       => 'Public Domain',
				'license_url'   => 'https://www.nasa.gov/multimedia/guidelines/index.html',
				'center'        => $data['center'] ?? '',
				'keywords'      => $data['keywords'] ?? array(),
			);

			$results[] = $result;
		}

		return $results;
	}

	/**
	 * Make NASA API request
	 *
	 * @param string $endpoint API endpoint.
	 * @param array  $params   Request parameters.
	 * @return mixed
	 */
	private static function make_nasa_request( $endpoint, $params = array() ) {
		// Log the request parameters
		error_log( 'NASA API request - Endpoint: ' . $endpoint . ', Params: ' . print_r( $params, true ) );

		// Build request URL
		$url = self::NASA_API_BASE_URL . 'planetary/apod';

		// If using the search endpoint, use NASA Images API instead
		if ( 'search' === $endpoint ) {
			$url = 'https://images-api.nasa.gov/search';
		} elseif ( 'apod' === $endpoint ) {
			$url = self::NASA_API_BASE_URL . 'planetary/apod';
		}

		if ( ! empty( $params ) ) {
			$url = add_query_arg( $params, $url );
		}

		// Log the request URL (hiding API key)
		$log_url = preg_replace( '/api_key=[^&]+/', 'api_key=HIDDEN', $url );
		error_log( 'NASA API request URL: ' . $log_url );

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
			error_log( 'NASA API error: ' . $response->get_error_message() );
			return $response;
		}

		// Check response code
		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $response_code ) {
			error_log( 'NASA API HTTP error: ' . $response_code . ' - ' . wp_remote_retrieve_body( $response ) );
			return new WP_Error(
				'nasa_api_error',
				sprintf(
					/* translators: %d: HTTP response code */
					__( 'NASA API request failed with code %d', 'mosne-media-library-astronomy' ),
					$response_code
				)
			);
		}

		// Parse response
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( JSON_ERROR_NONE !== json_last_error() ) {
			error_log( 'NASA API JSON parse error: ' . json_last_error_msg() );
			return new WP_Error( 'json_parse_error', __( 'Failed to parse NASA API response', 'mosne-media-library-astronomy' ) );
		}

		return $data;
	}
}
