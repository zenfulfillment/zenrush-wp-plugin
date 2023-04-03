<?php

$prefix = 'Zenrush_';

$settings = apply_filters( 'zenrush_settings',
    array(
        array(
            'id' => $prefix . 'general_config_settings',
            'name' => __( 'General Configuration', 'zenrush' ),
            'type' => 'title',
        ),
        array(
            'id' => $prefix . 'store_id',
            'name' => __( 'Store Key', 'zenrush' ),
            'type' => 'text',
            'desc_tip' => __( 'The Key of your Zenfulfillment Store. This Key will be provided to you by Zenfulfillment Customer Support.', 'zenrush' )
        ),
        array(
            'id' => $prefix . 'show_on_product_page',
            'name' => __( 'Zenrush On Product Pages', 'zenrush' ),
            'type' => 'checkbox',
            'default' => 'yes',
            'desc_tip' => __( 'Adds the dynamic Zenrush delivery timer widget to all product pages.', 'zenrush' )
        ),
    )
);