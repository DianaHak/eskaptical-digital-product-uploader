<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get uploaded files from the current request.
 *
 * @param string $field_name File input field name.
 * @return array
 */
function wdpbu_get_uploaded_files_from_request( $field_name ) {
	$field_name = sanitize_key( $field_name );

	// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce and capability checks are performed before this helper is called. Uploaded file arrays are sanitized in wdpbu_reformat_files_array().
	if ( empty( $_FILES[ $field_name ] ) || ! is_array( $_FILES[ $field_name ] ) ) {
		return array();
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce and capability checks are performed before this helper is called. Uploaded file arrays are sanitized in wdpbu_reformat_files_array().
	$file_post = wp_unslash( $_FILES[ $field_name ] );

	return wdpbu_reformat_files_array( $file_post );
}

/**
 * Convert the $_FILES multi-upload structure into a sanitized flat array.
 *
 * @param array $file_post Raw $_FILES[field_name] array.
 * @return array
 */
function wdpbu_reformat_files_array( $file_post ) {
	$files = array();

	if ( empty( $file_post ) || empty( $file_post['name'] ) || ! is_array( $file_post['name'] ) ) {
		return $files;
	}

	$file_count = count( $file_post['name'] );

	for ( $i = 0; $i < $file_count; $i++ ) {
		if ( empty( $file_post['name'][ $i ] ) ) {
			continue;
		}

		$files[] = array(
			'name'     => isset( $file_post['name'][ $i ] ) ? sanitize_file_name( $file_post['name'][ $i ] ) : '',
			'type'     => isset( $file_post['type'][ $i ] ) ? sanitize_mime_type( $file_post['type'][ $i ] ) : '',
			'tmp_name' => isset( $file_post['tmp_name'][ $i ] ) ? sanitize_text_field( $file_post['tmp_name'][ $i ] ) : '',
			'error'    => isset( $file_post['error'][ $i ] ) ? absint( $file_post['error'][ $i ] ) : 0,
			'size'     => isset( $file_post['size'][ $i ] ) ? absint( $file_post['size'][ $i ] ) : 0,
		);
	}

	return $files;
}

/**
 * Normalize filename base for matching.
 *
 * @param string $filename Filename or basename.
 * @return string
 */
function wdpbu_normalize_filename_base( $filename ) {
	$base = pathinfo( $filename, PATHINFO_FILENAME );
	$base = wp_strip_all_tags( $base );
	$base = remove_accents( $base );
	$base = strtolower( $base );
	$base = preg_replace( '/[\s\-_]+/', '-', $base );
	$base = preg_replace( '/[^a-z0-9\-]/', '', $base );
	$base = trim( $base, '-' );

	return (string) $base;
}

/**
 * Clean filename into a readable product title.
 *
 * @param string $filename Filename.
 * @return string
 */
function wdpbu_filename_to_title( $filename ) {
	$base = pathinfo( $filename, PATHINFO_FILENAME );
	$base = wp_strip_all_tags( $base );
	$base = remove_accents( $base );
	$base = preg_replace( '/[_\-]+/', ' ', $base );
	$base = preg_replace( '/\s+/', ' ', $base );
	$base = trim( $base );

	if ( '' === $base ) {
		return __( 'Untitled Product', 'eskaptical-digital-product-uploader-for-woocommerce' );
	}

	return wp_strip_all_tags( ucwords( $base ) );
}

/**
 * Allowed product file mimes.
 *
 * @return array
 */
function wdpbu_get_allowed_product_mimes() {
	return array(
		'pdf'  => 'application/pdf',
		'zip'  => 'application/zip',
		'epub' => 'application/epub+zip',
		'txt'  => 'text/plain',
		'doc'  => 'application/msword',
		'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'xls'  => 'application/vnd.ms-excel',
		'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		'ppt'  => 'application/vnd.ms-powerpoint',
		'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
		'mp3'  => 'audio/mpeg',
		'm4a'  => 'audio/mp4',
		'wav'  => 'audio/wav',
		'mp4'  => 'video/mp4',
		'mov'  => 'video/quicktime',
		'csv'  => 'text/csv',
		'rtf'  => 'application/rtf',
		'json' => 'application/json',
	);
}

/**
 * Allowed image mimes.
 *
 * @return array
 */
function wdpbu_get_allowed_image_mimes() {
	return array(
		'jpg|jpeg|jpe' => 'image/jpeg',
		'png'          => 'image/png',
		'gif'          => 'image/gif',
		'webp'         => 'image/webp',
	);
}

/**
 * Upload a single file to WordPress uploads and return data.
 *
 * @param array $file Single file array.
 * @param array $mimes Optional mime restrictions.
 * @return array|WP_Error
 */
function wdpbu_handle_single_upload( $file, $mimes = array() ) {
	if ( empty( $file['name'] ) || empty( $file['tmp_name'] ) ) {
		return new WP_Error( 'wdpbu_empty_file', __( 'Empty file upload.', 'eskaptical-digital-product-uploader-for-woocommerce' ) );
	}

	if ( ! function_exists( 'wp_handle_upload' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}

	$overrides = array(
		'test_form' => false,
	);

	if ( ! empty( $mimes ) ) {
		$overrides['mimes'] = $mimes;
	}

	$uploaded = wp_handle_upload( $file, $overrides );

	if ( isset( $uploaded['error'] ) ) {
		return new WP_Error( 'wdpbu_upload_error', sanitize_text_field( $uploaded['error'] ) );
	}

	return $uploaded;
}

/**
 * Create attachment from already-uploaded file path.
 *
 * @param string $file_path File path.
 * @param string $file_url  File URL.
 * @param int    $parent_id Parent post ID.
 * @param string $mime_type MIME type.
 * @return int|WP_Error
 */
function wdpbu_create_attachment_from_uploaded_file( $file_path, $file_url, $parent_id = 0, $mime_type = '' ) {
	if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
	}

	$filetype = wp_check_filetype( wp_basename( $file_path ), null );
	$mime     = $mime_type ? $mime_type : ( ! empty( $filetype['type'] ) ? $filetype['type'] : 'application/octet-stream' );

	$attachment = array(
		'guid'           => esc_url_raw( $file_url ),
		'post_mime_type' => sanitize_mime_type( $mime ),
		'post_title'     => sanitize_text_field( pathinfo( wp_basename( $file_path ), PATHINFO_FILENAME ) ),
		'post_content'   => '',
		'post_status'    => 'inherit',
	);

	$attachment_id = wp_insert_attachment( $attachment, $file_path, $parent_id );

	if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
		return new WP_Error( 'wdpbu_attachment_error', __( 'Could not create attachment.', 'eskaptical-digital-product-uploader-for-woocommerce' ) );
	}

	$attach_data = wp_generate_attachment_metadata( $attachment_id, $file_path );
	wp_update_attachment_metadata( $attachment_id, $attach_data );

	return (int) $attachment_id;
}

/**
 * Build image map by normalized base filename.
 *
 * @param array $image_files Flat image file array.
 * @return array
 */
function wdpbu_build_image_map( $image_files ) {
	$map = array();

	foreach ( $image_files as $image_file ) {
		if ( empty( $image_file['name'] ) ) {
			continue;
		}

		$key = wdpbu_normalize_filename_base( $image_file['name'] );

		if ( '' === $key ) {
			continue;
		}

		if ( ! isset( $map[ $key ] ) ) {
			$map[ $key ] = $image_file;
		}
	}

	return $map;
}

/**
 * Check whether a product with the same title already exists.
 *
 * @param string $title Product title.
 * @return int
 */
function wdpbu_find_existing_product_id_by_title( $title ) {
	$title = trim( wp_strip_all_tags( $title ) );

	if ( '' === $title ) {
		return 0;
	}

	$query = new WP_Query(
		array(
			'post_type'              => 'product',
			'post_status'            => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page'         => 20,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'ignore_sticky_posts'    => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			's'                      => $title,
		)
	);

	if ( empty( $query->posts ) ) {
		return 0;
	}

	foreach ( $query->posts as $post_id ) {
		if ( $title === get_the_title( $post_id ) ) {
			return (int) $post_id;
		}
	}

	return 0;
}