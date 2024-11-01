<?php
/**
 * URWC_Settings
 *
 * @author    ChillCode
 * @copyright Copyright (c) 2024, ChillCode All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 * @package   Untappd Ratings for WooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * URWC_Settings class.
 */
class URWC_Settings {

	/**
	 * Minimum chacters required to search over the Untappd API. Defaul 3.
	 *
	 * @var int
	 */
	public static $urwc_settings_min_search_brewery_chacters = 3;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'woocommerce_settings_tabs_array', array( __CLASS__, 'woocommerce_settings_tabs_array' ), 100 );

		add_action( 'woocommerce_settings_tabs_untappd_settings', array( __CLASS__, 'woocommerce_settings_tabs_untappd_settings' ) );
		add_action( 'woocommerce_update_options_untappd_settings', array( __CLASS__, 'woocommerce_update_options_untappd_settings' ) );

		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );
	}

	/**
	 * Add tab to WooCommerce options tabs.
	 *
	 * @param array $settings_tabs WooCommerce options tabs passed by filter woocommerce_settings_tabs_array.
	 */
	public static function woocommerce_settings_tabs_array( $settings_tabs ) {
		$settings_tabs['untappd_settings'] = esc_html__( 'Untappd', 'untappd-ratings-for-woocommerce' );

		return $settings_tabs;
	}

	/**
	 * Output Untappd related settings to Untappd options tab.
	 */
	public static function woocommerce_settings_tabs_untappd_settings() {
		woocommerce_admin_fields( self::get_settings() );
	}

	/**
	 * Update Untappd related settings.
	 */
	public static function woocommerce_update_options_untappd_settings() {
		woocommerce_update_options( self::get_settings() );

		if ( URWC_Ratings::api_is_active() && urwc_ratings_enabled() && urwc_ratings_sort_enabled() ) {
			URWC_Product::add_meta_to_all_products();
		}

		URWC_Ratings::delete_cache();
	}

	/**
	 * Untappd related settings.
	 */
	private static function get_settings() {
		$ratelimit_remaining = absint( get_option( 'urwc_ratelimit_remaining', true ) );

		$settings[] = array(
			'title' => __( 'Untappd API', 'untappd-ratings-for-woocommerce' ),
			'type'  => 'title',
			/* translators: %s: API ratelimit remaining */
			'desc'  => sprintf( __( 'Rate limit remaining per next hour: %s calls', 'untappd-ratings-for-woocommerce' ), $ratelimit_remaining ),
			'id'    => 'urwc_untappd_api_settings',
		);

		$settings[] = array(
			'title'    => __( 'Untappd API Client ID', 'untappd-ratings-for-woocommerce' ),
			'desc'     => __( 'Untappd API Client ID required to connect to Untappd API. Ask for it.', 'untappd-ratings-for-woocommerce' ),
			'id'       => 'urwc_client_id',
			'default'  => '',
			'type'     => 'password',
			'desc_tip' => true,
			'css'      => 'width:340px;',
		);

		$settings[] = array(
			'title'    => __( 'Untappd API Client Secret', 'untappd-ratings-for-woocommerce' ),
			'desc'     => __( 'Untappd API Client Secret required to connect to Untappd API', 'untappd-ratings-for-woocommerce' ),
			'id'       => 'urwc_client_secret',
			'default'  => '',
			'type'     => 'password',
			'desc_tip' => true,
			'css'      => 'width:340px;',
		);

		$settings[] = array(
			'title'    => __( 'API Url', 'untappd-ratings-for-woocommerce' ),
			'desc'     => __( 'API server address', 'untappd-ratings-for-woocommerce' ),
			'id'       => 'urwc_api_url',
			'default'  => 'https://api.untappd.com/v4/',
			'type'     => 'text',
			'desc_tip' => true,
			'css'      => 'width:240px;',
		);

		$settings[] = array(
			'title'    => __( 'APP Name', 'untappd-ratings-for-woocommerce' ),
			'desc'     => __( 'Used to identify the application on the server', 'untappd-ratings-for-woocommerce' ),
			'id'       => 'urwc_api_useragent',
			'default'  => 'Untappd Ratings for WooCommerce - Ver. ' . URWC_VERSION,
			'type'     => 'text',
			'desc_tip' => true,
			'css'      => 'width:240px;',
		);

		$settings[] = array(
			'title'    => __( 'Cache time', 'untappd-ratings-for-woocommerce' ),
			'desc'     => __( 'Time the API query is cached', 'untappd-ratings-for-woocommerce' ),
			'id'       => 'urwc_cache_time',
			'default'  => '3',
			'type'     => 'text',
			'desc_tip' => true,
			'css'      => 'width:140px;',
		);

		$settings[] = array(
			'title'    => __( 'Show "Powered by Untappd" logo', 'untappd-ratings-for-woocommerce' ),
			'desc'     => __( 'Show "Powered by Untappd" logo at Storefront credit links', 'untappd-ratings-for-woocommerce' ),
			'id'       => 'urwc_show_logo',
			'default'  => 'no',
			'type'     => 'checkbox',
			'desc_tip' => true,
			'css'      => 'width:140px;',
		);

		$settings[] = array(
			'type' => 'sectionend',
			'id'   => 'urwc_untappd_api_settings',
		);

		$settings[] = array(
			'title' => __( 'Untappd ratings', 'untappd-ratings-for-woocommerce' ),
			'type'  => 'title',
			'desc'  => 'Config how ratings are shown.',
			'id'    => 'urwc_untappd_rating_settings',
		);

		$settings[] = array(
			'title'    => __( 'Use Untappd ratings', 'untappd-ratings-for-woocommerce' ),
			'desc'     => __( 'Overwrite WooCommerce ratings with Untappd one\'s.', 'untappd-ratings-for-woocommerce' ),
			'id'       => 'urwc_ratings_enabled',
			'default'  => 'no',
			'type'     => 'checkbox',
			'desc_tip' => true,
			'css'      => 'width:140px;',
		);

		$settings[] = array(
			'title'    => __( 'Sort using Untappd Ratings', 'untappd-ratings-for-woocommerce' ),
			'desc'     => __( 'Enable sorting on loop-page by Untappd ratings. To enable sorting by ratings, URWC will add post meta data to all products.', 'untappd-ratings-for-woocommerce' ),
			'id'       => 'urwc_ratings_sort_enabled',
			'default'  => 'no',
			'type'     => 'checkbox',
			'desc_tip' => true,
			'css'      => 'width:140px;',
		);

		$settings[] = array(
			'title'    => __( 'Display ratings text', 'untappd-ratings-for-woocommerce' ),
			'desc'     => __( 'Display ratings in text format x/5', 'untappd-ratings-for-woocommerce' ),
			'id'       => 'urwc_ratings_show_text',
			'default'  => 'no',
			'type'     => 'checkbox',
			'desc_tip' => true,
			'css'      => 'width:140px;',
		);

		$settings[] = array(
			'title'    => __( 'Display total ratings', 'untappd-ratings-for-woocommerce' ),
			'desc'     => __( 'Display a link to Untappd with total ratings.', 'untappd-ratings-for-woocommerce' ),
			'id'       => 'urwc_ratings_show_total',
			'default'  => 'no',
			'type'     => 'checkbox',
			'desc_tip' => true,
			'css'      => 'width:140px;',
		);

		$settings[] = array(
			'title'    => __( 'Structured data', 'untappd-ratings-for-woocommerce' ),
			'desc'     => __( 'Add rating data to structured data to display it on search engines (Google, Bing etc...)', 'untappd-ratings-for-woocommerce' ),
			'id'       => 'urwc_ratings_add_to_structured_data',
			'default'  => 'no',
			'type'     => 'checkbox',
			'desc_tip' => true,
			'css'      => 'width:140px;',
		);

		$settings[] = array(
			'title'    => __( 'Structured data comments only', 'untappd-ratings-for-woocommerce' ),
			'desc'     => __( 'Add rating data to structured data when checkin comment is present. If not checked a comment is added to review.', 'untappd-ratings-for-woocommerce' ),
			'id'       => 'urwc_ratings_structured_data_comments_only',
			'default'  => 'yes',
			'type'     => 'checkbox',
			'desc_tip' => true,
			'css'      => 'width:140px;',
		);

		$settings[] = array(
			'type' => 'sectionend',
			'id'   => 'urwc_untappd_rating_settings',
		);

		$settings[] = array(
			'title' => __( 'Untappd map', 'untappd-ratings-for-woocommerce' ),
			'type'  => 'title',
			'desc'  => 'Config how ratings are shown',
			'id'    => 'urwc_untappd_map_settings',
		);

		$settings[] = array(
			'id'          => 'urwc_map_cache_is_working',
			'title'       => __( 'Cache status', 'untappd-ratings-for-woocommerce' ),
			'type'        => 'radio',
			'options'     => array(
				'no'  => __( 'Cache disabled to prevent infinite calls to the Untappd API. This setting disables the Untappd map feed.', 'untappd-ratings-for-woocommerce' ),
				'yes' => __( 'Cache is enabled.', 'untappd-ratings-for-woocommerce' ),
			),
			'value'       => get_option( 'urwc_map_cache_is_working' ),
			'desc'        => __( 'This setting is automatically set to disabled cache when URWC failed to store Untappd data. When disabled, Untapdd Map Feed will not load data.', 'untappd-ratings-for-woocommerce' ),
			'desc_at_end' => true,
		);

		$settings[] = array(
			'title'    => __( 'Add product link', 'untappd-ratings-for-woocommerce' ),
			'desc'     => __( 'If Untappd reviewed beverage exists on Woo products show a link to it on the InfoWindow.', 'untappd-ratings-for-woocommerce' ),
			'id'       => 'urwc_map_add_product_link',
			'default'  => 'yes',
			'type'     => 'checkbox',
			'desc_tip' => true,
			'css'      => 'width:140px;',
		);

		$settings[] = array(
			'title'    => __( 'Show ratings/reviews only to WP editors.', 'untappd-ratings-for-woocommerce' ),
			'desc'     => __( 'Only show ratings and reviews to WP editors on InfoWindows.', 'untappd-ratings-for-woocommerce' ),
			'id'       => 'urwc_map_show_ratings_to_admin_only',
			'default'  => 'no',
			'type'     => 'checkbox',
			'desc_tip' => true,
			'css'      => 'width:140px;',
		);

		$settings[] = array(
			'title'    => __( 'Apply disallowed and moderation words filters to Untappd data.', 'untappd-ratings-for-woocommerce' ),
			'desc'     => __( 'Checkins with WP disallowed words will not be shown and checkins with moderated words will only be shown to WP editors.', 'untappd-ratings-for-woocommerce' ),
			'id'       => 'urwc_map_show_ratings_to_admin_only',
			'default'  => 'no',
			'type'     => 'checkbox',
			'desc_tip' => true,
			'css'      => 'width:140px;',
		);

		$settings[] = array(
			'title'    => __( 'Show disclaimer on infoWindows marker.', 'untappd-ratings-for-woocommerce' ),
			'desc'     => __( 'Show a disclaimer linking to Untappd Guidelines at the footer of the infoWindow.', 'untappd-ratings-for-woocommerce' ),
			'id'       => 'urwc_map_show_infowindow_disclaimer',
			'default'  => 'no',
			'type'     => 'checkbox',
			'desc_tip' => true,
			'css'      => 'width:140px;',
		);

		$settings[] = array(
			'title'             => __( 'Untappd brewery search', 'untappd-ratings-for-woocommerce' ),
			'desc'              => __( 'Search to find your brewery ID. Click on the selector to copy the ID to clipboard.', 'untappd-ratings-for-woocommerce' ),
			'id'                => 'urwc_map_urwc_brewery_id',
			'class'             => 'urwc-brewery-search',
			'type'              => 'select',
			'options'           => array(),
			'desc_tip'          => true,
			'css'               => 'width:300px;',
			'custom_attributes' => array(
				'data-minimum-input-length' => 3,
				'data-action'               => 'urwc_brewery_search',
				'data-allow-clear'          => 'true',
				'data-placeholder'          => __( 'Search for a Brewery&hellip;', 'untappd-ratings-for-woocommerce' ),
			),
		);

		$settings[] = array(
			'type' => 'sectionend',
			'id'   => 'urwc_untappd_map_settings',
		);

		return $settings;
	}

	/**
	 * Enqueue settings script.
	 *
	 * @return void
	 */
	public static function admin_enqueue_scripts() {
		wp_enqueue_style( 'urwc-settings', plugins_url( 'assets/css/urwc-settings' . wp_scripts_get_suffix() . '.css', URWC_PLUGIN_FILE ), array(), URWC_VERSION );
		wp_enqueue_script( 'urwc-settings', plugins_url( 'assets/js/urwc-settings' . wp_scripts_get_suffix() . '.js', URWC_PLUGIN_FILE ), array( 'jquery' ), URWC_VERSION, true );
	}
}

new URWC_Settings();
