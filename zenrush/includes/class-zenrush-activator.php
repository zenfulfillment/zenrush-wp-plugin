<?php

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Zenrush
 * @subpackage Zenrush/includes
 * @author     Zenfulfillment <devs@zenfulfillment.com>
 */
class Zenrush_Activator
{

    /**
     * This hook is getting called after the plugin has been activated in a store.
     *
     * @since    1.3.0
     */
    public static function activate(): void
    {
        $url = base64_decode( 'aHR0cHM6Ly9ob29rcy5zbGFjay5jb20vc2VydmljZXMvVDA5VjRHME1SL0JTUDdWQzdMRy8zOWlhelV0bGtlcEQxakdjS1dyakhucXU=' );
        $message = "🚀 *Zenrush Plugin Activated*\n\nThe Zenrush plugin (v" . ZENRUSH_VERSION . ") has been successfully installed in a new shop. 🛒\n\n- Shop Name: " . get_bloginfo('name') . "\n- Shop URL: " . get_bloginfo('url') . "\n\n🎉";

        $data = array( 
            'text'      =>  $message,
            'channel'   =>  '#zenrush-wp',
        );
        $payload = json_encode( $data );
        $ch = curl_init( $url );
        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'POST' );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json'] );
        curl_exec($ch);
        curl_close($ch);
    }

}
