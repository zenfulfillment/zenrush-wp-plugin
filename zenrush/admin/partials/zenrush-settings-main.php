<?php

/**
 * The available plugin specific options being displayed in the admin.
 *
 * @package     Zenrush
 * @subpackage  Zenrush/admin/partials
 * @author      Zenfulfillment <devs@zenfulfillment.com>
 * @since       1.0.0
 */

$settings = apply_filters( 'zenrush_settings',
    array(

        array(
            'id'    =>  ZENRUSH_PREFIX . 'general_options',
            'name'  =>  __( 'General Options', 'zenrush' ),
            'type'  =>  'title',
            'desc'  =>  '',
        ),

            array(
                'id'        =>  ZENRUSH_PREFIX . 'store_id',
                'name'      =>  __( 'Merchant Key', 'zenrush' ),
                'type'      =>  'text',
                'desc_tip'  =>  __( 'The unique Merchant Key for the Zenrush Plugin. This Key will be provided to you by Zenfulfillment Customer Support.', 'zenrush' )
            ),

        array(
            'type'  =>  'sectionend',
            'id'    =>  ZENRUSH_PREFIX . 'general_options',
        ),

        array(
            'title' =>  __( 'Zenrush Store Front-End Integration', 'zenrush' ),
            'type'  =>  'title',
            'desc'  =>  __( 'Note: If the automatic front-end integration is disabled, you need to add the Zenrush snippet to your store theme manually. <a href="https://setup.zenfulfillment.com/zenrush/integration/woocommerce">Docs</a>', 'zenrush' ),
            'id'    =>  ZENRUSH_PREFIX . 'element_options',
        ),

            array(
                'title'             =>  __( 'Automatic Integration', 'zenrush' ),
                'desc'              =>  __( 'Enable Automatic Integration', 'zenrush' ),
                'default'           =>  'yes',
                'type'              =>  'checkbox',
                'checkboxgroup'     =>  'start',
                'show_if_checked'   =>  'option',
                'id'                =>  ZENRUSH_PREFIX . 'enable_automatic_integration',
            ),

                array(
                    'desc'              =>  __( 'On Product Detail Pages', 'zenrush' ),
                    'desc_tip'          =>  __( 'Show Zenrush on all product detail pages', 'zenrush' ),
                    'default'           =>  'yes',
                    'type'              =>  'checkbox',
                    'checkboxgroup'     =>  '',
                    'id'                =>  ZENRUSH_PREFIX . 'show_on_product_page',
                    'show_if_checked'   =>  'yes',
                    'autoload'          =>  false,
                ),

                array(
                    'desc'              => __( 'On Product Category Listings', 'zenrush' ),
                    'desc_tip'          => __( 'Show Zenrush on all product listings. This includes the product category pages and "related products"', 'zenrush' ),
                    'default'           => 'yes',
                    'type'              => 'checkbox',
                    'checkboxgroup'     => '',
                    'id'                => ZENRUSH_PREFIX . 'show_on_product_listing',
                    'show_if_checked'   => 'yes',
                    'autoload'          => false,
                ),

                array(
                    'desc'              => __( 'Disable delivery date on product listings', 'zenrush' ),
                    'desc_tip'          => __( 'Do not show delivery date on product category pages. Only the Zenrush icon will be shown, if enabled:', 'zenrush' ) . ' ' . __( 'On Product Category Listings', 'zenrush' ),
                    'default'           => 'no',
                    'type'              => 'checkbox',
                    'checkboxgroup'     => '',
                    'id'                => ZENRUSH_PREFIX . 'hide_delivery_date_on_listing',
                    'show_if_checked'   => 'yes',
                    'autoload'          => false,
                ),

                array(
                    'desc'              => __( 'Enable Styling on Checkout', 'zenrush' ),
                    'desc_tip'          => __( 'Adds additional styling to the Zenrush shipping option on checkout', 'zenrush' ),
                    'default'           => 'yes',
                    'type'              => 'checkbox',
                    'checkboxgroup'     => 'end',
                    'id'                => ZENRUSH_PREFIX . 'enable_checkout_styling',
                    'show_if_checked'   => 'yes',
                    'autoload'          => false,
                ),

        array(
            'type'  =>  'sectionend',
            'id'    =>  ZENRUSH_PREFIX . 'element_options',
        ),

        array(
            'title' =>  __( 'Disable Zenrush for some Products', 'zenrush' ),
            'type'  =>  'title',
            'desc'  =>  __( 'By default Zenrush will be enabled for all products that are in stock, but here you can configure products that should not be enabled for zenrush', 'zenrush' ),
            'id'    =>  ZENRUSH_PREFIX . 'filter_skus',
        ),

            array(
                'title'             =>  __( 'Disable Zenrush for specific products', 'zenrush' ),
                'desc'              =>  __( 'Add the products SKU you want to disable Zenrush for', 'zenrush' ),
                'desc_tip'          =>  __( 'Add multiple SKUs by separating them with a comma like this: SKU-1,SKU-2,SKU-3,...', 'zenrush' ),
                'type'              =>  'text',
                'id'                =>  ZENRUSH_PREFIX . 'disabled_skus',
            ),

        array(
            'type'  =>  'sectionend',
            'id'    =>  ZENRUSH_PREFIX . 'filter_skus',
        ),
    )
);