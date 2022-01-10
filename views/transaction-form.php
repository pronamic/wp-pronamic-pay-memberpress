<?php
/**
 * MemberPress transaction form.
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2022 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\MemberPress
 */

if ( ! isset( $memberpress_transaction_id ) ) {
	return;
}

$query = new WP_Query(
	array(
		'post_type'   => 'pronamic_payment',
		'post_status' => 'any',
		'nopaging'    => true,
		'meta_query'  => array(
			array(
				'key'     => '_pronamic_payment_source',
				'compare' => '=',
				'value'   => 'memberpress_transaction',
			),
			array(
				'key'     => '_pronamic_payment_source_id',
				'compare' => '=',
				'value'   => $memberpress_transaction_id,
			),
		),
	)
);

$ps = array_filter(
	$query->posts,
	function( $post ) {
		return $post instanceof WP_Post;
	} 
);

$items = array();

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

			foreach ( $items as $key => $url ) {
				echo '<li>';

				\printf(
					'<a href="%s">%s</a>',
					\esc_url( $url ),
					\esc_html( (string) $key )
				);

				echo '</li>';
			}

			echo '</ul>';
		}

		?>
	</td>
</tr>
