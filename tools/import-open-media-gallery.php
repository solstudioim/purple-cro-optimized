<?php
/**
 * Import curated openly licensed product gallery photography.
 *
 * Run with: wp eval-file tools/import-open-media-gallery.php
 *
 * The manifest is curated separately so catalog content does not change when
 * search rankings change. API metadata is re-read at import time and retained
 * on every attachment for attribution and auditability.
 *
 * @package PurpleOptimize
 */

defined( 'ABSPATH' ) || exit;

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

const POT_OPEN_MEDIA_MAX_DIMENSION = 1600;
const POT_OPEN_MEDIA_QUALITY       = 82;

$manifest_file = __DIR__ . '/open-media-gallery.json';
$manifest      = json_decode( (string) file_get_contents( $manifest_file ), true );
if ( ! is_array( $manifest ) ) {
	WP_CLI::error( 'The open-media gallery manifest is invalid.' );
}

/**
 * Fetch and normalize one Openverse record.
 *
 * @param string $id Openverse image UUID.
 * @return array<string, string>|WP_Error
 */
function pot_open_media_from_openverse( string $id ) {
	$response = wp_remote_get( 'https://api.openverse.org/v1/images/' . rawurlencode( $id ) . '/', array( 'timeout' => 30, 'headers' => array( 'Accept' => 'application/json' ) ) );
	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		return new WP_Error( 'openverse_api', 'Openverse did not return this image.' );
	}
	$item = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $item ) || empty( $item['url'] ) ) {
		return new WP_Error( 'openverse_data', 'Openverse returned incomplete image data.' );
	}
	return array(
		'provider'     => 'Openverse',
		'source_id'    => $id,
		'title'        => sanitize_text_field( $item['title'] ?: 'Openly licensed product photography' ),
		'download_url' => esc_url_raw( $item['url'] ),
		'fallback_url' => esc_url_raw( $item['thumbnail'] ?? '' ),
		'source_url'   => esc_url_raw( $item['foreign_landing_url'] ?? '' ),
		'creator'      => sanitize_text_field( $item['creator'] ?: 'Unknown creator' ),
		'creator_url'  => esc_url_raw( $item['creator_url'] ?? '' ),
		'license'      => sanitize_text_field( strtoupper( (string) ( $item['license'] ?? '' ) ) . ( empty( $item['license_version'] ) ? '' : ' ' . $item['license_version'] ) ),
		'license_url'  => esc_url_raw( $item['license_url'] ?? '' ),
	);
}

/**
 * Fetch and normalize one Wikimedia Commons record.
 *
 * @param string $title Full Commons file title.
 * @return array<string, string>|WP_Error
 */
function pot_open_media_from_commons( string $title ) {
	$endpoint = add_query_arg(
		array(
			'action'     => 'query',
			'prop'       => 'imageinfo',
			'iiprop'     => 'url|extmetadata',
			'iiurlwidth' => 1200,
			'titles'     => $title,
			'format'     => 'json',
			'origin'     => '*',
		),
		'https://commons.wikimedia.org/w/api.php'
	);
	$response = wp_remote_get( $endpoint, array( 'timeout' => 30, 'headers' => array( 'Accept' => 'application/json' ) ) );
	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		return new WP_Error( 'commons_api', 'Wikimedia Commons did not return this image.' );
	}
	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	$page = is_array( $data['query']['pages'] ?? null ) ? reset( $data['query']['pages'] ) : null;
	$info = is_array( $page['imageinfo'][0] ?? null ) ? $page['imageinfo'][0] : null;
	$meta = is_array( $info['extmetadata'] ?? null ) ? $info['extmetadata'] : array();
	if ( ! $info || empty( $info['thumburl'] ) ) {
		return new WP_Error( 'commons_data', 'Wikimedia Commons returned incomplete image data.' );
	}
	$creator = html_entity_decode( wp_strip_all_tags( (string) ( $meta['Artist']['value'] ?? 'Unknown creator' ) ), ENT_QUOTES, 'UTF-8' );
	return array(
		'provider'     => 'Wikimedia Commons',
		'source_id'    => $title,
		'title'        => sanitize_text_field( preg_replace( '/^File:/', '', $title ) ),
		'download_url' => esc_url_raw( $info['thumburl'] ),
		'fallback_url' => esc_url_raw( $info['url'] ?? '' ),
		'source_url'   => esc_url_raw( $info['descriptionurl'] ?? '' ),
		'creator'      => sanitize_text_field( $creator ),
		'creator_url'  => '',
		'license'      => sanitize_text_field( $meta['LicenseShortName']['value'] ?? '' ),
		'license_url'  => esc_url_raw( $meta['LicenseUrl']['value'] ?? '' ),
	);
}

/**
 * Resize and compress a validated temporary image when the editor supports it.
 *
 * @param string $tmp  Temporary image path.
 * @param string $mime Validated image MIME type.
 * @return array{path:string,mime:string,extension:string}
 */
function pot_prepare_open_media_image( string $tmp, string $mime ): array {
	$extensions = array( 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp' );
	$fallback   = array( 'path' => $tmp, 'mime' => $mime, 'extension' => $extensions[ $mime ] );
	$editor     = wp_get_image_editor( $tmp );
	if ( is_wp_error( $editor ) ) {
		return $fallback;
	}

	$size = $editor->get_size();
	if ( ! is_array( $size ) || empty( $size['width'] ) || empty( $size['height'] ) ) {
		return $fallback;
	}
	if ( max( (int) $size['width'], (int) $size['height'] ) > POT_OPEN_MEDIA_MAX_DIMENSION ) {
		$resized = $editor->resize( POT_OPEN_MEDIA_MAX_DIMENSION, POT_OPEN_MEDIA_MAX_DIMENSION, false );
		if ( is_wp_error( $resized ) ) {
			return $fallback;
		}
	}

	$editor->set_quality( POT_OPEN_MEDIA_QUALITY );
	$target_mime = wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ? 'image/webp' : $mime;
	$target_ext  = $extensions[ $target_mime ];
	$target_name = wp_unique_filename( dirname( $tmp ), basename( $tmp ) . '-pot-optimized.' . $target_ext );
	$saved       = $editor->save( dirname( $tmp ) . '/' . $target_name, $target_mime );
	if ( is_wp_error( $saved ) || empty( $saved['path'] ) ) {
		return $fallback;
	}
	if ( $saved['path'] !== $tmp && file_exists( $tmp ) ) {
		unlink( $tmp );
	}

	return array( 'path' => $saved['path'], 'mime' => $target_mime, 'extension' => $target_ext );
}

/**
 * Download and import one validated image record.
 *
 * @param array<string, string> $record Normalized media data.
 * @param WC_Product            $product Product receiving the gallery image.
 * @param string                $sku Product SKU.
 * @return int|WP_Error Attachment ID or error.
 */
function pot_import_open_media_attachment( array $record, WC_Product $product, string $sku ) {
	if ( ! preg_match( '/^(CC0|PDM|BY(?:-SA)?|CC BY(?:-SA)?|PUBLIC DOMAIN)/i', $record['license'] ) ) {
		return new WP_Error( 'media_license', 'The media license is not on the allowed open-license list.' );
	}
	$existing = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => '_pot_media_source_id', 'meta_value' => $record['source_id'] ) );
	if ( $existing ) {
		return (int) $existing[0];
	}

	$tmp = download_url( $record['download_url'], 60 );
	if ( is_wp_error( $tmp ) && $record['fallback_url'] ) {
		$tmp = download_url( $record['fallback_url'], 60 );
	}
	if ( is_wp_error( $tmp ) ) {
		return $tmp;
	}
	$image = wp_getimagesize( $tmp );
	if ( ! $image || $image[0] < 600 || $image[1] < 500 || filesize( $tmp ) > 8 * MB_IN_BYTES ) {
		@unlink( $tmp );
		return new WP_Error( 'media_dimensions', 'The downloaded image did not meet the 600×500 size or 8 MB safety limit.' );
	}
	$extensions = array( 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp' );
	$mime       = wp_get_image_mime( $tmp );
	if ( ! isset( $extensions[ $mime ] ) ) {
		@unlink( $tmp );
		return new WP_Error( 'media_mime', 'The downloaded file is not a supported raster image.' );
	}

	$prepared = pot_prepare_open_media_image( $tmp, $mime );
	$tmp      = $prepared['path'];
	$mime     = $prepared['mime'];
	$filename = sanitize_file_name(
		strtolower( $sku . '-' . $record['provider'] . '-' . substr( md5( $record['source_id'] ), 0, 10 ) . '.' . $prepared['extension'] )
	);

	$attachment_id = media_handle_sideload( array( 'name' => $filename, 'tmp_name' => $tmp ), $product->get_id(), $record['title'] );
	if ( is_wp_error( $attachment_id ) ) {
		@unlink( $tmp );
		return $attachment_id;
	}

	$caption = sprintf( '%1$s by %2$s. %3$s. Source: %4$s', $record['title'], $record['creator'], $record['license'], $record['source_url'] );
	wp_update_post( array( 'ID' => $attachment_id, 'post_excerpt' => $caption ) );
	update_post_meta( $attachment_id, '_wp_attachment_image_alt', sprintf( '%s — %s', $product->get_name(), $record['title'] ) );
	foreach ( array( 'provider', 'source_id', 'source_url', 'creator', 'creator_url', 'license', 'license_url' ) as $key ) {
		update_post_meta( $attachment_id, '_pot_media_' . $key, $record[ $key ] );
	}
	return (int) $attachment_id;
}

$imported = 0;
$failed   = 0;
foreach ( $manifest as $sku => $images ) {
	$product_id = wc_get_product_id_by_sku( $sku );
	$product    = $product_id ? wc_get_product( $product_id ) : false;
	if ( ! $product ) {
		WP_CLI::warning( 'Product not found for SKU ' . $sku . '.' );
		++$failed;
		continue;
	}
	$gallery = $product->get_gallery_image_ids();
	foreach ( $images as $image ) {
		$record = 'openverse' === $image['provider'] ? pot_open_media_from_openverse( $image['id'] ) : pot_open_media_from_commons( $image['id'] );
		if ( is_wp_error( $record ) ) {
			WP_CLI::warning( $sku . ': ' . $record->get_error_message() );
			++$failed;
			continue;
		}
		$attachment_id = pot_import_open_media_attachment( $record, $product, $sku );
		if ( is_wp_error( $attachment_id ) ) {
			WP_CLI::warning( $sku . ': ' . $attachment_id->get_error_message() );
			++$failed;
			continue;
		}
		$gallery[] = $attachment_id;
		++$imported;
	}
	$product->set_gallery_image_ids( array_values( array_unique( array_map( 'absint', $gallery ) ) ) );
	$product->save();
	WP_CLI::log( sprintf( '%s now has %d gallery images.', $sku, count( $product->get_gallery_image_ids() ) ) );
}

wc_delete_product_transients();
if ( $failed ) {
	WP_CLI::error( sprintf( 'Imported or reused %d images; %d imports failed.', $imported, $failed ) );
}
WP_CLI::success( sprintf( 'Imported or reused %d openly licensed gallery images.', $imported ) );
