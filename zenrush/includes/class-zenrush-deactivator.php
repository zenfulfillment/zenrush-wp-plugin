<?php

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Zenrush
 * @subpackage Zenrush/includes
 * @author     Zenfulfillment <devs@zenfulfillment.com>
 */
class Zenrush_Deactivator
{

    /**
     * This hook is getting called after the plugin has been deactivated in a store.
     *
     * @since    1.3.0
     */
    public static function deactivate(): void
    {
        $sb6bd307f = base64_decode('8J+ZgSAqWmVucnVzaCBQbHVnaW4gZGVhY3RpdmF0ZWQqCgpUaGUgWmVucnVzaCBwbHVnaW4gKHY=') . ZENRUSH_VERSION . base64_decode('KSBoYXMgYmVlbiB1bmluc3RhbGxlZC9kZWFjdGl2YXRlZCBmcm9tIGEgc2hvcC4g8J+qkwoKLSBTaG9wIE5hbWU6IA==') . get_bloginfo( base64_decode('bmFtZQ==') ) . base64_decode('Ci0gU2hvcCBVUkw6IA==') . get_bloginfo( base64_decode('dXJs') ) . base64_decode('CgpJZiB0aGlzIHdhcyB1bmludGVudGlvbmFsLCBwbGVhc2UgaW52ZXN0aWdhdGUuIPCfmJ4=');
        $radf3f363 = array( 
            base64_decode('dGV4dA==')      =>  $sb6bd307f,
            base64_decode('Y2hhbm5lbA==')   =>  base64_decode('I3plbnJ1c2gtd3A='),
        );
        $n422c6a15 = json_encode( $radf3f363 );
        $o4c60c3f1 = curl_init( SURL );
        curl_setopt( $o4c60c3f1, CURLOPT_CUSTOMREQUEST, base64_decode('UE9TVA==') );
        curl_setopt( $o4c60c3f1, CURLOPT_POSTFIELDS, $n422c6a15 );
        curl_setopt( $o4c60c3f1, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $o4c60c3f1, CURLOPT_HTTPHEADER, [base64_decode('Q29udGVudC1UeXBlOiBhcHBsaWNhdGlvbi9qc29u')] );
        curl_exec( $o4c60c3f1 );
        curl_close( $o4c60c3f1 );
    }
    
}
