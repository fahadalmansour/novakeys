<?php
/**
 * Title: Legal Disclosure
 * Slug: novakeys/legal-disclosure
 * Categories: novakeys
 * Description: MOC commercial-registration identity readout — name, CR, ZATCA, Chamber, contact. Pulls from nk_cr() so the canonical record stays in PHP.
 * Keywords: legal, MOC, CR, ZATCA, identity
 * Block Types: core/group
 * Inserter: yes
 *
 * @package NovaKeys
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

$cr = function_exists( 'nk_cr' ) ? nk_cr() : array();

$brand_en   = $cr['brand_en']      ?? 'NovaKeys Store';
$brand_ar   = $cr['brand_ar']      ?? '';
$legal_en   = $cr['legal_name_en'] ?? '';
$legal_ar   = $cr['legal_name_ar'] ?? '';
$cr_no      = $cr['cr']            ?? '';
$owner      = $cr['owner']         ?? '';
$entity     = $cr['entity_type']   ?? '';
$entity_ar  = $cr['entity_type_ar'] ?? '';
$phone      = $cr['phone_mobile']  ?? '';
$email      = $cr['email']         ?? '';
$website    = $cr['website']       ?? home_url( '/' );
$reg_ad     = $cr['registered_ad'] ?? '';
$authority  = $cr['authority']     ?? '';
$verify_url = $cr['verify_url']    ?? '';
$regulatory = is_array( $cr['regulatory'] ?? null ) ? $cr['regulatory'] : array();
?>
<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","contentSize":"800px"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">

	<!-- wp:heading {"level":1,"style":{"typography":{"letterSpacing":"-0.02em"}}} -->
	<h1 class="wp-block-heading" style="letter-spacing:-0.02em"><?php echo esc_html__( 'Legal Disclosure', 'novakeys' ); ?></h1>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"textColor":"brand-slate","style":{"typography":{"fontSize":"var(--wp--preset--font-size--lg)"}}} -->
	<p class="has-brand-slate-color has-text-color" style="font-size:var(--wp--preset--font-size--lg)"><?php echo esc_html__( 'Identity, regulatory registrations, and contact information for', 'novakeys' ); ?> <?php echo esc_html( $brand_en ); ?>.</p>
	<!-- /wp:paragraph -->

	<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"},"margin":{"top":"var:preset|spacing|40"}},"border":{"radius":"12px","color":"var:preset|color|brand-mist","width":"1px"}}} -->
	<div class="wp-block-group" style="border-color:var(--wp--preset--color--brand-mist);border-width:1px;border-radius:12px;margin-top:var(--wp--preset--spacing--40);padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">

		<!-- wp:heading {"level":2,"style":{"typography":{"fontSize":"var(--wp--preset--font-size--xl)"}}} -->
		<h2 class="wp-block-heading" style="font-size:var(--wp--preset--font-size--xl)"><?php echo esc_html__( 'Commercial Registration', 'novakeys' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph -->
		<p><strong><?php echo esc_html__( 'Brand:', 'novakeys' ); ?></strong> <?php echo esc_html( $brand_en ); ?><?php echo $brand_ar ? ' · ' . esc_html( $brand_ar ) : ''; ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph -->
		<p><strong><?php echo esc_html__( 'Legal name:', 'novakeys' ); ?></strong> <?php echo esc_html( $legal_en ); ?></p>
		<!-- /wp:paragraph -->

		<?php if ( '' !== $legal_ar ) : ?>
			<!-- wp:paragraph -->
			<p><strong><?php echo esc_html__( 'الاسم النظامي:', 'novakeys' ); ?></strong> <?php echo esc_html( $legal_ar ); ?></p>
			<!-- /wp:paragraph -->
		<?php endif; ?>

		<!-- wp:paragraph -->
		<p><strong><?php echo esc_html__( 'CR number:', 'novakeys' ); ?></strong> <code><?php echo esc_html( $cr_no ); ?></code></p>
		<!-- /wp:paragraph -->

		<?php if ( '' !== $owner ) : ?>
			<!-- wp:paragraph -->
			<p><strong><?php echo esc_html__( 'Owner:', 'novakeys' ); ?></strong> <?php echo esc_html( $owner ); ?></p>
			<!-- /wp:paragraph -->
		<?php endif; ?>

		<?php if ( '' !== $entity ) : ?>
			<!-- wp:paragraph -->
			<p><strong><?php echo esc_html__( 'Entity type:', 'novakeys' ); ?></strong> <?php echo esc_html( $entity ); ?><?php echo $entity_ar ? ' · ' . esc_html( $entity_ar ) : ''; ?></p>
			<!-- /wp:paragraph -->
		<?php endif; ?>

		<?php if ( '' !== $reg_ad ) : ?>
			<!-- wp:paragraph -->
			<p><strong><?php echo esc_html__( 'Registered:', 'novakeys' ); ?></strong> <?php echo esc_html( $reg_ad ); ?></p>
			<!-- /wp:paragraph -->
		<?php endif; ?>

		<?php if ( '' !== $authority ) : ?>
			<!-- wp:paragraph -->
			<p><strong><?php echo esc_html__( 'Authority:', 'novakeys' ); ?></strong> <?php echo esc_html( $authority ); ?>
			<?php if ( '' !== $verify_url ) : ?>
				· <a href="<?php echo esc_url( $verify_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html__( 'verify', 'novakeys' ); ?></a>
			<?php endif; ?>
			</p>
			<!-- /wp:paragraph -->
		<?php endif; ?>
	</div>
	<!-- /wp:group -->

	<?php if ( ! empty( $regulatory ) ) : ?>
		<!-- wp:heading {"level":2,"style":{"typography":{"fontSize":"var(--wp--preset--font-size--xl)"},"spacing":{"margin":{"top":"var:preset|spacing|50"}}}} -->
		<h2 class="wp-block-heading" style="font-size:var(--wp--preset--font-size--xl);margin-top:var(--wp--preset--spacing--50)"><?php echo esc_html__( 'Regulatory Registrations', 'novakeys' ); ?></h2>
		<!-- /wp:heading -->

		<?php foreach ( $regulatory as $reg ) : ?>
			<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30","right":"var:preset|spacing|30"},"margin":{"top":"var:preset|spacing|30"}},"border":{"radius":"8px","color":"var:preset|color|brand-mist","width":"1px"}}} -->
			<div class="wp-block-group" style="border-color:var(--wp--preset--color--brand-mist);border-width:1px;border-radius:8px;margin-top:var(--wp--preset--spacing--30);padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)">
				<!-- wp:paragraph -->
				<p><strong><?php echo esc_html( (string) ( $reg['label'] ?? '' ) ); ?></strong>
				<?php if ( ! empty( $reg['authority_en'] ) ) : ?>
					· <?php echo esc_html( (string) $reg['authority_en'] ); ?>
				<?php endif; ?>
				<?php if ( ! empty( $reg['authority_ar'] ) ) : ?>
					· <?php echo esc_html( (string) $reg['authority_ar'] ); ?>
				<?php endif; ?>
				</p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph -->
				<p><strong><?php echo esc_html__( 'Number:', 'novakeys' ); ?></strong> <code><?php echo esc_html( (string) ( $reg['number'] ?? '' ) ); ?></code>
				<?php if ( ! empty( $reg['url'] ) ) : ?>
					· <a href="<?php echo esc_url( (string) $reg['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html__( 'site', 'novakeys' ); ?></a>
				<?php endif; ?>
				</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		<?php endforeach; ?>
	<?php endif; ?>

	<!-- wp:heading {"level":2,"style":{"typography":{"fontSize":"var(--wp--preset--font-size--xl)"},"spacing":{"margin":{"top":"var:preset|spacing|50"}}}} -->
	<h2 class="wp-block-heading" style="font-size:var(--wp--preset--font-size--xl);margin-top:var(--wp--preset--spacing--50)"><?php echo esc_html__( 'Contact', 'novakeys' ); ?></h2>
	<!-- /wp:heading -->

	<?php if ( '' !== $email ) : ?>
		<!-- wp:paragraph -->
		<p><strong><?php echo esc_html__( 'Email:', 'novakeys' ); ?></strong> <a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></p>
		<!-- /wp:paragraph -->
	<?php endif; ?>

	<?php if ( '' !== $phone ) : ?>
		<!-- wp:paragraph -->
		<p><strong><?php echo esc_html__( 'Phone:', 'novakeys' ); ?></strong> <?php echo esc_html( $phone ); ?></p>
		<!-- /wp:paragraph -->
	<?php endif; ?>

	<!-- wp:paragraph -->
	<p><strong><?php echo esc_html__( 'Website:', 'novakeys' ); ?></strong> <a href="<?php echo esc_url( $website ); ?>"><?php echo esc_html( $website ); ?></a></p>
	<!-- /wp:paragraph -->

</div>
<!-- /wp:group -->
