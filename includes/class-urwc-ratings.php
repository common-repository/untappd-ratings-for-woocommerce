<?php
/**
 * URWC_Ratings
 *
 * @author    ChillCode
 * @copyright Copyright (c) 2024, ChillCode All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 * @package   Untappd Ratings for WooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * URWC_Ratings class.
 */
final class URWC_Ratings {

	/**
	 * The single instance of the class.
	 *
	 * @var URWC_Ratings
	 */
	protected static $urwc_instance = null;

	/**
	 * The instance of the API class.
	 *
	 * @var mixed
	 */
	protected static $urwc_api_instance = null;

	/**
	 * Initialize Untappd for WooCommerce.
	 *
	 * @return void
	 */
	public function __construct() {

		add_action( 'init', array( $this, 'init' ), -1 );

		$woocommerce_plugin_path = trailingslashit( WP_PLUGIN_DIR ) . 'woocommerce/woocommerce.php';

		if ( in_array( $woocommerce_plugin_path, (array) wp_get_active_and_valid_plugins(), true ) || is_multisite() && in_array( $woocommerce_plugin_path, wp_get_active_network_plugins(), true ) ) {
			if ( is_multisite() ) {
				add_filter( 'network_admin_plugin_action_links_untappd-ratings-for-woocommerce/untappd-ratings-for-woocommerce.php', array( $this, 'plugin_action_links_woocommerce' ) );
			}

			add_filter( 'plugin_action_links_untappd-ratings-for-woocommerce/untappd-ratings-for-woocommerce.php', array( $this, 'plugin_action_links_woocommerce' ) );

			add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
			add_action( 'current_screen', array( $this, 'current_screen' ) );
		} else {
			add_action(
				'admin_notices',
				function () {
					global $pagenow;

					if ( 'plugins.php' === $pagenow ) {
						printf( '<div class="%1$s"><p>%2$s</p></div>', 'notice notice-error is-dismissible', esc_html__( 'Untappd Ratings for WooCommerce requires WooCommerce to be installed and active.', 'untappd-ratings-for-woocommerce' ) );
					}
				}
			);
		}
	}

	/**
	 * Append links to plugin info.
	 *
	 * @param array $actions Actions Array.
	 *
	 * @return array
	 */
	public function plugin_action_links_woocommerce( array $actions ) {

		return array_merge(
			array(
				'settings' => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=untappd_settings' ) . '">' . esc_html__( 'Settings', 'untappd-ratings-for-woocommerce' ) . '</a>',
			),
			$actions
		);
	}

	/**
	 * Initialize plugin.
	 *
	 * @return void
	 */
	public function init() {
		if ( function_exists( 'load_plugin_textdomain' ) ) {
			load_plugin_textdomain( 'untappd-ratings-for-woocommerce', false, dirname( plugin_basename( URWC_PLUGIN_FILE ) ) . '/i18n/languages/' );
		}
	}

	/**
	 * Enqueue storefront style.
	 *
	 * @return void
	 */
	public function enqueue_storefront_styles() {
		wp_enqueue_style( 'urwc-front', plugins_url( 'assets/css/urwc-storefront' . wp_scripts_get_suffix() . '.css', URWC_PLUGIN_FILE ), array(), URWC_VERSION );
	}

	/**
	 * Manage current screen.
	 *
	 * @return void
	 */
	public function current_screen() {
		$current_screen = get_current_screen();

		if ( ! $current_screen ) {
			return;
		}

		/**
		 * Add only on product page.
		 */
		if ( in_array( $current_screen->id, array( 'product' ), true ) || 'woocommerce_page_wc-settings' === $current_screen->id ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		}
	}

	/**
	 * Enqueue scripts.
	 *
	 * @return void
	 */
	public function enqueue_admin_scripts() {
		// Enqueue selectwoo modified to allow Untappd related searches.
		wp_enqueue_script( 'urwc-selectwoo', plugins_url( 'assets/js/urwc-selectwoo' . wp_scripts_get_suffix() . '.js', URWC_PLUGIN_FILE ), array( 'jquery', 'selectWoo' ), URWC_VERSION, true );
		wp_localize_script(
			'urwc-selectwoo',
			'urwc_enhanced_select_params',
			array(
				'ajax_url'                       => admin_url( 'admin-ajax.php' ),
				'search_beers_nonce'             => wp_create_nonce( 'search-beer' ),
				'brewery_search_nonce'           => wp_create_nonce( 'brewery-search' ),
				'beer_info_nonce'                => wp_create_nonce( 'beer-info' ),
				'i18n_copied'                    => _x( 'Copied to clipboard', 'enhanced select', 'untappd-ratings-for-woocommerce' ),
				'i18n_input_integer_too_short_1' => _x( 'Please enter 1 or more numbers', 'enhanced select', 'untappd-ratings-for-woocommerce' ),
				'i18n_input_integer_too_short_n' => _x( 'Please enter %qty% or more numbers', 'enhanced select', 'untappd-ratings-for-woocommerce' ),
			)
		);
	}

	/**
	 * After plugins loaded.
	 *
	 * @return void
	 */
	public function plugins_loaded() {
		// Only load URWC settings on Woo settings page.
		if ( is_admin() && 'wc-settings' === filter_input( INPUT_GET, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) ) {
			require_once URWC_PLUGIN_DIR . 'includes' . DIRECTORY_SEPARATOR . 'class-urwc-settings.php';
		}

		// Check for API parameters.
		$untappd_params = array(
			'client_id'     => get_option( 'urwc_client_id' ),
			'client_secret' => get_option( 'urwc_client_secret' ),
			'api_url'       => get_option( 'urwc_api_url' ),
			'app_name'      => get_option( 'urwc_api_useragent' ),
		);

		if ( empty( $untappd_params['api_url'] ) || empty( $untappd_params['app_name'] ) || empty( $untappd_params['client_id'] ) || empty( $untappd_params['client_secret'] ) ) {
			// If empty API parameters show a notice on plugins page.
			add_action(
				'admin_notices',
				function () {
					global $pagenow;

					if ( 'plugins.php' === $pagenow ) {
						printf( '<div class="%1$s"><p>%2$s</p></div>', 'notice notice-error is-dismissible', esc_html__( 'Configure Untappd for WooCommerce to start using it.', 'untappd-ratings-for-woocommerce' ) );
					}
				}
			);
		} else {

			/**
			 * Override selected templates.
			*/
			add_filter( 'wc_get_template', array( $this, 'get_template' ), 11, 5 );

			require_once URWC_PLUGIN_DIR . 'includes' . DIRECTORY_SEPARATOR . 'urwc-functions.php';

			require_once URWC_PLUGIN_DIR . 'includes' . DIRECTORY_SEPARATOR . 'class-urwc-error.php';
			require_once URWC_PLUGIN_DIR . 'includes' . DIRECTORY_SEPARATOR . 'class-urwc-checkin.php';
			require_once URWC_PLUGIN_DIR . 'includes' . DIRECTORY_SEPARATOR . 'class-urwc-api.php';
			require_once URWC_PLUGIN_DIR . 'includes' . DIRECTORY_SEPARATOR . 'class-urwc-ajax.php';

			self::$urwc_api_instance = new URWC_API( $untappd_params['client_id'], $untappd_params['client_secret'], $untappd_params['app_name'], $untappd_params['api_url'] );

			/**
			 * Class initialized on it's own file.
			 *
			 * As we use require_once we don't need singleton.
			 */
			require_once URWC_PLUGIN_DIR . 'includes' . DIRECTORY_SEPARATOR . 'class-urwc-product.php';

			/**
			 * Class initialized on it's own file.
			 *
			 * As we use require_once we don't need singleton.
			 */
			require_once URWC_PLUGIN_DIR . '/addons/brewery-activity-feed/class-urwc-brewery-activity-feed.php';

			/**
			 * Show powered by Untappd logo on themes calling storefront_credit_links_output.
			 */
			if ( 'yes' === get_option( 'urwc_show_logo', 'no' ) ) {
				add_filter(
					'storefront_credit_links_output',
					function ( $links_output ) {
						return $links_output . '<div id="powered_by_untappd"><img alt="Powered by Untappd" width="166px" height="40px" src="' . plugin_dir_url( URWC_PLUGIN_FILE ) . 'assets/img/powered-by-untappd-logo-40px.png"></div>';
					}
				);

				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_storefront_styles' ) );
			}
		}
	}

	/**
	 * Activate plugin, keep for meta update.
	 *
	 * @return void
	 */
	public static function activate() {
	}

	/**
	 * Deactivate plugin.
	 *
	 * @return void
	 */
	public static function deactivate() {
		self::delete_cache();
	}

	/**
	 * Uninstall plugin.
	 *
	 * @return void
	 */
	public static function uninstall() {
		self::delete_cache();
		self::delete_options();
		self::delete_meta();
	}

	/**
	 * Singleton API.
	 *
	 * @return URWC_API
	 */
	public static function API() { // PHPCS:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		return self::$urwc_api_instance;
	}

	/**
	 * Check if API is initialized.
	 *
	 * @return bool
	 */
	public static function api_is_active(): bool {
		return self::$urwc_api_instance instanceof URWC_API;
	}

	/**
	 * Delete cache.
	 *
	 * @return WP_Post[]|int[]
	 */
	public static function delete_cache() {
		/**
		 * WP_Query
		 *
		 * @var WP_Query $wpdb
		 */
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
		return $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '%\_transient\_urwc%'" );
	}

	/**
	 * Delete postmeta data.
	 *
	 * @return int|false
	 */
	public static function delete_meta() {
		/**
		 * WP_Query
		 *
		 * @var WP_Query $wpdb
		 */
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
		return $wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '\_urwc%'" );
	}

	/**
	 * Delete options.
	 *
	 * @return int|false
	 */
	private static function delete_options() {
		/**
		 * WP_Query
		 *
		 * @var WP_Query $wpdb
		 */
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
		return $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'urwc%'" );
	}

	/**
	 * Hook WooCommerce templates
	 *
	 *  @since 1.0.0
	 *
	 * @param string $located       Located.
	 * @param string $template_name Template name.
	 * @param array  $args          Arguments. (default: array).
	 * @param string $template_path Template path. (default: '').
	 * @param string $default_path  Default path. (default: '').
	 */
	public function get_template( $located, $template_name, $args, $template_path, $default_path ) { //phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$plugin_template_path = untrailingslashit( plugin_dir_path( URWC_PLUGIN_FILE ) ) . '/templates/woocommerce/' . $template_name;

		if ( is_file( $plugin_template_path ) ) {
			$located = $plugin_template_path;
		}

		return $located;
	}

	/**
	 * Get this as singleton.
	 *
	 * @return URWC_Ratings
	 */
	public static function instance() {
		if ( is_null( self::$urwc_instance ) ) {
			self::$urwc_instance = new self();
		}

		return self::$urwc_instance;
	}
}

register_activation_hook(
	URWC_PLUGIN_FILE,
	array( 'URWC_Ratings', 'activate' )
);

register_deactivation_hook(
	URWC_PLUGIN_FILE,
	array( 'URWC_Ratings', 'deactivate' )
);

register_uninstall_hook(
	URWC_PLUGIN_FILE,
	array( 'URWC_Ratings', 'uninstall' )
);
