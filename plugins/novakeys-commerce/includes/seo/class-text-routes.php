<?php
/**
 * Text-only routes: /ads.txt, /llms.txt, plus a `robots_txt` filter that
 * appends explicit Allow lines for citation crawlers.
 *
 * @package NovaKeys\Commerce\SEO
 * @since   0.1.0
 */

namespace NovaKeys\Commerce\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Text-only SEO routes.
 *
 * @since 0.1.0
 */
final class Text_Routes {

	/**
	 * Register the route handlers.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function register(): void {
		add_action( 'init', array( __CLASS__, 'maybe_serve_ads_txt' ), 1 );
		add_action( 'init', array( __CLASS__, 'maybe_serve_llms_txt' ), 1 );
		add_filter( 'robots_txt', array( __CLASS__, 'filter_robots_txt' ), 10, 2 );
	}

	/**
	 * Serve `/ads.txt` when the request matches.
	 *
	 * Reads the AdSense client ID from Site Kit's option, falling back to
	 * `nk_adsense_client_id` then the legacy `ng_adsense_client_id`. Refuses
	 * to emit a publisher line unless the value matches Google's
	 * `pub-NNNNNNNNNNNNNNNN` shape.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function maybe_serve_ads_txt(): void {
		if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}
		$path = strtok( (string) $_SERVER['REQUEST_URI'], '?' );
		if ( '/ads.txt' !== $path && '/ads.txt/' !== $path ) {
			return;
		}

		nocache_headers();
		header( 'Content-Type: text/plain; charset=utf-8' );

		$sitekit  = get_option( 'googlesitekit_adsense_settings', array() );
		$client   = '';
		if ( is_array( $sitekit ) && ! empty( $sitekit['clientID'] ) ) {
			$client = (string) $sitekit['clientID'];
		}
		if ( '' === $client ) {
			$client = (string) get_option( 'nk_adsense_client_id', '' );
		}
		if ( '' === $client ) {
			$client = (string) get_option( 'ng_adsense_client_id', '' );
		}
		if ( '' === $client ) {
			echo "# ads.txt placeholder — no AdSense publisher configured yet.\n";
			exit;
		}

		$pub = preg_replace( '/^ca-/i', '', $client );
		if ( ! preg_match( '/^pub-\d+$/', (string) $pub ) ) {
			echo "# ads.txt — malformed AdSense publisher ID, refusing to serve.\n";
			echo "# Set nk_adsense_client_id option to a valid 'pub-NNNNNNNNNNNNNNNN' value.\n";
			exit;
		}

		echo "# ads.txt — novakeys.store · auto-generated\n";
		echo 'google.com, ' . $pub . ", DIRECT, f08c47fec0942fa0\n";
		exit;
	}

	/**
	 * Serve `/llms.txt` when the request matches.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function maybe_serve_llms_txt(): void {
		if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}
		$path = strtok( (string) $_SERVER['REQUEST_URI'], '?' );
		if ( '/llms.txt' !== $path && '/llms.txt/' !== $path ) {
			return;
		}

		nocache_headers();
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'X-Robots-Tag: noindex, follow', true );

		$home = rtrim( home_url( '/' ), '/' );

		echo "# NovaKeys Store\n";
		echo "# Saudi tech retail · networking · homelab · smart home · gaming\n";
		echo '# Updated: ' . esc_html( gmdate( 'Y-m-d' ) ) . "\n\n";
		echo "## Identity\n";
		echo "Name: NovaKeys Store\n";
		echo "Name (AR): نيوجين ستور\n";
		echo 'URL: ' . esc_url_raw( $home . '/' ) . "\n";
		echo "Country: Saudi Arabia\n";
		echo "Languages: ar-SA, en\n\n";
		echo "## Primary URLs\n";
		echo 'Home: ' . esc_url_raw( $home . '/' ) . "\n";
		echo 'Shop: ' . esc_url_raw( $home . '/shop/' ) . "\n";

		// Categories — prefer nk_top_product_cats(), fall back to legacy ng_*.
		$cats_fn = '';
		if ( function_exists( 'nk_top_product_cats' ) ) {
			$cats_fn = 'nk_top_product_cats';
		} elseif ( function_exists( 'ng_top_product_cats' ) ) {
			$cats_fn = 'ng_top_product_cats';
		}
		if ( taxonomy_exists( 'product_cat' ) && '' !== $cats_fn ) {
			echo "\n## Categories\n";
			$cats = call_user_func( $cats_fn, 12 );
			foreach ( (array) $cats as $term ) {
				$link = get_term_link( $term );
				if ( ! is_wp_error( $link ) ) {
					echo $term->name . ': ' . esc_url_raw( $link ) . "\n";
				}
			}
		}

		echo "\n## Information\n";
		foreach ( array( 'about', 'shipping', 'returns', 'warranty', 'privacy', 'terms', 'contact' ) as $slug ) {
			echo ucfirst( $slug ) . ': ' . esc_url_raw( $home . '/' . $slug . '/' ) . "\n";
		}
		echo 'Legal disclosure: ' . esc_url_raw( $home . '/legal/' ) . "\n\n";
		echo "## Notes\n";
		echo "- Single-merchant Saudi e-commerce, CR 7053130576.\n";
		echo "- Catalog is curated; SKUs are vetted, not drop-shipped.\n";
		echo "- Payment: Mada, Apple Pay, STC Pay, Tabby.\n";
		echo "- Shipping: Riyadh, Jeddah, Dammam (2-5 business days).\n";

		exit;
	}

	/**
	 * Append explicit Allow lines for citation crawlers and an explicit
	 * Disallow for `anthropic-ai` (until Cloudflare's managed list covers it).
	 *
	 * @since 0.1.0
	 *
	 * @param string $output  Existing robots.txt body.
	 * @param bool   $public  Whether the site is publicly indexable.
	 * @return string
	 */
	public static function filter_robots_txt( $output, $public ): string {
		if ( ! $public ) {
			return (string) $output;
		}
		$rules  = "\n# Citation / share crawlers — explicit allow (per novakeys.store)\n";
		$rules .= "User-agent: ChatGPT-User\nAllow: /\n\n";
		$rules .= "User-agent: PerplexityBot\nAllow: /\n\n";
		$rules .= "User-agent: FacebookBot\nAllow: /\n\n";
		$rules .= "User-agent: anthropic-ai\nDisallow: /\n\n";
		return (string) $output . $rules;
	}
}

Text_Routes::register();
