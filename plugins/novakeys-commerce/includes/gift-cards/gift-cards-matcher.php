<?php
/**
 * Gift-card asset matcher.
 *
 * Maps WooCommerce gift-card products to brand-authentic webp/svg
 * artwork shipped under the legacy `neogen-theme-assets/img/gift-cards/`
 * directory. Matching is keyword-driven (EN + AR), with a per-product
 * `_ng_gift_card_brand` meta override taking precedence.
 *
 * Stays procedural — the entry points are registered as `add_filter`
 * callbacks against WooCommerce product/title/image hooks, and filter
 * callback strings must resolve in the global namespace.
 *
 * Postmeta key `_ng_gift_card_brand` is preserved verbatim — it backs
 * live product data and CSV bulk-import columns.
 *
 * @package NovaKeys\Commerce\Gift_Cards
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'NK_THEME_ASSET_DIR' ) ) {
	define( 'NK_THEME_ASSET_DIR', WP_CONTENT_DIR . '/mu-plugins/novakeys-custom/mu-plugins/neogen-theme-assets' );
}
if ( ! defined( 'NK_THEME_ASSET_URL' ) ) {
	$nk_asset_rel = str_replace(
		wp_normalize_path( WP_CONTENT_DIR ),
		'',
		wp_normalize_path( NK_THEME_ASSET_DIR )
	);
	define( 'NK_THEME_ASSET_URL', content_url( $nk_asset_rel ) );
	unset( $nk_asset_rel );
}
// Legacy aliases — anything that grepped the old constant names still resolves.
if ( ! defined( 'NG_THEME_ASSET_DIR' ) ) {
	define( 'NG_THEME_ASSET_DIR', NK_THEME_ASSET_DIR );
}
if ( ! defined( 'NG_THEME_ASSET_URL' ) ) {
	define( 'NG_THEME_ASSET_URL', NK_THEME_ASSET_URL );
}

/**
 * Gift-card brand → asset/keyword map.
 *
 * Order matters — high-specificity slots (`stc-pay`, `snapchat-plus`)
 * must come before more general ones (`stc`, `snapchat`) so the generic
 * key doesn't shadow the specific one. The matcher is first-hit-wins.
 *
 * Filterable via `nk_gift_card_asset_map` (legacy `ng_gift_card_asset_map`
 * filter is also honoured for backward compatibility).
 *
 * @since 0.1.0
 * @return array<string, array{files: string[], keywords: string[]}>
 */
function nk_gift_card_asset_map(): array {
	$map = array(
		'apple'              => array(
			'files'    => array( 'apple.webp' ),
			'keywords' => array( 'apple', 'itunes', 'آبل', 'ابل', 'ايتونز' ),
		),
		'google-play'        => array(
			'files'    => array( 'google-play.webp', 'googleplay.webp' ),
			'keywords' => array( 'google play', 'googleplay', 'قوقل بلاي', 'جوجل بلاي' ),
		),
		'playstation'        => array(
			'files'    => array( 'playstation.webp', 'psn.webp' ),
			'keywords' => array( 'playstation', 'play station', 'psn', 'بلايستيشن', 'بلاي ستيشن' ),
		),
		'steam'              => array(
			'files'    => array( 'steam.webp' ),
			'keywords' => array( 'steam', 'ستيم' ),
		),
		'xbox'               => array(
			'files'    => array( 'xbox.webp', 'game-pass.webp' ),
			'keywords' => array( 'xbox', 'game pass', 'اكس بوكس', 'إكس بوكس', 'قيم باس', 'جيم باس' ),
		),
		'amazon'             => array(
			'files'    => array( 'amazon.webp', 'amazon-prime.webp', 'prime.webp' ),
			'keywords' => array( 'amazon', 'prime', 'أمازون', 'امازون' ),
		),
		'kaspersky'          => array(
			'files'    => array( 'kaspersky.webp' ),
			'keywords' => array( 'kaspersky', 'كاسبرسكي' ),
		),
		'adobe'              => array(
			'files'    => array( 'adobe.webp', 'creative-cloud.webp' ),
			'keywords' => array( 'adobe', 'creative cloud', 'أدوبي', 'ادوبي' ),
		),
		'office'             => array(
			'files'    => array( 'office.webp', 'office2024.webp', 'microsoft-office.webp' ),
			'keywords' => array( 'office', 'office 2024', 'microsoft office', 'أوفيس', 'اوفس' ),
		),
		'windows'            => array(
			'files'    => array( 'windows.webp', 'windows11.webp', 'windows-11.webp' ),
			'keywords' => array( 'windows', 'windows 11', 'ويندوز' ),
		),
		'youtube'            => array(
			'files'    => array( 'youtube.webp', 'youtube-premium.webp', 'youtube-music.webp' ),
			'keywords' => array( 'youtube', 'youtube premium', 'youtube music', 'يوتيوب', 'يوتيوب بريميوم' ),
		),

		// Wallet / telco (KSA) — high-specificity keys before general.
		'stc-pay'            => array(
			'files'    => array( 'stc-pay.webp', 'stcpay.webp' ),
			'keywords' => array( 'stc pay', 'stcpay', 'اس تي سي باي', 'إس تي سي باي' ),
		),
		'stc'                => array(
			'files'    => array( 'stc.webp', 'sawa.webp' ),
			'keywords' => array( 'stc', 'سوا', 'اس تي سي', 'إس تي سي' ),
		),
		'mobily'             => array(
			'files'    => array( 'mobily.webp' ),
			'keywords' => array( 'mobily', 'موبايلي' ),
		),
		'zain'               => array(
			'files'    => array( 'zain.webp' ),
			'keywords' => array( 'zain', 'زين' ),
		),
		'careem'             => array(
			'files'    => array( 'careem.webp' ),
			'keywords' => array( 'careem', 'كريم' ),
		),

		// Wallet / telco — UAE.
		'etisalat'           => array(
			'files'    => array( 'etisalat.webp', 'etisalat-uae.webp' ),
			'keywords' => array( 'etisalat', 'اتصالات', 'إتصالات' ),
		),
		'du'                 => array(
			'files'    => array( 'du.webp', 'du-uae.webp' ),
			'keywords' => array( 'du uae', 'du mobile', 'دو' ),
		),

		// Wallet / telco — Bahrain.
		'batelco'            => array(
			'files'    => array( 'batelco.webp' ),
			'keywords' => array( 'batelco', 'بتلكو' ),
		),

		// Wallet / telco — Oman.
		'omantel'            => array(
			'files'    => array( 'omantel.webp' ),
			'keywords' => array( 'omantel', 'عمانتل' ),
		),

		// Wallet / telco — Ooredoo Group (Qatar / Kuwait / Oman).
		'ooredoo'            => array(
			'files'    => array( 'ooredoo.webp' ),
			'keywords' => array( 'ooredoo', 'اوريدو', 'أوريدو' ),
		),

		// GCC marketplaces / retail.
		'talabat'            => array(
			'files'    => array( 'talabat.webp' ),
			'keywords' => array( 'talabat', 'طلبات' ),
		),
		'carrefour'          => array(
			'files'    => array( 'carrefour.webp', 'carrefour-maf.webp' ),
			'keywords' => array( 'carrefour', 'كارفور' ),
		),
		'sharaf-dg'          => array(
			'files'    => array( 'sharaf-dg.webp' ),
			'keywords' => array( 'sharaf dg', 'sharaf', 'شرف دي جي' ),
		),
		'lulu'               => array(
			'files'    => array( 'lulu.webp', 'lulu-hypermarket.webp' ),
			'keywords' => array( 'lulu', 'lulu hypermarket', 'لولو' ),
		),
		'x-cite'             => array(
			'files'    => array( 'x-cite.webp', 'xcite.webp', 'alghanim.webp' ),
			'keywords' => array( 'x-cite', 'xcite', 'alghanim', 'اكس سايت', 'إكس سايت', 'الغانم' ),
		),
		'virgin-megastore'   => array(
			'files'    => array( 'virgin-megastore.webp', 'virgin.webp' ),
			'keywords' => array( 'virgin megastore', 'virgin', 'فيرجن' ),
		),

		// Streaming / audio.
		'netflix'            => array(
			'files'    => array( 'netflix.webp' ),
			'keywords' => array( 'netflix', 'نتفلكس', 'نتفليكس' ),
		),
		'shahid'             => array(
			'files'    => array( 'shahid.webp', 'shahid-vip.webp' ),
			'keywords' => array( 'shahid', 'shahid vip', 'شاهد' ),
		),
		'spotify'            => array(
			'files'    => array( 'spotify.webp' ),
			'keywords' => array( 'spotify', 'سبوتيفاي', 'سبوتفاي' ),
		),
		'anghami'            => array(
			'files'    => array( 'anghami.webp' ),
			'keywords' => array( 'anghami', 'أنغامي', 'انغامي' ),
		),
		'disney-plus'        => array(
			'files'    => array( 'disney-plus.webp', 'disney.webp' ),
			'keywords' => array( 'disney+', 'disney plus', 'ديزني بلس', 'ديزني+' ),
		),

		// Console / store credit.
		'nintendo-eshop'     => array(
			'files'    => array( 'nintendo-eshop.webp', 'nintendo.webp', 'eshop.webp' ),
			'keywords' => array( 'nintendo', 'eshop', 'switch eshop', 'نينتندو', 'اي شوب' ),
		),

		// Game top-ups.
		'pubg'               => array(
			'files'    => array( 'pubg.webp', 'pubg-uc.webp' ),
			'keywords' => array( 'pubg', 'uc', 'ببجي', 'يوسي' ),
		),
		'free-fire'          => array(
			'files'    => array( 'free-fire.webp', 'freefire.webp', 'garena.webp' ),
			'keywords' => array( 'free fire', 'freefire', 'garena', 'فري فاير', 'جارينا', 'قارينا' ),
		),
		'roblox'             => array(
			'files'    => array( 'roblox.webp', 'robux.webp' ),
			'keywords' => array( 'roblox', 'robux', 'روبلوكس', 'روبكس' ),
		),
		'razer-gold'         => array(
			'files'    => array( 'razer-gold.webp', 'razer.webp' ),
			'keywords' => array( 'razer gold', 'razer pin', 'رايزر قولد', 'ريزر قولد' ),
		),
		'discord-nitro'      => array(
			'files'    => array( 'discord-nitro.webp', 'discord.webp' ),
			'keywords' => array( 'discord nitro', 'discord', 'ديسكورد' ),
		),
		'fortnite'           => array(
			'files'    => array( 'fortnite.webp', 'v-bucks.webp' ),
			'keywords' => array( 'fortnite', 'v-bucks', 'vbucks', 'فورتنايت' ),
		),
		'minecraft'          => array(
			'files'    => array( 'minecraft.webp', 'minecoins.webp' ),
			'keywords' => array( 'minecraft', 'minecoins', 'ماين كرافت', 'مايكنرافت' ),
		),

		// Marketplaces.
		'noon'               => array(
			'files'    => array( 'noon.webp' ),
			'keywords' => array( 'noon', 'نون' ),
		),
		'jarir'              => array(
			'files'    => array( 'jarir.webp' ),
			'keywords' => array( 'jarir', 'jarir bookstore', 'جرير' ),
		),
		'ebay'               => array(
			'files'    => array( 'ebay.webp' ),
			'keywords' => array( 'ebay', 'إيباي', 'ايباي' ),
		),

		// Social / utility — specific before generic.
		'snapchat-plus'      => array(
			'files'    => array( 'snapchat-plus.webp', 'snapchat.webp' ),
			'keywords' => array( 'snapchat+', 'snapchat plus', 'سناب شات بلس', 'سنابشات بلس' ),
		),
		'tiktok-coins'       => array(
			'files'    => array( 'tiktok-coins.webp', 'tiktok.webp' ),
			'keywords' => array( 'tiktok', 'tik tok', 'tiktok coins', 'تيك توك' ),
		),

		// Prepaid debit.
		'visa-prepaid'       => array(
			'files'    => array( 'visa-prepaid.webp', 'visa.webp' ),
			'keywords' => array( 'visa prepaid', 'visa gift', 'فيزا' ),
		),
		'mastercard-prepaid' => array(
			'files'    => array( 'mastercard-prepaid.webp', 'mastercard.webp' ),
			'keywords' => array( 'mastercard prepaid', 'mastercard gift', 'ماستركارد', 'ماستر كارد' ),
		),
	);

	/**
	 * Filter the gift-card asset map.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, array{files: string[], keywords: string[]}> $map Brand → asset/keyword map.
	 */
	$map = (array) apply_filters( 'nk_gift_card_asset_map', $map );

	return $map;
}

/**
 * Resolve the on-disk directory for gift-card images.
 *
 * @since 0.1.0
 * @return string
 */
function nk_gift_card_asset_dir(): string {
	return trailingslashit( NK_THEME_ASSET_DIR ) . 'img/gift-cards';
}

/**
 * Resolve the public URL base for gift-card images.
 *
 * @since 0.1.0
 * @return string
 */
function nk_gift_card_asset_url_base(): string {
	return trailingslashit( NK_THEME_ASSET_URL ) . 'img/gift-cards';
}

/**
 * Find the first existing webp file referenced by an asset entry.
 *
 * @since 0.1.0
 *
 * @param array<string, mixed> $asset Asset entry from the map.
 * @return string Filename (basename) or empty string.
 */
function nk_gift_card_existing_file( array $asset ): string {
	$files = array();
	if ( ! empty( $asset['files'] ) && is_array( $asset['files'] ) ) {
		$files = $asset['files'];
	} elseif ( ! empty( $asset['file'] ) ) {
		$files = array( (string) $asset['file'] );
	}

	foreach ( $files as $file ) {
		$file = basename( (string) $file );
		if ( '' !== $file && file_exists( nk_gift_card_asset_dir() . '/' . $file ) ) {
			return $file;
		}
	}

	return '';
}

/**
 * Return the parent product for a variation.
 *
 * @since 0.1.0
 *
 * @param mixed $product WC_Product or WC_Product_Variation.
 * @return mixed Parent product, or null.
 */
function nk_gift_card_parent_product( $product ) {
	if ( ! is_object( $product ) || ! method_exists( $product, 'get_parent_id' ) || ! function_exists( 'wc_get_product' ) ) {
		return null;
	}
	$parent_id = (int) $product->get_parent_id();
	if ( $parent_id <= 0 ) {
		return null;
	}
	$parent = wc_get_product( $parent_id );
	return $parent instanceof \WC_Product ? $parent : null;
}

/**
 * Lower-case + strip dashes/underscores + collapse whitespace.
 *
 * @since 0.1.0
 *
 * @param string $text Raw text.
 * @return string
 */
function nk_gift_card_normalize_match_text( $text ): string {
	$text = strtolower( (string) $text );
	$text = str_replace( array( '-', '_' ), ' ', $text );
	return (string) preg_replace( '/\s+/u', ' ', $text );
}

/**
 * Build the haystack used by the keyword matcher.
 *
 * @since 0.1.0
 *
 * @param mixed $product WC product.
 * @param mixed $parent  Optional parent product.
 * @return string Normalized haystack.
 */
function nk_gift_card_match_text( $product, $parent = null ): string {
	$chunks = array();

	foreach ( array( $product, $parent ) as $candidate ) {
		if ( ! is_object( $candidate ) || ! method_exists( $candidate, 'get_id' ) ) {
			continue;
		}

		$id = (int) $candidate->get_id();
		if ( method_exists( $candidate, 'get_name' ) ) {
			$chunks[] = (string) $candidate->get_name();
		}
		if ( method_exists( $candidate, 'get_sku' ) ) {
			$chunks[] = (string) $candidate->get_sku();
		}
		if ( $id > 0 ) {
			$chunks[] = (string) get_post_field( 'post_name', $id );
			$terms   = get_the_terms( $id, 'product_cat' );
			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
				foreach ( $terms as $term ) {
					$chunks[] = (string) $term->slug;
					$chunks[] = (string) $term->name;
				}
			}
		}
	}

	return nk_gift_card_normalize_match_text( implode( ' ', array_filter( $chunks ) ) );
}

/**
 * Detect whether a product looks like a gift-card candidate.
 *
 * @since 0.1.0
 *
 * @param mixed $product WC product.
 * @param mixed $parent  Optional parent product.
 * @return bool
 */
function nk_gift_card_is_candidate_product( $product, $parent = null ): bool {
	$haystack = nk_gift_card_match_text( $product, $parent );
	if ( '' === $haystack ) {
		return false;
	}
	if ( false !== strpos( $haystack, 'gift cards' ) || false !== strpos( $haystack, 'gift card' ) ) {
		return true;
	}
	if ( preg_match( '/\bgc\b/u', $haystack ) || preg_match( '/\bgc\s+/u', $haystack ) ) {
		return true;
	}
	return false !== strpos( $haystack, 'بطاقة' ) || false !== strpos( $haystack, 'card' );
}

/**
 * Resolve the brand asset for a product.
 *
 * Per-product `_ng_gift_card_brand` postmeta override takes precedence
 * (CSV bulk-import column), falling back to keyword-based first-hit-wins.
 *
 * @since 0.1.0
 *
 * @param mixed $product WC product.
 * @param mixed $parent  Optional parent product.
 * @return array<string, mixed>|null Asset entry with `key`, `file`, `matched_via`, or null.
 */
function nk_gift_card_asset_for_product( $product, $parent = null ): ?array {
	if ( ! $parent ) {
		$parent = nk_gift_card_parent_product( $product );
	}

	$map = nk_gift_card_asset_map();

	// Per-product override via _ng_gift_card_brand meta.
	foreach ( array( $product, $parent ) as $candidate ) {
		if ( ! is_object( $candidate ) || ! method_exists( $candidate, 'get_id' ) ) {
			continue;
		}
		$id = (int) $candidate->get_id();
		if ( $id <= 0 ) {
			continue;
		}
		$forced = strtolower( trim( (string) get_post_meta( $id, '_ng_gift_card_brand', true ) ) );
		if ( '' === $forced || ! isset( $map[ $forced ] ) ) {
			continue;
		}

		$asset = $map[ $forced ];
		$file  = nk_gift_card_existing_file( $asset );
		if ( '' === $file ) {
			break; // Override declared but no art on disk → fall through to keyword scan.
		}
		$asset['key']         = $forced;
		$asset['file']        = $file;
		$asset['matched_via'] = 'override';
		return $asset;
	}

	if ( ! nk_gift_card_is_candidate_product( $product, $parent ) ) {
		return null;
	}

	$haystack = nk_gift_card_match_text( $product, $parent );
	foreach ( $map as $key => $asset ) {
		foreach ( $asset['keywords'] as $keyword ) {
			if ( false !== strpos( $haystack, nk_gift_card_normalize_match_text( $keyword ) ) ) {
				$file = nk_gift_card_existing_file( $asset );
				if ( '' === $file ) {
					continue 2;
				}
				$asset['key']         = $key;
				$asset['file']        = $file;
				$asset['matched_via'] = 'keyword:' . $keyword;
				return $asset;
			}
		}
	}

	return null;
}

/**
 * Return the public image URL for a product, or empty string when no
 * brand asset matches.
 *
 * @since 0.1.0
 *
 * @param mixed $product WC product.
 * @param mixed $parent  Optional parent product.
 * @return string
 */
function nk_gift_card_image_url( $product, $parent = null ): string {
	$asset = nk_gift_card_asset_for_product( $product, $parent );
	if ( ! $asset || empty( $asset['file'] ) ) {
		return '';
	}
	return nk_gift_card_asset_url_base() . '/' . rawurlencode( (string) $asset['file'] );
}

/**
 * Strip promotional-suffix copy ("with chance to win", "اربح", etc.)
 * from product names and descriptions.
 *
 * @since 0.1.0
 *
 * @param string $text Raw text.
 * @return string
 */
function nk_gift_card_clean_product_name( $text ): string {
	$text = (string) $text;
	if ( '' === $text ) {
		return $text;
	}
	$original = $text;

	$patterns = array(
		'/\s*(?:[-–—|,:;]\s*)?(?:with\s+)?(?:a\s+)?chance\s+(?:to|of|in)\s+(?:win|winning|get|getting|receive|receiving|earn|earning)\b[^|،,;.\n\r<]*/iu',
		'/\s*(?:[-–—|,:;]\s*)?(?:for\s+)?(?:a\s+)?chance\s+to\s+(?:get|win)\b[^|،,;.\n\r<]*/iu',
		'/\s*(?:[-–—|,:;]\s*)?to\s+chance\s+in\s+getting\b[^|،,;.\n\r<]*/iu',
		'/\b(?:buy\s+and\s+win|win\s*\$?\d+)\b[^|،,;.\n\r<]*/iu',
		'/\bteam\s+of\s+the\s+year\s*[-–—:]\s*win\s*\$?\d+[^\|،,;.\n\r<]*/iu',
		'/\s*(?:[-–—|,:;،]\s*)?(?:مع\s+)?(?:فرصة|الفرصة)\s+(?:للفوز|فوز|للربح|ربح|للحصول|الحصول)\b[^|،,;.\n\r<]*/u',
		'/\s*(?:[-–—|,:;،]\s*)?(?:اربح|فز|فرصة\s+ربح)\b[^|،,;.\n\r<]*/u',
	);

	$text = preg_replace( $patterns, '', $text );
	if ( null === $text || $text === $original ) {
		return $original;
	}
	$text = preg_replace( '/\s+([|،,;:.])/u', '$1', $text );
	$text = preg_replace( '/([|،,;:])\s*([|،,;:])+/u', '$1', $text );
	$text = preg_replace( '/\s+/u', ' ', $text );
	$text = preg_replace( '/\s*[-–—|,:;،]\s*$/u', '', $text );

	return trim( (string) $text );
}

/**
 * Build an `<img>` tag for a gift-card product, or empty when no match.
 *
 * @since 0.1.0
 *
 * @param mixed                $product WC product.
 * @param string               $size    Image size keyword (currently unused — sizes are fixed).
 * @param string               $alt     Alt text override.
 * @param mixed                $parent  Optional parent product.
 * @param array<string, mixed> $attr    Additional HTML attributes (`class`, `loading`, `decoding`).
 * @return string Safe HTML.
 */
function nk_gift_card_image_html( $product, $size = 'woocommerce_thumbnail', $alt = '', $parent = null, $attr = array() ): string {
	unset( $size );
	$url = nk_gift_card_image_url( $product, $parent );
	if ( '' === $url ) {
		return '';
	}

	if ( '' === $alt && is_object( $product ) && method_exists( $product, 'get_name' ) ) {
		$alt = (string) $product->get_name();
	}

	$attr  = is_array( $attr ) ? $attr : array();
	$class = 'ng-gift-card-img';
	if ( ! empty( $attr['class'] ) ) {
		$class = trim( (string) $attr['class'] . ' ' . $class );
	}

	$html_attr = array(
		'src'      => esc_url( $url ),
		'class'    => esc_attr( $class ),
		'alt'      => esc_attr( nk_gift_card_clean_product_name( $alt ) ),
		'width'    => '400',
		'height'   => '225',
		'loading'  => $attr['loading'] ?? 'lazy',
		'decoding' => $attr['decoding'] ?? 'async',
	);

	$parts = array();
	foreach ( $html_attr as $name => $value ) {
		$parts[] = $name . '="' . esc_attr( (string) $value ) . '"';
	}

	return '<img ' . implode( ' ', $parts ) . '>';
}

/* ---------------------------------------------------------------------
 * Filter callbacks — preserved as global functions because filter
 * registrations reference them by name string.
 * ------------------------------------------------------------------- */

/**
 * Filter callback: replace stock product image with the gift-card brand image.
 *
 * @since 0.1.0
 *
 * @param string               $image    Existing image HTML.
 * @param mixed                $product  WC product.
 * @param string               $size     Image size keyword.
 * @param array<string, mixed> $attr     Image attributes.
 * @return string
 */
function nk_gift_card_filter_product_image( $image, $product, $size, $attr ): string {
	$gift_image = nk_gift_card_image_html( $product, $size, '', nk_gift_card_parent_product( $product ), $attr );
	return '' !== $gift_image ? $gift_image : (string) $image;
}

/**
 * Filter callback: hide gallery for gift-cards (the brand image is the entire visual).
 *
 * @since 0.1.0
 *
 * @param array<int> $image_ids Existing gallery IDs.
 * @param mixed      $product   WC product.
 * @return array<int>
 */
function nk_gift_card_filter_gallery_image_ids( $image_ids, $product ): array {
	return '' !== nk_gift_card_image_url( $product, nk_gift_card_parent_product( $product ) ) ? array() : (array) $image_ids;
}

/**
 * Filter callback for `post_thumbnail_html` — swap on product post type only.
 *
 * @since 0.1.0
 *
 * @param string               $html              Existing thumbnail HTML.
 * @param int                  $post_id           Post ID.
 * @param int                  $post_thumbnail_id Thumbnail attachment ID.
 * @param string|int[]         $size              Image size.
 * @param array<string, mixed> $attr              HTML attributes.
 * @return string
 */
function nk_gift_card_filter_post_thumbnail_html( $html, $post_id, $post_thumbnail_id, $size, $attr ): string {
	unset( $post_thumbnail_id );
	if ( ! $post_id || 'product' !== get_post_type( $post_id ) || ! function_exists( 'wc_get_product' ) ) {
		return (string) $html;
	}
	$product = wc_get_product( $post_id );
	if ( ! $product instanceof \WC_Product ) {
		return (string) $html;
	}
	$gift_image = nk_gift_card_image_html( $product, $size, '', null, is_array( $attr ) ? $attr : array() );
	return '' !== $gift_image ? $gift_image : (string) $html;
}

/**
 * Filter callback for the WC single-product gallery thumbnail HTML.
 *
 * @since 0.1.0
 *
 * @param string $html              Existing thumbnail HTML.
 * @param int    $post_thumbnail_id Thumbnail attachment ID.
 * @return string
 */
function nk_gift_card_filter_single_thumb( $html, $post_thumbnail_id ): string {
	unset( $post_thumbnail_id );
	global $product;
	if ( ! is_object( $product ) ) {
		return (string) $html;
	}
	$url = nk_gift_card_image_url( $product );
	if ( '' === $url ) {
		return (string) $html;
	}
	$alt = method_exists( $product, 'get_name' ) ? nk_gift_card_clean_product_name( $product->get_name() ) : '';
	$img = nk_gift_card_image_html( $product, 'large', $alt, null, array( 'class' => 'wp-post-image' ) );
	if ( '' === $img ) {
		return (string) $html;
	}
	return '<div data-thumb="' . esc_url( $url ) . '" data-thumb-alt="' . esc_attr( $alt ) . '" class="woocommerce-product-gallery__image"><a href="' . esc_url( $url ) . '">' . $img . '</a></div>';
}

/**
 * Filter callback for `woocommerce_cart_item_thumbnail`.
 *
 * @since 0.1.0
 *
 * @param string               $thumbnail Existing thumbnail HTML.
 * @param array<string, mixed> $cart_item Cart item array.
 * @return string
 */
function nk_gift_card_filter_cart_thumb( $thumbnail, $cart_item ): string {
	$product    = $cart_item['data'] ?? null;
	$gift_image = nk_gift_card_image_html(
		$product,
		'woocommerce_thumbnail',
		'',
		nk_gift_card_parent_product( $product ),
		array( 'class' => 'attachment-woocommerce_thumbnail size-woocommerce_thumbnail' )
	);
	return '' !== $gift_image ? $gift_image : (string) $thumbnail;
}

/**
 * Filter callback for `the_title` on product post type.
 *
 * @since 0.1.0
 *
 * @param string $title   Title.
 * @param int    $post_id Post ID.
 * @return string
 */
function nk_gift_card_filter_post_title( $title, $post_id = null ): string {
	if ( $post_id && 'product' === get_post_type( $post_id ) ) {
		return nk_gift_card_clean_product_name( $title );
	}
	return (string) $title;
}

/* ---------------------------------------------------------------------
 * Hook wiring — only run when WP is loaded (not during the smoke test).
 * ------------------------------------------------------------------- */

if ( function_exists( 'add_filter' ) && ! function_exists( 'ng_gift_card_asset_map' ) ) {
	add_filter( 'woocommerce_product_get_name', 'nk_gift_card_clean_product_name', 20 );
	add_filter( 'woocommerce_product_variation_get_name', 'nk_gift_card_clean_product_name', 20 );
	add_filter( 'woocommerce_product_get_description', 'nk_gift_card_clean_product_name', 20 );
	add_filter( 'woocommerce_product_get_short_description', 'nk_gift_card_clean_product_name', 20 );
	add_filter( 'woocommerce_product_variation_get_description', 'nk_gift_card_clean_product_name', 20 );
	add_filter( 'woocommerce_product_title', 'nk_gift_card_clean_product_name', 20 );
	add_filter( 'woocommerce_cart_item_name', 'nk_gift_card_clean_product_name', 20 );
	add_filter( 'woocommerce_order_item_name', 'nk_gift_card_clean_product_name', 20 );
	add_filter( 'the_title', 'nk_gift_card_filter_post_title', 20, 2 );
	add_filter( 'woocommerce_product_get_image', 'nk_gift_card_filter_product_image', 20, 4 );
	add_filter( 'woocommerce_product_get_gallery_image_ids', 'nk_gift_card_filter_gallery_image_ids', 20, 2 );
	add_filter( 'post_thumbnail_html', 'nk_gift_card_filter_post_thumbnail_html', 20, 5 );
	add_filter( 'woocommerce_single_product_image_thumbnail_html', 'nk_gift_card_filter_single_thumb', 20, 2 );
	add_filter( 'woocommerce_cart_item_thumbnail', 'nk_gift_card_filter_cart_thumb', 20, 2 );

	// One-shot transient bust on plugin upgrade.
	add_action(
		'init',
		static function () {
			if ( '1' === get_option( 'nk_gift_cards_assets_cache_busted_v1' ) ) {
				return;
			}
			delete_transient( 'ng_merchant_feed_xml' );
			delete_transient( 'ng_merchant_feed_tsv' );
			update_option( 'nk_gift_cards_assets_cache_busted_v1', '1', false );
		},
		20
	);
}
