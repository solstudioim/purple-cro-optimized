<?php
/**
 * Standalone pre-checkout offer template.
 *
 * @package PurpleOptimizeToolkit
 */

defined( 'ABSPATH' ) || exit;

$context = $GLOBALS['pot_offer_context'] ?? array();
$product = $context['product'] ?? false;
if ( ! $product instanceof WC_Product ) {
	wp_safe_redirect( wc_get_checkout_url() );
	exit;
}

$step       = (string) $context['step'];
$discount   = (int) $context['discount'];
$is_downsell = 'downsell' === $step;
$is_post_purchase = 'post_purchase' === ( $context['context'] ?? '' );
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'pot-offer-funnel-page' ); ?>>
	<?php wp_body_open(); ?>
	<header class="pot-offer-header">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a>
		<span><?php echo $is_post_purchase ? esc_html__( 'Order complete', 'purple-optimize-toolkit' ) : esc_html( sprintf( _n( '%d item reserved', '%d items reserved', WC()->cart->get_cart_contents_count(), 'purple-optimize-toolkit' ), WC()->cart->get_cart_contents_count() ) ); ?></span>
	</header>
	<main class="pot-offer-shell">
		<section class="pot-offer-card" aria-labelledby="pot-offer-title">
			<div class="pot-offer-media"><?php echo $product->get_image( 'woocommerce_single', array( 'loading' => 'eager' ) ); ?></div>
			<div class="pot-offer-copy">
				<span class="pot-offer-eyebrow"><?php echo esc_html( $is_downsell ? __( 'One last option', 'purple-optimize-toolkit' ) : ( $is_post_purchase ? __( 'Post-purchase offer', 'purple-optimize-toolkit' ) : __( 'Checkout-only offer', 'purple-optimize-toolkit' ) ) ); ?></span>
				<h1 id="pot-offer-title"><?php echo esc_html( $is_downsell ? __( 'Would you prefer this offer?', 'purple-optimize-toolkit' ) : __( 'Would you like to add this?', 'purple-optimize-toolkit' ) ); ?></h1>
				<h2><?php echo esc_html( $product->get_name() ); ?></h2>
				<div class="pot-offer-description"><?php echo wp_kses_post( wpautop( $product->get_short_description() ) ); ?></div>
				<p class="pot-offer-price">
					<del><?php echo wp_kses_post( wc_price( (float) $context['original_price'] ) ); ?></del>
					<ins><?php echo wp_kses_post( wc_price( (float) $context['discounted_price'] ) ); ?></ins>
					<strong><?php echo esc_html( sprintf( __( '%d%% off on this page', 'purple-optimize-toolkit' ), $discount ) ); ?></strong>
				</p>
				<?php if ( ! empty( $context['expiry'] ) ) : ?>
				<p class="pot-offer-timer" data-offer-expiry="<?php echo esc_attr( (string) ( (int) $context['expiry'] * 1000 ) ); ?>">
					<span><?php esc_html_e( 'This offer ends in', 'purple-optimize-toolkit' ); ?></span>
					<strong data-offer-countdown role="timer"></strong>
				</p>
				<?php endif; ?>
				<?php wc_print_notices(); ?>
				<form class="pot-offer-actions" method="post">
					<?php wp_nonce_field( 'pot_offer_' . $step, 'pot_offer_nonce' ); ?>
					<button class="pot-offer-accept" type="submit" name="pot_offer_action" value="accept"><?php echo esc_html( $is_post_purchase ? __( 'Yes, add this in a new order', 'purple-optimize-toolkit' ) : ( $is_downsell ? __( 'Yes, add this and continue', 'purple-optimize-toolkit' ) : __( 'Yes, add this to my order', 'purple-optimize-toolkit' ) ) ); ?></button>
					<button class="pot-offer-reject" type="submit" name="pot_offer_action" value="reject"><?php echo esc_html( $is_post_purchase ? __( 'No thanks, view my receipt', 'purple-optimize-toolkit' ) : ( $is_downsell ? __( 'No thanks, continue to checkout', 'purple-optimize-toolkit' ) : __( 'No thanks', 'purple-optimize-toolkit' ) ) ); ?></button>
				</form>
				<p class="pot-offer-fine-print"><?php echo esc_html( $is_post_purchase ? __( 'Your completed order is unchanged. Accepting starts a separate checkout and creates a separate order.', 'purple-optimize-toolkit' ) : __( 'Your existing cart is unchanged unless you accept. Payment is completed securely on the next checkout page.', 'purple-optimize-toolkit' ) ); ?></p>
			</div>
		</section>
	</main>
	<?php wp_footer(); ?>
</body>
</html>
