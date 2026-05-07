<?php
/**
 * Rank Math bridge: homepage title + description + robots overrides,
 * JSON-LD entity scrubber (drops Person, demo.local, duplicate Store /
 * WebSite / WebPage nodes), Twitter card cleanup, OG image emission,
 * Facebook locale + site name overrides.
 *
 * Falls through gracefully when Rank Math is not active.
 *
 * @package NovaKeys\Commerce\SEO
 * @since   0.1.0
 */

namespace NovaKeys\Commerce\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Rank Math bridge + homepage SEO.
 *
 * @since 0.1.0
 */
final class Rank_Math_Bridge {

	/**
	 * Register all hooks.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function register(): void {
		// Document title (homepage canonical).
		add_filter( 'document_title_parts', array( __CLASS__, 'filter_document_title_parts' ), 99 );
		add_filter( 'rank_math/frontend/title', array( __CLASS__, 'filter_rank_math_title' ), 99 );
		add_filter( 'rank_math/frontend/description', array( __CLASS__, 'filter_rank_math_description' ), 99 );
		add_filter( 'rank_math/frontend/canonical', array( __CLASS__, 'filter_rank_math_canonical' ), 99 );
		add_filter( 'rank_math/frontend/robots', array( __CLASS__, 'filter_rank_math_robots' ), 99 );

		// Buffered head rewrites (homepage only).
		add_action( 'wp_head', array( __CLASS__, 'buffer_robots_meta_start' ), 0 );
		add_action( 'wp_head', array( __CLASS__, 'buffer_robots_meta_end' ), PHP_INT_MAX );
		add_action( 'wp_head', array( __CLASS__, 'buffer_twitter_card_start' ), 1 );
		add_action( 'wp_head', array( __CLASS__, 'buffer_twitter_card_end' ), PHP_INT_MAX - 1 );

		// Fallback meta when Rank Math is OFF.
		add_action( 'wp_head', array( __CLASS__, 'emit_fallback_meta' ), 1 );

		// Optional dedup pass for duplicate description tags.
		$dedup = ( defined( 'NK_SEO_DEDUP_DESC' ) && NK_SEO_DEDUP_DESC )
			|| ( defined( 'NG_SEO_DEDUP_DESC' ) && NG_SEO_DEDUP_DESC );
		if ( $dedup ) {
			add_action( 'wp_head', array( __CLASS__, 'buffer_dedup_desc_start' ), 0 );
			add_action( 'wp_head', array( __CLASS__, 'buffer_dedup_desc_end' ), 9999 );
		}

		// JSON-LD entity scrub.
		add_filter( 'rank_math/json_ld', array( __CLASS__, 'scrub_json_ld' ), 99, 2 );

		// OG / Twitter image — feed through Rank Math when present.
		add_filter( 'rank_math/opengraph/facebook/og_image', array( __CLASS__, 'og_image_url' ), 99 );
		add_filter( 'rank_math/opengraph/facebook/og_image_secure_url', array( __CLASS__, 'og_image_url' ), 99 );
		add_filter( 'rank_math/opengraph/twitter/twitter_image', array( __CLASS__, 'twitter_image_url' ), 99 );
		add_filter( 'rank_math/opengraph/facebook/og_image_width', static function () { return 1200; }, 99 );
		add_filter( 'rank_math/opengraph/facebook/og_image_height', static function () { return 630; }, 99 );
		add_filter( 'rank_math/opengraph/facebook/site_name', static function () { return 'NovaKeys Store'; } );
		add_filter( 'rank_math/opengraph/facebook/og_locale', static function () { return 'ar_SA'; } );

		// Direct OG/Twitter emission only when Rank Math is OFF.
		add_action( 'wp_head', array( __CLASS__, 'emit_fallback_og' ), 5 );

		// Author display rewrite — global override of "admin".
		add_filter( 'the_author', array( __CLASS__, 'filter_author_display' ), 1 );
		add_filter( 'get_the_author_display_name', array( __CLASS__, 'filter_author_display' ), 1 );
		foreach ( array( 'user_nicename', 'first_name', 'nickname' ) as $field ) {
			add_filter( "the_author_{$field}", array( __CLASS__, 'filter_author_display' ), 1 );
		}
	}

	/**
	 * Canonical homepage AR description (~145 visual chars).
	 *
	 * @since 0.1.0
	 * @return string
	 */
	public static function home_description_ar(): string {
		return 'NovaKeys Store — متجر تقني سعودي للشبكات، الهوم لاب، البيوت الذكية، والألعاب. شحن من داخل المملكة، ضمان 12 شهر، إرجاع 14 يوم.';
	}

	/**
	 * Canonical homepage title — fixes the duplicate-brand bug.
	 *
	 * @since 0.1.0
	 * @return string
	 */
	public static function home_title_ar(): string {
		return 'NovaKeys Store · متجر تقني سعودي للشبكات والهوم لاب والبيوت الذكية';
	}

	/**
	 * @since 0.1.0
	 * @param array<string, string> $parts Title parts.
	 * @return array<string, string>
	 */
	public static function filter_document_title_parts( $parts ): array {
		if ( is_front_page() || is_home() ) {
			return array( 'title' => self::home_title_ar() );
		}
		return (array) $parts;
	}

	/**
	 * @since 0.1.0
	 * @param string $title Title.
	 * @return string
	 */
	public static function filter_rank_math_title( $title ): string {
		if ( is_front_page() || is_home() ) {
			return self::home_title_ar();
		}
		return (string) $title;
	}

	/**
	 * @since 0.1.0
	 * @param string $description Description.
	 * @return string
	 */
	public static function filter_rank_math_description( $description ): string {
		if ( is_front_page() || is_home() ) {
			return self::home_description_ar();
		}
		return (string) $description;
	}

	/**
	 * @since 0.1.0
	 * @param string $canonical Canonical URL.
	 * @return string
	 */
	public static function filter_rank_math_canonical( $canonical ): string {
		if ( ! is_string( $canonical ) ) {
			return (string) $canonical;
		}
		return (string) preg_replace( '#https?://(?:www\.)?ngs1\.blazr\.net#i', 'https://novakeys.store', $canonical );
	}

	/**
	 * Force homepage robots to index, follow.
	 *
	 * @since 0.1.0
	 * @param array<string, string> $robots Robots map.
	 * @return array<string, string>
	 */
	public static function filter_rank_math_robots( $robots ): array {
		if ( ! ( is_front_page() || is_home() ) ) {
			return (array) $robots;
		}
		return array(
			'index'             => 'index',
			'follow'            => 'follow',
			'max-snippet'       => 'max-snippet:-1',
			'max-video-preview' => 'max-video-preview:-1',
			'max-image-preview' => 'max-image-preview:large',
		);
	}

	/**
	 * Open buffered head rewrite — collapse robots meta to one canonical.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function buffer_robots_meta_start(): void {
		if ( ! ( is_front_page() || is_home() ) ) {
			return;
		}
		ob_start();
	}

	/**
	 * Close buffer + collapse multiple robots meta lines into one.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function buffer_robots_meta_end(): void {
		if ( ! ( is_front_page() || is_home() ) ) {
			return;
		}
		$html = ob_get_clean();
		if ( ! is_string( $html ) || '' === $html ) {
			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- buffered head HTML, not user input.
			return;
		}

		$canonical = '<meta name="robots" content="index, follow, max-snippet:-1, max-video-preview:-1, max-image-preview:large">';
		$count     = 0;
		$html      = preg_replace_callback(
			'#<meta\s+name=["\']robots["\'][^>]*>#i',
			static function () use ( $canonical, &$count ) {
				++$count;
				return 1 === $count ? $canonical : '';
			},
			$html
		);
		if ( 0 === $count ) {
			$html = $canonical . "\n" . $html;
		}
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- canonical meta + buffered head HTML.
	}

	/**
	 * Buffer head start — strip Twitter "label/data" pairs Rank Math
	 * emits on the homepage (article-style metadata that misreads on a
	 * storefront).
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function buffer_twitter_card_start(): void {
		if ( ! ( is_front_page() || is_home() ) ) {
			return;
		}
		ob_start();
	}

	/**
	 * Close Twitter card buffer.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function buffer_twitter_card_end(): void {
		if ( ! ( is_front_page() || is_home() ) ) {
			return;
		}
		$html = ob_get_clean();
		if ( ! is_string( $html ) || '' === $html ) {
			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- buffered head HTML.
			return;
		}
		$html = preg_replace(
			'#\s*<meta\s+name=["\']twitter:(?:label|data)[12]["\'][^>]*>#i',
			'',
			$html
		);
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- buffered head HTML.
	}

	/**
	 * Optional dedup buffer — keeps first description meta, drops the rest.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function buffer_dedup_desc_start(): void {
		if ( ! ( is_front_page() || is_home() ) ) {
			return;
		}
		ob_start();
	}

	/**
	 * Close dedup buffer.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function buffer_dedup_desc_end(): void {
		if ( ! ( is_front_page() || is_home() ) ) {
			return;
		}
		$html = ob_get_clean();
		if ( ! is_string( $html ) ) {
			return;
		}
		$count = 0;
		$html  = preg_replace_callback(
			'#<meta\s+name=["\']description["\'][^>]*>#i',
			static function ( $m ) use ( &$count ) {
				++$count;
				return 1 === $count ? $m[0] : '';
			},
			$html
		);
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- buffered head HTML.
	}

	/**
	 * Emit description + robots meta directly when Rank Math is not
	 * active. When Rank Math is present, it owns these tags and we stay
	 * out of the way.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function emit_fallback_meta(): void {
		if ( ! ( is_front_page() || is_home() ) ) {
			return;
		}
		if ( class_exists( 'RankMath' ) ) {
			return;
		}

		echo "\n<!-- NovaKeys SEO: canonical home description (no Rank Math) -->\n";
		echo '<meta name="description" content="' . esc_attr( self::home_description_ar() ) . '">' . "\n";
		echo '<meta name="robots" content="index, follow, max-snippet:-1, max-video-preview:-1, max-image-preview:large">' . "\n";
	}

	/**
	 * JSON-LD entity scrubber. Drops Person, demo.local nodes, homepage
	 * Article/BlogPosting, and Rank Math's competing Store / WebSite /
	 * WebPage nodes (the canonical Store node is emitted from the theme).
	 *
	 * @since 0.1.0
	 *
	 * @param array<int|string, mixed> $data    JSON-LD graph data.
	 * @param mixed                    $jsonld  Rank Math JSON-LD instance (unused).
	 * @return array<int|string, mixed>
	 */
	public static function scrub_json_ld( $data, $jsonld ) {
		unset( $jsonld );
		if ( ! is_array( $data ) || empty( $data ) ) {
			return (array) $data;
		}
		$is_home    = is_front_page() || is_home();
		$drop_types = array( 'Organization', 'ElectronicsStore', 'Store', 'LocalBusiness', 'OnlineStore', 'WebSite', 'WebPage' );

		foreach ( $data as $key => $node ) {
			if ( ! is_array( $node ) ) {
				continue;
			}

			if ( isset( $node['url'] ) && false !== stripos( (string) $node['url'], 'demo.local' ) ) {
				unset( $data[ $key ] );
				continue;
			}
			if ( isset( $node['@type'] ) && 'Person' === $node['@type'] ) {
				unset( $data[ $key ] );
				continue;
			}
			if ( isset( $node['@id'] ) && false !== stripos( (string) $node['@id'], '/author/' ) ) {
				unset( $data[ $key ] );
				continue;
			}
			if ( $is_home && isset( $node['@type'] )
				&& in_array( $node['@type'], array( 'Article', 'BlogPosting', 'NewsArticle' ), true ) ) {
				unset( $data[ $key ] );
				continue;
			}
			if ( isset( $node['@type'] ) ) {
				$node_types = is_array( $node['@type'] ) ? $node['@type'] : array( $node['@type'] );
				foreach ( $node_types as $t ) {
					if ( in_array( $t, $drop_types, true ) ) {
						unset( $data[ $key ] );
						continue 2;
					}
				}
			}
			if ( isset( $node['name'] )
				&& (
					false !== stripos( (string) $node['name'], 'بلازر' )
					|| false !== stripos( (string) $node['name'], 'blazr' )
					|| false !== stripos( (string) $node['name'], 'جيل التقنية' )
				)
			) {
				$data[ $key ]['name']          = 'NovaKeys Store';
				$data[ $key ]['alternateName'] = 'نيوجين ستور';
			}
			foreach ( array( 'image', 'logo', 'thumbnailUrl' ) as $img_key ) {
				if ( ! isset( $node[ $img_key ] ) ) {
					continue;
				}
				if ( is_string( $node[ $img_key ] ) && '' !== $node[ $img_key ] && '/' === $node[ $img_key ][0] ) {
					$data[ $key ][ $img_key ] = home_url( $node[ $img_key ] );
				} elseif ( is_array( $node[ $img_key ] ) && isset( $node[ $img_key ]['url'] )
					&& is_string( $node[ $img_key ]['url'] ) && '' !== $node[ $img_key ]['url']
					&& '/' === $node[ $img_key ]['url'][0] ) {
					$data[ $key ][ $img_key ]['url'] = home_url( $node[ $img_key ]['url'] );
				}
			}
			if ( isset( $node['slogan'], $node['name'] )
				&& trim( (string) $node['slogan'] ) === trim( (string) $node['name'] ) ) {
				unset( $data[ $key ]['slogan'] );
			}
			if ( isset( $node['sameAs'] ) && is_array( $node['sameAs'] ) ) {
				$data[ $key ]['sameAs'] = array_values(
					array_filter(
						$node['sameAs'],
						static function ( $u ) {
							return false === stripos( (string) $u, 'demo.local' );
						}
					)
				);
				if ( empty( $data[ $key ]['sameAs'] ) ) {
					unset( $data[ $key ]['sameAs'] );
				}
			}
		}

		return array_values( array_filter( $data ) );
	}

	/**
	 * Resolve OG image URL — locale-aware static asset.
	 *
	 * @since 0.1.0
	 * @return string
	 */
	public static function og_image_url(): string {
		$base   = content_url( 'mu-plugins/novakeys-custom/mu-plugins/neogen-theme-assets/img/social' );
		$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
		$is_ar  = 0 === strpos( (string) $locale, 'ar' );
		return $base . ( $is_ar ? '/og-default-ar.png' : '/og-default-en.png' );
	}

	/**
	 * Resolve Twitter image URL — locale-aware static asset.
	 *
	 * @since 0.1.0
	 * @return string
	 */
	public static function twitter_image_url(): string {
		$base   = content_url( 'mu-plugins/novakeys-custom/mu-plugins/neogen-theme-assets/img/social' );
		$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
		$is_ar  = 0 === strpos( (string) $locale, 'ar' );
		return $base . ( $is_ar ? '/twitter-card-ar.png' : '/twitter-card-en.png' );
	}

	/**
	 * Direct OG / Twitter meta when Rank Math is OFF.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function emit_fallback_og(): void {
		if ( class_exists( 'RankMath' ) ) {
			return;
		}

		$og   = esc_url( self::og_image_url() );
		$tw   = esc_url( self::twitter_image_url() );
		$url  = esc_url( is_singular() ? get_permalink() : home_url( '/' ) );
		$cr   = function_exists( 'nk_cr' ) ? nk_cr() : array();
		$name = ! empty( $cr['brand_en'] ) ? $cr['brand_en'] : 'NovaKeys Store';

		echo "\n<!-- NovaKeys OG/Twitter -->\n";
		echo '<meta property="og:type" content="website">' . "\n";
		echo '<meta property="og:site_name" content="' . esc_attr( $name ) . '">' . "\n";
		echo '<meta property="og:locale" content="ar_SA">' . "\n";
		echo '<meta property="og:locale:alternate" content="en_US">' . "\n";
		echo '<meta property="og:url" content="' . $url . '">' . "\n";
		echo '<meta property="og:image" content="' . $og . '">' . "\n";
		echo '<meta property="og:image:width" content="1200">' . "\n";
		echo '<meta property="og:image:height" content="630">' . "\n";
		echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
		echo '<meta name="twitter:image" content="' . $tw . '">' . "\n";
	}

	/**
	 * Replace literal "admin" author name with the brand.
	 *
	 * @since 0.1.0
	 *
	 * @param mixed $name Author name.
	 * @return string
	 */
	public static function filter_author_display( $name ): string {
		return 'admin' === strtolower( (string) $name ) ? 'NovaKeys Store' : (string) $name;
	}
}

Rank_Math_Bridge::register();
