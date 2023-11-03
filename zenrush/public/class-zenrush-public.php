<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @package    Zenrush
 * @subpackage Zenrush/public
 * @author     Zenfulfillment <devs@zenfulfillment.com>
 * @since      1.0.0
 */
class Zenrush_Public
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string
     */
    private string $plugin_name;


    /**
     * The ID of the zenrush snippet.
     *
     * @since    1.0.0
     * @access   private
     * @var      string
     */
    private string $snippet_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string
     */
    private string $version;

    /**
     * The store id
     *
     * @since    1.0.0
     * @access   private
     * @var      string
     */
    private string $store_id;

    /**
     * The locale to use for the <zf-zenrush> element
     *
     * @since    1.0.0
     * @access   private
     * @var      string
     */
    private string $element_locale;

    /**
     * Initialize the class and set its properties.
     *
     * @param string $plugin_name The name of the plugin.
     * @param string $version The version of this plugin.
     * @since    1.0.0
     */
    public function __construct(string $plugin_name, string $version)
    {

        $this->plugin_name = $plugin_name;
        $this->snippet_name = 'zf-zenrush';
        $this->version = $version;
        $this->element_locale = strtok( get_locale() , '_');
        $this->store_id = get_option( ZENRUSH_PREFIX . 'store_id' );
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles(): void
    {
        wp_enqueue_style( $this->plugin_name, plugin_dir_url(__FILE__) . 'css/zenrush-public.css', array(), $this->version, 'all' );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts(): void
    {
        $checkout_styling_enabled = get_option( ZENRUSH_PREFIX . 'enable_checkout_styling' ) === 'yes';

        if ( $checkout_styling_enabled ) {
            wp_enqueue_script( $this->plugin_name, plugin_dir_url(__FILE__) . 'js/zenrush-public.js', array( 'jquery' ), $this->version, false );
        }
    }

    /**
     * Registers the JavaScript for the Zenrush Element
     * Checks if the store_id is set and is valid, otherwise the script will not be injected
     *
     * @since    1.0.0
     */
    public function zenrush_add_bundle(): void
    {
        $store_id = get_option( ZENRUSH_PREFIX . 'store_id' );
        $idIsValid = $store_id && strlen( $store_id ) === 24 && strspn( $store_id, '0123456789ABCDEFabcdef' ) === 24;

        if ( $idIsValid ) {
            $snippet_src = 'https://zenrush.zenfulfillment.com/client/' . $this->snippet_name . '.js';
            wp_enqueue_script( $this->snippet_name, $snippet_src, array(), $this->version, false );
        } else {
            error_log( 'Zenrush Merchant Key is invalid! - Please check the Zenrush Settings Page at WooCommerce -> Settings -> Zenrush' );
        }
    }

    /**
     * Adds the zenrush element to the product details page
     * Note: Requires automatic integration to be enabled in settings
     *
     * @since    1.0.0
     */
    public function zenrush_add_element_to_product_page(): void
    {
        global $product;

        $showOnProductPage = get_option( ZENRUSH_PREFIX . 'show_on_product_page' ) === 'yes';

        if ( $showOnProductPage && $product->is_in_stock() ) {
            echo '<div id="zenrush_product_details"><zf-zenrush store="'. $this->store_id .'" locale="'. $this->element_locale .'"></zf-zenrush></div>';
        }
    }

    /**
     * Adds the zenrush element to the product listings (including category pages & related products)
     * Note: Requires automatic integration to be enabled in settings
     *
     * @since   1.0.0
     * 
     * @param   $add_to_cart_html
     * @param   $product
     * @param   $args
     * @return  string
     */
    public function zenrush_add_element_to_product_listing($add_to_cart_html, $product, $args): string
    {
        $showOnProductListing = get_option(ZENRUSH_PREFIX . 'show_on_product_listing') === 'yes';
        $hideDeliveryDate = get_option(ZENRUSH_PREFIX . 'hide_delivery_date_on_listing') === 'yes';

        if ( $showOnProductListing && $product->is_in_stock() ) {
            if ( $hideDeliveryDate ) {
                return '<div id="zenrush_product_listing_icon"><zf-zenrush store="'. $this->store_id  .'" locale="'. $this->element_locale .'" variant="badge"></zf-zenrush></div>' . $add_to_cart_html;
            } else {
                return '<div id="zenrush_product_listing_timer"><zf-zenrush store="'. $this->store_id  .'" locale="'. $this->element_locale .'" variant="badge" showdeliverydate></zf-zenrush></div>' . $add_to_cart_html;
            }
        }

        return $add_to_cart_html;
    }

}
