<?php
/**
 * URWC_Product
 *
 * @author    ChillCode
 * @copyright Copyright (c) 2024, ChillCode All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 * @package   Untappd Ratings for WooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * URWC_Product class.
 */
class URWC_Product {

	/**
	 * Constructor.
	 */
	public function __construct() {
		/**
		 * Add only if URWC is enabled to show ratings.
		 */
		if ( urwc_ratings_enabled() ) {
			/**
			 * Add product meta, panels and tabs filters only on admin pages.
			 */
			if ( is_admin() ) {
				add_filter( 'woocommerce_product_data_tabs', array( __CLASS__, 'woocommerce_product_data_tabs' ) );
				add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'woocommerce_process_product_meta' ), 1 );
				add_action( 'woocommerce_product_data_panels', array( __CLASS__, 'woocommerce_product_data_panels' ) );
			}

			/**
			 * Add sorting by Untappd ratings capabilities only if enabled.
			 */
			if ( urwc_ratings_sort_enabled() ) {
				add_filter( 'woocommerce_get_catalog_ordering_args', array( __CLASS__, 'woocommerce_get_catalog_ordering_args' ), 998 );
				add_filter( 'posts_clauses', array( __CLASS__, 'posts_clauses' ), 999, 2 );
			}

			/**
			 * Add beer info to structured data on product pages only if enabled.
			 */
			if ( urwc_structured_data_enabled() ) {
				add_filter( 'woocommerce_structured_data_context', array( __CLASS__, 'woocommerce_structured_data_context' ), 10, 4 );
			}
		}
	}

	/**
	 * Check product meta data panels nonce.
	 *
	 * @return bool True on success, false otherwise.
	 */
	private static function urw_product_data_panels_check_permissions() {
		$urwc_product_data_panels_nonce = filter_input( INPUT_POST, 'urwc_product_data_panels_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		if ( ! $urwc_product_data_panels_nonce || ! wp_verify_nonce( wc_clean( $urwc_product_data_panels_nonce ), 'urwc_product_data_panels_nonce' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Returns an array of arguments for ordering products based on Untappd ratings.
	 *
	 * @param array    $posts_clauses An array of arguments for ordering products based on the selected values.
	 * @param WP_Query $wp_query WP_Query object.
	 * @return mixed
	 */
	public static function posts_clauses( $posts_clauses, $wp_query ) {
		$orderby = $wp_query->get( 'orderby' );

		switch ( $orderby ) {
			case '_urwc_average_rating':
				$order                    = esc_sql( $wp_query->get( 'order', 'desc' ) );
				$posts_clauses['orderby'] = " wp_postmeta.meta_value+0 {$order}, wp_posts.ID {$order} ";
				break;
		}

		return $posts_clauses;
	}

	/**
	 * Returns an array of arguments for ordering products based on Untappd ratings.
	 *
	 * This method requires a post_meta named _urwc_average_rating in all products to prevent empty results on WC search.
	 *
	 * @param array $args An array of arguments for ordering products based on the selected values.
	 * @return mixed
	 */
	public static function woocommerce_get_catalog_ordering_args( $args ) {
		if ( isset( $args['orderby'] ) && 'rating' === $args['orderby'] ) {
			$args['order']    = 'DESC';
			$args['orderby']  = '_urwc_average_rating';
			$args['meta_key'] = '_urwc_average_rating'; //phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		}

		return $args;
	}

	/**
	 * Add Untappd tab to product page tabs.
	 *
	 * @param array $tabs Tabs passed by WC filter.
	 */
	public static function woocommerce_product_data_tabs( $tabs ) {
		$tabs['untappd_ratings_for_woocommerce'] = array(
			'label'    => esc_html__( 'Untappd', 'untappd-ratings-for-woocommerce' ),
			'target'   => 'urwc_product_data_panels',
			'class'    => array( '' ),
			'priority' => 90,
		);

		return $tabs;
	}

	/**
	 * Add fields to Untappd product meta tab.
	 *
	 * @return void
	 */
	public static function woocommerce_product_data_panels() {
		global $post;

		echo "<div id='urwc_product_data_panels' class='panel woocommerce_options_panel'>";

		wp_nonce_field( 'urwc_product_data_panels_nonce', 'urwc_product_data_panels_nonce' );

		echo '<div class="options_group">';

		$options = array();

		$urwc_beer_id   = get_post_meta( $post->ID, '_urwc_beer_id', true );
		$urwc_beer_name = get_post_meta( $post->ID, '_urwc_beer_name', true );

		if ( $urwc_beer_id && $urwc_beer_name ) {
			$options[ $urwc_beer_id ] = $urwc_beer_name;
		}

		woocommerce_wp_select(
			array(
				'id'                => '_urwc_beer_id',
				'name'              => '_urwc_beer_id',
				'class'             => 'urwc-beer-search',
				'style'             => 'width: 50%;',
				'options'           => $options,
				'label'             => __( 'Untappd beer search', 'untappd-ratings-for-woocommerce' ),
				'placeholder'       => '',
				'desc_tip'          => true,
				'description'       => __( 'Enter a beer id or a search term to find the beer. Use brewery name and beer name to prevent too many calls to Untappd API.', 'untappd-ratings-for-woocommerce' ),
				'custom_attributes' => array(
					'data-minimum-input-length' => 6,
					'data-action'               => 'urwc_beer_search',
					'data-allow-clear'          => 'true',
					'data-placeholder'          => __( 'Search for a beer&hellip;', 'untappd-ratings-for-woocommerce' ),
				),
			)
		);

		echo '</div>';
		echo '</div>';
	}

	/**
	 * Update Product Untappd ID.
	 *
	 * @param int $post_id post_id of the product to save meta to. Passed by WC filter.
	 */
	public static function woocommerce_process_product_meta( $post_id ) {
		/** Those checks are previously done by save_meta_boxes, so can be removed with confidence.*/
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE || ! self::urw_product_data_panels_check_permissions() || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Check if ratings are enabled.
		if ( ! urwc_ratings_enabled() ) {
			return;
		}

		$product_meta_beer_id   = filter_input( INPUT_POST, '_urwc_beer_id', FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 1 ) ) );
		$product_meta_beer_name = '';
		$product_meta_beer_slug = '';

		/**
		 * If beer id is not updated ignore the process.
		 */
		if ( $product_meta_beer_id && get_post_meta( $post_id, '_urwc_beer_id', true ) === $product_meta_beer_id ) {
			return;
		}

		if ( $product_meta_beer_id ) {
			$product_meta_beer_info = URWC_Ratings::API()->beer_info( $product_meta_beer_id );

			if ( urwc_is_error( $product_meta_beer_info ) ) {
				urwc_logger(
					wp_json_encode(
						array(
							'urwc_beer_info' => array(
								'beer_id' => $product_meta_beer_id,
								'message' => $product_meta_beer_info->getMessage(),
							),
						)
					),
					'warning'
				);
			} elseif ( ! empty( $product_meta_beer_info['response']['beer']['beer_name'] ) ) {
				$product_meta_beer_name = sanitize_text_field( $product_meta_beer_info['response']['beer']['beer_name'] . ' - ' . $product_meta_beer_info['response']['beer']['brewery']['brewery_name'] . ' (#' . $product_meta_beer_info['response']['beer']['bid'] . ')' );
				$product_meta_beer_slug = sanitize_text_field( $product_meta_beer_info['response']['beer']['beer_slug'] );

				update_post_meta( $post_id, '_urwc_last_updated', time() );
			}

			$product_meta_beer_id = ( ! empty( $product_meta_beer_name ) ) ? $product_meta_beer_id : 0;
		}

		update_post_meta( $post_id, '_urwc_beer_id', absint( $product_meta_beer_id ) );
		update_post_meta( $post_id, '_urwc_beer_name', $product_meta_beer_name );
		update_post_meta( $post_id, '_urwc_beer_slug', $product_meta_beer_slug );

		if ( urwc_ratings_sort_enabled() ) {
			/**
			 * Add post meta to every product to allow sort by rating.
			 */
			add_post_meta( $post_id, '_urwc_rating_count', 0, true );
			add_post_meta( $post_id, '_urwc_average_rating', 0, true );
		}
	}

	/**
	 * Add Untappd beer data to structured data.
	 *
	 * @param array  $context Context array.
	 * @param array  $data Structured data.
	 * @param string $type Structured data type.
	 * @param mixed  $value Structured data value.
	 */
	public static function woocommerce_structured_data_context( $context, $data, $type, $value ) { //phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		// If API is not active or type is not product do nothing.
		if ( ! URWC_Ratings::api_is_active() || 'product' !== $type ) {
			return $context;
		}

		/**
		 * WC_Product
		 *
		 * @var WC_Product $product
		 */
		global $product;

		$context_beer_id = absint( $product->get_meta( '_urwc_beer_id', true ) );

		// If beer_id is not preesnet or valid do nothing.
		if ( 0 === $context_beer_id ) {
			return $context;
		}

		// Get cached data.
		$context_cache_key = 'urwc_structured_data_context_' . $context_beer_id;

		$context_beer_cached_context = get_transient( $context_cache_key );

		if ( false !== $context_beer_cached_context ) {
			return $context_beer_cached_context;
		}

		$context_beer_info = URWC_Ratings::API()->beer_info( $context_beer_id );

		if ( urwc_is_error( $context_beer_info ) ) {
			urwc_logger(
				wp_json_encode(
					array(
						'woocommerce_structured_data_context' => array(
							'beer_id' => $context_beer_id,
							'message' => $context_beer_info->getMessage(),
						),
					)
				),
				'warning'
			);
			return $context;
		}

		// Set AggregateRating.
		if ( isset( $context_beer_info['response']['beer']['rating_count'] ) && isset( $context_beer_info['response']['beer']['rating_score'] ) ) {
			$context_rating_value = round( (float) $context_beer_info['response']['beer']['rating_score'], 2 );
			$context_review_count = absint( $context_beer_info['response']['beer']['rating_count'] );

			if ( $context_rating_value > 0 && $context_review_count > 0 ) {
				$context['aggregateRating']['@type']       = 'AggregateRating';
				$context['aggregateRating']['ratingValue'] = esc_attr( $context_rating_value );
				$context['aggregateRating']['reviewCount'] = esc_attr( $context_review_count );
			}
		}

		// Set Brand.
		$context['brand']['@type'] = 'Brand';
		$context['brand']['name']  = (string) isset( $context_beer_info['response']['beer']['brewery']['brewery_name'] ) ? esc_attr( $context_beer_info['response']['beer']['brewery']['brewery_name'] ) : esc_attr( get_bloginfo( 'name' ) );

		// Set MPM.
		$context_mpn = trim( $product->get_sku() );

		if ( empty( $context['MPN'] ) ) {
			$context_mpn = 'URWC' . $context_beer_id;
		}

		$context['MPN'] = esc_attr( $context_mpn );

		// Set reviews if any.
		if ( ! empty( $context_beer_info['response']['beer']['checkins'] ) ) {
			$context_reviews = $context_beer_info['response']['beer']['checkins'];

			if ( (int) $context_reviews['count'] > 1 ) {
				foreach ( $context_reviews['items'] as $context_review ) {

					// If comment is empty do not add review.
					if ( urwc_structured_data_comments_only() && empty( $context_review['checkin_comment'] ) ) {
						continue;
					}

					// If no rating_score do not add Review.
					$review_rating_score = filter_var( $context_review['rating_score'], FILTER_VALIDATE_FLOAT );

					if ( ! $review_rating_score ) {
						continue;
					}

					// Set Review reviewRating.
					$review_rating_score = esc_attr( round( (float) $review_rating_score, 2 ) );

					// Set Review author.
					$review_author = trim( $context_review['user']['first_name'] );
					$review_author = ( $review_author ) ? esc_attr( $review_author ) : esc_html__( 'Unknown Author', 'untappd-ratings-for-woocommerce' );

					// Set Review description.
					$review_description = trim( $context_review['checkin_comment'] );
					$review_description = ( $review_description ) ? esc_html( $review_description ) : esc_html__( 'Untappd Rating', 'untappd-ratings-for-woocommerce' );

					// Set datePublished.
					$review_date = esc_attr( gmdate( 'Y-m-d', strtotime( $context_review['created_at'] ) ) );

					// Set name.
					$review_name = esc_attr( $context_review['beer']['beer_name'] );

					// Set review data.
					$context['review'][] = array(
						'@type'         => 'Review',
						'author'        => array(
							'@type' => 'person',
							'name'  => $review_author,
						),
						'datePublished' => $review_date,
						'description'   => $review_description,
						'name'          => $review_name,
						'reviewRating'  => array(
							'@type'       => 'Rating',
							'bestRating'  => '5',
							'ratingValue' => $review_rating_score,
							'worstRating' => '1',
						),
					);
				}
			}
		}

		// Store $context on a transient to speed up process and prevent API calls.
		set_transient( $context_cache_key, $context, URWC_Ratings::API()->get_cache_time() );

		return $context;
	}

	/**
	 * Extends product meta data using Untappd fields.
	 *
	 * @param int $beverage_id (Required) The drink ID.
	 * @param int $product_id (Required) The product ID.
	 * @return array|bool
	 */
	public static function get_product_meta( int $beverage_id, int $product_id ) {
		if ( $beverage_id <= 0 || $product_id <= 0 || ! URWC_Ratings::api_is_active() ) {
			return false;
		}

		$cache_key = 'urwc_product_update_meta_' . hash( 'md5', 'beer/info/' . $beverage_id . '/' . $product_id . '/' );

		$product_meta_data = get_transient( $cache_key );

		if ( false !== $product_meta_data ) {
			return $product_meta_data;
		}

		$product_meta_data = array(
			'_urwc_average_rating' => get_post_meta( $product_id, '_urwc_average_rating', true ),
			'_urwc_beer_slug'      => get_post_meta( $product_id, '_urwc_beer_slug', true ),
			'_urwc_last_updated'   => get_post_meta( $product_id, '_urwc_last_updated', true ),
			'_urwc_rating_count'   => get_post_meta( $product_id, '_urwc_rating_count', true ),
			'_urwc_updated'        => false,
		);

		$beer_info = URWC_Ratings::API()->beer_info( $beverage_id );

		if ( urwc_is_error( $beer_info ) ) {
			urwc_logger(
				wp_json_encode(
					array(
						'get_product_meta' => array(
							'beverage_id' => $beverage_id,
							'product_id'  => $product_id,
							'message'     => $beer_info->getMessage(),
						),
					)
				),
				'warning'
			);
		} elseif ( isset( $beer_info['response']['beer'] ) ) {

			$product_meta_data = array(
				'_urwc_average_rating' => ( isset( $beer_info['response']['beer']['rating_score'] ) ) ? round( (float) $beer_info['response']['beer']['rating_score'], 2 ) : 0,
				'_urwc_beer_slug'      => ( isset( $beer_info['response']['beer']['beer_slug'] ) ) ? sanitize_text_field( wp_unslash( $beer_info['response']['beer']['beer_slug'] ) ) : '',
				'_urwc_last_updated'   => time(),
				'_urwc_rating_count'   => ( isset( $beer_info['response']['beer']['rating_count'] ) ) ? absint( $beer_info['response']['beer']['rating_count'] ) : 0,
				'_urwc_updated'        => true,
			);

			update_post_meta( $product_id, '_urwc_rating_count', $product_meta_data['_urwc_rating_count'] );
			update_post_meta( $product_id, '_urwc_average_rating', $product_meta_data['_urwc_average_rating'] );
			update_post_meta( $product_id, '_urwc_beer_slug', $product_meta_data['_urwc_beer_slug'] );
			update_post_meta( $product_id, '_urwc_last_updated', $product_meta_data['_urwc_last_updated'] );

			set_transient( $cache_key, $product_meta_data, URWC_Ratings::API()->get_cache_time() );
		}

		return $product_meta_data;
	}

	/**
	 * Add postmeta data to all products to allow WooCommerce to sort by Untappd ratings.
	 *
	 * TODO: Create a sync job.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function add_meta_to_all_products() {

		$all_product_ids = get_posts(
			array(
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'post_type'      => 'product',
			)
		);

		$urwc_add_product_meta = true;

		$urw_urwc_last_updated = time();

		foreach ( $all_product_ids as $product_id ) {
			$urwc_add_product_meta &= add_post_meta( $product_id, '_urwc_beer_id', 0, true );
			$urwc_add_product_meta &= add_post_meta( $product_id, '_urwc_beer_slug', '', true );
			$urwc_add_product_meta &= add_post_meta( $product_id, '_urwc_rating_count', 0, true );
			$urwc_add_product_meta &= add_post_meta( $product_id, '_urwc_average_rating', 0.0, true );
			$urwc_add_product_meta &= add_post_meta( $product_id, '_urwc_last_updated', $urw_urwc_last_updated, true );
		}

		return $urwc_add_product_meta;
	}
}

new URWC_Product();
