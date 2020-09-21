<?php
/**
 * Plugin Name: Woocommerce Free Sample
 * Description: Allows a user to get a free sample of a product.
 * Plugin URI: https://github.com/kevinruscoe/woocommerce-free-sample
 * Version: 2.0
 * Author: Kevin Ruscoe
 * Author URI: https://github.com/kevinruscoe
 * Text Domain: woocommerce-free-sample
 * Domain Path: /languages
 *
 * @package Woocommerce Free Sample
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

require __DIR__ . '/src/class-woocommerce-free-sample.php';

$plugin = new WooCommerce_Free_Sample(__FILE__);

$plugin->run();