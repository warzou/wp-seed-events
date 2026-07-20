<?php
/**
 * Neutral public event detail fallback template.
 *
 * @package WPSeedEvents
 */

defined( 'ABSPATH' ) || exit;
?>
<article class="wp-seed-event-single">
	<?php if ( ! empty( $event['primary_image_id'] ) ) : ?>
		<div class="wp-seed-event-single__image">
			<?php
			echo wp_kses_post(
				wp_get_attachment_image(
					(int) $event['primary_image_id'],
					'large',
					false,
					array(
						'class'   => 'wp-seed-event-single__image-element',
						'loading' => 'lazy',
						'style'   => 'max-width:100%;height:auto;',
					)
				)
			);
			?>
		</div>
	<?php endif; ?>

	<header class="wp-seed-event-single__header">
		<h1 class="wp-seed-event-single__title"><?php echo esc_html( $event['title'] ); ?></h1>

		<?php if ( ! empty( $event['types'] ) ) : ?>
			<p class="wp-seed-event-single__types"><?php echo esc_html( implode( ' • ', $event['types'] ) ); ?></p>
		<?php endif; ?>
	</header>

	<?php echo wp_kses_post( wp_seed_events_render_public_event_dates_section( $event ) ); ?>

	<?php if ( ! empty( $event['place'] ) ) : ?>
		<section class="wp-seed-event-single__section wp-seed-event-single__place">
			<h2>Lieu</h2>
			<p>
				<?php if ( ! empty( $event['place']['name'] ) ) : ?>
					<strong><?php echo esc_html( $event['place']['name'] ); ?></strong><br />
				<?php endif; ?>
				<?php if ( ! empty( $event['place']['address'] ) ) : ?>
					<?php echo esc_html( $event['place']['address'] ); ?><br />
				<?php endif; ?>
				<?php if ( ! empty( $event['place']['link'] ) ) : ?>
					<?php echo wp_kses_post( wp_seed_events_public_url_link( $event['place']['link'] ) ); ?><br />
				<?php endif; ?>
				<?php if ( ! empty( $event['place']['details'] ) ) : ?>
					<?php echo nl2br( esc_html( $event['place']['details'] ) ); ?>
				<?php endif; ?>
			</p>
		</section>
	<?php endif; ?>

	<?php
	$people_html = wp_seed_events_render_public_event_people_section(
		$event,
		array(
			'title' => 'Contacts et intervenants',
		)
	);
	?>
	<?php if ( '' !== $people_html ) : ?>
		<?php echo wp_kses_post( $people_html ); ?>
	<?php endif; ?>
	<?php if ( ! empty( $event['description'] ) ) : ?>
		<section class="wp-seed-event-single__section wp-seed-event-single__description">
			<?php echo apply_filters( 'the_content', $event['description'] ); ?>
		</section>
	<?php endif; ?>

	<?php if ( ! empty( $event['flyer_pdf_id'] ) ) : ?>
		<?php $flyer_url = wp_get_attachment_url( (int) $event['flyer_pdf_id'] ); ?>
		<?php if ( $flyer_url ) : ?>
			<p class="wp-seed-event-single__flyer">
				<?php echo wp_kses_post( wp_seed_events_public_url_link( $flyer_url, 'Télécharger le document PDF' ) ); ?>
			</p>
		<?php endif; ?>
	<?php endif; ?>
</article>
