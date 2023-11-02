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

if ( !defined('ABSPATH') ) {
    exit; // Exit if accessed directly.
}

/**
 * The shipping method functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Zenrush
 * @subpackage Zenrush/shipping_method
 * @author     Zenfulfillment <devs@zenfulfillment.com>
 */
class Zenrush_Shipping_Method
{
    public function zenrush_init_shipping_method(): void
    {
        if ( !class_exists( 'WC_Zenrush_Premiumversand' ) ) {
            include 'class-zenrush-wc-shipping-method.php';
        }
    }

    public function zenrush_add_shipping_method($methods): array
    {
        $methods['zenrush_premiumversand'] = 'WC_Zenrush_Premiumversand';
        return $methods;
    }

    public function zenrush_filter_skus( $rates, $package )
    {
        $methods = array_keys( $rates );
        foreach ($methods as $needle) {
            if (substr_count($needle, 'zenrush') > 0) {
                $method_id = $needle;
            }
        }

        if ( $method_id ) {
            $all_products_in_stock = true;
            $disabled_skus_str = get_option( 'Zenrush_disabled_skus' );
            $has_disabled_skus = !empty( $disabled_skus_str );
    
            if( $has_disabled_skus ) {
                $disabled_skus = array_map( 'trim', explode( ',', $disabled_skus_str ) );
            }
    
            foreach ( $package['contents'] as $item ) {
                $product = $item['data'];
                $sku = $product->get_sku();
    
                if ( $has_disabled_skus && in_array( $sku, $disabled_skus ) ) {
                    $all_products_in_stock = false;
                    break;
                }
                if ( !$product->is_in_stock() ) {
                    $all_products_in_stock = false;
                    break;
                }
            }
    
            if ( !$all_products_in_stock ) {
                unset( $rates[$method_id] );
            }
        }
        
        return $rates;
    }
}
