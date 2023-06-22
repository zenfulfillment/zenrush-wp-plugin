<?php

$prefix = 'Zenrush_';

$settings = apply_filters( 'zenrush_settings',
    array(

        array(
            'id'    =>  $prefix . 'general_options',
            'name'  =>  __( 'General Options', 'zenrush' ),
            'type'  =>  'title',
            'desc'  =>  '',
        ),

            array(
                'id'        =>  $prefix . 'store_id',
                'name'      =>  __( 'Merchant Key', 'zenrush' ),
                'type'      =>  'text',
                'desc_tip'  =>  __( 'The unique Merchant Key for the Zenrush Plugin. This Key will be provided to you by Zenfulfillment Customer Support.', 'zenrush' )
            ),

        array(
            'type'  =>  'sectionend',
            'id'    =>  $prefix . 'general_options',
        ),

        array(
            'title' =>  __( 'Zenrush Store Front-End Integration', 'zenrush' ),
            'type'  =>  'title',
            'desc'  =>  __( 'Note: If the automatic front-end integration is disabled, you need to add the Zenrush snippet to your store theme manually. <a href="https://setup.zenfulfillment.com/zenrush/integration/woocommerce">Docs</a>', 'zenrush' ),
            'id'    =>  $prefix . 'element_options',
        ),

            array(
                'title'             =>  __( 'Automatic Integration', 'zenrush' ),
                'desc'              =>  __( 'Enable Automatic Integration', 'zenrush' ),
                'default'           =>  'yes',
                'type'              =>  'checkbox',
                'checkboxgroup'     =>  'start',
                'show_if_checked'   =>  'option',
                'id'                =>  $prefix . 'enable_automatic_integration',
            ),

                array(
                    'desc'              =>  __( 'On Product Detail Pages', 'zenrush' ),
                    'desc_tip'          =>  __( 'Show Zenrush on all product detail pages', 'zenrush' ),
                    'default'           =>  'yes',
                    'type'              =>  'checkbox',
                    'checkboxgroup'     =>  '',
                    'id'                =>  $prefix . 'show_on_product_page',
                    'show_if_checked'   =>  'yes',
                    'autoload'          =>  false,
                ),

                array(
                    'desc'              => __( 'Product Category Listings', 'zenrush' ),
                    'desc_tip'          => __( 'Show Zenrush on all product listings. This includes the product category pages and "related products"', 'zenrush' ),
                    'default'           => 'yes',
                    'type'              => 'checkbox',
                    'checkboxgroup'     => '',
                    'id'                => $prefix . 'show_on_product_listing',
                    'show_if_checked'   => 'yes',
                    'autoload'          => false,
                ),

                array(
                    'desc'              => __( 'Only display Zenrush Badge on product listings', 'zenrush' ),
                    'desc_tip'          => __( 'Do not show delivery date on product category pages. Only the zenrush icon will be shown.', 'zenrush' ),
                    'default'           => 'no',
                    'type'              => 'checkbox',
                    'checkboxgroup'     => 'end',
                    'id'                => $prefix . 'hide_delivery_date_on_listing',
                    'show_if_checked'   => 'yes',
                    'autoload'          => false,
                ),

        array(
            'type'  =>  'sectionend',
            'id'    =>  $prefix . 'element_options',
        ),

    )
);