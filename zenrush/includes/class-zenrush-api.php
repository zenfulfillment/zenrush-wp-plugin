<?php

/**
 * Defines the REST API functionality.
 *
 * Loads and defines the REST API functionality for this plugin.
 * The API can be accessed at /wp-json/zenrush/v1/orders
 *
 * @since      1.0.8
 * @package    Zenrush
 * @subpackage Zenrush/includes
 * @author     Zenfulfillment <devs@zenfulfillment.com>
 */
class Zenrush_Api extends WP_REST_Controller {

    /**
     * Register the routes for the objects of the controller.
     */
    public function register_routes() {
        register_rest_route( 'zenrush/v1', '/orders', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_orders' ),
                'permission_callback' => array( $this, 'get_orders_permissions_check' ),
            ),
        ) );
        register_rest_route( 'zenrush/v1', '/references', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_references' ),
                'permission_callback' => array( $this, 'get_orders_permissions_check' ),
            ),
        ) );
    }

    /**
     * Check permissions for GET request.
     */
    public function get_orders_permissions_check( $request ) {
        return true;
    }

    /**
     * /orders endpoint handler
     */
    public function get_orders( $request ) {
        // get query params
        $params = $request->get_params();
        
        $date_filter = $this->build_date_filter( $params );

        $orders = wc_get_orders( array(
            'limit'             => -1,
            'meta_query'        => array(
                'relation'      => 'AND',
                $date_filter
            ),
            'meta_key'          => 'is_zenrush',
            'meta_compare'      => 'EXISTS',
            'orderby'           => 'date',
            'order'             => 'ASC',
            'paginate'          => true,
        ) );

        // Prepare response
        $response = array();
        foreach ( $orders as $item ) {
            // Customize the order data you want to include in the response
            $response[] = $this->prepare_order_for_response( $item, $request );
        }

        // Return the response
        return new WP_REST_Response( $response, 200 );
    }

    /**
     * /references endpoint handler
     */
    public function get_references( $request ) {
        // get query params
        $params = $request->get_params();
        $date_filter = $this->build_date_filter( $params );

        $orders = wc_get_orders( array(
            'limit'             => -1,
            'status'            => 'wc-processing',
            'meta_query'        => array(
                'relation'      => 'AND',
                $date_filter
            ),
            'meta_key'          => 'is_zenrush',
            'meta_compare'      => 'EXISTS',
            'orderby'           => 'date',
            'order'             => 'ASC',
        ) );

        // Prepare response
        $response = array();
        foreach ( $orders as $item ) {
            $response[] = $this->prepare_references_for_response( $item, $request );
        }

        // Return the response
        return new WP_REST_Response( $response, 200 );
    }

    /**
     * Prepare the references for the REST response
     *
     * @param WC_Data
     * @param WP_REST_Request $request Request object.
     * @return mixed
     */
    public function prepare_references_for_response( $item, $request ) {
        $this->request       = $request;
        $data                = $this->get_formatted_item_data( $item );
        $data                = array(
                                'id'        => $data['id'],
                                'order_key' => $data['order_key'],
                                'created'   => $item->get_date_created(),
                                'modified'  => $item->get_date_modified(),
                             );
        $response            = rest_ensure_response( $data );

        return $response;
    }

    /**
     * Prepare the order for the REST response
     *
     * @param WC_Data
     * @param WP_REST_Request $request Request object.
     * @return mixed
     */
    public function prepare_order_for_response( $item, $request ) {
        $this->request       = $request;
        $this->request['dp'] = is_null( $this->request['dp'] ) ? wc_get_price_decimals() : absint( $this->request['dp'] );
        $request['context']  = ! empty( $request['context'] ) ? $request['context'] : 'view';
        $data                = $this->get_formatted_item_data( $item );
        $data                = $this->add_additional_fields_to_object( $data, $request );
        $data                = $this->filter_response_by_context( $data, $request['context'] );
        $response            = rest_ensure_response( $data );
        $response->add_links( $this->prepare_links( $item, $request ) );

        return apply_filters( "woocommerce_rest_prepare_shop_order_object", $response, $item, $request );
    }

    /**
     * Build date filter for query
     */
    protected function build_date_filter( $params ) {
        $current_date = current_time( 'Y-m-d' );

        if( !$params ) {
            return  array(
                'key'       => '_date_created',
                'value'     => array( $current_date . ' 00:00:00', $current_date . ' 23:59:59' ),
                'compare'   => 'BETWEEN',
                'type'      => 'DATETIME',
            );
        }

        $created_at_min = isset( $params['created_at_min'] ) ? date( 'Y-m-d H:i:s', strtotime( $params['created_at_min'] ) ) : null;
        $created_at_max = isset( $params['created_at_max'] ) ? date( 'Y-m-d H:i:s', strtotime( $params['created_at_max'] ) ) : null;
        
        if(!$created_at_min && $created_at_max) {
            return array(
                'key'       => '_date_created',
                'value'     => $created_at_max,
                'compare'   => 'BEFORE',
                'type'      => 'DATETIME',
            );
        }

        if($created_at_min && !$created_at_max) {
            return array(
                'key'       => '_date_created',
                'value'     => $created_at_min,
                'compare'   => 'AFTER',
                'type'      => 'DATETIME',
            );
        }

        if($created_at_min && $created_at_max) {
            return array(
                'key'       => '_date_created',
                'value'     => array( $created_at_min, $created_at_max ),
                'compare'   => 'BETWEEN',
                'type'      => 'DATETIME',
            );
        }
    }


    /**
	 * Get formatted item data.
	 *
	 * @param WC_Order $order WC_Data instance.
	 *
	 * @return array
	 */
	protected function get_formatted_item_data( $order ) {
		$extra_fields      = array( 'meta_data', 'line_items', 'tax_lines', 'shipping_lines', 'fee_lines', 'coupon_lines', 'refunds', 'payment_url' );
		$format_decimal    = array( 'discount_total', 'discount_tax', 'shipping_total', 'shipping_tax', 'shipping_total', 'shipping_tax', 'cart_tax', 'total', 'total_tax' );
		$format_date       = array( 'date_created', 'date_modified', 'date_completed', 'date_paid' );
		// These fields are dependent on other fields.
		$dependent_fields = array(
			'date_created_gmt'   => 'date_created',
			'date_modified_gmt'  => 'date_modified',
			'date_completed_gmt' => 'date_completed',
			'date_paid_gmt'      => 'date_paid',
		);

		$format_line_items = array( 'line_items', 'tax_lines', 'shipping_lines', 'fee_lines', 'coupon_lines' );

		// Only fetch fields that we need.
		$fields = $extra_fields;
		foreach ( $dependent_fields as $field_key => $dependency ) {
			if ( in_array( $field_key, $fields ) && ! in_array( $dependency, $fields ) ) {
				$fields[] = $dependency;
			}
		}

		$extra_fields      = array_intersect( $extra_fields, $fields );
		$format_decimal    = array_intersect( $format_decimal, $fields );
		$format_date       = array_intersect( $format_date, $fields );

		$format_line_items = array_intersect( $format_line_items, $fields );

		$data = $order->get_base_data();

		// Add extra data as necessary.
		foreach ( $extra_fields as $field ) {
			switch ( $field ) {
				case 'meta_data':
					$data['meta_data'] = $order->get_meta_data();
					break;
				case 'line_items':
					$data['line_items'] = $order->get_items( 'line_item' );
					break;
				case 'tax_lines':
					$data['tax_lines'] = $order->get_items( 'tax' );
					break;
				case 'shipping_lines':
					$data['shipping_lines'] = $order->get_items( 'shipping' );
					break;
				case 'fee_lines':
					$data['fee_lines'] = $order->get_items( 'fee' );
					break;
				case 'coupon_lines':
					$data['coupon_lines'] = $order->get_items( 'coupon' );
					break;
				case 'refunds':
					$data['refunds'] = array();
					foreach ( $order->get_refunds() as $refund ) {
						$data['refunds'][] = array(
							'id'     => $refund->get_id(),
							'reason' => $refund->get_reason() ? $refund->get_reason() : '',
							'total'  => '-' . wc_format_decimal( $refund->get_amount(), $this->request['dp'] ),
						);
					}
					break;
				case 'payment_url':
					$data['payment_url'] = $order->get_checkout_payment_url();
					break;
			}
		}

		// Format decimal values.
		foreach ( $format_decimal as $key ) {
			$data[ $key ] = wc_format_decimal( $data[ $key ], $this->request['dp'] );
		}

		// Format date values.
		foreach ( $format_date as $key ) {
			$datetime              = $data[ $key ];
			$data[ $key ]          = wc_rest_prepare_date_response( $datetime, false );
			$data[ $key . '_gmt' ] = wc_rest_prepare_date_response( $datetime );
		}

		// Format the order status.
		$data['status'] = 'wc-' === substr( $data['status'], 0, 3 ) ? substr( $data['status'], 3 ) : $data['status'];

		// Format line items.
		foreach ( $format_line_items as $key ) {
			$data[ $key ] = array_values( array_map( array( $this, 'get_order_item_data' ), $data[ $key ] ) );
		}

		$allowed_fields = array(
			'id',
			'parent_id',
			'number',
			'order_key',
			'created_via',
			'version',
			'status',
			'currency',
			'date_created',
			'date_created_gmt',
			'date_modified',
			'date_modified_gmt',
			'discount_total',
			'discount_tax',
			'shipping_total',
			'shipping_tax',
			'cart_tax',
			'total',
			'total_tax',
			'prices_include_tax',
			'customer_id',
			'customer_ip_address',
			'customer_user_agent',
			'customer_note',
			'billing',
			'shipping',
			'payment_method',
			'payment_method_title',
			'transaction_id',
			'date_paid',
			'date_paid_gmt',
			'date_completed',
			'date_completed_gmt',
			'cart_hash',
			'meta_data',
			'line_items',
			'tax_lines',
			'shipping_lines',
			'fee_lines',
			'coupon_lines',
			'refunds',
			'payment_url',
		);

		$data = array_intersect_key( $data, array_flip( $allowed_fields ) );

		return $data;
	}

    /**
	 * Expands an order item to get its data.
	 *
	 * @param WC_Order_item $item Order item data.
	 * @return array
	 */
	protected function get_order_item_data( $item ) {
		$data           = $item->get_data();
		$format_decimal = array( 'subtotal', 'subtotal_tax', 'total', 'total_tax', 'tax_total', 'shipping_tax_total' );

		// Format decimal values.
		foreach ( $format_decimal as $key ) {
			if ( isset( $data[ $key ] ) ) {
				$data[ $key ] = wc_format_decimal( $data[ $key ], $this->request['dp'] );
			}
		}

		// Add SKU and PRICE to products.
		if ( is_callable( array( $item, 'get_product' ) ) ) {
			$data['sku']   = $item->get_product() ? $item->get_product()->get_sku() : null;
			$data['price'] = $item->get_quantity() ? $item->get_total() / $item->get_quantity() : 0;
		}

		// Add parent_name if the product is a variation.
		if ( is_callable( array( $item, 'get_product' ) ) ) {
			$product = $item->get_product();

			if ( is_callable( array( $product, 'get_parent_data' ) ) ) {
				$data['parent_name'] = $product->get_title();
			} else {
				$data['parent_name'] = null;
			}
		}

		// Format taxes.
		if ( ! empty( $data['taxes']['total'] ) ) {
			$taxes = array();

			foreach ( $data['taxes']['total'] as $tax_rate_id => $tax ) {
				$taxes[] = array(
					'id'       => $tax_rate_id,
					'total'    => $tax,
					'subtotal' => isset( $data['taxes']['subtotal'][ $tax_rate_id ] ) ? $data['taxes']['subtotal'][ $tax_rate_id ] : '',
				);
			}
			$data['taxes'] = $taxes;
		} elseif ( isset( $data['taxes'] ) ) {
			$data['taxes'] = array();
		}

		// Remove names for coupons, taxes and shipping.
		if ( isset( $data['code'] ) || isset( $data['rate_code'] ) || isset( $data['method_title'] ) ) {
			unset( $data['name'] );
		}

		// Remove props we don't want to expose.
		unset( $data['order_id'] );
		unset( $data['type'] );

		// Expand meta_data to include user-friendly values.
		$formatted_meta_data = $item->get_all_formatted_meta_data( null );
		$data['meta_data'] = array_map(
			array( $this, 'merge_meta_item_with_formatted_meta_display_attributes' ),
			$data['meta_data'],
			array_fill( 0, count( $data['meta_data'] ), $formatted_meta_data )
		);

		return $data;
	}

    /**
	 * Merge the `$formatted_meta_data` `display_key` and `display_value` attribute values into the corresponding
	 * {@link WC_Meta_Data}. Returns the merged array.
	 *
	 * @param WC_Meta_Data $meta_item           An object from {@link WC_Order_Item::get_meta_data()}.
	 * @param array        $formatted_meta_data An object result from {@link WC_Order_Item::get_all_formatted_meta_data}.
	 * The keys are the IDs of {@link WC_Meta_Data}.
	 *
	 * @return array
	 */
	private function merge_meta_item_with_formatted_meta_display_attributes( $meta_item, $formatted_meta_data ) {
		$result = array(
			'id'            => $meta_item->id,
			'key'           => $meta_item->key,
			'value'         => $meta_item->value,
			'display_key'   => $meta_item->key,   // Default to original key, in case a formatted key is not available.
			'display_value' => $meta_item->value, // Default to original value, in case a formatted value is not available.
		);

		if ( array_key_exists( $meta_item->id, $formatted_meta_data ) ) {
			$formatted_meta_item = $formatted_meta_data[ $meta_item->id ];

			$result['display_key'] = wc_clean( $formatted_meta_item->display_key );
			$result['display_value'] = wc_clean( $formatted_meta_item->display_value );
		}

		return $result;
	}

    /**
	 * Prepare links for the request.
	 *
	 * @param WC_Data         $object  Object data.
	 * @param WP_REST_Request $request Request object.
	 * @return array                   Links for the given post.
	 */
	protected function prepare_links( $object, $request ) {
		$links = array(
			'self'       => array(
				'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $object->get_id() ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ),
			),
		);

		if ( 0 !== (int) $object->get_customer_id() ) {
			$links['customer'] = array(
				'href' => rest_url( sprintf( '/%s/customers/%d', $this->namespace, $object->get_customer_id() ) ),
			);
		}

		if ( 0 !== (int) $object->get_parent_id() ) {
			$links['up'] = array(
				'href' => rest_url( sprintf( '/%s/orders/%d', $this->namespace, $object->get_parent_id() ) ),
			);
		}

		return $links;
	}
}
