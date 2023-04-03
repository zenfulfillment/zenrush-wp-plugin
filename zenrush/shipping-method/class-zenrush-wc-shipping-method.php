<?php

/**
 * The file that defines the zenrush shipping method class
 *
 * @since      1.0.0
 *
 * @package    Zenrush
 * @subpackage Zenrush/shipping-method
 */

if (!defined('ABSPATH')) {
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
     * The prefix for options to use
     *
     * @since    1.0.0
     * @access   private
     * @var      string $prefix The prefix to use to access settings and set ids
     */
    private string $prefix = 'Zenrush_';

    /**
     * The default rate to use for Zenrush, from the pricing config
     *
     * @since 1.0.0
     * @access private
     * @var int $base_rate The default rate for Zenrush
     */
    private int $base_rate;

    /**
     * Array of custom pricing rates for zenrush defined for this store
     *
     * @since 1.0.0
     * @access private
     * @var array $custom_rates The custom rates set for this store
     */
    private array $custom_rates = array();

    /**
     * Array of the cutoff time message, used to display underneath the title in store
     *
     * @since 1.0.0
     * @access private
     * @var array $cutoff_time_msg Zenrush Cutoff Message
     */
    private $cutoff_time_msg;

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
        $this->instance_id = absint($instance_id);

        // Description shown in admin
        $this->method_description = __('Powered by Zenfulfillment.com - Ordered today, delivered tomorrow', 'zenrush');

        /**
         * Features this method supports. Possible features used by core:
         * - shipping-zones - Shipping zone functionality + instances
         * - instance-settings - Instance settings screens.
         * - settings Non-instance settings screens. - Enabled by default for BW compatibility with methods before instances existed.
         * - instance-settings-modal - Allows the instance settings to be loaded within a modal in the zones UI.
         */
        $this->supports = ['shipping-zones', 'instance-settings', 'instance-settings-modal'];

        // Plugin ID, used as prefix for settings
        $this->plugin_id = $this->prefix;

        $this->instance_form_fields = array(
            'enabled' => array(
                'title' 		=> __( 'Enable/Disable' ),
                'type' 			=> 'checkbox',
                'label' 		=> __( 'Enable this shipping method' ),
                'default' 		=> 'yes',
            ),
        );

        // Title shown in store
        $this->title = __( 'Zenrush Premium Delivery', 'zenrush' );

        // Title shown in admin backend
        $this->method_title = __( 'Zenrush Premium Delivery', 'zenrush' );

        $this->init();
    }

    /**
     * Initializes the shipping option with WooCommerce
     *
     * @return void
     */
    function init(): void
    {
        // Initialize Settings API
        $this->init_form_fields();
        $this->init_settings();

        $this->enabled = $this->get_option( 'enabled' );

        // Fetch Zenrush Data
        $rates = $this->fetchCustomRates();
        $cutoff_time = $this->fetchCutoffTime();

        if( !empty($rates) ) {
            $this->base_rate = $rates['base_rate'];
            $this->custom_rates = $rates['custom_rates'];
        }

        if ( $cutoff_time ) {
            $this->cutoff_time_msg = $cutoff_time;
        }

        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
    }

    /**
     * Util to get the correct rate price from int to euros, e.g. 299 => 2.99
     *
     * @since 1.0.0
     * @access private
     * @param $rate
     * @return float|int
     */
    private function calcPrice($rate): float|int {
        if ($rate === 0) return $rate;
        return $rate / 100;
    }


    /**
     * Fetches current configured zenrush pricing rates for the store
     *
     * @since 1.0.0
     * @access private
     * @return array
     */
    private function fetchCustomRates(): array {
//        $storeId = get_option($this->prefix . 'store_id');
//        $url = 'https://zenrush.zenfulfillment.com/api/rates?storeId=' . $storeId;
        $url = 'http://host.docker.internal:6969/api/zenrush/rates?storeId=5f6dbc26604f5f002604410e';
        $response = wp_remote_get( $url );
        $decoded_data = json_decode( wp_remote_retrieve_body( $response ), true );
        $status_code = wp_remote_retrieve_response_code( $response );

        if ( is_wp_error( $response ) || $status_code !== 200 ) {
            return array(
                'base_rate'     =>  499,
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
     * Fetches the cutoff delivery time to be displayed in the store
     *
     * @since 1.0.0
     * @access private
     * @return array
     */
    private function fetchCutoffTime(): array
    {
        global $wp;

        $storeId = get_option( $this->prefix . 'store_id' );
        $locale = strtok( get_locale() , '_');
        $origin = strval( home_url( $wp->request ) );
        $data = array(
            'storeId' => $storeId,
            'locale' => $locale,
            'props' => json_encode( ['store' => $storeId, 'variant' => 'primary'] ),
            'origin' => $origin
        );
        $query = http_build_query( $data, NULL, '&', PHP_QUERY_RFC3986 );
        $req_args = array(
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
        );
//        $url = 'https://zenrush.zenfulfillment.com/api/rates?' . $query;

        $url = 'http://host.docker.internal:6969/api/zenrush/timer?' . $query;
        $response = wp_remote_get( $url, $req_args );
        $response_code = wp_remote_retrieve_response_code( $response );

        if ( is_wp_error( $response ) || $response_code !== 200 ) {
            return [];
        }

        return json_decode( wp_remote_retrieve_body( $response ), true )['message'];
    }

    /**
     * Calculates the shipping rate.
     *
     * @access public
     * @param array $package Package information.
     * @return void
     */
    public function calculate_shipping($package = array()): void {
        // this is the price of the cart without shipping costs & taxes, includes discounts!
        $cart_price = WC()->cart->get_cart_contents_total();

        // check stock of cart items
        $products_in_stock = true;
        foreach ($package['contents'] as $item) {
            $product = $item['data'];
            if (!$product->is_in_stock()) {
                $products_in_stock = false;
                break;
            }
        }

        if ( !$products_in_stock ) {
            error_log('Not displaying Zenrush - One or more products are out of stock!');
            return;
        }

        $cost = $this->calcPrice( $this->base_rate );
        $rates = $this->custom_rates;

        if ( !empty($rates) ) {
             foreach ( $rates as $rate ) {
                 $rule_definition = key($rate["definition"]);
                 $rule_cost = reset($rate["definition"]);
                 $rule_price = $rate['price'];

                 switch( $rule_definition ) {
                     case '$gte':
                        if ( $cart_price >= $rule_cost ) {
                            $cost = $this->calcPrice( $rule_price );
                        }
                        break;
                     case '$gt':
                         if ( $cart_price > $rule_cost ) {
                             $cost = $this->calcPrice( $rule_price );
                         }
                         break;
                     case '$lte':
                        if ( $cart_price <= $rule_cost ) {
                            $cost = $this->calcPrice( $rule_price );
                        }
                        break;
                     case '$lt':
                         if ( $cart_price < $rule_cost ) {
                             $cost = $this->calcPrice( $rule_price );
                         }
                         break;
                     default:
                         break;
                }
             }
        }

        $rate_title = $this->title;

        if ( !empty( $this->cutoff_time_msg ) ) {
            $rate_title = $rate_title . " - " . implode( ' ', $this->cutoff_time_msg );
        }

        $rate = array(
            'id'        =>  $this->id . $this->instance_id,
            'label'     =>  $rate_title,
            'cost'      =>  $cost,
            'package'   =>  $package,
        );

        $this->add_rate( $rate );
    }
}

