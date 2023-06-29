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
     * @var      string
     */
    private string $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string
     */
    private string $version;

    /**
     * Initialize the class and set its properties.
     *
     * @param   string $plugin_name The name of this plugin.
     * @param   string $version     The version of this plugin.
     * @since   1.0.0
     */
    public function __construct($plugin_name, $version)
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
     *
     * @param string[] $actions
     * @param string $plugin_file
     * @param array|null $plugin_data
     * @param string $context
     * @return  array
     */
    public function zenrush_settings_link($actions, $plugin_file, $plugin_data, $context): array
    {
        if ( empty( $plugin_data ) ) return $actions;

        $settings_url = admin_url( 'admin.php?page=wc-settings&tab=zenrush' );
        if ( $plugin_data['Name'] === "WooCommerce" ) {
            $settings_link = array( 'zenrush_settings' => '<a href="' . esc_url( $settings_url ) . '">' . __( 'Zenrush', 'zenrush' ) . '</a>' );
            return array_merge( $settings_link, $actions );
        }

        if ( $plugin_data['Name'] === 'Zenrush' ) {
            $dashboard_url = 'https://app.zenfulfillment.com/app/dashboard';
            $settings_link = array(
                'dashboard' =>  '<a href="' . esc_url( $dashboard_url ) . '">' . __( 'Dashboard', 'zenrush' ) . '</a>',
                'settings'  =>  '<a href="' . esc_url( $settings_url ) . '">' . __( 'Settings', 'zenrush' ) . '</a>',
            );
            return array_merge( $settings_link, $actions );
        }

        return $actions;
    }

    /**
     * Adds a `is_zenrush` custom meta field to the order.
     * This data be found on the order in the `"meta_data"` array.
     *
     * @since 1.0.0
     * @access public
     *
     * @param $order - Instance of WC_Order Class
     * @param $data - Some data fields of the order
     * @return void
     */
    public function zenrush_add_order_meta_data( WC_Order $order, $data ): void
    {

        $shipping_method = strtolower( $order->get_shipping_method() );

        if( $shipping_method && str_contains($shipping_method, 'zenrush') ) {
            $order->update_meta_data( 'is_zenrush', 'yes' );
        }

    }

    /**
     * Enables auto-updates for the plugin
     * 
     * @since 1.0.0
     * @return bool
     */
    public function zenrush_enable_auto_update(): bool
    {
        return true;
    }

    /**
     * Returns all configured shipping zones of the store
     * 
     * @since 1.0.8
     * @return array
     */
    public function zenrush_get_all_shipping_zones(): array
    {
        $data_store = WC_Data_Store::load( 'shipping-zone' );
        $raw_zones = $data_store->get_zones();
        foreach ( $raw_zones as $raw_zone ) {
            $zones[] = new WC_Shipping_Zone( $raw_zone );
        }
        return $zones;
    }

    /**
     * Returns all configured payment methods of the store
     * 
     * @since 1.0.8
     * @return array
     */
    public function zenrush_get_all_payment_methods(): array
    {
        $installed_payment_methods = WC()->payment_gateways()->payment_gateways();
        foreach( $installed_payment_methods as $method ) {
            if( $method->enabled === 'yes' ) {
                $available_payment_methods[$method->title] = $method;
            }
        }
        return $available_payment_methods;
    }

    /**
     * Checks if Zenrush is enabled for at least one payment method
     * Used for the todos list in the setup banner
     * 
     * @since 1.0.8
     * @return string
     */
    public function zenrush_check_payment_methods(): string
    {
        $available_payment_methods = $this->zenrush_get_all_payment_methods();
        if( count( $available_payment_methods ) === 0 ) {
            return 'NO_PAYMENT_METHODS_AVAILABLE';
        };
        foreach( $available_payment_methods as $method ) {
            $hasZenrush = array_filter($method->enable_for_methods, function($enabled_method_id) {
                return $enabled_method_id === 'zenrush_premiumversand';
            });
        }
        return count( $hasZenrush ) > 0 ? 'ZENRUSH_ENABLED_PAYMENT_METHOD' : 'NO_ZENRUSH_ENABLED_PAYMENT_METHOD';
    }

    /**
     * Checks if Zenrush is enabled for at least one shipping zone, and the shipping zone is configured to be in Germany
     * 
     * @since 1.0.8
     * @return string
     */
    public function zenrush_check_shipping_rates(): string
    {
        $foundDEZone = false;
        $shipping_zone = null;

        foreach ( $this->zenrush_get_all_shipping_zones() as $zone ) {
            $zone_locations = $zone->get_zone_locations();
            $isDE = array_filter($zone_locations, function($location) {
                $location_arr = (array) $location;
                return $location_arr['code'] === 'DE';
            });

            if ( $isDE ) {
                $foundDEZone = true;
                $shipping_zone = $zone;
            }
        }

        if ( !$shipping_zone ) {
            return 'NO_DE_SHIPPING_ZONES';
        }

        $zone_shipping_methods = $shipping_zone->get_shipping_methods();
        foreach ( $zone_shipping_methods as $index => $method ) {
            if(get_class( $method ) === 'WC_Zenrush_Premiumversand') {
                return 'ZENRUSH_FOUND_FOR_DE';
            }
        }

        return 'NO_ZENRUSH_FOUND_FOR_DE';
    }

    /**
     * Renders the setup banner in the admin backend
     */
    public function zenrush_complete_setup_notification(): void
    {
        $store_id_error = !get_option( 'Zenrush_store_id' );
        $shipping_zone_status = $this->zenrush_check_shipping_rates();
        $payment_methods_status = $this->zenrush_check_payment_methods();
        $shipping_zone_error = $shipping_zone_status !== 'ZENRUSH_FOUND_FOR_DE';
        $payment_methods_error = $payment_methods_status !== 'ZENRUSH_ENABLED_PAYMENT_METHOD';

        $todos = array(
            'store_id' => __( 'Enter your Merchant ID', 'zenrush' ),
            'shipping_zone' => __( 'Enable Zenrush for the Germany shipping zone', 'zenrush' ),
            'payment_methods' => __( 'Enable Zenrush for your payment methods', 'zenrush' ),
        );

        if( $shipping_zone_error ) {
            if( $shipping_zone_status === 'NO_DE_SHIPPING_ZONES' ) {
                $todos['shipping_zone'] = __( 'Add a shipping zone for Germany', 'zenrush' );
            } else {
                $todos['shipping_zone'] = __( 'Enable Zenrush for the Germany shipping zone', 'zenrush' );
            }
        }

        $setup_incomplete = $store_id_error || $shipping_zone_error || $payment_methods_error;

        if ( $setup_incomplete ) {
            $title = __( 'Complete Zenrush setup to activate', 'zenrush' );
            $message = __( '<b>Zenrush Premium Delivery</b> is almost ready to go! Once you completed the setup, you\'ll have access to a premium delivery option and shipping calculation in real-time.', 'zenrush' );

            if ( count( $todos) > 0 ) {
                $message .= '<br><b>' . __( 'To get started, you need to:', 'zenrush' ) . '</b><br>';

                foreach( $todos as $todo ) {
                    $isChecked = false;
                    $link = null;
                    switch($todo) {
                        case $todos['store_id']:
                            $isChecked = !$store_id_error;
                            $link = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=zenrush' ) ) . '">' . $todo . '</a>';
                            break;
                        case $todos['shipping_zone']:
                            $isChecked = !$shipping_zone_error;
                            $link = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping' ) ) . '">' . $todo . '</a>';
                            break;
                        case $todos['payment_methods']:
                            $isChecked = !$payment_methods_error;
                            $link = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) . '">' . $todo . '</a>';
                            break;
                    };

                    $message .= '<div class="todo">
                        <input type="checkbox" id="'. $todo .'" name="'. $todo .'" value="'. $todo .'" '. ($isChecked ? 'checked' : '') .' class="checkbox">
                        <label class="label">' . $link . '</label>
                    </div>';
                }
            }

            $message .= '<a href="https://setup.zenfulfillment.com/zenrush/integration/woocommerce?source=plugin" style="margin-top: 1rem">' . __( 'Plugin setup documentation', 'zenrush' ) . '</a>';

            $html = $this->zenrush_get_notification('setup', $title, $message, '', true);

            print $html;
        }
    }

    /**
     * Adds related links to the plugin meta row in admin backend
     *
     * @since 1.0.0
     * @access public
     *
     * @param string[] $links
     * @param string $plugin_file
     * @param array|null $plugin_data
     * @param string $status
     * @return array
     */
    public function zenrush_plugin_row_meta($links, $plugin_file, $plugin_data, $status): array
    {
        if ( empty ( $plugin_data ) ) return $links;

        if ( $plugin_data['Name'] === 'Zenrush' ) {
            $row_meta = array(
              'docs' => '<a href="' . esc_url( 'https://setup.zenfulfillment.com/zenrush/integration/woocommerce?source=plugin' ) . '" target="_blank" aria-label="' . esc_attr__( 'Zenrush Documentation', 'zenrush' ) . '">' . esc_html__( 'Docs', 'zenrush' ) . '</a>'
            );
            return array_merge( $links, $row_meta );
        }

        return $links;
    }

    /**
     * Util to generate the HTML for a notification banner in the admin backend.
     *
     * @since   1.0.0
     * @access  private
     * @used-by zenrush_complete_setup_notification
     *
     * @param string $type
     * @param string $title
     * @param string $message
     * @param string $btn_link
     * @param bool   $with_logo
     * @return string
     */
    private function zenrush_get_notification(string $type, string $title, string $message, string $btn_link, bool $with_logo = true ): string
    {
        $class_name = "zf-$type-notice";
        $logo_path = plugins_url( '../static/images/zenrush-logo.svg', __FILE__ );
        $btn = $btn_link ? "<button class='button button-primary zf-setup-notice__content-btn'>$btn_link</button>" : '';
        $logo = $with_logo ? "<div class='zf-setup-notice__logo'><img src='$logo_path' alt='Zenrush Logo' style='width: 220px; height: auto;'/></div>" : '';

        return "
            <div class='notice $class_name'>
                $logo
                <div class='zf-setup-notice__content'>
                    <div class='zf-setup-notice__content-title'><h1>$title</h1></div>
                    <p class='zf-setup-notice__content-text'>$message</p>
                    $btn
                </div>
            </div>";
    }
}
