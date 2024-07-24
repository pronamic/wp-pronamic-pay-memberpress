<?php
/**
 * MemberPress transaction form.
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2024 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\MemberPress
 */

if ( ! isset( $memberpress_transaction_id ) ) {
	return;
}

$query = new WP_Query(
	[
		'post_type'   => 'pronamic_payment',
		'post_status' => [ 'any', 'trash' ],
		'nopaging'    => true,
		'meta_query'  => [
			[
				'key'     => '_pronamic_payment_source',
				'compare' => '=',
				'value'   => 'memberpress_transaction',
			],
			[
				'key'     => '_pronamic_payment_source_id',
				'compare' => '=',
				'value'   => $memberpress_transaction_id,
			],
		],
	]
);

$ps = array_filter(
	$query->posts,
	function ( $post ) {
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
		<label for="trans_num"><?php esc_html_e( 'Pronamic Payment', 'pronamic_ideal' ); ?></label>
	</th>
	<td>
		<?php

		if ( \count( $items ) > 0 ) {
			echo '<ul>';

			foreach ( $items as $payment_id => $url ) {
				echo '<li>';

				// Status.
				$post_status = get_post_status( $payment_id );

				if ( 'trash' === $post_status ) {
					$post_status = get_post_meta( $payment_id, '_wp_trash_meta_status', true );
				}

				$status_object = get_post_status_object( $post_status );

				$status_label = isset( $status_object, $status_object->label ) ? $status_object->label : __( 'Unknown status', 'pronamic_ideal' );

				\printf(
					'<a href="%s">%s</a> — %s',
					\esc_url( $url ),
					\esc_html( (string) $payment_id ),
					\esc_html( $status_label )
				);

				echo '</li>';
			}

			echo '</ul>';
		}

		?>
	</td>
</tr>
