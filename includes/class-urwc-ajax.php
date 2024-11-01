<?php
/**
 * URWC_AJAX
 *
 * @author    ChillCode
 * @copyright Copyright (c) 2024, ChillCode All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 * @package   Untappd Ratings for WooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * URWC_AJAX class.
 */
class URWC_AJAX {

	/**
	 * Constructor.
	 */
	public static function initialize() {
		/**
		 * Hook backend ajax requests.
		 */
		add_action( 'wp_ajax_urwc_brewery_search', array( __CLASS__, 'urwc_brewery_search' ) );
		add_action( 'wp_ajax_urwc_beer_search', array( __CLASS__, 'urwc_beer_search' ) );
		add_action( 'wp_ajax_urwc_beer_info', array( __CLASS__, 'urwc_beer_info' ) );
	}

	/**
	 * Beer info.
	 *
	 * @return void
	 */
	public static function urwc_beer_info() {
		check_ajax_referer( 'beer-info', 'security' );

		$beer_info_result = array(
			'error'     => false,
			'beer_name' => '',
		);

		if ( ! URWC_Ratings::api_is_active() ) {
			$beer_info_result['error'] = __( 'Configure Untappd API to start using it.', 'untappd-ratings-for-woocommerce' );

			wp_send_json( $beer_info_result );
		}

		$beer_id = filter_input( INPUT_GET, 'beer_id', FILTER_VALIDATE_INT );

		if ( ! $beer_id ) {
			$beer_info_result['error'] = __( 'The supplied beer ID is not valid.', 'untappd-ratings-for-woocommerce' );

			wp_send_json( $beer_info_result );
		}

		$beer_info = URWC_Ratings::API()->beer_info( $beer_id, true );

		if ( urwc_is_error( $beer_info ) ) {
			urwc_logger(
				wp_json_encode(
					array(
						'urwc_beer_info' => array(
							'beer_id' => $beer_id,
							'message' => $beer_info->getMessage(),
						),
					)
				),
				'warning'
			);

			switch ( $beer_info->getMessage() ) {
				case '_invalid_resource':
					$beer_info_result['error'] = sprintf( __( 'Beer with id %d does not exist.', 'untappd-ratings-for-woocommerce' ), $beer_id ); // phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
					break;
				default:
					$beer_info_result['error'] = sprintf( __( 'Error retrieving beer information with id %d.', 'untappd-ratings-for-woocommerce' ), $beer_id ); // phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
			}

			wp_send_json( $beer_info_result );
		}

		if ( ! empty( $beer_info['response']['beer']['beer_name'] ) ) {
			$beer_info_result['beer_name'] = sanitize_text_field( $beer_info['response']['beer']['beer_name'] . ' - ' . $beer_info['response']['beer']['brewery']['brewery_name'] . ' (#' . $beer_info['response']['beer']['bid'] . ')' );
		}

		wp_send_json( apply_filters( 'urwc_json_info_beer', $beer_info_result ) );
	}

	/**
	 * Enqueue scripts.
	 *
	 * @return void
	 */
	public static function urwc_beer_search() {
		check_ajax_referer( 'search-beer', 'security' );

		$beer_search_result = array(
			'error' => false,
			'items' => array(),
		);

		if ( ! URWC_Ratings::api_is_active() ) {
			$beer_search_result['error'] = _x( 'API not configured.', 'enhanced select', 'untappd-ratings-for-woocommerce' );

			wp_send_json( $beer_search_result );
		}

		$beer_search_term = filter_input( INPUT_GET, 'term', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$beer_search_term = sanitize_text_field( $beer_search_term );

		if ( is_numeric( $beer_search_term ) ) {
			wp_send_json(
				array( 'items' => array( $beer_search_term => $beer_search_term ) )
			);
		}

		if ( mb_strlen( $beer_search_term ) < 6 ) {
			$beer_search_result['error'] = _x( 'Please enter %qty% or more characters', 'enhanced select', 'untappd-ratings-for-woocommerce' );

			wp_send_json( $beer_search_result );
		}

		$beer_search = URWC_Ratings::API()->beer_search( $beer_search_term );

		if ( urwc_is_error( $beer_search ) ) {
			$beer_search_result['error'] = $beer_search->getMessage();

			wp_send_json( $beer_search_result );
		}

		if ( empty( $beer_search['response']['beers']['count'] ) ) {
			$beer_search_result['error'] = _x( 'No matches found', 'enhanced select', 'untappd-ratings-for-woocommerce' );

			wp_send_json( $beer_search_result );
		}

		foreach ( $beer_search['response']['beers']['items'] as $untappd_beer ) {
			$beer_search_result['items'][ $untappd_beer['beer']['bid'] ] = esc_attr( $untappd_beer['beer']['beer_name'] . ' - ' . $untappd_beer['brewery']['brewery_name'] . ' (#' . $untappd_beer['beer']['bid'] . ')' );
		}

		wp_send_json( apply_filters( 'urwc_json_search_found_beers', $beer_search_result ) );
	}

	/**
	 * Brewery search.
	 *
	 * @return void
	 */
	public static function urwc_brewery_search() {
		check_ajax_referer( 'brewery-search', 'security' );

		$brewery_search_result = array(
			'items' => array(),
			'error' => false,
		);

		if ( ! URWC_Ratings::api_is_active() ) {
			$brewery_search_result['error'] = _x( 'API not configured.', 'enhanced select', 'untappd-ratings-for-woocommerce' );

			wp_send_json( $brewery_search_result );
		}

		/**
		 * Filter the search term.
		 */
		$urwc_search_term = filter_input( INPUT_GET, 'term', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$urwc_search_term = sanitize_text_field( $urwc_search_term );

		if ( mb_strlen( $urwc_search_term ) < 3 ) {
			$brewery_search_result['error'] = _x( 'Please enter %qty% or more characters', 'enhanced select', 'untappd-ratings-for-woocommerce' );

			wp_send_json( $brewery_search_result );
		}

		$brewery_search = URWC_Ratings::API()->brewery_search( $urwc_search_term );

		if ( urwc_is_error( $brewery_search ) ) {
			$brewery_search_result['error'] = $brewery_search->getMessage();

			wp_send_json( $brewery_search_result );
		}

		if ( empty( $brewery_search['response']['brewery']['count'] ) ) {
			$brewery_search_result['error'] = _x( 'No matches found', 'enhanced select', 'untappd-ratings-for-woocommerce' );

			wp_send_json( $brewery_search_result );
		}

		foreach ( $brewery_search['response']['brewery']['items'] as $untappd_brewery ) {
			$brewery_search_result['items'][ $untappd_brewery['brewery']['brewery_id'] ] = array(
				'text'             => esc_attr( '(#' . $untappd_brewery['brewery']['brewery_id'] . ') - ' . $untappd_brewery['brewery']['brewery_name'] ),
				'brewery_id'       => esc_attr( $untappd_brewery['brewery']['brewery_id'] ),
				'beer_count'       => esc_attr( $untappd_brewery['brewery']['beer_count'] ),
				'brewery_name'     => esc_attr( $untappd_brewery['brewery']['brewery_name'] ),
				'brewery_slug'     => esc_attr( $untappd_brewery['brewery']['brewery_slug'] ),
				'brewery_page_url' => esc_attr( $untappd_brewery['brewery']['brewery_page_url'] ),
				'brewery_label'    => esc_attr( $untappd_brewery['brewery']['brewery_label'] ),
				'country_name'     => esc_attr( $untappd_brewery['brewery']['country_name'] ),
			);
		}

		wp_send_json( apply_filters( 'urwc_json_search_found_breweries', $brewery_search_result ) );
	}
}

URWC_AJAX::initialize();
