<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WDPBU_Importer {

	/**
	 * Import products from uploaded files and matching images.
	 *
	 * @param array $product_files Flat product files array.
	 * @param array $image_files   Flat image files array.
	 * @param array $settings      Import settings.
	 * @return array
	 */
	public function import( $product_files, $image_files, $settings ) {
		$results = array(
			'created'                 => 0,
			'failed'                  => 0,
			'uploaded_files_count'    => count( $product_files ),
			'uploaded_images_count'   => count( $image_files ),
			'matched_images_count'    => 0,
			'unmatched_images_count'  => 0,
			'skipped_duplicates_count'=> 0,
			'items'                   => array(),
		);

		$image_map         = wdpbu_build_image_map( $image_files );
		$matched_image_keys = array();

		foreach ( $product_files as $product_file ) {
			$item_result = $this->import_single_product( $product_file, $image_map, $settings );

			$results['items'][] = $item_result;

			if ( ! empty( $item_result['success'] ) ) {
				$results['created']++;
			} else {
				$results['failed']++;
			}

			if ( ! empty( $item_result['is_duplicate'] ) ) {
				$results['skipped_duplicates_count']++;
			}

			if ( ! empty( $item_result['image_matched'] ) && ! empty( $item_result['image_match_key'] ) ) {
				$matched_image_keys[ $item_result['image_match_key'] ] = true;
			}
		}

		$results['matched_images_count']   = count( $matched_image_keys );
		$results['unmatched_images_count'] = max( 0, count( $image_map ) - $results['matched_images_count'] );

		return $results;
	}

	/**
	 * Import one product.
	 *
	 * @param array $product_file Uploaded product file.
	 * @param array $image_map    Normalized image map.
	 * @param array $settings     Import settings.
	 * @return array
	 */
	protected function import_single_product( $product_file, $image_map, $settings ) {
		$original_name = isset( $product_file['name'] ) ? $product_file['name'] : '';
		$match_key     = wdpbu_normalize_filename_base( $original_name );
		$title         = wdpbu_filename_to_title( $original_name );

		$result = array(
			'success'        => false,
			'product_id'     => 0,
			'title'          => $title,
			'file_name'      => $original_name,
			'image_name'     => '',
			'message'        => '',
			'is_duplicate'   => false,
			'image_matched'  => false,
			'image_match_key'=> '',
		);

		if ( '' === $original_name ) {
			$result['message'] = __( 'Missing product file name.', 'eskaptical-digital-product-uploader-for-woocommerce' );
			return $result;
		}

		$existing_product_id = wdpbu_find_existing_product_id_by_title( $title );

		if ( $existing_product_id > 0 ) {
			$result['is_duplicate'] = true;
			$result['message']      = sprintf(
				/* translators: %d: existing product ID */
				__( 'Skipped duplicate title. Product already exists with ID %d.', 'eskaptical-digital-product-uploader-for-woocommerce' ),
				$existing_product_id
			);
			return $result;
		}

		$file_upload = wdpbu_handle_single_upload( $product_file, wdpbu_get_allowed_product_mimes() );

		if ( is_wp_error( $file_upload ) ) {
			$result['message'] = $file_upload->get_error_message();
			return $result;
		}

		$status = ( isset( $settings['status'] ) && 'draft' === $settings['status'] ) ? 'draft' : 'publish';

		$product = new WC_Product_Simple();
		$product->set_name( $title );
		$product->set_status( $status );
		$product->set_catalog_visibility( 'visible' );
		$product->set_description( isset( $settings['description'] ) ? wp_kses_post( $settings['description'] ) : '' );
		$product->set_regular_price( isset( $settings['price'] ) ? wc_format_decimal( $settings['price'] ) : '' );
		$product->set_virtual( true );
		$product->set_downloadable( true );

		if ( ! empty( $settings['category_id'] ) ) {
			$product->set_category_ids( array( absint( $settings['category_id'] ) ) );
		}

		$download = new WC_Product_Download();
		$download_id = md5( $file_upload['file'] . '|' . $original_name . '|' . microtime( true ) );
		$download->set_id( $download_id );
		$download->set_name( sanitize_file_name( wp_basename( $original_name ) ) );
		$download->set_file( esc_url_raw( $file_upload['url'] ) );
		$product->set_downloads( array( $download_id => $download ) );

		$product_id = $product->save();

		if ( ! $product_id ) {
			$result['message'] = __( 'Could not create product.', 'eskaptical-digital-product-uploader-for-woocommerce' );
			return $result;
		}

		$result['product_id'] = (int) $product_id;

		$file_attachment_id = wdpbu_create_attachment_from_uploaded_file(
			$file_upload['file'],
			$file_upload['url'],
			$product_id,
			isset( $file_upload['type'] ) ? $file_upload['type'] : ''
		);

		if ( ! is_wp_error( $file_attachment_id ) ) {
			update_post_meta( $product_id, '_wdpbu_download_attachment_id', (int) $file_attachment_id );
		}

		if ( '' !== $match_key && isset( $image_map[ $match_key ] ) ) {
			$image_file = $image_map[ $match_key ];

			$result['image_name']      = isset( $image_file['name'] ) ? $image_file['name'] : '';
			$result['image_matched']   = true;
			$result['image_match_key'] = $match_key;

			$image_upload = wdpbu_handle_single_upload(
				$image_file,
				wdpbu_get_allowed_image_mimes()
			);

			if ( ! is_wp_error( $image_upload ) ) {
				$image_attachment_id = wdpbu_create_attachment_from_uploaded_file(
					$image_upload['file'],
					$image_upload['url'],
					$product_id,
					isset( $image_upload['type'] ) ? $image_upload['type'] : ''
				);

				if ( ! is_wp_error( $image_attachment_id ) ) {
					set_post_thumbnail( $product_id, $image_attachment_id );
				}
			}
		}

		$result['success'] = true;
		$result['message'] = sprintf(
			/* translators: %s: product status */
			__( 'Product created successfully with status: %s.', 'eskaptical-digital-product-uploader-for-woocommerce' ),
			$status
		);

		return $result;
	}
}