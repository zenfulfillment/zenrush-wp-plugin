<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://zenfulfillment.com
 * @since      1.0.0
 *
 * @package    Zenrush
 * @subpackage Zenrush/includes
 */

if (!defined('ABSPATH')) {
    exit;
}


/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Zenrush
 * @subpackage Zenrush/includes
 * @author     Zenfulfillment <devs@zenfulfillment.com>
 */
class Zenrush
{

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Zenrush_Loader $loader Maintains and registers all hooks for the plugin.
     */
    protected Zenrush_Loader $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $plugin_name The string used to uniquely identify this plugin.
     */
    protected string $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $version The current version of the plugin.
     */
    protected string $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct()
    {
        if (defined('ZENRUSH_VERSION')) {
            $this->version = ZENRUSH_VERSION;
        } else {
            $this->version = '1.0.0';
        }

        $this->plugin_name = 'zenrush';

        $this->load_dependencies();
        $this->init_auto_updater();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_shipping_method();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Zenrush_Loader. Orchestrates the hooks of the plugin.
     * - Zenrush_i18n. Defines internationalization functionality.
     * - Zenrush_Admin. Defines all hooks for the admin area.
     * - Zenrush_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies(): void
    {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-zenrush-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-zenrush-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-zenrush-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-zenrush-public.php';

        /**
         * The class responsible for defining the zenrush shipping method and calculation
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'shipping-method/class-zenrush-shipping-method.php';

        /**
         * The class responsible for getting updates from repo
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-zenrush-updater.php';

        $this->loader = new Zenrush_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Zenrush_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale(): void
    {

        $plugin_i18n = new Zenrush_i18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');

    }

    /**
     * Initialize the Updater class to check for latest releases on github and show a notice.
     *
     * @since   1.0.0
     * @access  private
     * @return  void
     */
    private function init_auto_updater(): void
    {

        $plugin_updater = new Zenrush_Updater( ZENRUSH_PLUGIN_FILE );

        // TODO: set values for username, repository and token
        $plugin_updater->set_username( '' );
        $plugin_updater->set_repository( '' );
        $plugin_updater->authorize( '' );
        $plugin_updater->initialize();

    }

    /**
     * Register all the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks(): void
    {
        $plugin_admin = new Zenrush_Admin($this->get_plugin_name(), $this->get_version());

        // Enables automatic update checking for the plugin
        $this->loader->add_filter( 'auto_update_plugin', $plugin_admin, 'zenrush_enable_auto_update' );

        // Show notification when plugin setup is not completed yet
        $this->loader->add_action( 'admin_notices', $plugin_admin, 'zenrush_complete_setup_notification' );

        // Add plugin css / js
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

        // Add plugin settings to WooCommerce
        $this->loader->add_filter('woocommerce_get_settings_pages', $plugin_admin, 'zenrush_add_settings');

        // Add 'Settings' link to plugin listing in admin backend
        $this->loader->add_filter('plugin_action_links', $plugin_admin, 'zenrush_settings_link', 10, 4 );

        // Add other links to plugin meta section in admin backend
        $this->loader->add_filter(  'plugin_row_meta', $plugin_admin, 'zenrush_plugin_row_meta', 10, 4 );

        // Adds a customer meta field 'zenrush' on the order, if the order is zenrush
        // This fires before the order is saved to the db.
        $this->loader->add_action( 'woocommerce_checkout_create_order', $plugin_admin,  'zenrush_add_order_meta_data', 10, 2  );
    }

    /**
     * Register the Zenrush shipping method
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_shipping_method(): void
    {
        $plugin_shipping = new Zenrush_Shipping_Method();

        // Add Zenrush Shipping Method to WooCommerce
        $this->loader->add_action('woocommerce_shipping_init', $plugin_shipping, 'zenrush_init_shipping_method');
        $this->loader->add_filter('woocommerce_shipping_methods', $plugin_shipping, 'zenrush_add_shipping_method');
    }


    /**
     * Register all the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks(): void
    {
        $plugin_public = new Zenrush_Public($this->get_plugin_name(), $this->get_version());

        // Add plugin css / js
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');

        // Add zenrush snippet bundle in <head></head> of store
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'zenrush_add_bundle');

        // Add <zf-zenrush> element to sections of the store
        // -> Product Page
        $this->loader->add_action('woocommerce_before_add_to_cart_button', $plugin_public, 'zenrush_add_element_to_product_page');
        // -> Listing/Category Pages & Related Articles
        $this->loader->add_action('woocommerce_loop_add_to_cart_link', $plugin_public, 'zenrush_add_element_to_product_listing', 10, 3);
        // -> Checkout Page
        $this->loader->add_action('woocommerce_after_checkout_shipping_form', $plugin_public, 'zenrush_add_element_to_checkout');
    }

    /**
     * Run the loader to execute all the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run(): void
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @return    string    The name of the plugin.
     * @since     1.0.0
     */
    public function get_plugin_name(): string
    {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @return    Zenrush_Loader    Orchestrates the hooks of the plugin.
     * @since     1.0.0
     */
    public function get_loader(): Zenrush_Loader
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @return    string    The version number of the plugin.
     * @since     1.0.0
     */
    public function get_version(): string
    {
        return $this->version;
    }

}
