<?php

/**
 * The Elementor widget specific functionality of the plugin.
 *
 * @link       https://zenfulfillment.com
 * @since      1.1.6
 *
 * @package    Zenrush
 * @subpackage Zenrush/elementor-widget
 */

if ( !defined('ABSPATH') ) {
    exit; // Exit if accessed directly.
}

/**
 * The Elementor widget functionality of the plugin.
 *
 * @package    Zenrush
 * @subpackage Zenrush/elementor-widget
 * @author     Zenfulfillment <devs@zenfulfillment.com>
 */
class Zenrush_Elementor
{
    /**
     * Minimum required Elementor Version
     *
     * @since 1.0.0
     * @var string Minimum Elementor version required to run the zenrush widget.
     */
    const MINIMUM_ELEMENTOR_VERSION = '3.0.0';

    public function zenrush_register_elementor_widget( $widgets_manager ): void
    {
        if ( ! version_compare( ELEMENTOR_VERSION, self::MINIMUM_ELEMENTOR_VERSION, '>=' ) ) {
            add_action( 'admin_notices', array( $this, 'zenrush_admin_notice_minimum_elementor_version' ) );
            return;
        }

        require_once( 'class-zenrush-elementor-widget.php' );
        $widgets_manager->register( new \Elementor_Zenrush_Widget() );
    }

    public function zenrush_add_elementor_widget_categories( $elements_manager ): void
    {
        $elements_manager->add_category(
            'zenfulfillment',
            [
                'title' => esc_html__( 'Zenfulfillment', 'zenrush' ),
                'icon' => 'eicon-code',
            ]
        );
    }


    /**
     * Admin notice
     *
     * Warning when the site doesn't have the minimum required Elementor version.
     *
     * @since 1.0.0
     * @access public
     */
    public function zenrush_admin_notice_minimum_elementor_version(): string
    {
        return sprintf(
            wp_kses(
                '<div class="notice notice-warning is-dismissible"><p><strong>"%1$s"</strong> requires <strong>"%2$s"</strong> version %3$s or greater.</p></div>',
                array(
                    'div' => array(
                        'class'  => array(),
                        'p'      => array(),
                        'strong' => array(),
                    ),
                )
            ),
            'Zenrush Elementor Widget',
            'Elementor',
            self::MINIMUM_ELEMENTOR_VERSION
        );
    }
}
