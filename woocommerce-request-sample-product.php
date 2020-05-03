<?php 


/**
 * Plugin Name: Request Sample Product
 * Description: Request Sample Product
 * Plugin URI: https://github.com/kevinruscoe/woocommerce-request-sample-product
 * Version: 1.0
 * Author: Kevin Ruscoe
 * Author URI: https://github.com/kevinruscoe
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'woocommerce-request-sample-product_VERSION', '1.0.0' );

require plugin_dir_path( __FILE__ ) . 'src/woocommerce-request-sample-product.php';