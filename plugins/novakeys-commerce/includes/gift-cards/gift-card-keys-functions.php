<?php
/**
 * Procedural wrappers for the gift-card key vault.
 *
 * Templates and external code call these by name; the underlying logic
 * lives in `class-vault.php` / `class-store.php` under the same dir.
 *
 * @package NovaKeys\Commerce\Gift_Cards
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'nk_gck_key' ) ) {
	/**
	 * Derive the AES key.
	 *
	 * @since 0.1.0
	 * @return string Raw 32-byte key.
	 */
	function nk_gck_key(): string {
		return \NovaKeys\Commerce\Gift_Cards\Vault::key();
	}
}

if ( ! function_exists( 'nk_gck_encrypt' ) ) {
	/**
	 * Encrypt plaintext (`enc:v2:` GCM by default).
	 *
	 * @since 0.1.0
	 *
	 * @param string $plain Plaintext.
	 * @return string
	 */
	function nk_gck_encrypt( $plain ): string {
		return \NovaKeys\Commerce\Gift_Cards\Vault::encrypt( (string) $plain );
	}
}

if ( ! function_exists( 'nk_gck_decrypt' ) ) {
	/**
	 * Decrypt a cipher envelope. Handles `enc:v1:` and `enc:v2:`.
	 *
	 * @since 0.1.0
	 *
	 * @param string $cipher Envelope or legacy plaintext.
	 * @return string
	 */
	function nk_gck_decrypt( $cipher ): string {
		return \NovaKeys\Commerce\Gift_Cards\Vault::decrypt( (string) $cipher );
	}
}

if ( ! function_exists( 'nk_gift_card_set_code' ) ) {
	/**
	 * Set / clear the gift-card code on a specific order item.
	 *
	 * @since 0.1.0
	 *
	 * @param int                  $order_id Order ID.
	 * @param int                  $item_id  Order line-item ID.
	 * @param string               $code     Plaintext code.
	 * @param array<string, mixed> $extras   Optional metadata.
	 * @return bool
	 */
	function nk_gift_card_set_code( $order_id, $item_id, $code, $extras = array() ): bool {
		return \NovaKeys\Commerce\Gift_Cards\Store::set_code( (int) $order_id, (int) $item_id, (string) $code, (array) $extras );
	}
}

if ( ! function_exists( 'nk_get_gift_card_keys' ) ) {
	/**
	 * Return all gift-card keys for a user.
	 *
	 * @since 0.1.0
	 *
	 * @param int $user_id User ID. Defaults to current user.
	 * @return array<int, array<string, mixed>>
	 */
	function nk_get_gift_card_keys( $user_id = 0 ): array {
		return \NovaKeys\Commerce\Gift_Cards\Store::get_keys_for_user( (int) $user_id );
	}
}
