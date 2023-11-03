<?php

/**
 * The shipping method specific functionality of the plugin.
 *
 * @link       https://zenfulfillment.com
 * @since      1.0.0
 *
 * @package    Zenrush
 * @subpackage Zenrush/admin
 */

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * The shipping method functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package     Zenrush
 * @subpackage  Zenrush/shipping_method
 * @author      Zenfulfillment <devs@zenfulfillment.com>
 */
class Zenrush_Shipping_Method
{
    /**
     * Creates an instance of the zenrush shipping method
     * 
     * @since   1.0.0
     */
    public function zenrush_init_shipping_method(): void
    {
        if ( !class_exists( 'WC_Zenrush_Premiumversand' ) ) {
            include 'class-zenrush-wc-shipping-method.php';
        }
    }

    /**
     * Adds the zenrush shipping method to the list of available shipping methods
     * 
     * @since   1.0.0
     */
    public function zenrush_add_shipping_method($methods): array
    {
        $methods['zenrush_premiumversand'] = 'WC_Zenrush_Premiumversand';
        return $methods;
    }

    /**
     * Checks if any of the products in cart are not available for zenrush.
     * If any products fails these checks the shipping method will not be available.
     * 
     * @since   1.0.0
     */
    public function zenrush_check_products($rates, $package)
    {
        $methods = array_keys( $rates );
        foreach ( $methods as $haystack ) {
            if ( substr_count($haystack, 'zenrush') > 0 ) {
                $method_id = $needle;
            }
        }

        if ( $method_id ) {
            $all_products_available = true;
            $disabled_skus_str = get_option( ZENRUSH_PREFIX . 'disabled_skus' );
            $has_disabled_skus = !empty( $disabled_skus_str );
    
            if( $has_disabled_skus ) {
                $disabled_skus = array_map( 'trim', explode( ',', $disabled_skus_str ) );
            }
    
            foreach ( $package['contents'] as $item ) {
                $product = $item['data'];
                $sku = $product->get_sku();
    
                if ( $has_disabled_skus && in_array( $sku, $disabled_skus ) ) {
                    $all_products_available = false;
                    break;
                }
                if ( !$product->is_in_stock() ) {
                    $all_products_available = false;
                    break;
                }
            }
    
            if ( !$all_products_available ) {
                unset( $rates[$method_id] );
            }
        }
        
        return $rates;
    }
}
