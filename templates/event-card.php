<?php
/**
 * Public event card template.
 *
 * @package WPSeedEvents
 */

defined( 'ABSPATH' ) || exit;

$time_line = wp_seed_events_public_event_next_time_line( $event );
?>
<article class="wp-seed-event-card" style="border:1px solid #dcdcde;border-radius:8px;overflow:hidden;max-width:420px;background:#fff;">
	<?php if ( ! empty( $event['primary_image_id'] ) ) : ?>
		<div class="wp-seed-event-card__media">
			<?php
			echo wp_kses_post(
				wp_get_attachment_image(
					(int) $event['primary_image_id'],
					'medium_large',
					false,
					array(
						'class'   => 'wp-seed-event-card__image',
						'loading' => 'lazy',
						'style'   => 'display:block;width:100%;max-width:100%;height:auto;',
					)
				)
			);
			?>
		</div>
	<?php endif; ?>

	<div class="wp-seed-event-card__body" style="padding:16px;">
		<?php if ( ! empty( $event['types'] ) ) : ?>
			<p class="wp-seed-event-card__types" style="margin:0 0 8px;font-size:13px;color:#646970;"><?php echo esc_html( implode( ' • ', $event['types'] ) ); ?></p>
		<?php endif; ?>

		<h3 class="wp-seed-event-card__title" style="margin:0 0 10px;font-size:1.25rem;line-height:1.25;"><?php echo esc_html( $event['title'] ); ?></h3>

		<?php if ( '' !== wp_seed_events_public_event_next_date_line( $event ) ) : ?>
			<p class="wp-seed-event-card__date" style="margin:0 0 8px;">
				<strong><?php echo esc_html( wp_seed_events_public_event_next_date_line( $event ) ); ?></strong>
				<?php if ( '' !== $time_line ) : ?>
					<br /><span><?php echo esc_html( $time_line ); ?></span>
				<?php endif; ?>
			</p>
		<?php endif; ?>

		<?php if ( ! empty( $event['place']['name'] ) ) : ?>
			<p class="wp-seed-event-card__place" style="margin:0 0 8px;"><?php echo esc_html( $event['place']['name'] ); ?></p>
		<?php endif; ?>

		<?php if ( ! empty( $event['excerpt'] ) ) : ?>
			<p class="wp-seed-event-card__excerpt" style="margin:0 0 14px;"><?php echo esc_html( $event['excerpt'] ); ?></p>
		<?php endif; ?>

		<?php if ( ! empty( $event['url'] ) ) : ?>
			<p class="wp-seed-event-card__action" style="margin:0;"><a href="<?php echo esc_url( $event['url'] ); ?>">En savoir plus</a></p>
		<?php endif; ?>
	</div>
</article>
