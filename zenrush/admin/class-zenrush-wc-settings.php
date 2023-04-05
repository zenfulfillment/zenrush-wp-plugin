<?php
/**
 * Extends the WC_Settings_Page class
 *
 * @since       1.0.0
 *
 * @package     Zenrush
 * @subpackage  Zenrush/admin
 *
 */

if ( !defined('ABSPATH') ) exit; // Exit if accessed directly

if ( !class_exists('Zenrush_WC_Settings') ) {

    /**
     * Settings class
     *
     * @since 1.0.0
     */
    class Zenrush_WC_Settings extends WC_Settings_Page
    {

        /**
         * Constructor
         *
         * @since  1.0.0
         * @noinspection PhpMissingParentConstructorInspection
         */
        public function __construct()
        {
            $this->id = 'zenrush';
            $this->label = __( 'Zenrush', 'zenrush' );

            // Define all hooks instead of inheriting from parent
            add_filter( 'woocommerce_settings_tabs_array', array($this, 'add_settings_page'), 20 );
            add_action( 'woocommerce_sections_' . $this->id, array($this, 'output_sections') );
            add_action( 'woocommerce_settings_' . $this->id, array($this, 'output') );
            add_action( 'woocommerce_settings_save_' . $this->id, array($this, 'save') );
        }


        /**
         * Gets sections
         *
         * @since   1.0.0
         * @access  public
         * @return  array
         */
        public function get_sections(): array
        {
            $sections = array(
                '' => __( 'Zenrush Settings', 'zenrush' ),
                'log' => __( 'Log', 'zenrush' )
            );

            return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
        }


        /**
         * Gets settings array
         *
         * @since   1.0.0
         * @access  public
         * @return  array
         */
        public function get_settings(): array
        {
            global $current_section;
            $settings = array();

            switch ( $current_section ) {
                // TODO: Implement error logs ?
                case 'log':
                    $settings = array(
                        array()
                    );
                    break;
                default:
                    include 'partials/zenrush-settings-main.php';
            }

            return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings, $current_section );
        }

        /**
         * Outputs the settings
         *
         * @since   1.0.0
         * @access  public
         * @returns void
         */
        public function output(): void
        {
            $settings = $this->get_settings();
            WC_Admin_Settings::output_fields( $settings );
        }

        /**
         * Saves settings
         *
         * @since   1.0.0
         * @access  public
         * @returns void
         */
        public function save(): void
        {
            $settings = $this->get_settings();
            WC_Admin_Settings::save_fields( $settings );
        }

    }

}


return new Zenrush_WC_Settings();