<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @package     Zenrush
 * @subpackage  Zenrush/admin
 * @author      Zenfulfillment <devs@zenfulfillment.com>
 * @since       1.0.0
 */
class Zenrush_Admin
{

    /**
     * The ID of this plugin.
     *
     * @since   1.0.0
     * @access  private
     * @var     string
     */
    private string $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since   1.0.0
     * @access  private
     * @var     string
     */
    private string $version;

    /**
     * Initialize the class and set its properties.
     *
     * @param   string  $plugin_name    The name of this plugin.
     * @param   string  $version        The version of this plugin.
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
     * @since   1.0.0
     */
    public function enqueue_styles(): void
    {
        wp_enqueue_style( $this->plugin_name, plugin_dir_url(__FILE__) . 'css/zenrush-admin.css', array(), $this->version, 'all' );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since   1.0.0
     */
    public function enqueue_scripts(): void
    {
        wp_enqueue_script( $this->plugin_name, plugin_dir_url(__FILE__) . 'js/zenrush-admin.js', array( 'jquery' ), $this->version, false );
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
     * @param   string[]    $actions
     * @param   string      $plugin_file
     * @param   array|null  $plugin_data
     * @param   string      $context
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
     * @since   1.0.0
     * @access  public
     *
     * @param   $order  Instance of WC_Order Class
     * @param   $data   Some data fields of the order
     * @return  void
     */
    public function zenrush_add_order_meta_data( WC_Order $order, $data ): void
    {
        $shipping_method = strtolower( $order->get_shipping_method() );

        if( $shipping_method ) {
            if( $shipping_method === 'zenrush_premiumversand' ) {
                $order->update_meta_data( 'is_zenrush', 'yes' );
            }
    
            if( $shipping_method === 'zenrush_standard' ) {
                $order->update_meta_data( 'is_zenrush_std', 'yes' );
            }
        }

    }

    /**
     * Returns all configured shipping zones of the store
     *
     * @return  array
     * @throws  Exception
     * @since   1.0.8
     */
    public function zenrush_get_all_shipping_zones(): array
    {
        $data_store = WC_Data_Store::load( 'shipping-zone' );
        $raw_zones = $data_store->get_zones();
        $zones = array();
        foreach ( $raw_zones as $raw_zone ) {
            $zones[] = new WC_Shipping_Zone( $raw_zone );
        }
        return $zones;
    }

    /**
     * Checks if Zenrush is enabled for at least one shipping zone, and the shipping zone is configured to be in Germany
     * 
     * @since   1.0.8
     * @return  string
     */
    public function zenrush_check_shipping_rates(): string
    {
        $shipping_zone = null;

        try {
            foreach ( $this->zenrush_get_all_shipping_zones() as $zone ) {
                $zone_locations = $zone->get_zone_locations();
                $is_DE = array_filter( $zone_locations,
                    function ($location) {
                        $location_arr = (array) $location;
                        return $location_arr['code'] === 'DE';
                    }
                );

                if ($is_DE) {
                    $shipping_zone = $zone;
                }
            }
        } catch (Exception $e) {
            return 'NO_DE_SHIPPING_ZONES';
        }

        if ( !$shipping_zone ) {
            return 'NO_DE_SHIPPING_ZONES';
        }

        if ( function_exists( 'get_class' ) ) {
            $zone_shipping_methods = $shipping_zone->get_shipping_methods();
            foreach ( $zone_shipping_methods as $index => $method ) {
                if ( get_class( $method ) === 'WC_Zenrush_Premiumversand' || get_class( $method ) === 'WC_Zenrush_Standardversand' ) {
                    return 'ZENRUSH_FOUND_FOR_DE';
                }
            }
        } else {
            return 'NOT_SUPPORTED_PHP_VERSION';
        }

        return 'NO_ZENRUSH_FOUND_FOR_DE';
    }

    /**
     * Renders the setup banner in the admin backend
     * 
     * @since   1.0.8
     */
    public function zenrush_complete_setup_notification(): void
    {
        $user_id = get_current_user_id();
        $store_id = get_option( 'Zenrush_store_id' );
        $idIsValid = $store_id && strlen( $store_id ) === 24 && strspn( $store_id, '0123456789ABCDEFabcdef' ) === 24;
        $store_id_error = !$store_id || !$idIsValid;
        $shipping_zone_status = $this->zenrush_check_shipping_rates();
        $shipping_zone_error = $shipping_zone_status !== 'ZENRUSH_FOUND_FOR_DE';

        if( !$store_id_error && get_user_meta( $user_id, 'zenrush_setup_notice_dismissed' ) ) {
            return;
        }

        $todos = array(
            'store_id' => __( 'Enter your Merchant Key', 'zenrush' ),
            'shipping_zone' => __( 'Enable Zenrush for the Germany shipping zone', 'zenrush' ),
        );

        if( $shipping_zone_status !== 'NOT_SUPPORTED_PHP_VERSION' && $shipping_zone_error ) {
            if( $shipping_zone_status === 'NO_DE_SHIPPING_ZONES' ) {
                $todos['shipping_zone'] = __( 'Add a shipping zone for Germany', 'zenrush' );
            } else {
                $todos['shipping_zone'] = __( 'Enable Zenrush for the Germany shipping zone', 'zenrush' );
            }
        }

        $setup_incomplete = $store_id_error || $shipping_zone_error;

        if ( $setup_incomplete ) {
            $title = __( 'Complete Zenrush setup to activate', 'zenrush' );
            $message = __( '<b>Zenrush Premium Delivery</b> is almost ready to go! Once you completed the setup, you\'ll have access to a premium delivery option and shipping calculation in real-time.', 'zenrush' );

            if ( count( $todos ) > 0 ) {
                $message .= '<br>' . __( 'To get started', 'zenrush' ) . ' <a href="https://setup.zenfulfillment.com/zenrush/integration/woocommerce" style="margin-top: 1rem">' . __( 'follow the setup documentation', 'zenrush' ) . '</a><br>';

                foreach( $todos as $todo ) {
                    $isChecked = false;
                    $link = null;
                    switch($todo) {
                        case $todos['store_id']:
                            $isChecked = !$store_id_error;
                            $link = $isChecked ? $todo : '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=zenrush' ) ) . '">' . $todo . '</a>';
                            break;
                        case $todos['shipping_zone']:
                            $isChecked = !$shipping_zone_error;
                            $link = $isChecked ? $todo : '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping' ) ) . '">' . $todo . '</a>';
                            break;
                    };

                    $message .= '<div class="todo">
                        <input type="checkbox" id="'. $todo .'" name="'. $todo .'" value="" '. ($isChecked ? 'checked' : '') .' class="checkbox">
                        <label class="label">' . $link . '</label>
                    </div>';
                }
            }

            $html = $this->zenrush_get_notification( 'setup', $title, $message, '', true );

            print $html;
        }
    }

    /**
     * Permanently removes the setup notification banner, even if setup was not completed yet
     * 
     * @since   1.0.8
     */
    function zenrush_complete_setup_notification_dismissed(): void
    {
        $user_id = get_current_user_id();
        if( isset( $_GET['dismiss-zf-setup-notice'] ) ) {
            add_user_meta( $user_id, 'zenrush_setup_notice_dismissed', 'true', true );
        }
    }

    /**
     * Adds related links to the plugin meta row in admin backend
     *
     * @since 1.0.0
     * @access public
     *
     * @param   string[]      $links
     * @param   string        $plugin_file
     * @param   array|null    $plugin_data
     * @param   string        $status
     * @return  array
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
     * @param   string  $type
     * @param   string  $title
     * @param   string  $message
     * @param   string  $btn_link
     * @param   bool    $with_logo
     * @return  string  
     */
    private function zenrush_get_notification(string $type, string $title, string $message, string $btn_link, bool $with_logo = true ): string
    {
        $class_name = "zf-$type-notice show";
        $logo_path = plugins_url( '../static/images/zenrush-logo.svg', __FILE__ );
        $btn = $btn_link ? "<button class='button button-primary zf-setup-notice__content-btn'>$btn_link</button>" : '';
        $logo = $with_logo ? "<div class='zf-setup-notice__logo'><img src='$logo_path' alt='Zenrush Logo' style='width: 220px; height: auto;'/></div>" : '';

        return "
            <div class='notice $class_name is-dismissible'>
                <a href='?dismiss-zf-setup-notice' class='button notice-dismiss dismiss-btn'>
                    <span class='screen-reader-text'>Diese Meldung ausblenden.</span>
                </a>
                $logo
                <div class='zf-setup-notice__content'>
                    <div class='zf-setup-notice__content-title'><h1>$title</h1></div>
                    <p class='zf-setup-notice__content-text'>$message</p>
                    $btn
                </div>
            </div>";
    }
}
