<?php
/**
 * Title: Brand Logo
 * Slug: novakeys/brand-logo
 * Categories: novakeys
 * Description: Centred NovaKeys logo. Used above the white card on cart, checkout, and my-account templates so each transaction-side page leads with the brand mark.
 * Keywords: brand, logo, novakeys, identity
 * Block Types: core/group
 * Inserter: yes
 *
 * @package NovaKeys
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:image {"align":"center","width":"160px","style":{"spacing":{"margin":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|30"}}}} -->
<figure class="wp-block-image aligncenter is-resized" style="margin-top:var(--wp--preset--spacing--40);margin-bottom:var(--wp--preset--spacing--30)">
	<img src="<?php echo esc_url( get_theme_file_uri( 'assets/novakeys-logo.svg' ) ); ?>" alt="<?php echo esc_attr__( 'NovaKeys', 'novakeys' ); ?>" width="160"/>
</figure>
<!-- /wp:image -->
