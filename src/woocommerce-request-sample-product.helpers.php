<?php
/**
 * Helpers for woocommerce-request-sample-product
 *
 * @package woocommerce-request-sample-product
 * @author Kevin Ruscoe
 */

/**
 * The key to determine if a product is a sample.
 *
 * @return string
 */
function sample_meta_key() {
	return '_is_sample';
}

/**
 * How many FREE samples are allowed in the cart?
 *
 * @return int
 */
function only_free_up_to() {
	return get_option( 'sample_free_up_to' );
}

/**
 * The price of the samples if they need to be charged.
 *
 * @return float
 */
function sample_charge_if_needed() {
	return get_option( 'sample_fee' );
}
