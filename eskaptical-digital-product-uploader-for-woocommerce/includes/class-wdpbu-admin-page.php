<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WDPBU_Admin_Page {

	/**
	 * Stored import results for current request.
	 *
	 * @var array
	 */
	protected $results = array();

	/**
	 * Menu hook suffix.
	 *
	 * @var string
	 */
	protected $page_hook = '';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_init', array( $this, 'handle_form_submission' ) );
	}

	public function register_menu() {
		$this->page_hook = add_submenu_page(
			'woocommerce',
			__( 'Eskaptical Digital Product Uploader', 'eskaptical-digital-product-uploader-for-woocommerce' ),
			__( 'Bulk Uploader', 'eskaptical-digital-product-uploader-for-woocommerce' ),
			'manage_woocommerce',
			'wdpbu-bulk-uploader',
			array( $this, 'render_page' )
		);
	}

	public function enqueue_assets( $hook ) {
		if ( $hook !== $this->page_hook ) {
			return;
		}

		wp_enqueue_style(
			'wdpbu-admin',
			WDPBU_URL . 'assets/css/admin.css',
			array(),
			WDPBU_VERSION
		);

		wp_enqueue_script(
			'wdpbu-admin',
			WDPBU_URL . 'assets/js/admin.js',
			array(),
			WDPBU_VERSION,
			true
		);
	}

	public function handle_form_submission() {
		if ( ! is_admin() ) {
			return;
		}

		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( 'wdpbu-bulk-uploader' !== $page ) {
			return;
		}

		if ( ! isset( $_POST['wdpbu_submit_import'], $_POST['wdpbu_import_nonce'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wdpbu_import_nonce'] ) ), 'wdpbu_import_action' ) ) {
			return;
		}

		$product_files = wdpbu_get_uploaded_files_from_request( 'wdpbu_product_files' );
		$image_files   = wdpbu_get_uploaded_files_from_request( 'wdpbu_product_images' );

		$price       = isset( $_POST['wdpbu_price'] ) ? sanitize_text_field( wp_unslash( $_POST['wdpbu_price'] ) ) : '';
		$category_id = isset( $_POST['wdpbu_category_id'] ) ? absint( wp_unslash( $_POST['wdpbu_category_id'] ) ) : 0;
		$description = isset( $_POST['wdpbu_description'] ) ? wp_kses_post( wp_unslash( $_POST['wdpbu_description'] ) ) : '';
		$status      = isset( $_POST['wdpbu_status'] ) ? sanitize_key( wp_unslash( $_POST['wdpbu_status'] ) ) : 'publish';

		if ( ! in_array( $status, array( 'publish', 'draft' ), true ) ) {
			$status = 'publish';
		}

		if ( empty( $product_files ) ) {
			$this->results = array(
				'created'                  => 0,
				'failed'                   => 1,
				'uploaded_files_count'     => 0,
				'uploaded_images_count'    => count( $image_files ),
				'matched_images_count'     => 0,
				'unmatched_images_count'   => 0,
				'skipped_duplicates_count' => 0,
				'items'                    => array(
					array(
						'success'    => false,
						'product_id' => 0,
						'title'      => '',
						'file_name'  => '',
						'image_name' => '',
						'message'    => __( 'Please upload at least one product file.', 'eskaptical-digital-product-uploader-for-woocommerce' ),
					),
				),
			);
			return;
		}

		$settings = array(
			'price'       => $price,
			'category_id' => $category_id,
			'description' => $description,
			'status'      => $status,
		);

		$importer      = new WDPBU_Importer();
		$this->results = $importer->import( $product_files, $image_files, $settings );
	}

	/**
	 * Get active admin tab.
	 *
	 * @return string
	 */
	protected function get_active_tab() {
		$tab = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$tab = sanitize_key( $tab );

		if ( ! in_array( $tab, array( 'overview', 'info' ), true ) ) {
			$tab = 'overview';
		}

		return $tab;
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$active_tab = $this->get_active_tab();
		?>
		<div class="wrap wdpbu-wrap">
			<h1><?php echo esc_html__( 'Eskaptical Digital Product Uploader for WooCommerce', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?></h1>

			<h2 class="nav-tab-wrapper">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wdpbu-bulk-uploader&tab=overview' ) ); ?>" class="nav-tab <?php echo 'overview' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php echo esc_html__( 'Overview', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wdpbu-bulk-uploader&tab=info' ) ); ?>" class="nav-tab <?php echo 'info' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php echo esc_html__( 'Info', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?>
				</a>
			</h2>

			<?php if ( 'info' === $active_tab ) : ?>
				<?php $this->render_info_tab(); ?>
			<?php else : ?>
				<?php $this->render_overview_tab(); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	protected function render_overview_tab() {
		?>
		<p class="wdpbu-intro">
			<?php echo esc_html__( 'Upload downloadable product files and optional matching images. Products will be created automatically using the file names.', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?>
		</p>

		<div class="wdpbu-card">
			<form method="post" enctype="multipart/form-data" class="wdpbu-form">
				<?php wp_nonce_field( 'wdpbu_import_action', 'wdpbu_import_nonce' ); ?>

				<div class="wdpbu-grid">
					<div class="wdpbu-field">
						<label for="wdpbu_product_files">
							<?php echo esc_html__( 'Product Files', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?>
						</label>
						<input
							type="file"
							id="wdpbu_product_files"
							name="wdpbu_product_files[]"
							multiple
							required
							accept=".pdf,.zip,.epub,.txt,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.mp3,.m4a,.wav,.mp4,.mov,.csv,.rtf,.json"
						/>
						<p class="description">
							<?php echo esc_html__( 'Allowed file types: PDF, ZIP, EPUB, TXT, DOC, DOCX, XLS, XLSX, PPT, PPTX, MP3, M4A, WAV, MP4, MOV, CSV, RTF, JSON.', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?>
						</p>
					</div>

					<div class="wdpbu-field">
						<label for="wdpbu_product_images">
							<?php echo esc_html__( 'Product Images', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?>
						</label>
						<input type="file" id="wdpbu_product_images" name="wdpbu_product_images[]" multiple accept=".jpg,.jpeg,.png,.gif,.webp" />
						<p class="description">
							<?php echo esc_html__( 'Supported image types: JPG, JPEG, PNG, GIF, WEBP. Images are matched to product files by base filename. WEBP support may depend on your WordPress/server configuration.', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?>
						</p>
					</div>

					<div class="wdpbu-field">
						<label for="wdpbu_price">
							<?php echo esc_html__( 'Price', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?>
						</label>
						<input type="text" id="wdpbu_price" name="wdpbu_price" value="9.99" placeholder="9.99" />
						<p class="description">
							<?php echo esc_html__( 'One shared regular price for all products.', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?>
						</p>
					</div>

					<div class="wdpbu-field">
						<label for="wdpbu_category_id">
							<?php echo esc_html__( 'Category', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?>
						</label>
						<?php
						wp_dropdown_categories(
							array(
								'taxonomy'          => 'product_cat',
								'name'              => 'wdpbu_category_id',
								'id'                => 'wdpbu_category_id',
								'show_option_none'  => __( 'No category', 'eskaptical-digital-product-uploader-for-woocommerce' ),
								'option_none_value' => '0',
								'hide_empty'        => false,
								'hierarchical'      => true,
								'value_field'       => 'term_id',
							)
						);
						?>
						<p class="description">
							<?php echo esc_html__( 'Optional shared category for all imported products.', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?>
						</p>
					</div>

					<div class="wdpbu-field">
						<label for="wdpbu_status">
							<?php echo esc_html__( 'Product Status', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?>
						</label>
						<select id="wdpbu_status" name="wdpbu_status">
							<option value="publish"><?php echo esc_html__( 'Publish', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?></option>
							<option value="draft"><?php echo esc_html__( 'Draft', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?></option>
						</select>
						<p class="description">
							<?php echo esc_html__( 'Choose whether imported products should be published immediately or saved as drafts.', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?>
						</p>
					</div>

					<div class="wdpbu-field wdpbu-field-full">
						<label for="wdpbu_description">
							<?php echo esc_html__( 'Description', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?>
						</label>
						<textarea id="wdpbu_description" name="wdpbu_description" rows="8"></textarea>
						<p class="description">
							<?php echo esc_html__( 'Optional shared description for all imported products.', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?>
						</p>
					</div>
				</div>

				<div class="wdpbu-actions">
					<button type="submit" name="wdpbu_submit_import" class="button button-primary">
						<?php echo esc_html__( 'Import Products', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?>
					</button>
				</div>
			</form>
		</div>

		<?php $this->render_results(); ?>
		<?php
	}

	protected function render_info_tab() {
		?>
		<div class="wdpbu-card">
			<h2><?php echo esc_html__( 'How to Use', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?></h2>

			<p><?php echo esc_html__( 'This plugin creates downloadable WooCommerce products in bulk from uploaded files.', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?></p>

			<h3><?php echo esc_html__( 'Basic Steps', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?></h3>
			<ol>
				<li><?php echo esc_html__( 'Upload one or more product files.', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?></li>
				<li><?php echo esc_html__( 'Optionally upload matching product images.', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?></li>
				<li><?php echo esc_html__( 'Set one shared price, category, product status, and description.', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?></li>
				<li><?php echo esc_html__( 'Click Import Products.', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?></li>
			</ol>

			<h3><?php echo esc_html__( 'Filename Matching', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?></h3>
			<p><?php echo esc_html__( 'Images are matched to files by base filename, not by upload order.', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?></p>

			<p><strong><?php echo esc_html__( 'Example:', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?></strong></p>
			<ul>
				<li><code>my-book.pdf</code> + <code>my-book.jpg</code></li>
				<li><code>summer-pack.zip</code> + <code>summer-pack.png</code></li>
			</ul>

			<h3><?php echo esc_html__( 'Duplicate Titles', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?></h3>
			<p><?php echo esc_html__( 'If a product with the same cleaned title already exists, the plugin skips it to avoid duplicates.', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?></p>

			<h3><?php echo esc_html__( 'Supported Product File Types', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?></h3>
			<p><code>PDF, ZIP, EPUB, TXT, DOC, DOCX, XLS, XLSX, PPT, PPTX, MP3, M4A, WAV, MP4, MOV, CSV, RTF, JSON</code></p>

			<h3><?php echo esc_html__( 'Supported Image Types', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?></h3>
			<p><code>JPG, JPEG, PNG, GIF, WEBP</code></p>
			<p><?php echo esc_html__( 'WEBP support may depend on your WordPress and server configuration.', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?></p>

			<h3><?php echo esc_html__( 'Tips', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?></h3>
			<ul>
				<li><?php echo esc_html__( 'Use clear filenames before upload for cleaner product titles.', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?></li>
				<li><?php echo esc_html__( 'Use matching file and image names for automatic thumbnail assignment.', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?></li>
				<li><?php echo esc_html__( 'Choose Draft if you want to review products before publishing.', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?></li>
			</ul>
		</div>
		<?php
	}

	protected function render_results() {
		if ( empty( $this->results ) || ! is_array( $this->results ) ) {
			return;
		}

		$created                  = isset( $this->results['created'] ) ? absint( $this->results['created'] ) : 0;
		$failed                   = isset( $this->results['failed'] ) ? absint( $this->results['failed'] ) : 0;
		$uploaded_files_count     = isset( $this->results['uploaded_files_count'] ) ? absint( $this->results['uploaded_files_count'] ) : 0;
		$uploaded_images_count    = isset( $this->results['uploaded_images_count'] ) ? absint( $this->results['uploaded_images_count'] ) : 0;
		$matched_images_count     = isset( $this->results['matched_images_count'] ) ? absint( $this->results['matched_images_count'] ) : 0;
		$unmatched_images_count   = isset( $this->results['unmatched_images_count'] ) ? absint( $this->results['unmatched_images_count'] ) : 0;
		$skipped_duplicates_count = isset( $this->results['skipped_duplicates_count'] ) ? absint( $this->results['skipped_duplicates_count'] ) : 0;
		$items                    = isset( $this->results['items'] ) && is_array( $this->results['items'] ) ? $this->results['items'] : array();

		$notice_class = $created > 0 && 0 === $failed ? 'notice-success' : 'notice-warning';
		?>
		<div class="notice <?php echo esc_attr( $notice_class ); ?> is-dismissible wdpbu-results-notice">
			<p>
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: created count, 2: failed count */
						__( 'Import finished. Created: %1$d. Failed: %2$d.', 'eskaptical-digital-product-uploader-for-woocommerce' ),
						$created,
						$failed
					)
				);
				?>
			</p>
		</div>

		<div class="wdpbu-card">
			<h2><?php echo esc_html__( 'Import Summary', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?></h2>

			<div class="wdpbu-summary-grid">
				<div class="wdpbu-summary-item">
					<span class="wdpbu-summary-number"><?php echo esc_html( $uploaded_files_count ); ?></span>
					<span class="wdpbu-summary-label"><?php echo esc_html__( 'Uploaded Product Files', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?></span>
				</div>

				<div class="wdpbu-summary-item">
					<span class="wdpbu-summary-number"><?php echo esc_html( $uploaded_images_count ); ?></span>
					<span class="wdpbu-summary-label"><?php echo esc_html__( 'Uploaded Images', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?></span>
				</div>

				<div class="wdpbu-summary-item">
					<span class="wdpbu-summary-number"><?php echo esc_html( $matched_images_count ); ?></span>
					<span class="wdpbu-summary-label"><?php echo esc_html__( 'Matched Images', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?></span>
				</div>

				<div class="wdpbu-summary-item">
					<span class="wdpbu-summary-number"><?php echo esc_html( $unmatched_images_count ); ?></span>
					<span class="wdpbu-summary-label"><?php echo esc_html__( 'Unmatched Images', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?></span>
				</div>

				<div class="wdpbu-summary-item">
					<span class="wdpbu-summary-number"><?php echo esc_html( $skipped_duplicates_count ); ?></span>
					<span class="wdpbu-summary-label"><?php echo esc_html__( 'Skipped Duplicates', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?></span>
				</div>

				<div class="wdpbu-summary-item">
					<span class="wdpbu-summary-number"><?php echo esc_html( $created ); ?></span>
					<span class="wdpbu-summary-label"><?php echo esc_html__( 'Created Products', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?></span>
				</div>
			</div>
		</div>

		<div class="wdpbu-card">
			<h2><?php echo esc_html__( 'Import Results', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?></h2>

			<div class="wdpbu-table-wrap">
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php echo esc_html__( 'Status', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?></th>
							<th><?php echo esc_html__( 'Title', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?></th>
							<th><?php echo esc_html__( 'Product File', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?></th>
							<th><?php echo esc_html__( 'Matched Image', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?></th>
							<th><?php echo esc_html__( 'Message', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $items as $item ) : ?>
							<tr>
								<td><?php echo ! empty( $item['success'] ) ? esc_html__( 'Success', 'eskaptical-digital-product-uploader-for-woocommerce' ) : esc_html__( 'Failed', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?></td>
								<td><?php echo isset( $item['title'] ) ? esc_html( $item['title'] ) : ''; ?></td>
								<td><?php echo isset( $item['file_name'] ) ? esc_html( $item['file_name'] ) : ''; ?></td>
								<td><?php echo ! empty( $item['image_name'] ) ? esc_html( $item['image_name'] ) : esc_html__( 'No match', 'eskaptical-digital-product-uploader-for-woocommerce' ); ?></td>
								<td><?php echo isset( $item['message'] ) ? esc_html( $item['message'] ) : ''; ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}
}