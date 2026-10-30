<?php
/** Native WooCommerce review selection and reusable testimonial shortcodes. */
defined( 'ABSPATH' ) || exit;

/** Accept only bounded, explicit positive ID lists. Invalid selections fail closed. */
function pot_reviews_ids( string $value ): array {
	if ( strlen( $value ) > 1024 || ! preg_match( '/^[\d\s,]*$/D', $value ) ) {
		return array();
	}
	return array_slice( array_values( array_filter( wp_parse_id_list( $value ) ) ), 0, 50 );
}

/** Normalize both shortcode attributes and admin preview input. */
function pot_reviews_config( $attributes ): array {
	$attributes = is_array( $attributes ) ? $attributes : array();
	$defaults = array( 'source' => 'all', 'products' => '', 'review_ids' => '', 'limit' => '3', 'min_rating' => '0', 'order' => 'newest', 'layout' => 'list', 'autoplay' => '0', 'author' => 'first_name', 'heading' => '' );
	$config = array();
	foreach ( $defaults as $key => $default ) {
		$config[ $key ] = isset( $attributes[ $key ] ) && is_scalar( $attributes[ $key ] ) ? (string) $attributes[ $key ] : $default;
	}
	foreach ( array( 'source' => array( 'all', 'products', 'reviews' ), 'order' => array( 'newest', 'oldest' ), 'layout' => array( 'list', 'rotator' ), 'author' => array( 'first_name', 'anonymous' ) ) as $key => $values ) {
		$config[ $key ] = in_array( $config[ $key ], $values, true ) ? $config[ $key ] : $defaults[ $key ];
	}
	$config['products'] = implode( ',', pot_reviews_ids( $config['products'] ) );
	$config['review_ids'] = implode( ',', pot_reviews_ids( $config['review_ids'] ) );
	$config['limit'] = max( 1, min( 12, absint( $config['limit'] ) ) );
	$config['min_rating'] = min( 5, absint( $config['min_rating'] ) );
	$config['autoplay'] = absint( $config['autoplay'] ) ? max( 5, min( 30, absint( $config['autoplay'] ) ) ) : 0;
	if ( 'list' === $config['layout'] ) {
		$config['autoplay'] = 0;
	}
	$config['heading'] = sanitize_text_field( $config['heading'] );
	return $config;
}

/** Query approved reviews only; never expose protected/unpublished products. */
function pot_reviews_query( array $config ): array {
	if ( ! function_exists( 'wc_get_product' ) ) {
		return array();
	}
	$args = array( 'status' => 'approve', 'parent' => 0, 'type' => 'review', 'post_type' => 'product', 'post_status' => 'publish', 'number' => 100, 'orderby' => 'comment_date_gmt comment_ID', 'order' => 'oldest' === $config['order'] ? 'ASC' : 'DESC' );
	if ( 'products' === $config['source'] ) {
		$args['post__in'] = pot_reviews_ids( $config['products'] );
		if ( ! $args['post__in'] ) { return array(); }
	} elseif ( 'reviews' === $config['source'] ) {
		$args['comment__in'] = pot_reviews_ids( $config['review_ids'] );
		if ( ! $args['comment__in'] ) { return array(); }
	}
	if ( $config['min_rating'] ) {
		$args['meta_query'] = array( array( 'key' => 'rating', 'value' => $config['min_rating'], 'compare' => '>=', 'type' => 'NUMERIC' ) );
	}
	$reviews = array();
	foreach ( get_comments( $args ) as $review ) {
		$post = get_post( $review->comment_post_ID );
		if ( ! $post || 'product' !== $post->post_type || 'publish' !== $post->post_status || $post->post_password || ! is_post_publicly_viewable( $post ) || ! trim( wp_strip_all_tags( $review->comment_content ) ) ) {
			continue;
		}
		$reviews[] = $review;
		if ( count( $reviews ) >= $config['limit'] ) { break; }
	}
	return $reviews;
}

/** Render only stored review text, without running user content as shortcodes. */
function pot_reviews_shortcode( $attributes = array() ): string {
	$config = pot_reviews_config( $attributes );
	$reviews = pot_reviews_query( $config );
	if ( ! $reviews ) { return ''; }
	$rotator = 'rotator' === $config['layout'] && count( $reviews ) > 1;
	ob_start();
	?>
	<section class="pot-testimonials" aria-label="<?php esc_attr_e( 'Customer reviews', 'purple-optimize-toolkit' ); ?>"<?php if ( $rotator ) : ?> data-pot-review-rotator data-autoplay="<?php echo esc_attr( (string) $config['autoplay'] ); ?>"<?php endif; ?>>
	<?php if ( $config['heading'] ) : ?><h3 class="pot-testimonials-heading"><?php echo esc_html( $config['heading'] ); ?></h3><?php endif; ?>
	<div class="pot-testimonials-stage<?php echo $rotator && $config['autoplay'] ? ' pot-has-review-autoplay' : ''; ?>">
	<div class="pot-testimonials-items">
	<?php foreach ( $reviews as $review ) :
		$rating = min( 5, max( 0, (int) get_comment_meta( $review->comment_ID, 'rating', true ) ) );
		$name = sanitize_text_field( $review->comment_author );
		$name = preg_split( '/\s+/u', trim( $name ) )[0] ?? '';
		if ( 'anonymous' === $config['author'] || ! $name || is_email( $review->comment_author ) ) { $name = __( 'Customer', 'purple-optimize-toolkit' ); }
		?>
		<figure class="pot-testimonial">
		<?php if ( $rating ) : ?><div class="pot-testimonial-rating" role="img" aria-label="<?php echo esc_attr( sprintf( __( '%d out of 5 stars', 'purple-optimize-toolkit' ), $rating ) ); ?>"><span aria-hidden="true"><?php echo esc_html( str_repeat( '★', $rating ) . str_repeat( '☆', 5 - $rating ) ); ?></span></div><?php endif; ?>
		<blockquote><?php echo wpautop( esc_html( wp_strip_all_tags( $review->comment_content ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></blockquote>
		<figcaption><span class="pot-testimonial-author"><?php echo esc_html( $name ); ?></span>
		<?php if ( function_exists( 'wc_review_is_from_verified_owner' ) && wc_review_is_from_verified_owner( $review->comment_ID ) ) : ?><span class="pot-testimonial-verified"><?php esc_html_e( 'Verified owner', 'purple-optimize-toolkit' ); ?></span><?php endif; ?>
		<a href="<?php echo esc_url( get_permalink( $review->comment_post_ID ) . '#reviews' ); ?>"><?php echo esc_html( get_the_title( $review->comment_post_ID ) ); ?></a></figcaption>
		</figure>
	<?php endforeach; ?>
	</div>
	<?php if ( $rotator ) : ?><div class="pot-testimonials-controls" hidden>
	<button type="button" class="pot-review-arrow" data-review-previous aria-label="<?php esc_attr_e( 'Previous review', 'purple-optimize-toolkit' ); ?>"><span aria-hidden="true">‹</span></button>
	<span class="pot-testimonials-position" role="status" aria-live="polite" data-position-template="<?php esc_attr_e( '%1$d of %2$d', 'purple-optimize-toolkit' ); ?>"></span>
	<button type="button" class="pot-review-arrow" data-review-next aria-label="<?php esc_attr_e( 'Next review', 'purple-optimize-toolkit' ); ?>"><span aria-hidden="true">›</span></button>
	<?php if ( $config['autoplay'] ) : ?><button type="button" class="pot-review-play" data-review-play aria-label="<?php esc_attr_e( 'Pause rotation', 'purple-optimize-toolkit' ); ?>" aria-pressed="true" data-play-label="<?php esc_attr_e( 'Play rotation', 'purple-optimize-toolkit' ); ?>" data-pause-label="<?php esc_attr_e( 'Pause rotation', 'purple-optimize-toolkit' ); ?>"><span aria-hidden="true">Ⅱ</span></button><?php endif; ?>
	</div><?php endif; ?>
	</div>
	</section>
	<?php
	return ob_get_clean();
}
add_shortcode( 'purple_optimize_reviews', 'pot_reviews_shortcode' );

/** Canonical shortcode independent of global settings, safe to copy between placements. */
function pot_reviews_build_shortcode( array $config ): string {
	$shortcode = '[purple_optimize_reviews';
	foreach ( $config as $key => $value ) {
		if ( ( 'products' === $key && 'products' !== $config['source'] ) || ( 'review_ids' === $key && 'reviews' !== $config['source'] ) || '' === (string) $value ) { continue; }
		// Shortcode attribute syntax cannot quote literal brackets or double quotes.
		$value = str_replace( array( '[', ']', '"' ), '', (string) $value );
		$shortcode .= ' ' . $key . '="' . $value . '"';
	}
	return $shortcode . ']';
}

/** Only our review shortcode may execute inside the restricted checkout document. */
function pot_reviews_checkout_shortcodes( string $content ): string {
	preg_match_all( '/' . get_shortcode_regex( array( 'purple_optimize_reviews' ) ) . '/s', $content, $matches, PREG_SET_ORDER );
	$html = '';
	foreach ( $matches as $match ) {
		if ( '[' === $match[1] && ']' === $match[6] ) { continue; }
		$html .= pot_reviews_shortcode( shortcode_parse_atts( $match[3] ) );
	}
	return $html;
}

/** Authenticated preview uses exactly the same normalization/query/render as shoppers. */
function pot_preview_reviews(): void {
	if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_send_json_error( array( 'message' => __( 'You cannot preview reviews.', 'purple-optimize-toolkit' ) ), 403 ); }
	check_ajax_referer( 'pot_preview_reviews', 'nonce' );
	$config = pot_reviews_config( isset( $_POST['config'] ) && is_array( $_POST['config'] ) ? wp_unslash( $_POST['config'] ) : array() );
	if ( ( 'products' === $config['source'] && ! $config['products'] ) || ( 'reviews' === $config['source'] && ! $config['review_ids'] ) ) {
		wp_send_json_error( array( 'message' => __( 'Enter at least one valid ID for the selected review source.', 'purple-optimize-toolkit' ) ), 400 );
	}
	// Use the canonical attributes for both preview and copied shortcode, including heading syntax.
	$shortcode = pot_reviews_build_shortcode( $config );
	$html = pot_reviews_checkout_shortcodes( $shortcode );
	wp_send_json_success( array( 'shortcode' => $shortcode, 'html' => $html, 'message' => $html ? __( 'Preview uses current approved reviews. Reviews update automatically when placed on your site.', 'purple-optimize-toolkit' ) : __( 'No approved reviews match. Change the selection or rating filter; nothing will display until a review matches.', 'purple-optimize-toolkit' ) ) );
}
add_action( 'wp_ajax_pot_preview_reviews', 'pot_preview_reviews' );

/** Load independently of other feature switches, including checkout Store API inserts. */
function pot_reviews_assets(): void {
	if ( ! class_exists( 'WooCommerce' ) ) { return; }
	wp_enqueue_style( 'pot-reviews', POT_URL . 'assets/reviews.css', array(), POT_VERSION );
	wp_enqueue_script( 'pot-reviews', POT_URL . 'assets/reviews.js', array(), POT_VERSION, true );
}
add_action( 'wp_enqueue_scripts', 'pot_reviews_assets' );
