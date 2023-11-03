<?php

/**
 * Loads and defines the internationalization functionality
 * of the Zenrush Plugin.
 *
 *
 * @since       1.0.0
 * @package     Zenrush
 * @subpackage  Zenrush/includes
 * @author      Zenfulfillment <devs@zenfulfillment.com>
 */
class Zenrush_i18n
{

    /**
     * Load the plugin text domain for translation.
     *
     * @since   1.0.0
     */
    public function load_plugin_textdomain(): void
    {
        load_plugin_textdomain(
            'zenrush',
            false,
            dirname(plugin_basename(__FILE__), 2) . '/languages/'
        );
    }
    
}
