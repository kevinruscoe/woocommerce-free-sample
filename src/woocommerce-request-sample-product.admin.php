<?php
/**
 * Admin for Request Sample Products.
 *
 * @package woocommerce-request-sample-product
 * @author Kevin Ruscoe
 */

/**
 * Create the general admin section named 'sample'.
 */
add_filter(
	'woocommerce_get_sections_products',
	function ( $sections ) {
		$sections['sample'] = 'Sample Product';
		return $sections;
	}
);

/**
 * Add settings to the 'sample' section.
 */
add_filter(
	'woocommerce_get_settings_products',
	function ( $settings, $current_section ) {
		if ( 'sample' === $current_section ) {
			return [
				[
					'name' => 'Sample Product Settings',
					'type' => 'title',
				],
				[
					'name' => 'How many free samples are allowed?',
					'id'   => 'sample_free_up_to',
					'type' => 'number',
				],
				[
					'name'              => 'How much is the flat rate for paid samples?',
					'id'                => 'sample_fee',
					'type'              => 'number',
					'custom_attributes' => [
						'step' => '0.01',
					],
				],
				[
					'type' => 'sectionend',
					'id'   => 'sample',
				],
			];
		}
		return $settings;
	},
	10,
	2
);

/**
 * Update '_has_sample' meta key.
 */
add_action(
	'woocommerce_process_product_meta',
	function ( $post_id ) {
		if ( isset( $_POST['woocommerce_meta_nonce'], $_POST['_has_sample'] ) ) {
			if ( ! wp_verify_nonce( sanitize_key( $_POST['woocommerce_meta_nonce'] ), 'woocommerce_save_data' ) ) {
				exit;
			}

			$product = wc_get_product( $post_id );
			$product->update_meta_data(
				'_has_sample',
				isset( $_POST['_has_sample'] ) ? 'yes' : 'no'
			);
			$product->save();
		}
	}
);

/**
 * Displays a new field in the "Advanced" tab.
 */
add_action(
	'woocommerce_product_options_advanced',
	function () {
		woocommerce_wp_checkbox(
			[
				'label' => 'Enable sample',
				'name'  => '_has_sample',
				'id'    => '_has_sample',
			]
		);
	}
);

/**
 * Hide samples from admin grid.
 *
 * @see https://wordpress.stackexchange.com/a/143106
 */
add_action(
	'pre_get_posts',
	function ( $query ) {

		if ( ! is_admin() && ! $query->is_main_query() ) {
			return;
		}

		global $typenow, $wpdb;

		// @codingStandardsIgnoreLine
		$sample_ids = $wpdb->get_results(
			$wpdb->prepare(
				'select distinct post_id from wp_postmeta ' .
				'where meta_key = %s and meta_value = 1',
				sample_meta_key()
			),
			ARRAY_A
		);

		if ( 'product' === $typenow ) {
			$query->set(
				'post__not_in',
				array_map(
					function ( $row ) {
						return (int) $row['post_id'];
					},
					$sample_ids
				)
			);

			return;
		}
	}
);

/**
 * Deletes the samples within an order.
 *
 * @param int $order_id The order ID.
 *
 * @return void
 */
function delete_samples_after_order_dealt_with( $order_id ) {
	$order = wc_get_order( $order_id );

	foreach ( $order->get_items() as $item_key => $item ) {
		$product = $item->get_product();

		if ( get_post_meta( $product->get_id(), sample_meta_key() ) ) {
			wp_delete_post( $product->get_id() );
		}
	}
}

/**
 * Once we've completed/cancelled an order that contains samples,
 * delete the samples.
 */
add_action(
	'woocommerce_order_status_completed',
	'delete_samples_after_order_dealt_with',
	PHP_INT_MAX
);

add_action(
	'woocommerce_order_status_cancelled',
	'delete_samples_after_order_dealt_with',
	PHP_INT_MAX
);
