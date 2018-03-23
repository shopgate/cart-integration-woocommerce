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
if ( ! class_exists( 'SG_Import_Order' ) ) :

	class SG_Import_Order extends SG_Abstract_Loader {

		CONST DEFAULT_PAYMENT_METHOD = 'shopgate';
		CONST SHIPPING_TYPE_PLUGIN   = 'PLUGINAPI';

		protected $loader_methods = array(
			'start_add',
			'cart',
			'cart_to_order',
			'shipping',
			'customer',
			'payment',
			'end_add',
		);
		/** @var ShopgateOrder */
		protected $shopgate_order;
		/** @var SG_Shopgate_Order */
		protected $local_shopgate_order;
		/** @var WC_Cart */
		protected $woocommerce_cart;
		/** @var WC_Order|WP_Error */
		protected $woocommerce_order;
		/** @var WC_Checkout */
		protected $woocommerce_checkout;
		/** @var WC_Tax */
		protected $woocommerce_tax;

		public function __construct( $shopgate_order ) {
			$this->shopgate_order       = $shopgate_order;
			$this->local_shopgate_order = new SG_Shopgate_Order();
			$this->woocommerce_tax      = new WC_Tax();
			$this->woocommerce_cart     = WC()->cart;
			$this->woocommerce_checkout = WC()->checkout();

			$this->woocommerce_cart->empty_cart();
		}

		/**
		 * @return WC_Order|WP_Error
		 *
		 * @throws ShopgateLibraryException
		 */
		public function add_order() {
			$this->load_methods( $this->loader_methods );

			return $this->woocommerce_order;
		}

		/**
		 * @throws ShopgateLibraryException
		 */
		protected function set_start_add() {
			$this->local_shopgate_order->check_order_exists( $this->shopgate_order->getOrderNumber(), true );
		}

		protected function set_cart() {
			foreach ( $this->shopgate_order->getItems() as $order_item ) {
				$this->woocommerce_cart->add_to_cart( $order_item->getItemNumber(), $order_item->getQuantity() );
			}

			foreach ( $this->shopgate_order->getExternalCoupons() as $external_coupon ) {
				$this->woocommerce_cart->add_discount( $external_coupon->getCode() );
			}

			if ( $this->shopgate_order->getShippingType() == self::SHIPPING_TYPE_PLUGIN ) {
				$shipping_info = $this->shopgate_order->getShippingInfos();
				WC()->session->set( 'chosen_shipping_methods', array( $shipping_info->getName() ) );
			}
		}

		protected function set_cart_to_order() {
			$order_id                = $this->woocommerce_checkout->create_order();
			$this->woocommerce_order = wc_get_order( $order_id );
		}

		protected function set_customer() {
			if ( null !== $this->shopgate_order->getExternalCustomerId() ) {
				$this->woocommerce_order->set_customer_id( $this->shopgate_order->getExternalCustomerId() );
			}

			$billing_address  = array(
				'first_name' => $this->shopgate_order->getInvoiceAddress()->getFirstName(),
				'last_name'  => $this->shopgate_order->getInvoiceAddress()->getLastName(),
				'company'    => $this->shopgate_order->getInvoiceAddress()->getCompany(),
				'email'      => $this->shopgate_order->getInvoiceAddress()->getMail(),
				'phone'      => $this->shopgate_order->getInvoiceAddress()->getPhone(),
				'address_1'  => $this->shopgate_order->getInvoiceAddress()->getStreet1(),
				'address_2'  => $this->shopgate_order->getInvoiceAddress()->getStreet2(),
				'city'       => $this->shopgate_order->getInvoiceAddress()->getCity(),
				'state'      => $this->shopgate_order->getInvoiceAddress()->getState(),
				'postcode'   => $this->shopgate_order->getInvoiceAddress()->getZipcode(),
				'country'    => $this->shopgate_order->getInvoiceAddress()->getCountry()
			);
			$shipping_address = array(
				'first_name' => $this->shopgate_order->getDeliveryAddress()->getFirstName(),
				'last_name'  => $this->shopgate_order->getDeliveryAddress()->getLastName(),
				'company'    => $this->shopgate_order->getDeliveryAddress()->getCompany(),
				'email'      => $this->shopgate_order->getDeliveryAddress()->getMail(),
				'phone'      => $this->shopgate_order->getDeliveryAddress()->getPhone(),
				'address_1'  => $this->shopgate_order->getDeliveryAddress()->getStreet1(),
				'address_2'  => $this->shopgate_order->getDeliveryAddress()->getStreet2(),
				'city'       => $this->shopgate_order->getDeliveryAddress()->getCity(),
				'state'      => $this->shopgate_order->getDeliveryAddress()->getState(),
				'postcode'   => $this->shopgate_order->getDeliveryAddress()->getZipcode(),
				'country'    => $this->shopgate_order->getDeliveryAddress()->getCountry()
			);
			$this->woocommerce_order->set_address( $billing_address, 'billing' );
			$this->woocommerce_order->set_address( $shipping_address, 'shipping' );
		}

		protected function set_payment() {
			$payment_infos = $this->shopgate_order->getPaymentInfos();
			update_post_meta( $this->woocommerce_order->id, '_payment_method', self::DEFAULT_PAYMENT_METHOD );
			update_post_meta( $this->woocommerce_order->id,
				'_payment_method_title',
				$payment_infos['shopgate_payment_name'] );
		}

		protected function set_shipping() {

			if ( $this->shopgate_order->getShippingType() == self::SHIPPING_TYPE_PLUGIN ) {
				return;
			}

			// TODO: check how to add the articles to the shipping packages and create non plugin shipping shipping
			$item_id         = wc_add_order_item( $this->woocommerce_order->id,
				array(
					'order_item_name' => $this->shopgate_order->getShippingInfos()->getDisplayName(),
					'order_item_type' => 'shipping'
				) );
			$shipping_amount = $this->shopgate_order->getShippingInfos()->getAmountNet();
			$tax_rates       = $this->woocommerce_tax->get_shipping_tax_rates();
			$shipping_taxes  = $this->woocommerce_tax->calc_shipping_tax( $shipping_amount, $tax_rates );

			wc_add_order_item_meta( $item_id, 'method_id', $this->shopgate_order->getShippingInfos()->getName() );
			wc_add_order_item_meta( $item_id, 'cost', wc_format_decimal( $shipping_amount ) );
			wc_add_order_item_meta( $item_id, 'taxes', $shipping_taxes );
		}

		protected function set_fees() {
			// TODO: implement, e. g. payment fees
		}

		protected function set_end_add() {
			$this->woocommerce_order->calculate_totals();
			if ( $this->shopgate_order->getIsPaid() || ! $this->shopgate_order->getIsShippingBlocked() ) {
				$this->woocommerce_order->payment_complete();
			}
			$this->local_shopgate_order->create_and_save( $this->shopgate_order, $this->woocommerce_order->id );
		}
	}
endif;
