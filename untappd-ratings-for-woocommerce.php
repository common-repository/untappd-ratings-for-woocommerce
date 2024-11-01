<?php
/**
 * Untappd Ratings for WooCommerce
 *
 * @package   Untappd Ratings for WooCommerce
 * @author    Chillcode
 * @copyright Copyright (c) 2003-2024, Chillcode (https://github.com/chillcode/)
 * @license   GPLv3
 *
 * @wordpress-plugin
 * Plugin Name: Untappd Ratings for WooCommerce
 * Plugin URI: https://github.com/chillcode/untappd-ratings-for-woocommerce
 * Description: Connect your WooCommerce Store with Untappd
 * Version: 1.0.6
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Chillcode
 * Author URI: https://github.com/chillcode/
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: untappd-ratings-for-woocommerce
 * Domain Path: /i18n/languages/
 *
 * WC requires at least: 6.0.1
 * WC tested up to: 8.5.2
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Define global constants.
define( 'URWC_PLUGIN_FILE', __FILE__ );
define( 'URWC_PLUGIN_DIR', dirname( URWC_PLUGIN_FILE ) . DIRECTORY_SEPARATOR );
define( 'URWC_NAME', 'Untappd Ratings for WooCommerce' );
define( 'URWC_VERSION', '1.0.6' );

require_once URWC_PLUGIN_DIR . 'includes' . DIRECTORY_SEPARATOR . 'class-urwc-ratings.php';

/**
 * Main Instance.
 *
 * Ensures only one instance of Untappd for WooCommerce is loaded or can be loaded.
 *
 * @since 1.0
 * @static
 * @see URWC_Ratings()
 * @return URWC_Ratings - Main instance.
 */
function URWC_Ratings(): URWC_Ratings { //phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	return URWC_Ratings::instance();
}

/**
 * Initialize the plugin.
 */
URWC_Ratings();
