<?php

/**
 * The file that defines the zenrush standard shipping method class
 *
 * @since      1.2.15
 *
 * @package    Zenrush
 * @subpackage Zenrush/shipping-method
 */

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Include the Zenrush_Shipping_Method class file if not already included
require_once 'class-zenrush-shipping-method.php';

/**
 * The standard shipping method class
 *
 * @since      1.2.15
 * @package    Zenrush
 * @subpackage Zenrush/admin
 * @author     Zenfulfillment <devs@zenfulfillment.com>
 */
class WC_Zenrush_Standardversand extends WC_Shipping_Method
{
    /**
     * The url to fetch custom zenrush rate rules from
     *
     * @since   1.1.5
     * @access  private
     * @var     string Zenrush Rates url
     */
    private string $rates_url = 'https://zenrush.zenfulfillment.com/api/zenrush/rates?zenrushType=standard';

    /**
     * The default rate to use for Zenrush, from the pricing config
     *
     * @since   1.0.0
     * @access  private
     * @var     int $base_rate The default rate for Zenrush
     */
    private int $base_rate = 399;

    /**
     * Array of custom pricing rates for zenrush defined for this store
     *
     * @since   1.0.0
     * @access  private
     * @var     array   $custom_rates The custom rates set for this store
     */
    private array $custom_rates = array();

    /**
     * Constructor for your shipping class
     *
     * @access  public
     * @return  void
     */
    public function __construct( $instance_id = 0 )
    {
        parent::__construct();

        // ID of the shipping option
        $this->id = 'zenrush_standard';

        // Instance ID
        $this->instance_id = absint( $instance_id );

        // Description shown in admin
        $this->method_description = __( 'Reliable 2 day delivery option - powered by Zenfulfillment.com', 'zenrush' );

        // Supported features
        $this->supports = ['shipping-zones', 'instance-settings', 'instance-settings-modal'];

        // Plugin ID, used as prefix for settings
        $this->plugin_id = ZENRUSH_PREFIX;

        // Title shown in store
        $this->title = __( 'Zenrush Standard Delivery (2 days)', 'zenrush' );

        // Title shown in admin backend
        $this->method_title = __( 'Zenrush Standard Delivery (2 days)', 'zenrush' );

        $this->enabled = "yes";

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

        // Controls the availability of the shipping option via the store settings
        $is_enabled = $this->is_enabled();
        $this->enabled = get_option( ZENRUSH_PREFIX . 'store_id' ) !== false && $is_enabled === true ? 'yes' : 'no';

        add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
    }

    /**
     * Fetches current configured zenrush pricing rules for the store
     * and parses them to an array with `base_rate` and `custom_rates`
     *
     * @since   1.0.0
     * @access  private
     * @return  array
     */
    private function get_rate_rules(): array
    {
        $storeId = get_option( ZENRUSH_PREFIX . 'store_id' );
        $url = $this->rates_url . '&storeId=' . $storeId;
        $transient_key = $this->id . '_rate_rules';
        $cached_rates = get_transient( $transient_key );

        if ( false === $cached_rates ) {
            // when to cached data is available
            $response = wp_remote_get( $url );
            $decoded_data = json_decode( wp_remote_retrieve_body( $response ), true );
            $status_code = wp_remote_retrieve_response_code( $response );
            if ( is_wp_error( $response ) || $status_code !== 200 ) {
                error_log( 'Failed to fetch zenrush standard pricing rules' );
                $default_data = array(
                    'base_rate'     =>  $this->base_rate,
                    'custom_rates'  =>  array()
                );
                set_transient( $transient_key, $default_data, 1 * MINUTE_IN_SECONDS );
                return $default_data;
            }

            $data = $decoded_data['data'];
            $formatted_data = array(
                'base_rate'     =>  $data['base_rate'],
                'custom_rates'  =>  $data['rules']
            );
            set_transient( $transient_key, $formatted_data, 1 * HOUR_IN_SECONDS );
            return $formatted_data;
        }
    
        // Return cached rate rules
        return $cached_rates;
    }

    /**
     * Calculates the shipping rate to be displayed in store frontend
     *
     * @access  public
     * @param   array   $package    Package information.
     * @return  void
     */
    public function calculate_shipping( $package = array() ): void
    {
        $helper = new Zenrush_Shipping_Method();
        $request_id = uniqid();
        $store_id = get_option( ZENRUSH_PREFIX . 'store_id' );

        $helper->send_beacon(
            array(
                'request_id'    =>  $request_id,
                'store_id'      =>  $store_id,
                'event'         =>  'NEW_REQUEST',
                'rate_returned' =>  null,
                'data'          =>  null,
            )
        );

        if ( $this->enabled === 'no' ) {
            $helper->send_beacon(
                array(
                    'request_id'    =>  uniqid(),
                    'store_id'      =>  get_option( ZENRUSH_PREFIX . 'store_id' ),
                    'event'         =>  'ZENRUSH_NOT_ENABLED',
                    'rate_returned' =>  false,
                    'data'          =>  array ( 'zenrush_std' => false ),
                )
            );
        }

        // total products price of the cart, includes discounts!
        $cart_price = floatval( WC()->cart->get_cart_contents_total() );
        // total amount of taxes
        $cart_tax = floatval( WC()->cart->get_taxes_total() );
        // this is the final cart price (products + taxes)
        $cart_total = $cart_price + $cart_tax;

        // Fetch store specific zenrush pricing rules
        $raw_rates = $this->get_rate_rules();
        $rates = $raw_rates['custom_rates'];
        $cost = $this->calc_cost( $raw_rates['base_rate'] );

        if ( !empty( $rates ) ) {
            foreach ( $rates as $rate ) {
                $rule_definition = key( $rate['definition'] );
                $rule_cost = reset( $rate['definition'] );
                $rule_price = (int) $rate['price'];

                switch( $rule_definition ) {
                    case '$gte':
                        if ( $cart_total >= $rule_cost ) {
                            $cost = $this->calc_cost( $rule_price );
                        }
                        break;
                    case '$gt':
                        if ( $cart_total > $rule_cost ) {
                            $cost = $this->calc_cost( $rule_price );
                        }
                        break;
                    case '$lte':
                        if ( $cart_total <= $rule_cost ) {
                            $cost = $this->calc_cost( $rule_price );
                        }
                        break;
                    case '$lt':
                        if ( $cart_total < $rule_cost ) {
                            $cost = $this->calc_cost( $rule_price );
                        }
                        break;
                    default:
                        break;
                }
            }
        }

        $rate = array(
            'id'        =>  $this->id . $this->instance_id,
            'label'     =>  $this->title,
            'cost'      =>  $cost,
            'package'   =>  $package,
        );

        $helper->send_beacon(
            array(
                'request_id'    =>  $request_id,
                'store_id'      =>  $store_id,
                'event'         =>  'SENT_RATES',
                'rate_returned' =>  true,
                'data'          =>  $rate,
            )
        );

        $this->add_rate( $rate );
    }

    /**
     * Util to transform the rate price from cents to euros, e.g. 299 => 2.99
     *
     * @since   1.0.0
     * @access  private
     */
    private function calc_cost( int $rule_price )
    {
        if ($rule_price === 0) {
            return $rule_price;
        }
        return $rule_price / 100;
    }

    /**
     * Checks if this shipping method is enabled for the store
     * 
     * @since   1.2.15
     * @access  public
     * @return  bool
     */
    public function is_enabled() {
        $zenrushShippingMethod = new Zenrush_Shipping_Method();
        $transient_key = 'zenrush_shipping_methods_enabled';
        $cached_status = get_transient( $transient_key );

        if ( false === $cached_status ) {
            // If not in cache, fetch data from API
            $status = $zenrushShippingMethod->fetch_shipping_methods_status();
    
            // Cache the status for 15min
            set_transient( $transient_key, $status, 15 * MINUTE_IN_SECONDS );
    
            return $status['zenrush_std'];
        }
        
        // Return cached status
        return $cached_status['zenrush_std'];
    }
}
