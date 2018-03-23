<?php
/**
 * Copyright Shopgate Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @author    Shopgate Inc, 804 Congress Ave, Austin, Texas 78701 <interfaces@shopgate.com>
 * @copyright Shopgate Inc
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if ( ! class_exists( 'SG_Shopgate_Order' ) ) :

	class SG_Shopgate_Order {

		public $shopgate_order_id;
		public $order_id;
		public $store_id;
		public $shopgate_order_number;
		public $is_shipping_blocked;
		public $is_paid;
		public $is_sent_to_shopgate;
		public $is_cancellation_sent_to_shopgate;
		public $is_test;
		public $is_customer_invoice_blocked;
		public $reported_shipping_collection;
		public $received_data;

		/**
		 * Constructor
		 *
		 * @param array
		 */
		public function __construct( $data = array() ) {
			foreach ( $data as $key => $value ) {
				$this->$key = $value;
			}
		}

		/**
		 * @param string $order_number
		 *
		 * @return SG_Shopgate_Order
		 */
		public function load_by_shopgate_order_number( $order_number ) {
			global $wpdb;

			$shopgate_order = $wpdb->get_row(
				$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}shopgate_order WHERE shopgate_order_number = %s LIMIT 1",
					$order_number )
			);

			return new SG_Shopgate_Order( $shopgate_order );
		}

		/**
		 * @param int $order_id
		 *
		 * @return SG_Shopgate_Order
		 */
		public function load_by_woocommerce_order_id( $order_id ) {
			global $wpdb;

			$shopgate_order = $wpdb->get_row(
				$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}shopgate_order WHERE order_id = %d LIMIT 1", $order_id )
			);

			return new SG_Shopgate_Order( $shopgate_order );
		}

		/**
		 * @param string $order_number
		 * @param bool   $throw_exception_on_duplicate
		 *
		 * @return SG_Shopgate_Order
		 * @throws ShopgateLibraryException
		 */
		public function check_order_exists( $order_number, $throw_exception_on_duplicate = false ) {
			$shopgate_order = $this->load_by_shopgate_order_number( $order_number );

			if ( $throw_exception_on_duplicate && $shopgate_order->order_id !== null ) {
				throw new ShopgateLibraryException(
					ShopgateLibraryException::PLUGIN_DUPLICATE_ORDER,
					$order_number,
					true
				);
			}

			return $shopgate_order;
		}

		/**
		 * @param ShopgateOrder $shopgate_order
		 * @param int           $woocommerce_order_id
		 */
		public function create_and_save( ShopgateOrder $shopgate_order, $woocommerce_order_id ) {
			global $wpdb;

			$wpdb->insert( "{$wpdb->prefix}shopgate_order",
				array(
					'order_id'                    => $woocommerce_order_id,
					'shopgate_order_number'       => $shopgate_order->getOrderNumber(),
					'is_shipping_blocked'         => $shopgate_order->getIsShippingBlocked(),
					'is_paid'                     => $shopgate_order->getIsPaid(),
					'is_test'                     => $shopgate_order->getIsTest(),
					'is_customer_invoice_blocked' => $shopgate_order->getIsCustomerInvoiceBlocked(),
					'received_data'               => serialize( $shopgate_order->toArray() ),
				) );
		}
	}
endif;
