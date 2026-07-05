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

	<?php if ( ! empty( $event['occurrences'] ) ) : ?>
		<section class="wp-seed-event-single__section wp-seed-event-single__dates">
			<h2>Dates</h2>
			<ul>
				<?php foreach ( $event['occurrences'] as $occurrence ) : ?>
					<li>
						<?php echo esc_html( wp_seed_events_format_occurrence_date_line( $occurrence ) ); ?>
						<?php if ( ! empty( $occurrence['cancelled'] ) ) : ?>
							<span class="wp-seed-event-single__cancelled">Annulée</span>
						<?php endif; ?>
						<?php if ( '' !== wp_seed_events_format_occurrence_time_line( $occurrence ) ) : ?>
							<br /><?php echo esc_html( wp_seed_events_format_occurrence_time_line( $occurrence ) ); ?>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>

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

	<?php if ( ! empty( $event['people'] ) ) : ?>
		<section class="wp-seed-event-single__section wp-seed-event-single__people">
			<h2>Contacts et intervenants</h2>
			<ul>
				<?php foreach ( $event['people'] as $person ) : ?>
					<li>
						<strong><?php echo esc_html( $person['name'] ); ?></strong>
						<?php if ( ! empty( $person['roles'] ) ) : ?>
							<br /><?php echo esc_html( implode( ' • ', $person['roles'] ) ); ?>
						<?php endif; ?>
						<?php if ( ! empty( $person['phone'] ) ) : ?>
							<br /><?php echo wp_kses_post( wp_seed_events_public_phone_link( $person['phone'] ) ); ?>
						<?php endif; ?>
						<?php if ( ! empty( $person['email'] ) ) : ?>
							<br /><?php echo wp_kses_post( wp_seed_events_public_email_link( $person['email'] ) ); ?>
						<?php endif; ?>
						<?php if ( ! empty( $person['link'] ) ) : ?>
							<br /><?php echo wp_kses_post( wp_seed_events_public_url_link( $person['link'] ) ); ?>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
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
				<?php echo wp_kses_post( wp_seed_events_public_url_link( $flyer_url, 'Télécharger le flyer' ) ); ?>
			</p>
		<?php endif; ?>
	<?php endif; ?>
</article>
