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
}
