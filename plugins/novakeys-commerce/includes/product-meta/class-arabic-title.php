<?php
/**
 * Per-product Arabic-title meta box.
 *
 * Renders a side metabox on the WooCommerce product edit screen that
 * stores `_ng_ar_title` postmeta. Templates (homepage Operator Picks
 * card, shop loop card) read this meta to render Arabic-first product
 * titles alongside or instead of the canonical English name.
 *
 * The `_ng_ar_title` postmeta key is preserved verbatim — it backs
 * existing product data on the live install.
 *
 * Bulk import: column `Meta: _ng_ar_title` on the WC CSV.
 *
 * @package NovaKeys\Commerce\Product_Meta
 * @since   0.1.0
 */

namespace NovaKeys\Commerce\Product_Meta;

defined( 'ABSPATH' ) || exit;

/**
 * Arabic-title metabox.
 *
 * @since 0.1.0
 */
final class Arabic_Title {

	/**
	 * Postmeta key. Frozen — backs live product data.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	private const META_KEY = '_ng_ar_title';

	/**
	 * Nonce action.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	private const NONCE_ACTION = 'nk_product_ar_title_save';

	/**
	 * Nonce field name.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	private const NONCE_FIELD = 'nk_product_ar_title_nonce';

	/**
	 * POST input name.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	private const INPUT_NAME = 'nk_product_ar_title';

	/**
	 * Singleton instance.
	 *
	 * @since 0.1.0
	 * @var Arabic_Title|null
	 */
	private static ?Arabic_Title $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @since 0.1.0
	 * @return Arabic_Title
	 */
	public static function instance(): Arabic_Title {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register WP hooks.
	 *
	 * Guarded against double-registration when the legacy
	 * `mu-plugins/novakeys-product-meta.php` is still loaded on the
	 * server during a transitional deploy.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register_hooks(): void {
		if ( function_exists( 'ng_product_ar_title_meta_box' ) ) {
			return;
		}
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post_product', array( $this, 'save' ) );
	}

	/**
	 * Register the metabox.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function add_meta_box(): void {
		add_meta_box(
			'nk-product-ar-title',
			esc_html__( 'NovaKeys — العنوان العربي / Arabic title', 'novakeys-commerce' ),
			array( $this, 'render' ),
			'product',
			'side',
			'default'
		);
	}

	/**
	 * Render the metabox.
	 *
	 * @since 0.1.0
	 *
	 * @param \WP_Post $post Current post.
	 * @return void
	 */
	public function render( \WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );
		$value       = (string) get_post_meta( $post->ID, self::META_KEY, true );
		$placeholder = esc_attr__( 'مثال: لوحة مفاتيح ميكانيكية لاسلكية', 'novakeys-commerce' );
		?>
		<p>
			<label for="<?php echo esc_attr( self::INPUT_NAME ); ?>" style="display:block;font-weight:600;margin-bottom:4px;">
				<?php echo esc_html__( 'العنوان العربي / Arabic title', 'novakeys-commerce' ); ?>
			</label>
			<input type="text"
				id="<?php echo esc_attr( self::INPUT_NAME ); ?>"
				name="<?php echo esc_attr( self::INPUT_NAME ); ?>"
				value="<?php echo esc_attr( $value ); ?>"
				placeholder="<?php echo $placeholder; // Already escaped via esc_attr__. ?>"
				style="width:100%;direction:rtl;text-align:right;font-family:'Tajawal',sans-serif;">
			<span class="description" style="font-size:11px;color:#666;display:block;margin-top:4px;">
				<?php
				echo wp_kses(
					__( 'Shown on the homepage Operator Picks card and shop loop card. If empty, the product title is cleaned via <code>nk_ar_label()</code> instead.', 'novakeys-commerce' ),
					array( 'code' => array() )
				);
				?>
			</span>
		</p>
		<p style="font-size:11px;color:#666;margin:0;">
			<?php
			echo wp_kses(
				__( 'Bulk import: column <code>Meta: _ng_ar_title</code> on the WC CSV.', 'novakeys-commerce' ),
				array( 'code' => array() )
			);
			?>
		</p>
		<?php
	}

	/**
	 * Persist the Arabic title on product save.
	 *
	 * REST / block-editor saves don't carry the nonce — bail without
	 * touching meta so a Gutenberg save doesn't accidentally clear a
	 * value set elsewhere (CSV import, direct meta edit).
	 *
	 * @since 0.1.0
	 *
	 * @param int $post_id Product post ID.
	 * @return void
	 */
	public function save( int $post_id ): void {
		if ( ! isset( $_POST[ self::NONCE_FIELD ] ) ) {
			return;
		}
		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) );
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$value = isset( $_POST[ self::INPUT_NAME ] )
			? sanitize_text_field( wp_unslash( $_POST[ self::INPUT_NAME ] ) )
			: '';

		// Use WC CRUD when WooCommerce is active so writes go through the
		// product data store; falls back to core post-meta otherwise.
		if ( function_exists( 'wc_get_product' ) ) {
			$product = wc_get_product( $post_id );
			if ( $product ) {
				if ( '' === $value ) {
					$product->delete_meta_data( self::META_KEY );
				} else {
					$product->update_meta_data( self::META_KEY, $value );
				}
				$product->save();
				return;
			}
		}

		if ( '' === $value ) {
			delete_post_meta( $post_id, self::META_KEY );
		} else {
			update_post_meta( $post_id, self::META_KEY, $value );
		}
	}
}

Arabic_Title::instance()->register_hooks();
