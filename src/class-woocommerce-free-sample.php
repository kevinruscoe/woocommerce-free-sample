<?php

class WooCommerce_Free_Sample {
	protected $file;

	private $sample_product_id_option_name = 'sample_product_id';

	public function __construct(string $file) {
		$this->file = $file;

		// register plugin activation/deactivation hooks
		register_activation_hook( $this->file, array( $this, 'activate' ) );
		register_deactivation_hook( $this->file, array( $this, 'deactivate' ) );
	}

	/**
	 * Activate the plugin.
	 *
	 * @return void
	 * @throws WC_Data_Exception
	 */
	public function activate()
	{
		// Upon activating the plugin, add the sample product if it needs to be created.

		// Get the sample product.
		if ( ! get_option( $this->sample_product_id_option_name ) ) {
			// Create the product.
			$sample = new WC_Product_Simple();
			$sample->set_name( 'Sample Product' );
			$sample->set_sku( 'sample-product' );
			$sample->set_catalog_visibility( 'hidden' );
			$sample->set_price( 0 );
			$sample->set_regular_price( 0 );
			$sample->set_reviews_allowed( false );
			$sample->set_sold_individually( true );
			$sample->set_status( 'publish' );
			$sample->save();

			// Save the ID globally to easily find the sample.
			update_option(
				$this->sample_product_id_option_name,
				$sample->get_id()
			);
		}
	}

	/**
	 * Deactivate the plugin.
	 *
	 * @return void
	 */
	public function deactivate() {
		// Upon disabling the plugin, delete the sample product.

		if ( get_option( $this->sample_product_id_option_name ) ) {
			$sample_product = wc_get_product( (int) get_option( $this->sample_product_id_option_name ) );
			$sample_product->delete();

			delete_option( $this->sample_product_id_option_name );
		}
	}

	/**
	 * The core function.
	 *
	 * @return void
	 */
	public function run() {
		/**
		 * When passing cart items to order items, we need to repush meta data.
		 * TODO: Use the meta tools on the product, so this is autopassed.
		 */
		add_action(
			'woocommerce_new_order_item',
			function( $item_id, $item, $order_id ) {
				if ( isset( $item->legacy_values['base_product_id'] ) ) {
					$item->add_meta_data(
						'base_product_id',
						$item->legacy_values['base_product_id']
					);
					$item->save();
				}
			},
			20,
			3
		);

		/**
		 * Meta vales are shown on the frontend. Hide the base_product_id one.
		 */
		add_filter(
			'woocommerce_order_item_get_formatted_meta_data',
			function( $formatted_meta ) {
				$formatted_meta = array_filter(
					$formatted_meta,
					function( $value ) {
						return $value->key !== 'base_product_id';
					}
				);

				return $formatted_meta;
			},
			10
		);

		add_action(
			'woocommerce_order_item_name',
			function( $item_name, $item ) {
				$base_product_id = $item->get_meta( 'base_product_id' );

				if ( $base_product_id ) {
					$base_product = wc_get_product( $base_product_id );

					return sprintf(
						"Free sample of '%s'",
						$base_product->get_name()
					);
				}

				return $item_name;
			},
			10,
			2
		);

		/**
		 * On the admin grid, we need to hide the sample product.
		 *
		 * @see https://wordpress.stackexchange.com/a/143106
		 */
		add_action(
			'pre_get_posts',
			function ( $query ) {

				if ( ! is_admin() && ! $query->is_main_query() ) {
					return;
				}

				global $typenow;

				if ( 'product' === $typenow ) {
					$query->set(
						'post__not_in',
						array( get_option( $this->sample_product_id_option_name ) )
					);

					return;
				}

				return;
			}
		);

		/**
		 * Change the All (x) | Published (x) to reflect the actual amount shown.
		 */
		add_filter(
			'wp_count_posts',
			function( $counts, $type ) {
				if ( ! is_admin() ) {
					return $counts;
				}

				global $current_screen;

				if ( $current_screen ) {
					if ( 'product' === $type && 'edit-product' === $current_screen->id ) {
						$counts->publish--;
					}
				}

				return $counts;
			},
			10,
			2
		);

		/**
		 * WC now adds the action to the "add to cart" button, so we need a hidden field to mimic it's functionality.
		 * As well as add our new button.
		 */
		add_action(
			'woocommerce_after_add_to_cart_button',
			function () {
				if ( wc_get_product()->get_meta( 'allow_sample' ) !== 'yes' ) {
					return;
				}

				$add_sample_button_classes = apply_filters(
					'woocommerce_free_sample_add_sample_button_classes',
					'single_add_sample_to_cart_button button alt'
				);

				print sprintf(
					"<button type='submit' name='add-sample-to-cart' class='%s' value='%s'>%s</button>",
					esc_attr( $add_sample_button_classes ),
					esc_html( wc_get_product()->get_id() ),
					'Add Sample'
				);

				print sprintf(
					"<input type='hidden' name='add-to-cart' value='%s'>",
					esc_html( wc_get_product()->get_id() )
				);
			}
		);

		/**
		 * Intercept the data we're adding to the cart if the $_POST['add-sample-to-cart'] is set.
		 * This adds the sample product to the cart, instead of the actual product.
		 */
		add_filter(
			'woocommerce_add_to_cart_product_id',
			function( $product_id ) {
				if ( isset( $_POST['add-sample-to-cart'] ) ) { // phpcs:ignore
					return (int) get_option( $this->sample_product_id_option_name );
				}

				return $product_id;
			},
			10
		);

		/**
		 * Adds the base product to the product meta, so we can use it.
		 */
		add_filter(
			'woocommerce_add_cart_item_data',
			function ( $cart_item_data ) {
				if ( isset( $_POST['add-sample-to-cart'] ) ) { // phpcs:ignore
					return array_merge(
						$cart_item_data,
						array(
							'base_product_id' => (int) $_POST['add-to-cart'], // phpcs:ignore
						)
					);
				}

				return $cart_item_data;
			},
			15,
			2
		);

		/**
		 * On the cart pages, overwrite product name.
		 */
		add_filter(
			'woocommerce_cart_item_name',
			function( $name, $cart_item_data ) {
				if ( isset( $cart_item_data['base_product_id'] ) ) {
					$base_product = wc_get_product( $cart_item_data['base_product_id'] );

					if ( $base_product->is_visible() ) {
						return sprintf(
							"Free sample of '<a href='%s'>%s</a>'",
							esc_url( $base_product->get_permalink() ),
							$base_product->get_name()
						);
					}

					return sprintf(
						"Free sample of '%s'",
						$base_product->get_name()
					);
				}

				return $name;
			},
			10,
			2
		);

		/**
		 * On the cart pages, overwrite product thumbnail.
		 */
		add_filter(
			'woocommerce_cart_item_thumbnail',
			function( $image, $cart_item_data ) {
				if ( isset( $cart_item_data['base_product_id'] ) ) {
					return wc_get_product( $cart_item_data['base_product_id'] )->get_image();
				}

				return $image;
			},
			10,
			2
		);

		/**
		 * On the cart pages, overwrite product permalink.
		 */
		add_filter(
			'woocommerce_cart_item_permalink',
			function( $permalink, $cart_item_data ) {
				if ( isset( $cart_item_data['base_product_id'] ) ) {
					return wc_get_product( $cart_item_data['base_product_id'] )->get_permalink();
				}

				return $permalink;
			},
			10,
			2
		);

		/**
		 * Update error flash messages.
		 */
		add_filter(
			'wc_add_to_cart_message_html',
			'replace_sample_product_with_product_name_in_messages',
			10
		);

		add_filter(
			'woocommerce_add_error',
			'replace_sample_product_with_product_name_in_messages',
			10
		);

		add_filter(
			'woocommerce_cart_item_removed_title',
			function( $message, $cart_item ) {
				if ( isset( $cart_item['base_product_id'] ) ) {
					return sprintf(
						'Sample of %s',
						wc_get_product( $cart_item['base_product_id'] )->get_name()
					);
				}

				return $message;
			},
			10,
			2
		);

		/**
		 * Displays a new field in the "Advanced" tab.
		 */
		add_action(
			'woocommerce_product_options_advanced',
			function () {
				woocommerce_wp_checkbox(
					array(
						'label' => 'Allow sample',
						'name'  => 'allow_sample',
						'id'    => 'allow_sample',
					)
				);
			}
		);

		/**
		 * Update 'allow_sample' meta key on product save.
		 */
		add_action(
			'woocommerce_process_product_meta',
			function ( $post_id ) {
				if ( isset( $_POST['woocommerce_meta_nonce'] ) ) {
					if ( ! wp_verify_nonce( sanitize_key( $_POST['woocommerce_meta_nonce'] ), 'woocommerce_save_data' ) ) {
						return;
					}

					$product = wc_get_product( $post_id );
					$product->update_meta_data(
						'allow_sample',
						isset( $_POST['allow_sample'] ) ? 'yes' : 'no'
					);
					$product->save();
				}
			}
		);

		/**
		 * Replaces the word "sample product" with "sample of ...".
		 *
		 * @param string $message The message.
		 * @return string
		 */
		function replace_sample_product_with_product_name_in_messages( $message ) {
			if ( false !== strpos( $message, 'Sample Product' ) ) {
				$base_product_id = (int) $_POST['add-sample-to-cart']; // phpcs:ignore

				$base_product = wc_get_product( $base_product_id );

				$message = str_replace(
					'Sample Product',
					sprintf( 'Sample of %s', $base_product->get_name() ),
					$message
				);
			}

			return $message;
		}
	}
}
