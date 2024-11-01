<?php
/**
 * Single Product Rating
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/rating.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
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

$rating_count = (int) $product->get_rating_count();
$review_count = (int) $product->get_review_count();
$average      = (float) $product->get_average_rating();

/*
	Use untappd ratings instead WooCommerce one's.
*/

$urwc_ratings_enabled = urwc_ratings_enabled();

if ( $urwc_ratings_enabled ) {
	$urwc_beer_link         = '#reviews';
	$urwc_ratings_show_text = '';

	$urwc_beer_id = absint( $product->get_meta( '_urwc_beer_id', true ) );

	if ( $urwc_beer_id > 0 ) {
		$product_id = $product->get_id();

		$urwc_product_meta = URWC_Product::get_product_meta( $urwc_beer_id, $product_id );

		if ( isset( $urwc_product_meta['_urwc_rating_count'] ) && isset( $urwc_product_meta['_urwc_average_rating'] ) ) {
			$rating_count           = absint( $urwc_product_meta['_urwc_rating_count'] );
			$average                = (float) $urwc_product_meta['_urwc_average_rating'];
			$urwc_ratings_show_text = ( urwc_show_text() ) ? number_format_i18n( $average, 2 ) . '/' . number_format_i18n( 5 ) : '';
		}

		if ( isset( $urwc_product_meta['_urwc_beer_slug'] ) ) {
			$urwc_beer_slug = $urwc_product_meta['_urwc_beer_slug'];
			$urwc_beer_link = 'https://untappd.com/b/' . $urwc_beer_slug . '/' . $urwc_beer_id;
		}
	}

	/* translators: %s rating: Total ratings */
	$urwc_rating_text = sprintf( _n( '%s rating', '%s ratings', $rating_count, 'untappd-ratings-for-woocommerce' ), $rating_count );
} else {
	/* translators: %s customer review: Total customer reviews */
	$urwc_rating_text = sprintf( _n( '%s customer review', '%s customer reviews', $review_count, 'untappd-ratings-for-woocommerce' ), $review_count );
}

/**
 * We can ignore warnings related to wc_get_rating_html since all output data is generated securely by Woo.
 */
if ( $urwc_ratings_enabled && $rating_count > 0 ) : ?>
	<div class="woocommerce-product-rating">
		<div><?php esc_html_e( 'Untappd Ratings', 'untappd-ratings-for-woocommerce' ); ?></div>
			<?php echo wc_get_rating_html( $average, $rating_count ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span class="urwc-review-text"><?php echo esc_html( $urwc_ratings_show_text ); ?></span>
			<?php if ( urwc_show_total() ) : ?>
			<a target="_blank" href="<?php echo esc_attr( $urwc_beer_link ); ?>" class="woocommerce-review-link" rel="noopener noreferer"><span class="count">(<?php echo esc_html( $urwc_rating_text ); ?>)</span></a> 
			<?php endif; ?>
		</div>

<?php elseif ( $rating_count > 0 ) : ?>

	<div class="woocommerce-product-rating">
		<?php echo wc_get_rating_html( $average, $rating_count ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php if ( comments_open() ) : ?>
			<a href="#reviews" class="woocommerce-review-link" rel="nofollow"><span class="count">(<?php echo esc_html( $urwc_rating_text ); ?>)</span></a>
		<?php endif ?>
	</div>

<?php endif; ?>
