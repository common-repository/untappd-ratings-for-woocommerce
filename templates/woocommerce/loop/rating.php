<?php
/**
 * Loop Rating
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/loop/rating.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce Global Product Variable.
 *
 * @var WC_Product $product
 */
global $product;

if ( ! wc_review_ratings_enabled() ) {
	return;
}

$urwc_average_rating = $product->get_average_rating();

if ( urwc_ratings_enabled() ) {
	$urwc_beer_id = absint( $product->get_meta( '_urwc_beer_id', true ) );

	$latest_meta = URWC_Product::get_product_meta( $urwc_beer_id, $product->get_id() );

	if ( isset( $latest_meta['_urwc_average_rating'] ) ) {
		$urwc_average_rating = $latest_meta['_urwc_average_rating'];
	} elseif ( urwc_ratings_sort_enabled() ) {
		$urwc_average_rating = 0;
	}
}

/**
 * We can ignore this warning, all output data is generated securely by Woo.
 */
echo wc_get_rating_html( (float) $urwc_average_rating ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
