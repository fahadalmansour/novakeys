<?php
/**
 * Voucher gallery shortcode `[nk_vouchers]`.
 *
 * Renders a filterable grid of brand voucher cards across six categories
 * (gaming, shopping, entertainment, apps, telecom, productivity). RTL-aware
 * via `is_rtl()` — Arabic labels swap in for the hero copy, filter buttons,
 * and section headings on RTL contexts.
 *
 * Brand artwork is referenced from the legacy `neogen-theme-assets/img/brands/`
 * directory. The base URL is filterable via `nk_vouchers_brand_url_base` so
 * the assets can move (e.g. into the FSE theme in a later phase) without
 * touching this module.
 *
 * @todo Standards deviation: the shortcode inlines its own <style> and
 *       <script> rather than enqueueing. Tracked for the phase-3 theme
 *       cutover when this becomes a block pattern with theme.json tokens.
 *
 * @package NovaKeys\Commerce\Vouchers
 * @since   0.1.0
 */

namespace NovaKeys\Commerce\Vouchers;

defined( 'ABSPATH' ) || exit;

/**
 * Voucher gallery shortcode.
 *
 * @since 0.1.0
 */
final class Shortcode {

	/**
	 * Singleton instance.
	 *
	 * @since 0.1.0
	 * @var Shortcode|null
	 */
	private static ?Shortcode $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @since 0.1.0
	 * @return Shortcode
	 */
	public static function instance(): Shortcode {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register the shortcode.
	 *
	 * Guarded against double-registration when the legacy mu-plugin is
	 * still loaded on the server during a transitional deploy.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register_hooks(): void {
		if ( shortcode_exists( 'nk_vouchers' ) ) {
			return;
		}
		add_shortcode( 'nk_vouchers', array( $this, 'render' ) );
	}

	/*
	 * brand_url_base() removed 2026-05-08. Voucher cards no longer
	 * load third-party brand SVGs from a runtime path — they emit
	 * typographic monograms via .nk-card-mono spans instead.
	 * The `nk_vouchers_brand_url_base` filter is gone with it; no
	 * known external callers.
	 */

	/**
	 * Get the bilingual label set for the current locale.
	 *
	 * @since 0.1.0
	 *
	 * @param bool $is_ar Whether the current request is RTL/Arabic.
	 * @return array<string, string>
	 */
	private function labels( bool $is_ar ): array {
		if ( $is_ar ) {
			return array(
				'eyebrow'       => 'منتجات رقمية',
				'heading_a'     => 'القسائم و',
				'heading_b'     => 'بطاقات الهدايا.',
				'sub'           => 'تسليم رقمي فوري. بطاقات تعبئة وقسائم هدايا للألعاب والترفيه والتسوق وأكثر.',
				'stat_brands'   => 'علامة تجارية متاحة',
				'stat_value'    => 'نطاق القيمة',
				'stat_speed'    => 'تسليم فوري',
				'all'           => 'الكل',
				'gaming'        => 'ألعاب',
				'shopping'      => 'تسوّق',
				'entertainment' => 'ترفيه',
				'apps'          => 'تطبيقات',
				'apps_section'  => 'تطبيقات وبرامج',
				'telecom'       => 'اتصالات',
				'productivity'  => 'إنتاجية',
			);
		}
		return array(
			'eyebrow'       => 'Digital Products',
			'heading_a'     => 'Vouchers &amp; ',
			'heading_b'     => 'Gift Cards.',
			'sub'           => 'Instant digital delivery. Top-up and gift cards for gaming, entertainment, shopping, and more.',
			'stat_brands'   => 'Brands available',
			'stat_value'    => 'Value range',
			'stat_speed'    => 'Instant delivery',
			'all'           => 'All',
			'gaming'        => 'Gaming',
			'shopping'      => 'Shopping',
			'entertainment' => 'Entertainment',
			'apps'          => 'Apps',
			'apps_section'  => 'Apps &amp; Software',
			'telecom'       => 'Telecom',
			'productivity'  => 'Productivity',
		);
	}

	/**
	 * Render the shortcode.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, mixed>|string $atts Shortcode attributes (currently unused).
	 * @return string HTML.
	 */
	public function render( $atts = array() ): string {
		unset( $atts );
		// brand_url_base() retired 2026-05-08 — voucher cards now use
		// typographic monograms (.nk-card-mono) instead of <img> brand
		// art, sidestepping the dead mu-plugins asset path and the
		// trademark exposure that comes with bundling third-party logos.
		$is_ar     = function_exists( 'is_rtl' ) && is_rtl();
		// Round-4 audit B1 — voucher cards must be real anchors so
		// keyboard users can reach them and clicks land somewhere.
		// All 18 cards point at the gift-card archive; per-brand
		// filtering can replace this when subterm taxonomy lands.
		$card_href = esc_url( home_url( '/product-category/gift-cards/' ) );
		$t         = $this->labels( $is_ar );
		// Store the bare direction value (`rtl|''`) so each echo site
		// can build the attribute through esc_attr() — was previously
		// the full literal `' dir="rtl"'` echoed unescaped (WPCS flag
		// from readiness-2026-05-08 HIGH).
		$dir_attr  = $is_ar ? 'rtl' : '';

		ob_start();
		?>
<style>
#nk-vouchers-wrap *{box-sizing:border-box;}
#nk-vouchers-wrap{font-family:'DM Sans','Helvetica Neue',sans-serif;margin:0 -16px;}
.nk-v-hero{padding:48px 16px 36px;background:var(--nk-color-paper, #F8FAFC);border-bottom:1px solid #E3DDD4;}
.nk-v-label{display:inline-flex;align-items:center;gap:8px;background:#EFF9FF;border:1px solid rgba(56,189,248,.3);color:#0EA5E9;font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;padding:5px 14px;border-radius:100px;margin-bottom:18px;}
.nk-v-label::before{content:'';width:6px;height:6px;background:#38BDF8;border-radius:50%;animation:nkpulse 2s ease-in-out infinite;}
@keyframes nkpulse{0%,100%{opacity:1}50%{opacity:.4}}
.nk-v-hero h2{font-size:clamp(32px,5vw,52px);font-weight:800;letter-spacing:-.03em;line-height:1;color:var(--nk-color-ink, #0F172A);margin-bottom:12px;}
.nk-v-hero h2 .nk-v-em{color:#0284C7;}
.nk-v-sub{font-size:15px;font-weight:300;color:#5C5953;max-width:480px;line-height:1.65;margin-bottom:28px;}
.nk-v-stats{display:flex;gap:28px;flex-wrap:wrap;}
.nk-v-stat-num{font-size:26px;font-weight:800;letter-spacing:-.04em;color:var(--nk-color-ink, #0F172A);display:block;line-height:1;}
.nk-v-stat-lbl{font-size:11px;color:#ABA79F;}

.nk-v-filters{padding:24px 16px 8px;display:flex;gap:8px;flex-wrap:wrap;}
.nk-vf-btn{background:#fff;border:1px solid #E3DDD4;color:#78746E;font-size:13px;font-weight:500;padding:7px 16px;border-radius:100px;cursor:pointer;transition:all .18s;font-family:inherit;}
.nk-vf-btn:hover{border-color:#38BDF8;color:var(--nk-color-link, #0369A1);}
.nk-vf-btn:focus-visible{outline:2px solid var(--nk-color-link, #0369A1);outline-offset:2px;}
.nk-vf-btn.nk-active{background:var(--nk-color-ink, #0F172A);border-color:var(--nk-color-ink, #0F172A);color:#fff;}
.nk-vf-count{font-size:11px;opacity:.55;margin-inline-start:2px;}

.nk-v-section{padding:20px 16px 4px;}
.nk-v-section.nk-hidden{display:none;}
.nk-v-cat-lbl{font-size:11px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#ABA79F;display:flex;align-items:center;gap:12px;margin-bottom:16px;}
.nk-v-cat-lbl::after{content:'';flex:1;height:1px;background:#E3DDD4;}

.nk-v-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;}
@media(max-width:900px){.nk-v-grid{grid-template-columns:repeat(3,1fr);}}
@media(max-width:600px){.nk-v-grid{grid-template-columns:repeat(2,1fr);gap:10px;} .nk-card-logo{height:72px;}}

.nk-card{background:#fff;border:1px solid #E3DDD4;border-radius:14px;overflow:hidden;display:flex;flex-direction:column;transition:transform .22s cubic-bezier(.25,.46,.45,.94),border-color .22s,box-shadow .22s;box-shadow:0 1px 3px rgba(0,0,0,.05),0 4px 12px rgba(0,0,0,.04);text-decoration:none;color:inherit;}
.nk-card:focus-visible{outline:2px solid var(--nk-color-link, #0369A1);outline-offset:3px;}
.nk-card:hover{transform:translateY(-5px);border-color:#38BDF8;box-shadow:0 8px 24px rgba(14,165,233,.14),0 2px 8px rgba(0,0,0,.06);}
.nk-card:hover .nk-card-arrow{background:#38BDF8;color:#fff;border-color:#38BDF8;}
.nk-card:hover .nk-card-logo img{transform:scale(1.05);}

.nk-card-top{padding:14px 14px 0;display:flex;align-items:center;justify-content:space-between;}
.nk-card-cat{font-size:10px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:#78746E;}
.nk-badge{font-size:9px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;padding:3px 7px;border-radius:100px;}
.nk-badge-hot{background:#FEF2F2;color:#B91C1C;}
.nk-badge-new{background:#EFF9FF;color:#0369A1;}
.nk-badge-pop{background:#F0FDF4;color:#15803D;}

.nk-card-logo{height:96px;display:flex;align-items:center;justify-content:center;padding:16px 20px;}
.nk-card-logo img{max-width:100%;max-height:64px;object-fit:contain;transition:transform .22s ease;}

.nk-card-bottom{padding:11px 14px 14px;display:flex;align-items:flex-end;justify-content:space-between;border-top:1px solid #F0EDE8;margin-top:auto;}
.nk-card-name{font-weight:700;font-size:13px;color:var(--nk-color-ink, #0F172A);letter-spacing:-.01em;line-height:1.2;}
.nk-card-range{font-size:11px;color:#78746E;margin-top:3px;font-weight:400;}
.nk-card-arrow{width:32px;height:32px;border-radius:8px;background:#F7F6F3;border:1px solid #E3DDD4;display:flex;align-items:center;justify-content:center;color:#78746E;font-size:14px;transition:all .18s;flex-shrink:0;}
[dir="rtl"] .nk-card-arrow{transform:scaleX(-1);}
</style>

<div id="nk-vouchers-wrap">

  <div class="nk-v-hero"<?php echo $dir_attr ? ' dir="' . esc_attr( $dir_attr ) . '"' : ''; ?>>
	<div class="nk-v-label"><?php echo esc_html( $t['eyebrow'] ); ?></div>
	<h2><?php echo $t['heading_a']; // safe: contains '&amp;' entity ?><span class="nk-v-em"><?php echo esc_html( $t['heading_b'] ); ?></span></h2>
	<p class="nk-v-sub"><?php echo esc_html( $t['sub'] ); ?></p>
	<div class="nk-v-stats">
	  <div><span class="nk-v-stat-num">18+</span><span class="nk-v-stat-lbl"><?php echo esc_html( $t['stat_brands'] ); ?></span></div>
	  <div><span class="nk-v-stat-num">SAR 10–2000</span><span class="nk-v-stat-lbl"><?php echo esc_html( $t['stat_value'] ); ?></span></div>
	  <div><span class="nk-v-stat-num">&lt; 60s</span><span class="nk-v-stat-lbl"><?php echo esc_html( $t['stat_speed'] ); ?></span></div>
	</div>
  </div>

  <div class="nk-v-filters"<?php echo $dir_attr ? ' dir="' . esc_attr( $dir_attr ) . '"' : ''; ?>>
	<button class="nk-vf-btn nk-active" data-cat="all"          ><?php echo esc_html( $t['all'] ); ?> <span class="nk-vf-count">18</span></button>
	<button class="nk-vf-btn" data-cat="gaming"                 ><?php echo esc_html( $t['gaming'] ); ?> <span class="nk-vf-count">5</span></button>
	<button class="nk-vf-btn" data-cat="shopping"               ><?php echo esc_html( $t['shopping'] ); ?> <span class="nk-vf-count">3</span></button>
	<button class="nk-vf-btn" data-cat="entertainment"          ><?php echo esc_html( $t['entertainment'] ); ?> <span class="nk-vf-count">2</span></button>
	<button class="nk-vf-btn" data-cat="apps"                   ><?php echo esc_html( $t['apps'] ); ?> <span class="nk-vf-count">2</span></button>
	<button class="nk-vf-btn" data-cat="telecom"                ><?php echo esc_html( $t['telecom'] ); ?> <span class="nk-vf-count">3</span></button>
	<button class="nk-vf-btn" data-cat="productivity"           ><?php echo esc_html( $t['productivity'] ); ?> <span class="nk-vf-count">3</span></button>
  </div>

  <!-- GAMING -->
  <div class="nk-v-section" data-section="gaming"<?php echo $dir_attr ? ' dir="' . esc_attr( $dir_attr ) . '"' : ''; ?>>
	<div class="nk-v-cat-lbl"><?php echo esc_html( $t['gaming'] ); ?></div>
	<div class="nk-v-grid">
	  <a class="nk-card" href="<?php echo $card_href; ?>"><div class="nk-card-top"><span class="nk-card-cat">Gaming</span><span class="nk-badge nk-badge-hot">Popular</span></div><div class="nk-card-logo"><span class="nk-card-mono" aria-label="PlayStation Store">PS</span></div><div class="nk-card-bottom"><div><div class="nk-card-name">PlayStation Store</div><div class="nk-card-range">SAR 50–500</div></div><div class="nk-card-arrow">&#x2192;</div></div></a>
	  <a class="nk-card" href="<?php echo $card_href; ?>"><div class="nk-card-top"><span class="nk-card-cat">Gaming</span><span class="nk-badge nk-badge-pop">Trending</span></div><div class="nk-card-logo"><span class="nk-card-mono" aria-label="Xbox">XB</span></div><div class="nk-card-bottom"><div><div class="nk-card-name">Xbox</div><div class="nk-card-range">SAR 50–500</div></div><div class="nk-card-arrow">&#x2192;</div></div></a>
	  <a class="nk-card" href="<?php echo $card_href; ?>"><div class="nk-card-top"><span class="nk-card-cat">Gaming</span></div><div class="nk-card-logo"><span class="nk-card-mono" aria-label="Steam">ST</span></div><div class="nk-card-bottom"><div><div class="nk-card-name">Steam</div><div class="nk-card-range">SAR 25–300</div></div><div class="nk-card-arrow">&#x2192;</div></div></a>
	  <a class="nk-card" href="<?php echo $card_href; ?>"><div class="nk-card-top"><span class="nk-card-cat">Gaming</span><span class="nk-badge nk-badge-hot">Hot</span></div><div class="nk-card-logo"><span class="nk-card-mono" aria-label="PUBG Mobile">PG</span></div><div class="nk-card-bottom"><div><div class="nk-card-name">PUBG Mobile</div><div class="nk-card-range">SAR 15–200</div></div><div class="nk-card-arrow">&#x2192;</div></div></a>
	  <a class="nk-card" href="<?php echo $card_href; ?>"><div class="nk-card-top"><span class="nk-card-cat">Gaming</span></div><div class="nk-card-logo"><span class="nk-card-mono" aria-label="Roblox">RX</span></div><div class="nk-card-bottom"><div><div class="nk-card-name">Roblox</div><div class="nk-card-range">SAR 15–150</div></div><div class="nk-card-arrow">&#x2192;</div></div></a>
	</div>
  </div>

  <!-- SHOPPING -->
  <div class="nk-v-section" data-section="shopping"<?php echo $dir_attr ? ' dir="' . esc_attr( $dir_attr ) . '"' : ''; ?>>
	<div class="nk-v-cat-lbl"><?php echo esc_html( $t['shopping'] ); ?></div>
	<div class="nk-v-grid">
	  <a class="nk-card" href="<?php echo $card_href; ?>"><div class="nk-card-top"><span class="nk-card-cat">Shopping</span><span class="nk-badge nk-badge-hot">Popular</span></div><div class="nk-card-logo"><span class="nk-card-mono" aria-label="Amazon">AM</span></div><div class="nk-card-bottom"><div><div class="nk-card-name">Amazon</div><div class="nk-card-range">SAR 50–1000</div></div><div class="nk-card-arrow">&#x2192;</div></div></a>
	  <a class="nk-card" href="<?php echo $card_href; ?>"><div class="nk-card-top"><span class="nk-card-cat">Shopping</span></div><div class="nk-card-logo"><span class="nk-card-mono" aria-label="NOON">NN</span></div><div class="nk-card-bottom"><div><div class="nk-card-name">NOON</div><div class="nk-card-range">SAR 50–2000</div></div><div class="nk-card-arrow">&#x2192;</div></div></a>
	  <a class="nk-card" href="<?php echo $card_href; ?>"><div class="nk-card-top"><span class="nk-card-cat">Shopping</span></div><div class="nk-card-logo"><span class="nk-card-mono" aria-label="KSP">KS</span></div><div class="nk-card-bottom"><div><div class="nk-card-name">KSP</div><div class="nk-card-range">SAR 100–2000</div></div><div class="nk-card-arrow">&#x2192;</div></div></a>
	</div>
  </div>

  <!-- ENTERTAINMENT -->
  <div class="nk-v-section" data-section="entertainment"<?php echo $dir_attr ? ' dir="' . esc_attr( $dir_attr ) . '"' : ''; ?>>
	<div class="nk-v-cat-lbl"><?php echo esc_html( $t['entertainment'] ); ?></div>
	<div class="nk-v-grid">
	  <a class="nk-card" href="<?php echo $card_href; ?>"><div class="nk-card-top"><span class="nk-card-cat">Entertainment</span><span class="nk-badge nk-badge-pop">Trending</span></div><div class="nk-card-logo"><span class="nk-card-mono" aria-label="Amazon Prime">PR</span></div><div class="nk-card-bottom"><div><div class="nk-card-name">Amazon Prime</div><div class="nk-card-range">SAR 30–200</div></div><div class="nk-card-arrow">&#x2192;</div></div></a>
	  <a class="nk-card" href="<?php echo $card_href; ?>"><div class="nk-card-top"><span class="nk-card-cat">Entertainment</span></div><div class="nk-card-logo"><span class="nk-card-mono" aria-label="Twitch">TW</span></div><div class="nk-card-bottom"><div><div class="nk-card-name">Twitch</div><div class="nk-card-range">SAR 25–150</div></div><div class="nk-card-arrow">&#x2192;</div></div></a>
	</div>
  </div>

  <!-- APPS -->
  <div class="nk-v-section" data-section="apps"<?php echo $dir_attr ? ' dir="' . esc_attr( $dir_attr ) . '"' : ''; ?>>
	<div class="nk-v-cat-lbl"><?php echo $t['apps_section']; // safe: '&amp;' entity ?></div>
	<div class="nk-v-grid">
	  <a class="nk-card" href="<?php echo $card_href; ?>"><div class="nk-card-top"><span class="nk-card-cat">Apps</span><span class="nk-badge nk-badge-new">New</span></div><div class="nk-card-logo"><span class="nk-card-mono" aria-label="Google Play">GP</span></div><div class="nk-card-bottom"><div><div class="nk-card-name">Google Play</div><div class="nk-card-range">SAR 25–400</div></div><div class="nk-card-arrow">&#x2192;</div></div></a>
	  <a class="nk-card" href="<?php echo $card_href; ?>"><div class="nk-card-top"><span class="nk-card-cat">Apps</span><span class="nk-badge nk-badge-hot">Popular</span></div><div class="nk-card-logo"><span class="nk-card-mono" aria-label="Apple Gift Card">AP</span></div><div class="nk-card-bottom"><div><div class="nk-card-name">Apple Gift Card</div><div class="nk-card-range">SAR 25–500</div></div><div class="nk-card-arrow">&#x2192;</div></div></a>
	</div>
  </div>

  <!-- TELECOM -->
  <div class="nk-v-section" data-section="telecom"<?php echo $dir_attr ? ' dir="' . esc_attr( $dir_attr ) . '"' : ''; ?>>
	<div class="nk-v-cat-lbl"><?php echo esc_html( $t['telecom'] ); ?></div>
	<div class="nk-v-grid">
	  <a class="nk-card" href="<?php echo $card_href; ?>"><div class="nk-card-top"><span class="nk-card-cat">Telecom</span><span class="nk-badge nk-badge-hot">Popular</span></div><div class="nk-card-logo"><span class="nk-card-mono" aria-label="STC Cards">SC</span></div><div class="nk-card-bottom"><div><div class="nk-card-name">STC Cards</div><div class="nk-card-range">SAR 30–500</div></div><div class="nk-card-arrow">&#x2192;</div></div></a>
	  <a class="nk-card" href="<?php echo $card_href; ?>"><div class="nk-card-top"><span class="nk-card-cat">Telecom</span></div><div class="nk-card-logo"><span class="nk-card-mono" aria-label="Zain Cards">ZN</span></div><div class="nk-card-bottom"><div><div class="nk-card-name">Zain Cards</div><div class="nk-card-range">SAR 10–500</div></div><div class="nk-card-arrow">&#x2192;</div></div></a>
	  <a class="nk-card" href="<?php echo $card_href; ?>"><div class="nk-card-top"><span class="nk-card-cat">Telecom</span></div><div class="nk-card-logo"><span class="nk-card-mono" aria-label="Mobily Cards">MB</span></div><div class="nk-card-bottom"><div><div class="nk-card-name">Mobily Cards</div><div class="nk-card-range">SAR 10–500</div></div><div class="nk-card-arrow">&#x2192;</div></div></a>
	</div>
  </div>

  <!-- PRODUCTIVITY -->
  <div class="nk-v-section" data-section="productivity"<?php echo $dir_attr ? ' dir="' . esc_attr( $dir_attr ) . '"' : ''; ?>>
	<div class="nk-v-cat-lbl"><?php echo esc_html( $t['productivity'] ); ?></div>
	<div class="nk-v-grid">
	  <a class="nk-card" href="<?php echo $card_href; ?>"><div class="nk-card-top"><span class="nk-card-cat">Productivity</span></div><div class="nk-card-logo"><span class="nk-card-mono" aria-label="Adobe">AD</span></div><div class="nk-card-bottom"><div><div class="nk-card-name">Adobe</div><div class="nk-card-range">SAR 50–500</div></div><div class="nk-card-arrow">&#x2192;</div></div></a>
	  <a class="nk-card" href="<?php echo $card_href; ?>"><div class="nk-card-top"><span class="nk-card-cat">Productivity</span></div><div class="nk-card-logo"><span class="nk-card-mono" aria-label="Microsoft">MS</span></div><div class="nk-card-bottom"><div><div class="nk-card-name">Microsoft</div><div class="nk-card-range">SAR 50–800</div></div><div class="nk-card-arrow">&#x2192;</div></div></a>
	  <a class="nk-card" href="<?php echo $card_href; ?>"><div class="nk-card-top"><span class="nk-card-cat">Productivity</span></div><div class="nk-card-logo"><span class="nk-card-mono" aria-label="McAfee">MC</span></div><div class="nk-card-bottom"><div><div class="nk-card-name">McAfee</div><div class="nk-card-range">SAR 60–300</div></div><div class="nk-card-arrow">&#x2192;</div></div></a>
	</div>
  </div>

</div>

<script>
(function () {
  function activate(btn) {
	var cat = btn.dataset.cat;
	document.querySelectorAll('.nk-vf-btn').forEach(function (b) { b.classList.remove('nk-active'); });
	btn.classList.add('nk-active');
	document.querySelectorAll('.nk-v-section').forEach(function (s) {
	  s.classList.toggle('nk-hidden', cat !== 'all' && s.dataset.section !== cat);
	});
  }
  document.querySelectorAll('.nk-vf-btn').forEach(function (btn) {
	btn.addEventListener('click', function () { activate(btn); });
  });
}());
</script>
		<?php
		return (string) ob_get_clean();
	}
}

/**
 * Procedural wrapper retained for templates / external callers that already
 * reference the shortcode function by name.
 *
 * @since 0.1.0
 * @return string
 */
function nk_vouchers_shortcode(): string {
	return Shortcode::instance()->render();
}

Shortcode::instance()->register_hooks();
