<?php
/**
 * URWC Global Functions
 *
 * @author    ChillCode
 * @copyright Copyright (c) 2024, ChillCode All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 * @package   Untappd Ratings for WooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'urwc_is_error' ) ) {
	/**
	 * Check whether variable is a Untappd Error.
	 *
	 * Returns true if $thing is an object of the URWC_Error class.
	 *
	 * @since 1.0.4
	 *
	 * @param mixed $thing Check if unknown variable is a URWC_Error object.
	 * @return bool True, if URWC_Error. False, if not URWC_Error.
	 */
	function urwc_is_error( $thing ) {
		return ( $thing instanceof URWC_Error );
	}
}

if ( ! function_exists( 'urwc_logger' ) ) {
	/**
	 * Get a shared logger instance.
	 *
	 * Use the woocommerce_logging_class filter to change the logging class. You may provide one of the following:
	 *     - a class name which will be instantiated as `new $class` with no arguments
	 *     - an instance which will be used directly as the logger
	 * In either case, the class or instance *must* implement WC_Logger_Interface.
	 *
	 * @see WC_Logger_Interface
	 *
	 * @param string $message Log message.
	 * @param string $level One of the following:
	 *     'emergency': System is unusable.
	 *     'alert': Action must be taken immediately.
	 *     'critical': Critical conditions.
	 *     'error': Error conditions.
	 *     'warning': Warning conditions.
	 *     'notice': Normal but significant condition.
	 *     'info': Informational messages.
	 *     'debug': Debug-level messages.
	 * @return void
	 */
	function urwc_logger( $message, $level = 'debug' ) {
		if ( function_exists( 'wc_get_logger' ) ) {
			$logger = wc_get_logger();

			$context = array( 'source' => 'untappd-ratings-for-woocommerce' );

			$logger->log( $level, $message, $context );
		} elseif ( WP_DEVELOPMENT_MODE ) {
			error_log( "[URWC] ({$level}): {$message}" ); // PHPCS:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}
}

if ( ! function_exists( 'urwc_ratings_enabled' ) ) {
	/**
	 * Check if reviews ratings are enabled.
	 *
	 * @return bool
	 */
	function urwc_ratings_enabled() {
		return get_option( 'urwc_ratings_enabled' ) === 'yes' && URWC_Ratings::api_is_active() ? true : false;
	}
}

if ( ! function_exists( 'urwc_ratings_sort_enabled' ) ) {
	/**
	 * Check if reviews ratings are enabled.
	 *
	 * @return bool
	 */
	function urwc_ratings_sort_enabled() {
		return get_option( 'urwc_ratings_sort_enabled' ) === 'yes' && URWC_Ratings::api_is_active() ? true : false;
	}
}

if ( ! function_exists( 'urwc_show_total' ) ) {
	/**
	 * Check if to show total ratings.
	 *
	 * @return bool
	 */
	function urwc_show_total() {
		return get_option( 'urwc_ratings_show_total' ) === 'yes' ? true : false;
	}
}

if ( ! function_exists( 'urwc_show_text' ) ) {
	/**
	 * Check if to show total text.
	 *
	 * @return bool
	 */
	function urwc_show_text() {
		return get_option( 'urwc_ratings_show_text' ) === 'yes' ? true : false;
	}
}

if ( ! function_exists( 'urwc_structured_data_enabled' ) ) {
	/**
	 * Check if structured data is enabled.
	 *
	 * @return bool
	 */
	function urwc_structured_data_enabled() {
		return get_option( 'urwc_ratings_add_to_structured_data' ) === 'yes' ? true : false;
	}
}

if ( ! function_exists( 'urwc_structured_data_comments_only' ) ) {
	/**
	 * Only add checkins with comments to structure data.
	 *
	 * @return bool
	 */
	function urwc_structured_data_comments_only() {
		return get_option( 'urwc_ratings_structured_data_comments_only' ) === 'yes' ? true : false;
	}
}
