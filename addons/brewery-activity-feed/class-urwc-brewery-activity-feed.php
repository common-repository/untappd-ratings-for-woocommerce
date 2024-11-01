<?php
/**
 * URWC_Brewery_Activity_Feed
 *
 * @author    ChillCode
 * @copyright Copyright (c) 2024, ChillCode All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 * @package   Untappd Ratings for WooCommerce
 */

/**
 * URWC_Brewery_Activity_Feed class.
 *
 * @package WooCommerce Untappd\Addons
 */

defined( 'ABSPATH' ) || exit;

/**
 * URWC_Brewery_Activity_Feed class.
 */
class URWC_Brewery_Activity_Feed {


	/**
	 * Untappd default coordinates.
	 *
	 * @var array
	 */
	protected $default_coordinates;

	/**
	 * Untappd default coordinates.
	 *
	 * @var bool
	 */
	protected $assets_already_enqueued;

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		/**
		 * Register private and public access to Ajax endpont.
		 */
		add_action( 'wp_ajax_urwc_map_feed', array( $this, 'urwc_map_feed' ) );
		add_action( 'wp_ajax_nopriv_urwc_map_feed', array( $this, 'urwc_map_feed' ) );

		if ( is_admin() ) {
			/**
			 * Check whenever the moderation & disallowed keys are updated.
			 */
			add_action( 'update_option_moderation_keys', array( $this, 'update_option_keys' ), 10, 2 );
			add_action( 'update_option_disallowed_keys', array( $this, 'update_option_keys' ), 10, 2 );
		}

		/**
		 * Add a shortcode.
		 */
		add_shortcode( 'urwc_untappd_map', array( $this, 'urwc_untappd_map_sc' ) );

		/**
		 * Enque assets only once.
		 */
		$this->assets_already_enqueued = false;

		/** Untappd coordinates */
		$this->default_coordinates = array(
			0 => '34.2346598',
			1 => '-77.9482096',
		);
	}

	/**
	 * Reset cache on moderation/disallowed key change.
	 *
	 * @param string $previous_keys Previous moderation keys.
	 * @param string $new_keys New moderation keys.
	 * @return void
	 */
	public function update_option_keys( $previous_keys, $new_keys ) { //phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		URWC_Ratings::delete_cache();
	}

	/**
	 * Display a map with checkins.
	 *
	 * @param array $map_atts (Required) Shortcode attributes.
	 */
	public function urwc_untappd_map_sc( $map_atts ) {
		/**
		 * Parse shortcode attributes.
		 */
		$map_atts = shortcode_atts(
			apply_filters(
				'urwc_untappd_map_atts',
				array(
					'api_key'          => '',
					'brewery_id'       => 0,
					'center_map'       => 'yes',
					'lat_lng'          => '',
					'max_checkins'     => 25,
					'class'            => '',
					'container_class'  => '',
					'height'           => '500px',
					'container_height' => '',
					'width'            => '640px',
					'container_width'  => '',
					'id'               => '',
					'style'            => '',
					'container_style'  => '',
					'type'             => 'interactive',
					'use_icon'         => true,
					'use_url_icon'     => false,
					'zoom'             => 4,
				)
			),
			$map_atts,
			'urwc_untappd_map'
		);

		/**
		 * Check required shortcode attributes.
		 */
		if ( empty( $map_atts['api_key'] ) || empty( $map_atts['brewery_id'] ) || empty( $map_atts['type'] ) ) {
			return sprintf(
				'<div class="woocommerce-info">%s</div>',
				esc_html( __( 'Invalid shortcode attributes. Please verify the configuration.', 'untappd-ratings-for-woocommerce' ) )
			);
		}

		/**
		 * Prepare shortcode attributes.
		 */

		/** Uniqid */
		$map_atts['uniqid'] = uniqid();

		if ( empty( $map_atts['id'] ) ) {
			$map_atts['id'] = 'urwc-map-canvas-' . $map_atts['uniqid'];
		}

		/** Shortcode id */
		$map_scid = wp_hash( (string) $map_atts['brewery_id'] . '_' . (string) $map_atts['max_checkins'], 'nonce' );

		/** Map type */
		$map_type = esc_attr( $map_atts['type'] );

		/** Map class */
		if ( empty( $map_atts['class'] ) ) {
			$map_atts['class'] = 'urwc-map';
		}

		$map_class      = esc_attr( $map_atts['class'] );
		$map_class_attr = ' class="' . $map_class . ' ' . $map_class . '-' . $map_type . '"';

		if ( empty( $map_atts['container_class'] ) ) {
			$map_atts['container_class'] = 'urwc-map-container';
		}

		$map_class_attr_container = ' class="' . esc_attr( $map_atts['container_class'] . ' ' . $map_atts['container_class'] . '-' . $map_type ) . '"';

		$map_class_loading_overlay = $map_class . '-loading-overlay-' . $map_atts['uniqid'];
		$map_class_loading_content = $map_class . '-loading-content-' . $map_atts['uniqid'];

		$map_is_interactive = 'interactive' === $map_atts['type'];

		/**
		 * Map style.
		 */
		$map_style_array = array();

		if ( ! empty( $map_atts['style'] ) ) {
			$map_style_array[] = $map_atts['style'];
		}

		/**
		 * Check to use % or px.
		 *
		 * For static maps we convert % to px to prevent error 400 Google Maps API response.
		 */

		/**
		 * Map style.
		 */
		if ( ! empty( $map_atts['height'] ) ) {
			$map_atts['height'] = absint( $map_atts['height'] ) . ( ( $map_is_interactive && '%' === substr( $map_atts['height'], -1, 1 ) ) ? '%' : 'px' );
			$map_style_array[]  = 'height:' . $map_atts['height'];
		}

		if ( ! empty( $map_atts['width'] ) ) {
			$map_atts['width'] = absint( $map_atts['width'] ) . ( ( $map_is_interactive && '%' === substr( $map_atts['width'], -1, 1 ) ) ? '%' : 'px' );
			$map_style_array[] = 'width:' . $map_atts['width'];
		}

		$map_style_attr = ! empty( $map_style_array ) ? ' style="' . esc_attr( implode( ';', $map_style_array ) ) . '"' : '';

		/**
		 * Map container style.
		 */
		$map_style_container_array = array();

		if ( ! empty( $map_atts['container_style'] ) ) {
			$map_style_container_array[] = $map_atts['container_style'];
		}

		// Check to use % or px.

		if ( ! empty( $map_atts['container_height'] ) ) {
			$map_style_container_array[] = 'height:' . absint( $map_atts['container_height'] ) . ( ( $map_is_interactive && '%' === substr( $map_atts['container_height'], -1, 1 ) ) ? '%' : 'px' );
		}

		if ( ! empty( $map_atts['container_width'] ) ) {
			$map_style_container_array[] = 'width:' . absint( $map_atts['container_width'] ) . ( ( $map_is_interactive && '%' === substr( $map_atts['container_width'], -1, 1 ) ) ? '%' : 'px' );
		}

		$map_style_container_attr = ! empty( $map_style_container_array ) ? ' style="' . esc_attr( implode( ';', $map_style_container_array ) ) . '"' : '';

		/**
		 * Prepare data-map-* attributes.
		 */
		$map_at_home_coordinates = $this->get_home_coordinates( $map_atts['lat_lng'] );

		$map_data_attributes = array(
			'id'         => esc_attr( $map_atts['id'] ),
			'uniqid'     => esc_attr( $map_atts['uniqid'] ),
			'type'       => esc_attr( $map_atts['type'] ),
			'useIcon'    => esc_attr( $map_atts['use_icon'] ),
			'useurlicon' => esc_attr( $map_atts['use_url_icon'] ),
			'centerlat'  => esc_attr( $map_at_home_coordinates['lat'] ),
			'centerlng'  => esc_attr( $map_at_home_coordinates['lng'] ),
			'centermap'  => esc_attr( $map_atts['center_map'] ),
			'zoom'       => esc_attr( $map_atts['zoom'] ),
			'height'     => esc_attr( $map_atts['height'] ),
			'width'      => esc_attr( $map_atts['width'] ),
			'scid'       => esc_attr( $map_scid ),
			'apikey'     => esc_attr( $map_atts['api_key'] ),
		);

		$map_data_attributes_str = '';

		array_walk(
			$map_data_attributes,
			function ( $value, $key ) use ( &$map_data_attributes_str ) {
				$map_data_attributes_str .= ' data-map-' . $key . '="' . $value . '"';
			}
		);

		/**
		 * Enqueue script only once.
		 *
		 * URWC allows multiple maps per page, but Google Maps has a known limitation:
		 * only one library is allowed to load, preventing maps with different keys or languages.
		 */
		if ( false === $this->assets_already_enqueued ) {
			$this->assets_already_enqueued = $this->enqueue_assets( $map_data_attributes['apikey'] );
		}

		/**
		 * Generate jQuery map initializer, unique per each shortcode.
		 *
		 * Append it after Google Maps JavaScript API loader.
		 */
		$map_output_inline_script = 'jQuery("#' . $map_data_attributes['id'] . '").UntappdMap(urwc_settings);';

		wp_add_inline_script( 'urwc-brewery-activity-feed', $map_output_inline_script );

		/**
		 * Update shortcode options.
		 *
		 * $map_scid: Map shortcode id.
		 */
		update_option( 'urwc_map_scid_' . $map_scid, absint( $map_atts['brewery_id'] ) );
		update_option( 'urwc_map_max_checkins_' . $map_scid, absint( $map_atts['max_checkins'] ) );

		/**
		 * Generate HTML output.
		 *
		 * Append container div to Google map div.
		 */
		$map_output = '<div class="' . $map_class_loading_content . '"></div>';
		$map_output = '<div class="' . $map_class_loading_overlay . '">' . $map_output . '</div>';
		$map_output = '<div id="' . $map_data_attributes['id'] . '"' . $map_data_attributes_str . $map_class_attr . $map_style_attr . '></div>' . $map_output;
		$map_output = '<div id="' . $map_data_attributes['id'] . '-container"' . $map_class_attr_container . $map_style_container_attr . '>' . $map_output . '</div>';

		return $map_output;
	}

	/**
	 * Enqueue assets required for Google Maps.
	 *
	 * @param string $api_key Google maps API key.
	 *
	 * @return bool True on success, false otherwise.
	 */
	private function enqueue_assets( string $api_key ) {

		/**
		 * Enqueue shortcode local assets.
		 */
		$map_scripts_suffix = wp_scripts_get_suffix();

		/**
		 * Stylesheet.
		 */
		wp_enqueue_style( 'urwc-brewery-activity-feed', plugins_url( 'assets/css/urwc-brewery-activity-feed' . $map_scripts_suffix . '.css', __FILE__ ), array(), URWC_VERSION );

		/**
		 * Javascript.
		 */
		wp_enqueue_script( 'urwc-brewery-activity-feed-google-loader', plugins_url( 'assets/js/urwc-brewery-activity-feed-google-loader' . $map_scripts_suffix . '.js', __FILE__ ), array( 'jquery' ), URWC_VERSION, true );
		wp_enqueue_script( 'urwc-brewery-activity-feed-pagination', plugins_url( 'assets/js/urwc-brewery-activity-feed-pagination' . $map_scripts_suffix . '.js', __FILE__ ), array( 'jquery' ), URWC_VERSION, true );
		wp_enqueue_script( 'urwc-brewery-activity-feed', plugins_url( 'assets/js/urwc-brewery-activity-feed' . $map_scripts_suffix . '.js', __FILE__ ), array( 'jquery' ), URWC_VERSION, true );

		return wp_localize_script(
			'urwc-brewery-activity-feed-google-loader',
			'urwc_settings',
			array(
				'map_api_key'                    => esc_attr( $api_key ),
				'map_show_infowindow_disclaimer' => esc_attr( get_option( 'urwc_map_show_infowindow_disclaimer', false ) ),
				/**
				 * We only use WP language to prevent inconsistencies. WPML compatible.
				 */
				'map_lang'                       => apply_filters( 'wpml_current_language', get_locale() ),
				'map_nonce'                      => wp_create_nonce( 'map-feed' ),
				'map_ajax_url'                   => admin_url( 'admin-ajax.php' ),
				// TODO: Gettext method using keys.
				'map_i18n'                       => array(
					0 => esc_attr__( ' on ', 'untappd-ratings-for-woocommerce' ),
					1 => esc_attr__( 'Comment', 'untappd-ratings-for-woocommerce' ),
					2 => esc_attr__( 'Rating', 'untappd-ratings-for-woocommerce' ),
					3 => esc_attr__( 'is drinking a', 'untappd-ratings-for-woocommerce' ),
					4 => esc_attr__( ' at ', 'untappd-ratings-for-woocommerce' ),
					5 => esc_attr__( 'View product', 'untappd-ratings-for-woocommerce' ),
					6 => number_format_i18n( 5 ),
					7 => esc_attr__( 'Reviews are not verified', 'untappd-ratings-for-woocommerce' ),
				),
			)
		);
	}

	/**
	 * Generate a map feed.
	 */
	public function urwc_map_feed() {
		check_ajax_referer( 'map-feed', 'security' );

		if ( ! URWC_Ratings::api_is_active() ) {
			$map_feed_brewery_activity['error'] = _x( 'API not configured.', 'enhanced select', 'untappd-ratings-for-woocommerce' );

			wp_send_json( $map_feed_brewery_activity );
		}

		/** Get and validate short code id. */
		$map_feed_scid = filter_input( INPUT_GET, 'scid', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$map_feed_scid = sanitize_text_field( $map_feed_scid );

		if ( empty( $map_feed_scid ) ) {
			wp_send_json( array( 'error' => __( 'Invalid map id.', 'untappd-ratings-for-woocommerce' ) ) );
		}

		$map_feed_brewery_id = get_option( 'urwc_map_scid_' . $map_feed_scid );

		if ( false === $map_feed_brewery_id ) {
			wp_send_json( array( 'error' => __( 'Invalid brewery id.', 'untappd-ratings-for-woocommerce' ) ) );
		}

		/** Max checkins. */
		$map_feed_max_checkins = get_option( 'urwc_map_max_checkins_' . $map_feed_scid, 25 );

		/** Global options. */
		$map_feed_show_ratings_to_admin_only = get_option( 'urwc_map_show_ratings_to_admin_only', false );
		$map_feed_add_product_link           = get_option( 'urwc_map_add_product_link', true );

		/** Get cache data. */
		$map_feed_cache_key = 'urwc_map_feed_' . $map_feed_scid . ( ( current_user_can( 'edit_posts' ) ) ? '_is_editor_' : '_' ) . apply_filters( 'wpml_current_language', get_locale() ) . '_' . $map_feed_max_checkins . '_' . $map_feed_show_ratings_to_admin_only . '_' . $map_feed_add_product_link;

		$map_feed_data = get_transient( $map_feed_cache_key );

		if ( false !== $map_feed_data && is_array( $map_feed_data ) ) {
			wp_send_json( $map_feed_data );
		}

		/** Check if cache failed twice. */
		if ( get_option( 'urwc_map_cache_is_working' ) === 'no' ) {
			wp_send_json( array( 'error' => __( 'Cache not working.', 'untappd-ratings-for-woocommerce' ) ) );
		}

		/** Populate feed. */
		$map_feed_limit = $map_feed_max_checkins < 25 ? $map_feed_max_checkins : 25;

		$map_feed_brewery_activity = URWC_Ratings::API()->brewery_activity_feed( $map_feed_brewery_id, null, null, $map_feed_limit );

		if ( urwc_is_error( $map_feed_brewery_activity ) ) {
			urwc_logger(
				wp_json_encode(
					array(
						'urwc_map_feed' => array(
							'scid'       => $map_feed_scid,
							'brewery_id' => $map_feed_brewery_id,
							'message'    => $map_feed_brewery_activity->getMessage(),
						),
					)
				),
				'warning'
			);
			wp_send_json( array( 'error' => __( 'API not working:', 'untappd-ratings-for-woocommerce' ) . ' ' . $map_feed_brewery_activity->getMessage() ) );
		}

		$map_feed_data = $this->brewery_feed( $map_feed_brewery_activity, $map_feed_add_product_link, $map_feed_show_ratings_to_admin_only );

		if ( empty( $map_feed_data ) ) {
			wp_send_json( array( 'error' => __( 'Empty feed.', 'untappd-ratings-for-woocommerce' ) ) );
		}

		$map_feed_max_checkins = $this->max_checkins( $map_feed_max_checkins );

		if ( $map_feed_max_checkins > 1 ) {
			for ( $i = 1; $i < $map_feed_max_checkins; $i++ ) {
				// If max_id is empty no more pagination need.
				if ( empty( $map_feed_brewery_activity['response']['pagination']['max_id'] ) ) {
					break;
				}

				$map_feed_brewery_activity = URWC_Ratings::API()->brewery_activity_feed( $map_feed_brewery_id, $map_feed_brewery_activity['response']['pagination']['max_id'] );

				if ( urwc_is_error( $map_feed_brewery_activity ) ) {
					urwc_logger(
						wp_json_encode(
							array(
								'urwc_map_feed' => array(
									'scid'       => $map_feed_scid,
									'brewery_id' => $map_feed_brewery_id,
									'message'    => $map_feed_brewery_activity->getMessage(),
								),
							)
						),
						'warning'
					);
					break;
				}

				if ( empty( $map_feed_brewery_activity ) ) {
					break;
				}

				$map_feed_data = $this->array_merge_recursive_distinct( $map_feed_data, $this->brewery_feed( $map_feed_brewery_activity ) );
			}
		}

		/**
		 * Cache data.
		 */
		$map_feed_set_transient_result = set_transient( $map_feed_cache_key, $map_feed_data, URWC_Ratings::API()->get_cache_time() );

		/**
		 * If transient cannot be set, delete it and try again, we need to ensure data is stored.
		 */
		if ( false === $map_feed_set_transient_result ) {
			delete_transient( $map_feed_cache_key );
			$map_feed_set_transient_result = set_transient( $map_feed_cache_key, $map_feed_data, URWC_Ratings::API()->get_cache_time() );
		}

		/**
		 * If transient cannot be set, stop API call execution to prevent 429 response from Untappd endpoint.
		 */
		update_option( 'urwc_map_cache_is_working', ( $map_feed_set_transient_result ) ? 'yes' : 'no' );

		/**
		 * Output data.
		 */
		wp_send_json( $map_feed_data );
	}

	/**
	 * Get Untappd at Home corrdinates.
	 *
	 * @param string $at_home_coordinates Latitude, Longitude.
	 */
	private function get_home_coordinates( $at_home_coordinates = '' ) {
		if (
			empty( $at_home_coordinates ) ||
			! is_string( $at_home_coordinates ) ||
			substr_count( $at_home_coordinates, ',' ) !== 1 ||
			//phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.Found, Squiz.PHP.DisallowMultipleAssignments.FoundInControlStructure
			! ( $home_coordinates = explode( ',', $at_home_coordinates ) )
		) {
			$home_coordinates = $this->default_coordinates;
		}

		$home_coordinates['lat'] = $home_coordinates[0];
		$home_coordinates['lng'] = $home_coordinates[1];

		return $home_coordinates;
	}

	/**
	 * Generate a brewey feed array from Untappd results.
	 *
	 * @param array $brewery_feed_result Json decoded result from Untappd call.
	 * @param bool  $add_product_link add a link to the product if present on shop.
	 * @param bool  $show_ratings_to_admin_only Show comments and ratings on infoWindows only to administrators.
	 */
	private function brewery_feed( array $brewery_feed_result, bool $add_product_link = true, $show_ratings_to_admin_only = false ) {

		$brewery_feed = array();

		if ( ! empty( $brewery_feed_result ) && isset( $brewery_feed_result['response']['checkins']['count'] ) ) {

			foreach ( $brewery_feed_result['response']['checkins']['items'] as $untappd_checkin ) {
				/**
				 * Do not add checkins that not belong to any venue location and do not have lat long set.
				 */
				if ( isset( $untappd_checkin['venue']['location'] ) && ! empty( $untappd_checkin['venue']['location']['lat'] ) && ! empty( $untappd_checkin['venue']['location']['lng'] ) ) {

					/**
					 * If checkin contains disallowed words go to next.
					 */
					if ( $this->moderate_checkin(
						'disallowed',
						$untappd_checkin['user']['user_name'],
						$untappd_checkin['venue']['venue_name'],
						$untappd_checkin['user']['location'],
						$untappd_checkin['checkin_comment'],
						$untappd_checkin['beer']['beer_label'],
						$untappd_checkin['beer']['beer_name']
					) ) {
						continue;
					}

					$show_rating = ( ! $show_ratings_to_admin_only || $show_ratings_to_admin_only && current_user_can( 'edit_posts' ) ) ? true : false;

					/**
					 * If checkin contains moderated words mark as admin read only.
					 */
					if ( $this->moderate_checkin(
						'moderation',
						$untappd_checkin['user']['user_name'],
						$untappd_checkin['venue']['venue_name'],
						$untappd_checkin['user']['location'],
						$untappd_checkin['checkin_comment'],
						$untappd_checkin['beer']['beer_label'],
						$untappd_checkin['beer']['beer_name']
					) ) {
						$show_rating = ( current_user_can( 'edit_posts' ) ) ? true : false;
					}

					/**
					 * If product exists on WooCommerce store, add a link to it on infoWindow().
					 */
					$permalink = '';

					if ( $add_product_link ) {
						$args = array(
							'posts_per_page' => 1,
							'orderby'        => 'title',
							'order'          => 'asc',
							'post_type'      => 'product',
							'post_status'    => 'publish',
							'meta_query'     => array( // PHPCS:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
								'relation' => 'AND',
								array(
									'key'   => '_urwc_beer_id',
									'value' => absint( $untappd_checkin['beer']['bid'] ),
								),
							),
						);

						$products = get_posts( $args );

						if ( count( $products ) ) {
							$product_id = apply_filters( 'wpml_object_id', $products[0]->ID, 'product', true );
							$permalink  = get_permalink( $product_id );
						}
					}

					/**
					 * If configuration set to change Untappd at home coordinates.
					 */
					if ( 9917985 === absint( $untappd_checkin['venue']['venue_id'] ) ) {

						$at_home_coordinates = $this->get_home_coordinates();

						$untappd_checkin['venue']['location']['lat'] = (float) $at_home_coordinates['lat'];
						$untappd_checkin['venue']['location']['lng'] = (float) $at_home_coordinates['lng'];
					}

					/**
					 *  Show comments and ratings to admins or to all users.
					 */
					$untappd_checkin_comment = '';

					if ( $show_rating ) {
						$untappd_checkin_comment = ( ! empty( $untappd_checkin['checkin_comment'] ) ) ? $untappd_checkin['checkin_comment'] : '';
					} else {
						$untappd_checkin['rating_score'] = '';
					}

					$brewery_feed[ $untappd_checkin['venue']['venue_id'] ][ $untappd_checkin['checkin_id'] ] = array(
						'lat'            => (float) $untappd_checkin['venue']['location']['lat'],
						'lng'            => (float) $untappd_checkin['venue']['location']['lng'],
						'beer_name'      => sanitize_text_field( $untappd_checkin['beer']['beer_name'] ),
						'beer_label'     => sanitize_text_field( $untappd_checkin['beer']['beer_label'] ),
						'user_name'      => sanitize_text_field( $untappd_checkin['user']['user_name'] ),
						'comment'        => sanitize_textarea_field( $untappd_checkin_comment ),
						'permalink'      => $permalink,
						'location'       => ( ! empty( $untappd_checkin['user']['location'] ) ) ? sanitize_text_field( $untappd_checkin['user']['location'] ) : '',
						'venue_name'     => sanitize_text_field( $untappd_checkin['venue']['venue_name'] ),
						'foursquare_url' => ( filter_var( $untappd_checkin['venue']['foursquare']['foursquare_url'], FILTER_VALIDATE_URL ) ) ? $untappd_checkin['venue']['foursquare']['foursquare_url'] : '',
						'checkin_date'   => date_i18n( get_option( 'date_format' ), strtotime( $untappd_checkin['created_at'] ) ),
						'rating_score'   => ( ! empty( $untappd_checkin['rating_score'] ) ) ? number_format_i18n( $untappd_checkin['rating_score'], 2 ) : '',
					);
				}
			}
		}

		return $brewery_feed;
	}

	/**
	 * Checks if a comment contains disallowed or moderate characters or words.
	 *
	 * Read more on /wp-includes/comment.php wp_check_comment_disallowed_list
	 *
	 * This function is just a wrapper of WP one but we check both keys with one method.
	 *
	 * @since 1.0.5
	 *
	 * @param string $keys The keys to use, moderation or disallowed.
	 * @param string $user_name The author of the comment.
	 * @param string $venue_name The venue of the checkin.
	 * @param string $location The location used in the comment.
	 * @param string $comment The comment content.
	 * @param string $beer_label The label of the drink.
	 * @param string $beer_name The name of the drink.
	 *
	 * @return bool True if comment contains filtered content, false if comment does not.
	 */
	private function moderate_checkin( $keys, $user_name, $venue_name, $location, $comment, $beer_label, $beer_name ) {

		if ( false === in_array( $keys, array( 'moderation', 'disallowed' ), true ) ) {
			return false; // If keys are not valid.
		}

		/**
		 * Fires before the comment is tested for filtered characters or words.
		 *
		 * @since 5.5.0
		 *
		 * @param string $user_name     The author of the comment.
		 * @param string $venue_name    The venue of the checkin.
		 * @param string $location      The location used in the comment.
		 * @param string $comment       The comment content.
		 * @param string $beer_label    The label of the drink.
		 * @param string $beer_name     The name of the drink.
		 */
		do_action( 'urwc_ratings_checkin_' . $keys . '_list', $user_name, $venue_name, $location, $comment, $beer_label, $beer_name );

		$mod_keys = trim( get_option( $keys . '_keys' ) );

		if ( empty( $mod_keys ) ) {
			return false; // If moderation keys are empty.
		}

		// Ensure HTML tags are not being used to bypass the list of filtered characters and words.
		$comment_without_html = wp_strip_all_tags( $comment );

		$words = explode( "\n", $mod_keys );

		foreach ( (array) $words as $word ) {
			$word = trim( $word );

			// Skip empty lines.
			if ( empty( $word ) ) {
				continue;
			}

			// Do some escaping magic so that '#' chars in the spam words don't break things.
			$word = preg_quote( $word, '#' );

			$pattern = "#$word#iu";
			$matches = array();
			if (
				preg_match( $pattern, $user_name, $matches )
				|| preg_match( $pattern, $venue_name, $matches )
				|| preg_match( $pattern, $location, $matches )
				|| preg_match( $pattern, $comment, $matches )
				|| preg_match( $pattern, $comment_without_html, $matches )
				|| preg_match( $pattern, $beer_label, $matches )
				|| preg_match( $pattern, $beer_name, $matches )
			) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get maximum calls to make, 25 checkins per call, max 12.
	 *
	 * @param int $max_checkins Chekins to show.
	 */
	private function max_checkins( int $max_checkins = null ) {
		$max_checkins = ceil( ( $max_checkins ? $max_checkins : 300 ) / 25 );
		return ( $max_checkins > 12 ) ? 12 : $max_checkins;
	}

	/**
	 * Merge array recursively with new brewery feed results.
	 *
	 * @param array $array1 array to merge.
	 * @param array $array2 array to merge.
	 *
	 * @return array $merged The resulting array
	 */
	private function array_merge_recursive_distinct( array $array1, array $array2 ) {
		$merged = $array1;

		foreach ( $array2 as $key => &$value ) {
			if ( is_array( $value ) && isset( $merged[ $key ] ) && is_array( $merged[ $key ] ) ) {
				$merged[ $key ] = $this->array_merge_recursive_distinct( $merged[ $key ], $value );
			} else {
				$merged[ $key ] = $value;
			}
		}

		return $merged;
	}
}

new URWC_Brewery_Activity_Feed();
