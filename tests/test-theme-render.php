<?php
/**
 * Smoke test for the NovaKeys block theme.
 *
 * Validates that every theme.json file is syntactically valid, every
 * pattern PHP file parses, every template HTML file is non-empty and
 * starts with a wp:template-part header, and that no template still
 * references the deleted `mu-plugins/novakeys-custom/` asset path.
 *
 * Plain PHP — no PHPUnit. Run with:
 *
 *   php tests/test-theme-render.php
 *
 * Exits 0 on pass, 1 on any failure.
 *
 * @package NovaKeys
 */

declare( strict_types = 1 );

$root  = dirname( __DIR__ );
$theme = $root . '/themes/novakeys';

$failures = array();
$checks   = 0;

if ( ! function_exists( 'nk_assert' ) ) {
	function nk_assert( bool $cond, string $msg ): void {
		global $failures, $checks;
		$checks++;
		if ( ! $cond ) {
			$failures[] = $msg;
			fwrite( STDERR, "  [FAIL] {$msg}\n" );
		}
	}
}

echo "NovaKeys theme smoke test\n";
echo "=========================\n";

// 1. theme.json + style variations parse.
foreach ( array(
	$theme . '/theme.json',
	$theme . '/styles/dark.json',
) as $json_path ) {
	nk_assert( is_readable( $json_path ), "JSON readable: {$json_path}" );
	$body = (string) file_get_contents( $json_path );
	$decoded = json_decode( $body, true );
	nk_assert(
		JSON_ERROR_NONE === json_last_error(),
		"JSON parses: {$json_path} (" . json_last_error_msg() . ')'
	);
	nk_assert(
		isset( $decoded['version'] ) && 3 === (int) $decoded['version'],
		"theme.json version is 3: {$json_path}"
	);
}

// 2. Every pattern PHP parses.
$patterns = glob( $theme . '/patterns/*.php' ) ?: array();
nk_assert( count( $patterns ) >= 12, 'At least 12 patterns present (got ' . count( $patterns ) . ')' );
foreach ( $patterns as $p ) {
	$out  = array();
	$rc   = 1;
	exec( 'php -l ' . escapeshellarg( $p ) . ' 2>&1', $out, $rc );
	nk_assert( 0 === $rc, "Pattern parses: {$p}" );
}

// 3. Every template HTML is non-empty and references a header part.
$templates = glob( $theme . '/templates/*.html' ) ?: array();
nk_assert( count( $templates ) >= 10, 'At least 10 templates present (got ' . count( $templates ) . ')' );
foreach ( $templates as $t ) {
	$body = (string) file_get_contents( $t );
	nk_assert( strlen( $body ) > 0, "Template non-empty: {$t}" );
	$has_header = false !== strpos( $body, 'wp:template-part {"slug":"header"' );
	$is_legal   = basename( $t ) === 'page-legal.html';
	nk_assert( $has_header || $is_legal, "Template includes header part: {$t}" );
}

// 4. No template references the deleted mu-plugins/novakeys-custom path.
foreach ( $templates as $t ) {
	$body = (string) file_get_contents( $t );
	nk_assert(
		false === strpos( $body, 'mu-plugins/novakeys-custom' ),
		"No legacy mu-plugins path in: {$t}"
	);
}

// 5. functions.php parses.
$out = array();
$rc  = 1;
exec( 'php -l ' . escapeshellarg( $theme . '/functions.php' ) . ' 2>&1', $out, $rc );
nk_assert( 0 === $rc, 'functions.php parses' );

// 6. Self-hosted fonts present (or at least the dir).
$fonts_dir = $theme . '/assets/fonts';
nk_assert( is_dir( $fonts_dir ), 'assets/fonts/ directory exists' );

// Required font files per theme.json fontFace declarations.
$required_fonts = array(
	'space-grotesk-500.woff2',
	'space-grotesk-700.woff2',
	'inter-400.woff2',
	'inter-500.woff2',
	'inter-600.woff2',
	'jetbrains-mono-400.woff2',
	'jetbrains-mono-500.woff2',
	'ibm-plex-sans-arabic-400.woff2',
	'ibm-plex-sans-arabic-500.woff2',
	'ibm-plex-sans-arabic-700.woff2',
);
foreach ( $required_fonts as $font ) {
	nk_assert( is_readable( $fonts_dir . '/' . $font ), "Font present: {$font}" );
}

// Summary.
echo "\n";
echo str_repeat( '-', 30 ) . "\n";
echo "Checks: {$checks}\n";
echo 'Failures: ' . count( $failures ) . "\n";
if ( 0 === count( $failures ) ) {
	echo "PASS\n";
	exit( 0 );
}
echo "FAIL\n";
foreach ( $failures as $f ) {
	echo "  - {$f}\n";
}
exit( 1 );
