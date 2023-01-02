<?php
/**
 * MemberPress subscription form
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2023 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\MemberPress
 */

if ( ! isset( $memberpress_subscription_id ) ) {
	return;
}

$query = new WP_Query(
	[
		'post_type'   => 'pronamic_pay_subscr',
		'post_status' => [ 'any', 'trash' ],
		'nopaging'    => true,
		'meta_query'  => [
			[
				'key'     => '_pronamic_subscription_source',
				'compare' => '=',
				'value'   => 'memberpress_subscription',
			],
			[
				'key'     => '_pronamic_subscription_source_id',
				'compare' => '=',
				'value'   => $memberpress_subscription_id,
			],
		],
	]
);

$ps = array_filter(
	$query->posts,
	function( $post ) {
		return $post instanceof WP_Post;
	}
);

$items = [];

foreach ( $ps as $p ) {
	$url = get_edit_post_link( $p );

	if ( null === $url ) {
		continue;
	}

	$items[ $p->ID ] = $url;
}

?>
<tr valign="top">
	<th scope="row">
		<label for="trans_num"><?php esc_html_e( 'Pronamic Subscription', 'pronamic_ideal' ); ?></label>
	</th>
	<td>
		<?php

		if ( \count( $items ) > 0 ) {
			echo '<ul>';

			foreach ( $items as $subscription_id => $url ) {
				echo '<li>';

				// Status.
				$post_status = get_post_status( $subscription_id );

				if ( 'trash' === $post_status ) {
					$post_status = get_post_meta( $subscription_id, '_wp_trash_meta_status', true );
				}

				$status_object = get_post_status_object( $post_status );

				$status_label = isset( $status_object, $status_object->label ) ? $status_object->label : __( 'Unknown status', 'pronamic_ideal' );

				// Next payment date.
				$next_payment = __( 'No payment scheduled', 'pronamic_ideal' );

				$subscription = get_pronamic_subscription( $subscription_id );

				if ( null !== $subscription ) {
					$next_payment_date = $subscription->get_next_payment_date();

					if ( null !== $next_payment_date ) {
						$next_payment = sprintf(
						/* translators: %s: formatted next payment date */
							__( 'Next payment at %s', 'pronamic_ideal' ),
							$next_payment_date->format_i18n( __( 'D j M Y', 'pronamic_ideal' ) )
						);
					}
				}

				\printf(
					'<a href="%s">%s</a> — %s — %s',
					\esc_url( $url ),
					\esc_html( (string) $subscription_id ),
					\esc_html( $status_label ),
					\esc_html( $next_payment )
				);

				echo '</li>';
			}

			echo '</ul>';
		}

		?>
	</td>
</tr>
