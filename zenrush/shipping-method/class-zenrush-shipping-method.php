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
     * Creates instances of the zenrush shipping methods (premium & standard)
     * 
     * @since   1.0.0
     */
    public function zenrush_init_shipping_methods(): void
    {
        if ( !class_exists( 'WC_Zenrush_Premiumversand' ) ) {
            include 'class-zenrush-premium.php';
        }

        if ( !class_exists( 'WC_Zenrush_Standardversand' ) ) {
            include 'class-zenrush-standard.php';
        }
    }

    /**
     * Adds the zenrush premium shipping method to the list of available shipping methods
     * 
     * @since   1.2.15
     */
    public function zenrush_register_shipping_methods($methods): array
    {
        $methods['zenrush_standard'] = 'WC_Zenrush_Standardversand';
        $methods['zenrush_premiumversand'] = 'WC_Zenrush_Premiumversand';
        return $methods;
    }

    /**
     * Checks if any of the products in cart are not available for zenrush (premium).
     * If any products fails these checks the shipping method will not be available.
     * This has no effect on the standard 2 day delivery method.
     * 
     * @since   1.0.0
     */
    public function zenrush_check_products($rates, $package)
    {
        $methods = array_keys( $rates );
        $method_id = null;
        foreach ( $methods as $method ) {
            if ( substr_count($method, 'zenrush_premiumversand') > 0 ) {
                $method_id = $method;
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

    /**
     * Checks if this shipping method is enabled for the store
     * 
     * @since   1.2.15
     * @access  public
     * @return  bool
     */
    public function fetch_shipping_methods_status() {
        $store_id = get_option( ZENRUSH_PREFIX . 'store_id' );

        $error_response = array(
            'zenrush' => false,
            'zenrush_std' => false
        );

        if ( !$store_id ) {
            return $error_response;
        }

        $response = wp_remote_get( 'https://zenrush.zenfulfillment.com/api/zenrush/check-store?storeId=' . $store_id );

        if ( is_wp_error( $response ) ) {
            return $error_response;
        } else {
            $data = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( $data ) {
                return $data;
            }
            return $error_response;
        }
    }
}
