<?php

/**
 * The key to determine if a product is a sample.
 * 
 * @return string
 */
function sample_meta_key()
{
    return '_is_a_sample';
}

/**
 * How many FREE samples are allowed in the cart?
 * 
 * @return int
 */
function only_free_up_to()
{
    return 2;
}

/**
 * The price of the samples if they need to be charged.
 * 
 * @return float
 */
function sample_charge_if_needed()
{
    return 5.00;
}

/**
 * Registed the 'add-sample-button' template just
 * after the add-to-cart form.
 */
add_action(
    'woocommerce_after_add_to_cart_form',
    function () {
        get_template_part(basename(dirname(__FILE__)) . '/add-sample-button');
    }
);

/**
 * If we remove a sample from the cart, we want to
 * delete it from WP.
 */
add_action(
    'woocommerce_remove_cart_item',
    function ($removedCartItemKey, $cart) {
        $sampleId = $cart->cart_contents[$removedCartItemKey]['product_id'];

        if (get_post_meta($sampleId, sample_meta_key())) {
            wp_delete_post($sampleId);
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
    function ($cartItemKey) {
        $sampleId = WC()->cart->cart_contents[$cartItemKey]['product_id'];
        
        if (get_post_meta($sampleId, sample_meta_key())) {
            wp_delete_post($sampleId);
        }
    },
    5
);

/**
 * If a POST 'sample-id' is posted, check if we can create
 * a sample. If we can, create and add it to the cart.
 */
add_action(
    'woocommerce_cart_loaded_from_session', 
    function () {
        if (isset($_POST['add-sample-from-id'])) {
            if (! wp_verify_nonce($_POST['_wpnonce'], 'add_sample_to_cart')) {
                exit;
            }

            $productId = $_POST['add-sample-from-id'];
            
            // Get cart
            $cart = WC()->cart;

            // Get base
            $baseProduct = wc_get_product($productId);

            // Check if cart contains this sample already.
            $sampleAlreadyExists = ! empty(
                array_filter(
                    $cart->get_cart_contents(), 
                    function ($item) use ($baseProduct) {
                        return $item['parent_id'] == $baseProduct->get_id();
                    }
                )
            );

            if ($sampleAlreadyExists) {
                wc_add_notice("This sample already exists in your cart.");

                wp_redirect($baseProduct->get_permalink());

                exit;
            }

            // 'Clone' base product as a sample
            $sampleProduct = new WC_Product();
            $sampleProduct->set_name(sprintf("%s sample", $baseProduct->get_name()));
            $sampleProduct->set_description(sprintf("Sample of %s", $baseProduct->get_name()));
            $sampleProduct->set_short_description(sprintf("Sample of %s", $baseProduct->get_name()));
            $sampleProduct->set_catalog_visibility('hidden');
            $sampleProduct->set_image_id($baseProduct->get_image_id());
            $sampleProduct->set_price(0);
            $sampleProduct->set_regular_price(0);
            $sampleProduct->set_manage_stock(true);
            $sampleProduct->set_stock_quantity(1);
            $sampleProduct->save();
            
            // Set meta so we can delete it later.
            add_post_meta($sampleProduct->get_id(), sample_meta_key(), true);

            // Hide it from the site.
            wp_set_object_terms(
                $sampleProduct->get_id(),
                ['exclude-from-search', 'exclude-from-catalog'],
                'product_visibility',
                false
            );

            // Add it to the cart.
            $cart->add_to_cart(
                $sampleProduct->get_id(), 
                1, 
                0, 
                null, 
                [
                    'parent_id' => $baseProduct->get_id(),
                    sample_meta_key() => true
                ]
            );

            wc_add_notice(
                sprintf(
                    "Sample of %s has been added to your cart.", 
                    $baseProduct->get_name()
                )
            );
            
            wp_redirect($baseProduct->get_permalink());

            exit;
        }
    },
    5
);
    

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

/**
 * Deletes the samples within an order.
 * 
 * @param int $orderId The order ID
 * 
 * @return void
 */
function delete_samples_after_order_dealt_with($orderId)
{
    $order = wc_get_order($orderId);

    // get producuts
    foreach ($order->get_items() as $item_key => $item) {
        $product = $item->get_product();

        // delete samples
        if (get_post_meta($product->get_id(), sample_meta_key())) {
            wp_delete_post($product->get_id());
        }
    }
}

/**
 * Hide samples from admin grid.
 *
 * @see https://wordpress.stackexchange.com/a/143106
 */
add_action(
    'pre_get_posts',
    function ($query) {

        if (! is_admin() && ! is_main_query()) {
            return;
        }
        
        global $typenow, $wpdb;

        $sampleIds = $wpdb->get_results(
            sprintf(
                "select distinct post_id from wp_postmeta ".
                "where meta_key = '%s' and meta_value = 1",
                sample_meta_key()
            ),
            ARRAY_A
        );

        if ('product' == $typenow) {
            $query->set(
                'post__not_in',
                array_map(
                    function ($row) {
                        return (int) $row['post_id'];
                    },
                    $sampleIds
                )
            );

            return;
        }
    }
);

/**
 * If we need to charge for samples.
 */
add_action(
    'woocommerce_cart_calculate_fees',
    function ($cart) {

        // How many samples are in the cart?
        $amountOfSamplesInCart = count(
            array_filter(
                $cart->get_cart_contents(), 
                function ($item) {
                    return $item[sample_meta_key()] == true;
                }
            )
        );

        if ($amountOfSamplesInCart > only_free_up_to()) {
            $cart->add_fee(
                'Sample Service', 
                sample_charge_if_needed(), 
                true, 
                'standard'
            );
        }
    }
);