<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://zenfulfillment.com
 * @since      1.0.0
 *
 * @package    Zenrush
 * @subpackage Zenrush/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Zenrush
 * @subpackage Zenrush/public
 * @author     Zenfulfillment <devs@zenfulfillment.com>
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
     * The prefix for options to use
     *
     * @since    1.0.0
     * @access   private
     * @var      string
     */
    private string $prefix;

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
        $this->prefix = "Zenrush_";
        $this->element_locale = strtok( get_locale() , '_');
        $this->store_id = get_option( $this->prefix . 'store_id' );
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles(): void
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Zenrush_Loader as all the hooks are defined
         * in that particular class.
         *
         * The Zenrush_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_style( $this->plugin_name, plugin_dir_url(__FILE__) . 'css/zenrush-public.css', array(), $this->version, 'all' );

    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts(): void
    {
        wp_enqueue_script( $this->plugin_name, plugin_dir_url(__FILE__) . 'js/zenrush-public.js', array('jquery'), $this->version, false );
    }

    /**
     * Registers the JavaScript for zenrush snippet
     * Checks if the store_id is set and is valid, otherwise the script will not be injected
     *
     * @since    1.0.0
     */
    public function zenrush_add_bundle(): void
    {
        $store_id = get_option( $this->prefix . 'store_id' );
        $idIsValid = $store_id && strlen( $store_id ) === 24 && strspn( $store_id, '0123456789ABCDEFabcdef' ) === 24;

        if ( $idIsValid ) {
            $snippet_src = 'https://zenrush.zenfulfillment.com/client/' . $this->snippet_name . '.js';
            wp_enqueue_script( $this->snippet_name, $snippet_src, null, null, false );
        } else {
            error_log( 'Zenrush Store Key is invalid!' );
        }
    }

    public function zenrush_add_element_to_product_page(): void
    {
        global $product;

        $showOnProductPage = get_option( $this->prefix . 'show_on_product_page' ) === 'yes';

        if ( $showOnProductPage && $product->is_in_stock() ) {
            echo '<zf-zenrush store="'. $this->store_id .'" locale="'. $this->element_locale .'"></zf-zenrush>';
        }
    }


    public function zenrush_add_element_to_product_listing($add_to_cart_html, $product, $args): string
    {
        // TODO: Add these settings
        // $showOnProductListing = get_option($this->prefix . 'show_on_product_listing');
        // $showDeliveryDate = get_option($this->prefix . 'show_delivery_date_on_listing');
        // $beforeAddToCart = get_option($this->prefix . 'before_add_to_cart_on_listing');
        // $before = '...'
        // $after = '...'
        // return $before . $add_to_cart_html . $after
        if ( $product->is_in_stock() ) {
            return '<zf-zenrush store="'. $this->store_id  .'" locale="'. $this->element_locale .'" variant="badge" showdeliverydate></zf-zenrush>' . $add_to_cart_html;
        }
        return $add_to_cart_html;
    }

    public function zenrush_add_element_to_checkout(): void {
        // TODO: Add checkout for woocommerce to element
        echo '<zf-zenrush store="'. $this->store_id .'" locale="'. $this->element_locale .'" type="checkout" debug></zf-zenrush>';
    }
}
