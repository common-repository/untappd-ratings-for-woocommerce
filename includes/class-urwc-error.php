<?php
/**
 * URWC_Error
 *
 * @author    ChillCode
 * @copyright Copyright (c) 2024, ChillCode All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 * @package   Untappd Ratings for WooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * URWC_Error class.
 */
class URWC_Error extends Exception {

	/**
	 * Error code.
	 *
	 * @var int
	 */
	protected $error_code;

	/**
	 * Constructor
	 *
	 * @param string $error_code       Error code.
	 * @param string $error_message    Error message.
	 * @param string $http_status_code http status code.
	 */
	public function __construct( $error_code, $error_message, $http_status_code ) {
		$this->error_code = (int) $error_code;

		parent::__construct( $error_message, (int) $http_status_code );
	}

	/**
	 * Get error code.
	 */
	public function getErrorCode() {
		return $this->error_code;
	}
}
