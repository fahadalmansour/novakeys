<?php
/**
 * Cookie-based recently-viewed tracking + rule-based product recommendations.
 *
 * No ML, no third party. Cookie name `ng_recent` is preserved (data
 * contract — frontend / live customers may have it set already). Constant
 * names move to `NK_REC_*`; functions move to `nk_*` with `ng_*` shims.
 *
 * Strategy:
 *  a) Track up to 12 recently-viewed product IDs in `ng_recent` (HttpOnly,
 *     SameSite=Lax, 30-day TTL, server-set on `template_redirect`).
 *  b) Recommend popular products from the same product_cat as recent
 *     items, then top up with featured + latest.
 *  c) Render via Woo's `wc_get_template_part('content','product')` so the
 *     theme card design stays consistent with the rest of the catalogue.
 *  d) Auto-render on single-product pages via
 *     `woocommerce_after_single_product` priority 20.
 *  e) Shortcode for manual placement: `[nk_recommendations]` (canonical)
 *     and `[neogen_recommendations]` (legacy alias).
 *
 * Admin test mode: `?nk_simulate_recent=12,15,22` (or the legacy
 * `ng_simulate_recent`) on any page, gated by `manage_options`.
 *
 * @package NovaKeys\Commerce\Recommendations
 * @since   0.1.0
 */

namespace NovaKeys\Commerce\Recommendations;

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'NK_REC_COOKIE' ) ) {
	define( 'NK_REC_COOKIE', 'ng_recent' ); // Cookie value preserved verbatim.
}
if ( ! defined( 'NK_REC_MAX' ) ) {
	define( 'NK_REC_MAX', 12 );
}
if ( ! defined( 'NK_REC_TTL_DAYS' ) ) {
	define( 'NK_REC_TTL_DAYS', 30 );
}

/**
 * Recommendation engine + cookie tracker + renderer.
 *
 * @since 0.1.0
 */
final class Recommender {

	/**
	 * Singleton instance.
	 *
	 * @since 0.1.0
	 * @var Recommender|null
	 */
	private static ?Recommender $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @since 0.1.0
	 * @return Recommender
	 */
	public static function instance(): Recommender {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Wire all WP hooks.
	 *
	 * Guarded against double-registration with the legacy
	 * mu-plugins/novakeys-recommendations.php during transitional deploys.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register_hooks(): void {
		if ( function_exists( 'ng_render_recommendations' ) ) {
			return;
		}
		add_action( 'template_redirect', array( $this, 'track_view' ), 5 );
		add_action( 'woocommerce_after_single_product', array( $this, 'auto_render_strip' ), 20 );
		add_shortcode( 'nk_recommendations', array( $this, 'shortcode_handler' ) );
		add_shortcode( 'neogen_recommendations', array( $this, 'shortcode_handler' ) );
	}

	/**
	 * Track the current product as recently viewed.
	 *
	 * Server-set cookie. HttpOnly + SameSite=Lax. Skipped in admin or when
	 * not on a single product, or when headers are already sent.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function track_view(): void {
		if ( is_admin() ) {
			return;
		}
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}
		$id = (int) get_queried_object_id();
		if ( 0 === $id ) {
			return;
		}

		$existing = $this->read_cookie();
		$existing = array_values(
			array_filter(
				$existing,
				static function ( $x ) use ( $id ) {
					return (int) $x !== $id;
				}
			)
		);
		array_unshift( $existing, $id );
		$existing = array_slice( $existing, 0, NK_REC_MAX );

		// PDPL gate — `ng_recent` is a functional cookie. Skip the
		// write entirely when the visitor hasn't consented to
		// functional cookies (or hasn't decided yet — fail-closed).
		if ( class_exists( '\NovaKeys\Commerce\Consent\Cookie_Consent' )
			&& ! \NovaKeys\Commerce\Consent\Cookie_Consent::has( 'functional' )
		) {
			return;
		}
		$value = implode( ',', array_map( 'intval', $existing ) );
		if ( ! headers_sent() ) {
			setcookie(
				NK_REC_COOKIE,
				$value,
				array(
					'expires'  => time() + ( NK_REC_TTL_DAYS * DAY_IN_SECONDS ),
					'path'     => '/',
					'secure'   => is_ssl(),
					'httponly' => true,
					'samesite' => 'Lax',
				)
			);
		}
		$_COOKIE[ NK_REC_COOKIE ] = $value;
	}

	/**
	 * Read the recently-viewed list.
	 *
	 * Admin test mode: `?nk_simulate_recent=ID,ID,ID` (or the legacy
	 * `ng_simulate_recent`) — only honoured for users with `manage_options`,
	 * so customers can't poison their own recommendations via URL.
	 *
	 * @since 0.1.0
	 * @return int[] Recent product IDs, most recent first.
	 */
	public function read_cookie(): array {
		if ( current_user_can( 'manage_options' ) ) {
			$sim_param = null;
			if ( isset( $_GET['nk_simulate_recent'] ) && is_string( $_GET['nk_simulate_recent'] ) ) {
				$sim_param = $_GET['nk_simulate_recent']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only debug param.
			} elseif ( isset( $_GET['ng_simulate_recent'] ) && is_string( $_GET['ng_simulate_recent'] ) ) {
				$sim_param = $_GET['ng_simulate_recent']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only debug param.
			}
			if ( null !== $sim_param ) {
				$sim = sanitize_text_field( wp_unslash( $sim_param ) );
				$ids = array_filter( array_map( 'intval', explode( ',', $sim ) ) );
				return array_values( $ids );
			}
		}

		if ( ! isset( $_COOKIE[ NK_REC_COOKIE ] ) ) {
			return array();
		}
		$raw = sanitize_text_field( wp_unslash( $_COOKIE[ NK_REC_COOKIE ] ) );
		$ids = array_filter( array_map( 'intval', explode( ',', $raw ) ) );
		return array_values( $ids );
	}

	/**
	 * Get recent product IDs, optionally excluding one.
	 *
	 * @since 0.1.0
	 *
	 * @param int $exclude Product ID to exclude. Default 0 (no exclusion).
	 * @return int[]
	 */
	public function recent_product_ids( int $exclude = 0 ): array {
		$ids = $this->read_cookie();
		if ( 0 !== $exclude ) {
			$ids = array_values(
				array_filter(
					$ids,
					static function ( $x ) use ( $exclude ) {
						return (int) $x !== $exclude;
					}
				)
			);
		}
		return $ids;
	}

	/**
	 * Build a recommendation list.
	 *
	 * @since 0.1.0
	 *
	 * @param int $exclude Product ID to exclude. Default 0.
	 * @param int $limit   Max recommendations. Default 4.
	 * @return \WC_Product[] Up to $limit visible WC_Product instances.
	 */
	public function recommended_products( int $exclude = 0, int $limit = 4 ): array {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return array();
		}
		$limit = max( 1, $limit );

		$recent      = $this->recent_product_ids( $exclude );
		$exclude_ids = array_values(
			array_unique(
				array_filter(
					array_merge( array( $exclude ), $recent ),
					static function ( $x ) {
						return $x > 0;
					}
				)
			)
		);

		// a) Category seeds — from up to 4 most recent.
		$cat_ids = array();
		foreach ( array_slice( $recent, 0, 4 ) as $rid ) {
			$terms = wp_get_post_terms( (int) $rid, 'product_cat', array( 'fields' => 'ids' ) );
			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
				$cat_ids = array_merge( $cat_ids, array_map( 'intval', $terms ) );
			}
		}
		$cat_ids = array_values( array_unique( array_filter( $cat_ids ) ) );

		$picks      = array();
		$picked_ids = array();

		// b) Same-category popular.
		if ( ! empty( $cat_ids ) ) {
			$args = array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => $limit * 2,
				'post__not_in'   => $exclude_ids ? $exclude_ids : array( 0 ),
				'meta_key'       => 'total_sales', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- deliberate orderby for popularity.
				'orderby'        => 'meta_value_num date',
				'order'          => 'DESC',
				'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- single-taxonomy filter is the point.
					array(
						'taxonomy' => 'product_cat',
						'field'    => 'term_id',
						'terms'    => $cat_ids,
					),
				),
				'no_found_rows'  => true,
			);
			$query = new \WP_Query( $args );
			foreach ( $query->posts as $post ) {
				if ( count( $picks ) >= $limit ) {
					break;
				}
				$product = wc_get_product( $post->ID );
				if ( ! $product instanceof \WC_Product || ! $product->is_visible() ) {
					continue;
				}
				if ( in_array( $product->get_id(), $picked_ids, true ) ) {
					continue;
				}
				$picks[]      = $product;
				$picked_ids[] = $product->get_id();
			}
			wp_reset_postdata();
		}

		// c) Top up from featured.
		if ( count( $picks ) < $limit && function_exists( 'wc_get_featured_product_ids' ) ) {
			$featured = wc_get_featured_product_ids();
			$featured = array_values( array_diff( $featured, $exclude_ids, $picked_ids ) );
			if ( ! empty( $featured ) ) {
				$more = wc_get_products(
					array(
						'status'  => 'publish',
						'limit'   => $limit - count( $picks ),
						'include' => $featured,
					)
				);
				foreach ( (array) $more as $product ) {
					if ( ! $product instanceof \WC_Product || ! $product->is_visible() ) {
						continue;
					}
					if ( in_array( $product->get_id(), $picked_ids, true ) ) {
						continue;
					}
					$picks[]      = $product;
					$picked_ids[] = $product->get_id();
					if ( count( $picks ) >= $limit ) {
						break;
					}
				}
			}
		}

		// d) Top up from latest.
		if ( count( $picks ) < $limit ) {
			$exclude_combined = array_values( array_unique( array_merge( $exclude_ids, $picked_ids ) ) );
			$more             = wc_get_products(
				array(
					'status'  => 'publish',
					'limit'   => ( $limit - count( $picks ) ) * 2,
					'exclude' => $exclude_combined ? $exclude_combined : array( 0 ),
					'orderby' => 'date',
					'order'   => 'DESC',
				)
			);
			foreach ( (array) $more as $product ) {
				if ( ! $product instanceof \WC_Product || ! $product->is_visible() ) {
					continue;
				}
				if ( in_array( $product->get_id(), $picked_ids, true ) ) {
					continue;
				}
				$picks[]      = $product;
				$picked_ids[] = $product->get_id();
				if ( count( $picks ) >= $limit ) {
					break;
				}
			}
		}

		return array_slice( $picks, 0, $limit );
	}

	/**
	 * Resolve a "Works Best With" Arabic copy line for the PDP green box.
	 *
	 * @since 0.1.0
	 *
	 * @param \WC_Product|null $source Source product (the PDP).
	 * @param \WC_Product|null $compat Recommended companion.
	 * @return string Filtered Arabic copy.
	 */
	public function compatibility_note( ?\WC_Product $source = null, ?\WC_Product $compat = null ): string {
		$note = 'يعمل بشكل أفضل عند تشغيله مع هذه الوحدة المختارة من نفس الفئة.';

		if ( $source instanceof \WC_Product ) {
			$cats = wp_get_post_terms( $source->get_id(), 'product_cat', array( 'fields' => 'slugs' ) );
			if ( ! is_wp_error( $cats ) && ! empty( $cats ) ) {
				$first  = (string) reset( $cats );
				$by_cat = array(
					'networking' => 'مكوّن مكمّل للشبكة — تكامل مباشر مع الراوتر/السويتش بدون إعداد إضافي.',
					'homelab'    => 'إضافة موصى بها للهوم لاب — توافق مُختبَر مع المنصات الشائعة.',
					'smart-home' => 'يندمج مع منظومة البيت الذكي — Matter / HomeKit / Home Assistant.',
					'gaming'     => 'مكوّن مكمّل لتجهيز الألعاب — أداء مُختبَر مع المنتج الرئيسي.',
					'hardware'   => 'مكوّن مكمّل للجهاز — قابل للتركيب مباشرةً بدون إعداد إضافي.',
					'gift-cards' => 'بطاقة مكمّلة — نفس المنطقة وتفعيل فوري.',
				);
				if ( isset( $by_cat[ $first ] ) ) {
					$note = $by_cat[ $first ];
				}
			}
		}

		/**
		 * Filter the per-pair compatibility note.
		 *
		 * @since 0.1.0
		 *
		 * @param string            $note   Default note.
		 * @param \WC_Product|null  $source Source product.
		 * @param \WC_Product|null  $compat Recommended companion.
		 */
		return (string) apply_filters( 'nk_compatibility_note', $note, $source, $compat );
	}

	/**
	 * Render the recommendation strip as HTML.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, mixed> $args Render args.
	 * @return string HTML; empty string when no products to show.
	 */
	public function render( array $args = array() ): string {
		$args = wp_parse_args(
			$args,
			array(
				'exclude'  => 0,
				'limit'    => 4,
				'title_ar' => 'مقترحات لك',
				'title_en' => 'RECOMMENDED FOR YOU',
				'kicker'   => 'OPERATOR · NEXT PICKS',
			)
		);

		$products = $this->recommended_products( (int) $args['exclude'], (int) $args['limit'] );
		if ( empty( $products ) ) {
			return '';
		}

		ob_start();
		?>
		<section class="ng-rec-strip" aria-label="<?php echo esc_attr__( 'Recommended products', 'novakeys-commerce' ); ?>">
		  <div class="ng-rec-head">
			<span class="ng-rec-kicker">
			  <span class="led" aria-hidden="true"></span>
			  <?php echo esc_html( $args['kicker'] ); ?>
			</span>
			<h2 class="ng-rec-h">
			  <span class="ar"><?php echo esc_html( $args['title_ar'] ); ?></span>
			  <span class="en"><?php echo esc_html( $args['title_en'] ); ?></span>
			</h2>
		  </div>
		  <ul class="products columns-<?php echo (int) $args['limit']; ?>">
			<?php
			foreach ( $products as $p ) :
				global $product;
				$product = $p; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- WC template-part contract requires this.
				setup_postdata( get_post( $p->get_id() ) );
				wc_get_template_part( 'content', 'product' );
			endforeach;
			wp_reset_postdata();
			?>
		  </ul>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Auto-render the strip on single-product pages, after Woo's
	 * built-in related-products section.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function auto_render_strip(): void {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}
		$id = (int) get_queried_object_id();
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- internal-render trusted output.
		echo $this->render(
			array(
				'exclude' => $id,
				'limit'   => 4,
			)
		);
	}

	/**
	 * Shortcode handler — `[nk_recommendations]` and the legacy
	 * `[neogen_recommendations]` alias.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, mixed>|string $atts Shortcode attributes.
	 * @return string
	 */
	public function shortcode_handler( $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'exclude'  => 0,
				'limit'    => 4,
				'title_ar' => 'مقترحات لك',
				'title_en' => 'RECOMMENDED FOR YOU',
				'kicker'   => 'OPERATOR · NEXT PICKS',
			),
			(array) $atts,
			'nk_recommendations'
		);
		return $this->render( $atts );
	}
}

/* -- procedural wrappers (back-compat for templates) ----------------- */

/**
 * Read the recently-viewed product IDs from the cookie.
 *
 * @since 0.1.0
 * @return int[]
 */
function nk_rec_read_cookie(): array {
	return Recommender::instance()->read_cookie();
}

/**
 * Get recent product IDs, optionally excluding one.
 *
 * @since 0.1.0
 *
 * @param int $exclude Product ID to exclude.
 * @return int[]
 */
function nk_recent_product_ids( int $exclude = 0 ): array {
	return Recommender::instance()->recent_product_ids( $exclude );
}

/**
 * Recommended products list.
 *
 * @since 0.1.0
 *
 * @param int $exclude Product ID to exclude.
 * @param int $limit   Max items.
 * @return \WC_Product[]
 */
function nk_recommended_products( int $exclude = 0, int $limit = 4 ): array {
	return Recommender::instance()->recommended_products( $exclude, $limit );
}

/**
 * Compatibility note copy for the PDP "Works Best With" box.
 *
 * @since 0.1.0
 *
 * @param \WC_Product|null $source Source product.
 * @param \WC_Product|null $compat Recommended companion.
 * @return string
 */
function nk_compatibility_note( ?\WC_Product $source = null, ?\WC_Product $compat = null ): string {
	return Recommender::instance()->compatibility_note( $source, $compat );
}

/**
 * Render the recommendation strip.
 *
 * @since 0.1.0
 *
 * @param array<string, mixed> $args Render args.
 * @return string
 */
function nk_render_recommendations( array $args = array() ): string {
	return Recommender::instance()->render( $args );
}

Recommender::instance()->register_hooks();
