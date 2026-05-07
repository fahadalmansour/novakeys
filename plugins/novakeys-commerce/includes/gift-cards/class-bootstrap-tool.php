<?php
/**
 * Gift Cards Bootstrap admin tool.
 *
 * Tools → NovaKeys Gift Cards · Bootstrap. One-click seeder that
 * auto-creates a draft WooCommerce product per gift-card brand whose
 * webp ships under `neogen-theme-assets/img/gift-cards/`. Idempotent —
 * skips by SKU `gc-<slot>` and never overwrites existing products.
 *
 * Per the project contract, draft only — operator sets prices and
 * publishes manually. Postmeta `_ng_gift_card_brand` and `_ng_gc_regions`
 * are written verbatim (live data contract).
 *
 * Admin page slug stays `neogen-gift-cards-bootstrap` so any operator
 * bookmark or sysbar link keeps resolving.
 *
 * Sideload cache option keys stay `ng_gc_attach_id_<slot>` so re-runs
 * after the rename don't re-sideload existing artwork (which would
 * create duplicate attachments).
 *
 * @package NovaKeys\Commerce\Gift_Cards
 * @since   0.1.0
 */

namespace NovaKeys\Commerce\Gift_Cards;

defined( 'ABSPATH' ) || exit;

/**
 * Admin bootstrap tool.
 *
 * @since 0.1.0
 */
final class Bootstrap_Tool {

	private const PAGE_SLUG     = 'neogen-gift-cards-bootstrap'; // URL contract — do not rename.
	private const NONCE_ACTION  = 'nk_gc_bootstrap_run';
	private const NONCE_FIELD   = 'nk_gc_bootstrap_nonce';
	private const REQUIRED_CAP  = 'manage_woocommerce';

	/**
	 * Wire the admin-menu hook.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function register(): void {
		if ( function_exists( 'ng_gift_card_bootstrap_render' ) ) {
			return; // Legacy mu-plugin still loaded.
		}
		add_action( 'admin_menu', array( __CLASS__, 'register_page' ) );
	}

	/**
	 * Register the Tools page.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function register_page(): void {
		add_management_page(
			__( 'NovaKeys Gift Cards · Bootstrap', 'novakeys-commerce' ),
			__( 'NovaKeys Gift Cards', 'novakeys-commerce' ),
			self::REQUIRED_CAP,
			self::PAGE_SLUG,
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * Slot → title map. Slot keys must match the gift-card asset map.
	 *
	 * @since 0.1.0
	 * @return array<string, array{en: string, ar: string}>
	 */
	public static function titles(): array {
		return array(
			'apple'       => array( 'en' => 'Apple Gift Card',           'ar' => 'بطاقة آبل' ),
			'google-play' => array( 'en' => 'Google Play Gift Card',     'ar' => 'بطاقة قوقل بلاي' ),
			'playstation' => array( 'en' => 'PlayStation Gift Card',     'ar' => 'بطاقة بلايستيشن' ),
			'steam'       => array( 'en' => 'Steam Gift Card',           'ar' => 'بطاقة ستيم' ),
			'xbox'        => array( 'en' => 'Xbox Gift Card',            'ar' => 'بطاقة إكس بوكس' ),
			'amazon'      => array( 'en' => 'Amazon Gift Card',          'ar' => 'بطاقة أمازون' ),
			'kaspersky'   => array( 'en' => 'Kaspersky Subscription',    'ar' => 'اشتراك كاسبرسكي' ),
			'adobe'       => array( 'en' => 'Adobe Gift Card',           'ar' => 'بطاقة أدوبي' ),
			'office'      => array( 'en' => 'Microsoft Office Key',      'ar' => 'مفتاح أوفيس' ),
			'windows'     => array( 'en' => 'Microsoft Windows Key',     'ar' => 'مفتاح ويندوز' ),
			'youtube'     => array( 'en' => 'YouTube Premium Gift Card', 'ar' => 'بطاقة يوتيوب بريميوم' ),
		);
	}

	/**
	 * Idempotently ensure the `gift-cards` product_cat term exists.
	 *
	 * @since 0.1.0
	 * @return \WP_Term|null
	 */
	public static function ensure_category(): ?\WP_Term {
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return null;
		}

		$term = get_term_by( 'slug', 'gift-cards', 'product_cat' );
		if ( $term instanceof \WP_Term ) {
			return $term;
		}

		$result = wp_insert_term(
			'بطاقات هدايا',
			'product_cat',
			array(
				'slug'        => 'gift-cards',
				'description' => 'بطاقات هدايا رقمية · شحن فوري بالبريد الإلكتروني · ضمان رسمي.',
			)
		);
		if ( is_wp_error( $result ) ) {
			return null;
		}
		$term = get_term( (int) $result['term_id'], 'product_cat' );
		return $term instanceof \WP_Term ? $term : null;
	}

	/**
	 * Sideload a webp from the in-repo gift-cards directory into
	 * `wp-content/uploads/` and register a wp_attachment.
	 *
	 * Caches the resulting attachment ID under
	 * `ng_gc_attach_id_<slot>` so re-runs are O(1) and don't create
	 * duplicates. Returns the attachment ID or a WP_Error.
	 *
	 * @since 0.1.0
	 *
	 * @param string $slot     Slot key (e.g. `apple`).
	 * @param string $src_path Source webp absolute path.
	 * @param string $title    Attachment post_title.
	 * @return int|\WP_Error
	 */
	public static function sideload( string $slot, string $src_path, string $title ) {
		if ( ! file_exists( $src_path ) ) {
			return new \WP_Error( 'no_file', 'Source webp missing: ' . $src_path );
		}

		$cache_key = 'ng_gc_attach_id_' . $slot;
		$cached    = (int) get_option( $cache_key, 0 );
		if ( $cached > 0 && 'inherit' === get_post_status( $cached ) ) {
			return $cached;
		}

		$bytes = file_get_contents( $src_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local file read, not remote.
		if ( false === $bytes ) {
			return new \WP_Error( 'read_failed', 'Could not read ' . $src_path );
		}

		$filename = 'gift-card-' . sanitize_file_name( basename( $src_path ) );
		$upload   = wp_upload_bits( $filename, null, $bytes );
		if ( ! empty( $upload['error'] ) ) {
			return new \WP_Error( 'upload_failed', (string) $upload['error'] );
		}

		$filetype   = wp_check_filetype( $upload['file'] );
		$attachment = array(
			'guid'           => $upload['url'],
			'post_mime_type' => $filetype['type'] ?: 'image/webp',
			'post_title'     => $title,
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		$attach_id = wp_insert_attachment( $attachment, $upload['file'] );
		if ( is_wp_error( $attach_id ) ) {
			return $attach_id;
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		$metadata = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
		wp_update_attachment_metadata( $attach_id, $metadata );

		update_option( $cache_key, (int) $attach_id, false );
		return (int) $attach_id;
	}

	/**
	 * Run the bootstrap. Returns a structured report.
	 *
	 * @since 0.1.0
	 * @return array<string, mixed>
	 */
	public static function run(): array {
		if ( ! class_exists( 'WC_Product_Simple' ) || ! function_exists( 'wc_get_product_id_by_sku' ) ) {
			return array( 'error' => 'WooCommerce is not active. Activate it before running this tool.' );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$cat = self::ensure_category();
		if ( ! $cat instanceof \WP_Term ) {
			return array( 'error' => 'Could not create or find the Gift Cards product_cat term.' );
		}

		$report = array(
			'created' => array(),
			'skipped' => array(),
			'errors'  => array(),
		);

		$titles = self::titles();
		$map    = function_exists( 'nk_gift_card_asset_map' )
			? nk_gift_card_asset_map()
			: ( function_exists( 'ng_gift_card_asset_map' ) ? ng_gift_card_asset_map() : array() );

		foreach ( $titles as $slot => $names ) {
			$sku         = 'gc-' . $slot;
			$existing_id = (int) wc_get_product_id_by_sku( $sku );

			if ( $existing_id > 0 ) {
				$report['skipped'][] = array(
					'slot'       => $slot,
					'sku'        => $sku,
					'product_id' => $existing_id,
					'reason'     => 'sku exists',
				);
				continue;
			}

			if ( empty( $map[ $slot ] ) ) {
				$report['errors'][] = array(
					'slot'   => $slot,
					'sku'    => $sku,
					'reason' => 'slot not in asset map',
				);
				continue;
			}

			$file = function_exists( 'nk_gift_card_existing_file' )
				? nk_gift_card_existing_file( $map[ $slot ] )
				: ( function_exists( 'ng_gift_card_existing_file' ) ? ng_gift_card_existing_file( $map[ $slot ] ) : '' );

			if ( '' === $file ) {
				$report['errors'][] = array(
					'slot'   => $slot,
					'sku'    => $sku,
					'reason' => 'no webp on disk',
				);
				continue;
			}

			$asset_dir = function_exists( 'nk_gift_card_asset_dir' )
				? nk_gift_card_asset_dir()
				: ( function_exists( 'ng_gift_card_asset_dir' ) ? ng_gift_card_asset_dir() : '' );
			$src_path  = $asset_dir . '/' . $file;
			$attach_id = self::sideload( $slot, $src_path, $names['en'] );
			if ( is_wp_error( $attach_id ) ) {
				$report['errors'][] = array(
					'slot'   => $slot,
					'sku'    => $sku,
					'reason' => $attach_id->get_error_message(),
				);
				continue;
			}

			try {
				$product = new \WC_Product_Simple();
				$product->set_name( $names['ar'] . ' · ' . $names['en'] );
				$product->set_slug( $slot . '-gift-card' );
				$product->set_status( 'draft' );
				$product->set_sku( $sku );
				$product->set_virtual( true );
				$product->set_manage_stock( false );
				$product->set_stock_status( 'instock' );
				$product->set_short_description(
					sprintf(
						/* translators: %s: Arabic brand name. */
						'بطاقة هدايا %s · يتم إرسال الكود فور إتمام الطلب.',
						$names['ar']
					)
				);
				$product->set_description(
					sprintf(
						/* translators: 1: Arabic brand name. 2: English brand name. */
						"بطاقة %1\$s الرقمية · شحن فوري على البريد الإلكتروني · ضمان رسمي.\n\n%2\$s digital gift card · code delivered to your inbox right after checkout · official manufacturer warranty.",
						$names['ar'],
						$names['en']
					)
				);
				$product->set_image_id( (int) $attach_id );
				$product->set_category_ids( array( (int) $cat->term_id ) );

				// WC CRUD writes — no raw post-meta on products.
				$product->update_meta_data( '_ng_gift_card_brand', $slot );

				// Region seed (Apple is the canonical multi-region case). Idempotent
				// per-product — never overwrites if already set.
				$region_seeds = array(
					'apple' => array( 'SA', 'AE', 'BH', 'OM', 'QA', 'KW', 'US', 'GB' ),
				);
				if ( isset( $region_seeds[ $slot ] ) && '' === (string) $product->get_meta( '_ng_gc_regions', true ) ) {
					$product->update_meta_data( '_ng_gc_regions', $region_seeds[ $slot ] );
				}

				$product_id = (int) $product->save();

				$report['created'][] = array(
					'slot'       => $slot,
					'sku'        => $sku,
					'product_id' => $product_id,
					'attach_id'  => (int) $attach_id,
				);
			} catch ( \Throwable $e ) {
				$report['errors'][] = array(
					'slot'   => $slot,
					'sku'    => $sku,
					'reason' => $e->getMessage(),
				);
			}
		}

		return $report;
	}

	/**
	 * Render the Tools page.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function render(): void {
		if ( ! current_user_can( self::REQUIRED_CAP ) ) {
			wp_die( esc_html__( 'forbidden', 'novakeys-commerce' ) );
		}

		$report = null;
		if ( isset( $_POST[ self::NONCE_FIELD ] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) );
			if ( wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
				$report = self::run();
			}
		}

		$titles = self::titles();
		$map    = function_exists( 'nk_gift_card_asset_map' )
			? nk_gift_card_asset_map()
			: ( function_exists( 'ng_gift_card_asset_map' ) ? ng_gift_card_asset_map() : array() );
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'NovaKeys Gift Cards · Bootstrap', 'novakeys-commerce' ); ?></h1>
			<p>
				<?php
				echo wp_kses(
					__( 'One-click tool that auto-creates a draft WooCommerce product for every brand whose webp ships under <code>neogen-theme-assets/img/gift-cards/</code>. Each product is virtual, manage_stock = false, status = <strong>draft</strong>, with the matching webp sideloaded to <code>wp-content/uploads/</code> and set as featured image. The runtime matcher still serves the in-repo art on the storefront; this attachment is for the admin <em>Set Image</em> picker and any code that reads <code>attachment_id</code>.', 'novakeys-commerce' ),
					array(
						'code'   => array(),
						'strong' => array(),
						'em'     => array(),
					)
				);
				?>
			</p>
			<p>
				<?php
				echo wp_kses(
					__( '<strong>Idempotency:</strong> products are keyed by SKU <code>gc-&lt;slot&gt;</code>. Re-running the tool is safe — existing products are skipped, not modified.', 'novakeys-commerce' ),
					array( 'code' => array(), 'strong' => array() )
				);
				?>
			</p>

			<?php if ( is_array( $report ) && isset( $report['error'] ) ) : ?>
				<div class="notice notice-error is-dismissible"><p><strong><?php echo esc_html__( 'Error:', 'novakeys-commerce' ); ?></strong> <?php echo esc_html( $report['error'] ); ?></p></div>
			<?php elseif ( is_array( $report ) ) : ?>
				<?php
				$c = count( $report['created'] );
				$s = count( $report['skipped'] );
				$e = count( $report['errors'] );
				?>
				<div class="notice notice-<?php echo $e > 0 ? 'warning' : 'success'; ?> is-dismissible">
					<p><strong><?php echo esc_html__( 'Run complete:', 'novakeys-commerce' ); ?></strong>
					<?php
					echo esc_html(
						sprintf(
							/* translators: 1: created count. 2: skipped count. 3: error count. */
							__( '%1$d created · %2$d skipped · %3$d errors.', 'novakeys-commerce' ),
							$c,
							$s,
							$e
						)
					);
					?>
					</p>
				</div>

				<?php if ( $c > 0 ) : ?>
					<h2 style="margin-top:1.5em;"><?php echo esc_html__( 'Created', 'novakeys-commerce' ); ?></h2>
					<table class="widefat striped" style="max-width:760px;">
						<thead><tr>
							<th><?php echo esc_html__( 'Slot', 'novakeys-commerce' ); ?></th>
							<th><?php echo esc_html__( 'SKU', 'novakeys-commerce' ); ?></th>
							<th><?php echo esc_html__( 'Product', 'novakeys-commerce' ); ?></th>
							<th><?php echo esc_html__( 'Attachment', 'novakeys-commerce' ); ?></th>
						</tr></thead>
						<tbody>
							<?php foreach ( $report['created'] as $row ) : ?>
								<tr>
									<td><code><?php echo esc_html( $row['slot'] ); ?></code></td>
									<td><code><?php echo esc_html( $row['sku'] ); ?></code></td>
									<td><a href="<?php echo esc_url( admin_url( 'post.php?post=' . $row['product_id'] . '&action=edit' ) ); ?>" target="_blank">#<?php echo (int) $row['product_id']; ?> ·  edit</a></td>
									<td><a href="<?php echo esc_url( admin_url( 'upload.php?item=' . $row['attach_id'] ) ); ?>" target="_blank">#<?php echo (int) $row['attach_id']; ?></a></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>

				<?php if ( $s > 0 ) : ?>
					<h2 style="margin-top:1.5em;"><?php echo esc_html__( 'Skipped', 'novakeys-commerce' ); ?></h2>
					<table class="widefat striped" style="max-width:760px;">
						<thead><tr>
							<th><?php echo esc_html__( 'Slot', 'novakeys-commerce' ); ?></th>
							<th><?php echo esc_html__( 'SKU', 'novakeys-commerce' ); ?></th>
							<th><?php echo esc_html__( 'Existing product', 'novakeys-commerce' ); ?></th>
							<th><?php echo esc_html__( 'Reason', 'novakeys-commerce' ); ?></th>
						</tr></thead>
						<tbody>
							<?php foreach ( $report['skipped'] as $row ) : ?>
								<tr>
									<td><code><?php echo esc_html( $row['slot'] ); ?></code></td>
									<td><code><?php echo esc_html( $row['sku'] ); ?></code></td>
									<td><a href="<?php echo esc_url( admin_url( 'post.php?post=' . $row['product_id'] . '&action=edit' ) ); ?>" target="_blank">#<?php echo (int) $row['product_id']; ?></a></td>
									<td><?php echo esc_html( $row['reason'] ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>

				<?php if ( $e > 0 ) : ?>
					<h2 style="margin-top:1.5em;"><?php echo esc_html__( 'Errors', 'novakeys-commerce' ); ?></h2>
					<table class="widefat striped" style="max-width:760px;">
						<thead><tr>
							<th><?php echo esc_html__( 'Slot', 'novakeys-commerce' ); ?></th>
							<th><?php echo esc_html__( 'SKU', 'novakeys-commerce' ); ?></th>
							<th><?php echo esc_html__( 'Reason', 'novakeys-commerce' ); ?></th>
						</tr></thead>
						<tbody>
							<?php foreach ( $report['errors'] as $row ) : ?>
								<tr>
									<td><code><?php echo esc_html( $row['slot'] ); ?></code></td>
									<td><code><?php echo esc_html( $row['sku'] ); ?></code></td>
									<td><?php echo esc_html( $row['reason'] ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			<?php endif; ?>

			<h2 style="margin-top:1.5em;"><?php echo esc_html__( 'Slots that will run', 'novakeys-commerce' ); ?></h2>
			<table class="widefat striped" style="max-width:760px;">
				<thead><tr>
					<th><?php echo esc_html__( 'Slot', 'novakeys-commerce' ); ?></th>
					<th><?php echo esc_html__( 'Title (AR · EN)', 'novakeys-commerce' ); ?></th>
					<th><?php echo esc_html__( 'Webp', 'novakeys-commerce' ); ?></th>
					<th><?php echo esc_html__( 'SKU', 'novakeys-commerce' ); ?></th>
				</tr></thead>
				<tbody>
					<?php
					foreach ( $titles as $slot => $names ) :
						$file = '';
						if ( ! empty( $map[ $slot ] ) ) {
							if ( function_exists( 'nk_gift_card_existing_file' ) ) {
								$file = nk_gift_card_existing_file( $map[ $slot ] );
							} elseif ( function_exists( 'ng_gift_card_existing_file' ) ) {
								$file = ng_gift_card_existing_file( $map[ $slot ] );
							}
						}
						$sku = 'gc-' . $slot;
						?>
						<tr>
							<td><code><?php echo esc_html( $slot ); ?></code></td>
							<td><?php echo esc_html( $names['ar'] . ' · ' . $names['en'] ); ?></td>
							<td><?php echo '' !== $file ? '<code>' . esc_html( $file ) . '</code>' : '<em>' . esc_html__( 'missing', 'novakeys-commerce' ) . '</em>'; ?></td>
							<td><code><?php echo esc_html( $sku ); ?></code></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<form method="post" style="margin-top:2em;">
				<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD ); ?>
				<p>
					<button type="submit" class="button button-primary"><?php echo esc_html__( 'Create draft products', 'novakeys-commerce' ); ?></button>
					<span style="margin-inline-start:.8em;color:#64748B;"><?php echo esc_html__( 'Idempotent · safe to re-run · creates drafts only.', 'novakeys-commerce' ); ?></span>
				</p>
			</form>

			<h2 style="margin-top:2em;"><?php echo esc_html__( 'After running', 'novakeys-commerce' ); ?></h2>
			<ol style="max-width:760px;">
				<li>
					<?php
					echo wp_kses(
						__( 'Open each draft product, set the <strong>regular price</strong> (and any variations / denominations).', 'novakeys-commerce' ),
						array( 'strong' => array() )
					);
					?>
				</li>
				<li>
					<?php
					echo wp_kses(
						__( '(Optional) Set <strong>tax class</strong> if the digital VAT rule differs from the storefront default.', 'novakeys-commerce' ),
						array( 'strong' => array() )
					);
					?>
				</li>
				<li>
					<?php
					echo wp_kses(
						__( 'Verify the featured image renders. The runtime matcher will continue to serve the in-repo art on the storefront thanks to the <code>_ng_gift_card_brand</code> override meta this tool sets.', 'novakeys-commerce' ),
						array( 'code' => array() )
					);
					?>
				</li>
				<li>
					<?php
					echo wp_kses(
						__( 'Click <strong>Publish</strong> when ready.', 'novakeys-commerce' ),
						array( 'strong' => array() )
					);
					?>
				</li>
			</ol>

			<h2 style="margin-top:2em;"><?php echo esc_html__( 'Rollback', 'novakeys-commerce' ); ?></h2>
			<p>
				<?php
				echo wp_kses(
					__( 'Trash the drafts from Products admin (bulk action). Optionally delete the sideloaded attachments from Media Library and remove the <code>gift-cards</code> product_cat term. The webps in the repo are untouched and the runtime matcher remains active.', 'novakeys-commerce' ),
					array( 'code' => array() )
				);
				?>
			</p>
		</div>
		<?php
	}
}

Bootstrap_Tool::register();
