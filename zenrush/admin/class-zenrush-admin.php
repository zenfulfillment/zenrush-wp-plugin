<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://zenfulfillment.com
 * @since      1.0.0
 *
 * @package    Zenrush
 * @subpackage Zenrush/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Zenrush
 * @subpackage Zenrush/admin
 * @author     Zenfulfillment <devs@zenfulfillment.com>
 */
class Zenrush_Admin
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $plugin_name The ID of this plugin.
     */
    private string $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version of this plugin.
     */
    private string $version;

    /**
     * Initialize the class and set its properties.
     *
     * @param   string $plugin_name The name of this plugin.
     * @param   string $version     The version of this plugin.
     * @since   1.0.0
     */
    public function __construct(string $plugin_name, string $version)
    {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

    }

    /**
     * Register the stylesheets for the admin area.
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

        wp_enqueue_style( $this->plugin_name, plugin_dir_url(__FILE__) . 'css/zenrush-admin.css', array(), $this->version, 'all' );

    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts(): void
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

        wp_enqueue_script( $this->plugin_name, plugin_dir_url(__FILE__) . 'js/zenrush-admin.js', array('jquery'), $this->version, false );

    }

    /**
     * Load dependencies for Zenrush tab in WooCommerce settings
     *
     * @since   1.0.0
     */
    public function zenrush_add_settings($settings): array
    {
        $settings[] = include plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-zenrush-wc-settings.php';
        return $settings;
    }

    /**
     * Add Settings link to plugin listing in admin backend
     *
     * @since   1.0.0
     * @access  public
     * @param   array $links
     * @return  array
     */
    public function zenrush_settings_link(array $links): array
    {
        $url = admin_url( 'admin.php?page=wc-settings&tab=zenrush' );
        $settings_link = array( 'settings' => '<a href="' . esc_url( $url ) . '">' . __('Settings', 'zenrush') . '</a>' );
        return array_merge($settings_link, $links);
    }
}
