<?php
/**
 * Frontend for Request Sample Products.
 *
 * @package woocommerce-request-sample-product
 * @author Kevin Ruscoe
 */

/**
 * Adds a hidden form after the add-to-cart form.
 */
add_action(
	'woocommerce_after_add_to_cart_form',
	function () {
		if ( wc_get_product()->get_meta( '_has_sample' ) !== 'yes' ) {
			return;
		}
		?>
		<form
			id='add-sample-form' 
			action='<?php the_permalink(); ?>' 
			method='post'>
			<?php wp_nonce_field( 'add_sample_to_cart' ); ?>
			<input 
				type='hidden' 
				name='product_id' 
				value="<?php print esc_html( wc_get_product()->get_id() ); ?>">
		</form>
		<?php
	}
);

/**
 * Renders the 'Add Sample' button.
 */
function render_add_sample_button() {
	if ( wc_get_product()->get_meta( '_has_sample' ) !== 'yes' ) {
		return;
	}
	$additional_attributes = '';

	if ( already_in_cart( wc_get_product()->get_id() ) ) {
		$additional_attributes = "disabled='disabled' class='button alt disabled'";
	}
	?>
	<button
		type='submit'
		form='add-sample-form' 
		<?php print esc_html( $additional_attributes ); ?>>
		Add Sample
	</button>
	<?php
}

/**
 * Adds the 'add sample' button after the 'add to cart' button.
 */
add_action(
	'woocommerce_after_add_to_cart_button',
	'render_add_sample_button'
);

/**
 * If we remove a sample from the cart, we want to
 * delete it from WP.
 */
add_action(
	'woocommerce_remove_cart_item',
	function ( $removed_cart_item_key, $cart ) {
		$sample_id = $cart->cart_contents[ $removed_cart_item_key ]['product_id'];

		if ( get_post_meta( $sample_id, sample_meta_key() ) ) {
			wp_delete_post( $sample_id );
		}
	},
	10,
	2
);

/**
 * If we set quatity of a sample to zero, delete it.
 */
add_action(
	'woocommerce_before_cart_item_quantity_zero',
	function ( $cart_item_key ) {
		$sample_id = WC()->cart->cart_contents[ $cart_item_key ]['product_id'];

		if ( get_post_meta( $sample_id, sample_meta_key() ) ) {
			wp_delete_post( $sample_id );
		}
	},
	5
);

/**
 * If a POST 'product_id' is posted, check if we can create
 * a sample. If we can, create and add it to the cart.
 */
add_action(
	'woocommerce_cart_loaded_from_session',
	function () {
		if ( isset( $_POST['product_id'], $_POST['_wpnonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'add_sample_to_cart' ) ) {
				exit;
			}

			$product_id = sanitize_text_field( wp_unslash( $_POST['product_id'] ) );

			// Get cart.
			$cart = WC()->cart;

			// Get base product.
			$base_product = wc_get_product( $product_id );

			// Check if cart contains this sample.
			if ( already_in_cart( $base_product->get_id() ) ) {
				wc_add_notice( 'This sample already exists in your cart.' );

				wp_safe_redirect( $base_product->get_permalink() );

				exit;
			}

			// 'Clone' base product as a sample
			$sample_product = new WC_Product();
			$sample_product->set_name( sprintf( '%s sample', $base_product->get_name() ) );
			$sample_product->set_description( sprintf( 'Sample of %s', $base_product->get_name() ) );
			$sample_product->set_short_description( sprintf( 'Sample of %s', $base_product->get_name() ) );
			$sample_product->set_catalog_visibility( 'hidden' );
			$sample_product->set_image_id( $base_product->get_image_id() );
			$sample_product->set_price( 0 );
			$sample_product->set_regular_price( 0 );
			$sample_product->set_manage_stock( true );
			$sample_product->set_stock_quantity( 1 );
			$sample_product->save();

			// Set meta so we can delete it later.
			add_post_meta( $sample_product->get_id(), sample_meta_key(), true );

			// Hide it from the site.
			wp_set_object_terms(
				$sample_product->get_id(),
				[ 'exclude-from-search', 'exclude-from-catalog' ],
				'product_visibility',
				false
			);

			// Add it to the cart.
			$cart->add_to_cart(
				$sample_product->get_id(),
				1,
				0,
				null,
				[
					'parent_id'       => $base_product->get_id(),
					sample_meta_key() => true,
				]
			);

			wc_add_notice(
				sprintf(
					'Sample of %s has been added to your cart.',
					$base_product->get_name()
				)
			);

			wp_safe_redirect( $base_product->get_permalink() );

			exit;
		}
	},
	5
);

/**
 * Adds a fee if we need to.
 */
add_action(
	'woocommerce_cart_calculate_fees',
	function ( $cart ) {

		// How many samples are in the cart?
		$amount_of_samples_in_cart = count(
			array_filter(
				$cart->get_cart_contents(),
				function ( $item ) {
					return isset( $item[ sample_meta_key() ] );
				}
			)
		);

		if ( $amount_of_samples_in_cart > only_free_up_to() ) {
			$cart->add_fee(
				'Sample Service',
				sample_charge_if_needed(),
				true,
				'standard'
			);
		}
	}
);

/**
 * Is the product already in the cart?
 *
 * @param int $product_id The product ID.
 *
 * @return bool
 */
function already_in_cart( int $product_id ) {
	return ( ! empty(
		array_filter(
			WC()->cart->get_cart_contents(),
			function ( $item ) use ( $product_id ) {
				if ( ! isset( $item['parent_id'] ) ) {
					return false;
				}

				return $item['parent_id'] === $product_id;
			}
		)
	) );
}
