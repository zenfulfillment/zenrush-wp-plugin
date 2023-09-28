<?php

/**
 * The file that defines the zenrush shipping method class
 *
 * @since      1.0.0
 *
 * @package    Zenrush
 * @subpackage Zenrush/shipping-method
 */

if ( !defined('ABSPATH') ) {
    exit; // Exit if accessed directly.
}

/**
 * The core shipping method class
 *
 * @since      1.0.0
 * @package    Zenrush
 * @subpackage Zenrush/admin
 * @author     Zenfulfillment <devs@zenfulfillment.com>
 */
class WC_Zenrush_Premiumversand extends WC_Shipping_Method
{
    /**
     * The url to fetch custom zenrush rate rules from
     *
     * @since    1.1.5
     * @access   private
     * @var string Zenrush Rates url
     */
    private string $rates_url = 'https://zenrush.zenfulfillment.com/api/zenrush/rates';

    /**
     * The prefix for options to use
     *
     * @since    1.0.0
     * @access   private
     * @var      string $prefix The prefix to use to access settings and set ids
     */
    private string $prefix = 'Zenrush_';

    /**
     * Constructor for your shipping class
     *
     * @access public
     * @return void
     */
    public function __construct($instance_id = 0)
    {
        parent::__construct();

        // ID of the shipping option
        $this->id = 'zenrush_premiumversand';

        // Instance ID
        $this->instance_id = absint( $instance_id );

        // Description shown in admin
        $this->method_description = __( 'Powered by Zenfulfillment.com - Ordered today, delivered tomorrow', 'zenrush' );

        // Supported features
        $this->supports = ['shipping-zones', 'instance-settings', 'instance-settings-modal'];

        // Plugin ID, used as prefix for settings
        $this->plugin_id = $this->prefix;

        // Title shown in store
        $this->title = __( 'Zenrush Premium Delivery', 'zenrush' );

        // Title shown in admin backend
        $this->method_title = __( 'Zenrush Premium Delivery', 'zenrush' );

        // Initially defaults the shipping option to be enabled, could be overwritten after init()
        $this->enabled = 'yes';

        $this->init();
    }

    /**
     * Initializes the shipping option with WooCommerce
     *
     * @return void
     */
    function init(): void
    {
        // Initialize WC_Shipping_Method settings API
        $this->init_form_fields();
        $this->init_settings();

        // If shipping option is enabled or not via option in admin backend
        $this->enabled = get_option( $this->prefix . 'store_id' ) ? $this->get_option( 'enabled' ) : 'no';

        add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
    }

    /**
     * Fetches current configured zenrush pricing rules for the store
     * and parses them to an array with `base_rate` and `custom_rates`
     *
     * @since 1.0.0
     * @access private
     * @return array
     */
    private function fetch_zenrush_pricing_rules($cart_price): array
    {
        $storeId = get_option( $this->prefix . 'store_id' );
        $url = $this->rates_url . '?storeId=' . $storeId . '&cartPrice=' . $cart_price;

        $response = wp_remote_get( $url );
        $decoded_data = json_decode( wp_remote_retrieve_body( $response ), true );
        $status_code = wp_remote_retrieve_response_code( $response );
        if ( is_wp_error( $response ) || $status_code !== 200 ) {
            error_log( 'Failed to fetch zenrush pricing rules' );
            return array(
                'base_rate'     =>  699,
                'custom_rates'  =>  array()
            );
        }

        $data = $decoded_data['data'];
        return array(
            'base_rate'     =>  $data['base_rate'],
            'custom_rates'  =>  $data['rules']
        );
    }

    /**
     * Calculates the shipping rate to be displayed in store frontend
     *
     * @access public
     * @param array $package Package information.
     * @return void
     */
    public function calculate_shipping($package = array()): void
    {
        // this is the total price of the cart, includes discounts!
        $cart_price = WC()->cart->get_cart_contents_total();

        // check if all products in the cart are in stock
        $products_in_stock = $this->check_stock_in_cart( $package );
        if ( !$products_in_stock ) {
            error_log( 'Not displaying Zenrush - One or more products are out of stock!' );
            return;
        }

        // Fetch store specific zenrush pricing rules
        $raw_rates = $this->fetch_zenrush_pricing_rules( $cart_price );
        $cost = $this->calc_cost( $raw_rates['base_rate'] );
        $rates = $raw_rates['custom_rates'];

        if ( !empty($rates) ) {
             foreach ( $rates as $rate ) {
                 $rule_definition = key( $rate['definition'] );
                 $rule_cost = reset( $rate['definition'] );
                 $rule_price = (int) $rate['price'];

                 switch( $rule_definition ) {
                     case '$gte':
                        if ( $cart_price >= $rule_cost ) {
                            $cost = $this->calc_cost( $rule_price );
                        }
                        break;
                     case '$gt':
                         if ( $cart_price > $rule_cost ) {
                             $cost = $this->calc_cost( $rule_price );
                         }
                         break;
                     case '$lte':
                        if ( $cart_price <= $rule_cost ) {
                            $cost = $this->calc_cost( $rule_price );
                        }
                        break;
                     case '$lt':
                         if ( $cart_price < $rule_cost ) {
                             $cost = $this->calc_cost( $rule_price );
                         }
                         break;
                     default:
                         break;
                }
             }
        }

        $rate_title = $this->title;

        $rate = array(
            'id'        =>  $this->id . $this->instance_id,
            'label'     =>  $rate_title,
            'cost'      =>  $cost,
            'package'   =>  $package,
        );

        // registers the shipping option with the calculated price to be displayed on checkout / cart
        $this->add_rate( $rate );
    }

    /**
     * Util to transform the rate price from cents to euros, e.g. 299 => 2.99
     *
     * @param int $rule_price
     * @return float|int
     * @since 1.0.0
     * @access private
     */
    private function calc_cost(int $rule_price): float|int
    {
        if ($rule_price === 0) {
            return $rule_price;
        }
        return $rule_price / 100;
    }

    /**
     * Util to check if at least one of the items in the current cart is out of stock
     *
     * @param $package
     * @return bool
     */
    private function check_stock_in_cart($package): bool
    {
        $all_products_in_stock = true;

        foreach ( $package['contents'] as $item ) {
            $product = $item['data'];
            if ( !$product->is_in_stock() ) {
                $all_products_in_stock = false;
                break;
            }
        }

        return $all_products_in_stock;
    }
}
