<?php
/**
 * MCP / REST meta guard.
 *
 * Strips encrypted gift-card meta and other sensitive `_ng_*` fields from
 * outbound WordPress / WooCommerce REST responses, including those served
 * via the WordPress/mcp-adapter plugin to Claude Code's MCP client. Without
 * this guard, an MCP tool like `get_product` could return the encrypted
 * `_ng_gift_card_code` ciphertext to any caller authenticated to the API.
 *
 * Loaded by `class-plugin.php` when the MCP integration is activated.
 *
 * @package NovaKeys\Commerce\Security
 * @since   0.1.0
 */

namespace NovaKeys\Commerce\Security;

defined( 'ABSPATH' ) || exit;

/**
 * MCP / REST meta guard.
 *
 * @since 0.1.0
 */
final class MCP_Meta_Guard {

	/**
	 * Meta keys that must never traverse the REST/MCP boundary.
	 *
	 * Encrypted gift-card payloads, status, expiry, and routing meta. The MCP
	 * adapter must not surface these to clients — even read-only queries
	 * leaking the ciphertext degrade the threat model and risk customer-facing
	 * status flips driven by stale snapshots.
	 *
	 * @since 0.1.0
	 * @var string[]
	 */
	private const PROTECTED_KEYS = array(
		'_ng_gift_card_code',
		'_ng_gift_card_status',
		'_ng_gift_card_expires_at',
		'_ng_gift_card_brand',
		'_ng_gift_card_region',
	);

	/**
	 * Register the meta-guard hooks.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function register(): void {
		// Generic WP REST product filter (used when WC REST is unavailable).
		add_filter( 'rest_prepare_product', array( __CLASS__, 'strip_from_response' ), 10, 3 );
		add_filter( 'rest_prepare_shop_order', array( __CLASS__, 'strip_from_response' ), 10, 3 );

		// WooCommerce REST product object (legacy + v3).
		add_filter( 'woocommerce_rest_prepare_product_object', array( __CLASS__, 'strip_from_response' ), 10, 3 );

		// WooCommerce REST shop order object (legacy + v3).
		add_filter( 'woocommerce_rest_prepare_shop_order_object', array( __CLASS__, 'strip_from_response' ), 10, 3 );

		// HPOS / Custom Order Tables order responses.
		add_filter( 'woocommerce_rest_prepare_order_object', array( __CLASS__, 'strip_from_response' ), 10, 3 );

		// Order line-item meta on order endpoints — strip from each line item too.
		add_filter( 'woocommerce_rest_prepare_order_object', array( __CLASS__, 'strip_from_line_items' ), 11, 3 );

		// Block protected keys from being written via REST PATCH/PUT.
		add_filter( 'is_protected_meta', array( __CLASS__, 'is_protected_meta' ), 10, 2 );
	}

	/**
	 * Strip protected meta keys from a REST response.
	 *
	 * @since 0.1.0
	 * @param \WP_REST_Response                              $response Outbound response.
	 * @param \WP_Post|\WC_Data|\WC_Order|\WC_Product        $object   Post or CRUD object being prepared.
	 * @param \WP_REST_Request                               $request  Inbound request.
	 * @return \WP_REST_Response                                       Filtered response.
	 */
	public static function strip_from_response( $response, $object, $request ) {
		unset( $object, $request );

		if ( ! is_object( $response ) || ! method_exists( $response, 'get_data' ) ) {
			return $response;
		}

		$data = $response->get_data();
		if ( ! is_array( $data ) ) {
			return $response;
		}

		if ( isset( $data['meta_data'] ) && is_array( $data['meta_data'] ) ) {
			$data['meta_data'] = self::filter_meta_array( $data['meta_data'] );
		}

		// Some endpoints expose meta under `meta` instead of `meta_data`.
		if ( isset( $data['meta'] ) && is_array( $data['meta'] ) ) {
			$data['meta'] = self::filter_meta_assoc( $data['meta'] );
		}

		$response->set_data( $data );
		return $response;
	}

	/**
	 * Strip protected meta from each line item on an order response.
	 *
	 * @since 0.1.0
	 * @param \WP_REST_Response       $response Outbound response.
	 * @param \WC_Order               $order    Order being prepared.
	 * @param \WP_REST_Request        $request  Inbound request.
	 * @return \WP_REST_Response                Filtered response.
	 */
	public static function strip_from_line_items( $response, $order, $request ) {
		unset( $order, $request );

		if ( ! is_object( $response ) || ! method_exists( $response, 'get_data' ) ) {
			return $response;
		}

		$data = $response->get_data();
		if ( ! is_array( $data ) || empty( $data['line_items'] ) || ! is_array( $data['line_items'] ) ) {
			return $response;
		}

		foreach ( $data['line_items'] as $idx => $item ) {
			if ( isset( $item['meta_data'] ) && is_array( $item['meta_data'] ) ) {
				$data['line_items'][ $idx ]['meta_data'] = self::filter_meta_array( $item['meta_data'] );
			}
		}

		$response->set_data( $data );
		return $response;
	}

	/**
	 * Mark protected keys as protected meta — blocks REST writes.
	 *
	 * @since 0.1.0
	 * @param bool   $protected Whether the meta key is protected.
	 * @param string $meta_key  Meta key under check.
	 * @return bool             True if protected.
	 */
	public static function is_protected_meta( $protected, $meta_key ): bool {
		if ( in_array( $meta_key, self::PROTECTED_KEYS, true ) ) {
			return true;
		}
		return (bool) $protected;
	}

	/**
	 * Filter a list-of-objects meta array (each entry has `key` / `value`).
	 *
	 * @since 0.1.0
	 * @param array<int, array<string, mixed>> $meta_data Array of meta entries.
	 * @return array<int, array<string, mixed>>           Entries with protected keys removed.
	 */
	private static function filter_meta_array( array $meta_data ): array {
		return array_values(
			array_filter(
				$meta_data,
				static function ( $entry ): bool {
					if ( ! is_array( $entry ) && ! is_object( $entry ) ) {
						return true;
					}
					$key = is_array( $entry ) ? ( $entry['key'] ?? '' ) : ( $entry->key ?? '' );
					return ! in_array( (string) $key, self::PROTECTED_KEYS, true );
				}
			)
		);
	}

	/**
	 * Filter an associative meta map.
	 *
	 * @since 0.1.0
	 * @param array<string, mixed> $meta Associative meta map.
	 * @return array<string, mixed>      Map with protected keys removed.
	 */
	private static function filter_meta_assoc( array $meta ): array {
		foreach ( self::PROTECTED_KEYS as $key ) {
			unset( $meta[ $key ] );
		}
		return $meta;
	}

	/**
	 * Public accessor — used by tests and audit tooling.
	 *
	 * @since 0.1.0
	 * @return string[]
	 */
	public static function protected_keys(): array {
		return self::PROTECTED_KEYS;
	}
}
