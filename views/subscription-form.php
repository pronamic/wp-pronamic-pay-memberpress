<?php
/**
 * MemberPress subscription form
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2021 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\MemberPress
 */

if ( ! isset( $memberpress_subscription_id ) ) {
	return;
}

?>
<tr valign="top">
	<th scope="row">
		<label for="trans_num"><?php esc_html_e( 'Pronamic Subscription', 'pronamic_ideal' ); ?></label>
	</th>
	<td>
		<?php

		$query = new WP_Query(
			array(
				'post_type'   => 'pronamic_pay_subscr',
				'post_status' => 'any',
				'nopaging'    => true,
				'meta_query'  => array(
					array(
						'key'     => '_pronamic_subscription_source',
						'compare' => '=',
						'value'   => 'memberpress',
					),
					array(
						'key'     => '_pronamic_subscription_source_id',
						'compare' => '=',
						'value'   => $memberpress_subscription_id,
					),
				),
			)
		);

		if ( $query->have_posts() ) {
			echo '<ul>';

			while ( $query->have_posts() ) {
				$query->the_post();

				echo '<li>';

				printf(
					'<a href="%s">%s</a>',
					esc_attr( get_edit_post_link( get_post() ) ),
					esc_html( get_the_ID() )
				);

				echo '</li>';
			}

			echo '</ul>';

			wp_reset_postdata();
		}

		?>
	</td>
</tr>
