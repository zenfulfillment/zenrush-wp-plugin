<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://zenfulfillment.com
 * @since             1.0.0
 * @package           Zenrush
 * @author            Zenfulfillment <devs@zenfulfillment.com>
 *
 * @wordpress-plugin
 * Plugin Name:       Zenrush
 * GitHub Plugin URI: zenfulfillment/zenrush-wp-plugin
 * GitHub Plugin URI: https://github.com/zenfulfillment/zenrush-wp-plugin
 * Description:       Integration Plugin for Zenrush Premium Delivery
 * Version:           1.0.2
 * Author:            Zenfulfillment
 * Author URI:        https://zenfulfillment.com
 * License:           No License
 * License URI:       https://choosealicense.com/no-permission/
 * Text Domain:       zenrush
 * Domain Path:       /languages
 * Requires at least: 5.9
 * Requires PHP:      7.2
 * Tested up to:      6.2
 */

// If this file is called directly, abort.
if ( !defined('WPINC') ) {
    die;
}

/**
 * Current plugin version
 */
const ZENRUSH_VERSION = '1.0.2';

if ( !function_exists('is_plugin_active') ) {
    include_once(ABSPATH . '/wp-admin/includes/plugin.php');
}

/**
 * Check for the existence of WooCommerce and any other requirements
 */
function zenrush_check_requirements(): bool
{
    if ( is_plugin_active('woocommerce/woocommerce.php') ) {
        return true;
    } else {
        add_action( 'admin_notices', 'zenrush_missing_wc_notice' );
        return false;
    }
}

/**
 * Display a message advising WooCommerce is required
 */
function zenrush_missing_wc_notice(): void
{
    $class = 'notice notice-error';
    $message = __( 'It seems like your store does not have WooCommerce installed. This is required to use the Zenrush plugin. Please install or activate WooCommerce to use Zenrush.', 'zenrush' );
    $img_url = plugins_url( 'static/images/zenrush-badge.png', __FILE__ );

    printf('<div class="%1$s"><img src="%3$s" alt="Zenrush Badge" style="width: 30px; height: auto; margin-top: 0.25rem;"/><p><strong>%2$s</strong></p></div>', esc_attr($class), esc_html($message), $img_url);
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-zenrush-activator.php
 */
function activate_zenrush(): void
{
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-zenrush-activator.php';
    Zenrush_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-zenrush-deactivator.php
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
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-zenrush.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_zenrush(): void
{
    if ( zenrush_check_requirements() ) {
        $plugin = new Zenrush();
        $plugin->run();
    }
}

run_zenrush();
