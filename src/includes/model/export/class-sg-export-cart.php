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
if ( ! class_exists( 'SG_Export_Cart' ) ) :

	class SG_Export_Cart extends SG_Abstract_Loader {

		/** @var array */
		public static $supported_methods = array( 'customer', 'items', 'external_coupons', 'shipping_methods' );
		/** @var array */
		protected $result = array();
		/** @var ShopgateCart */
		protected $shopgate_cart;
		/** @var WC_Cart */
		protected $woocommerce_cart;
		/** @var SG_Helper_Item */
		protected $item_helper;

		/**
		 * @param ShopgateCart $cart
		 */
		public function __construct( ShopgateCart $cart ) {
			$this->shopgate_cart    = $cart;
			$this->woocommerce_cart = WC()->cart;
			$this->item_helper      = new SG_Helper_Item();

			$this->woocommerce_cart->empty_cart();
		}

		/**
		 * @inheritdoc
		 */
		public function check_cart() {
			return $this->load_methods( self::$supported_methods );
		}

		protected function set_customer() {
			$delivery_address = $this->shopgate_cart->getDeliveryAddress();

			if ( $delivery_address instanceof ShopgateAddress ) {

				WC()->shipping->reset_shipping();

				$country  = $delivery_address->getCountry();
				$state    = preg_replace( "/{$country}\-/", "", $delivery_address->getState() );
				$postcode = apply_filters( 'woocommerce_shipping_calculator_enable_postcode', true )
					? $delivery_address->getZipcode()
					: '';
				$city     = apply_filters( 'woocommerce_shipping_calculator_enable_city', false )
					? $delivery_address->getCity()
					: '';

				if ( $country ) {
					WC()->customer->set_location( $country, $state, $postcode, $city );
					WC()->customer->set_shipping_location( $country, $state, $postcode, $city );
				} else {
					WC()->customer->set_billing_address_to_base();
					WC()->customer->set_shipping_address_to_base();
				}

				WC()->customer->set_calculated_shipping( true );
				WC()->customer->save();
			}
		}

		/**
		 * @return array
		 */
		protected function set_external_coupons() {
			$coupons = [];
			foreach ( $this->shopgate_cart->getExternalCoupons() as $external_coupon ) {

				$external_coupon->setCode( wc_format_coupon_code( $external_coupon->getCode()) );
				$this->woocommerce_cart->remove_coupon( $external_coupon->getCode() );
				$is_valid = $this->woocommerce_cart->add_discount( $external_coupon->getCode() );

				$coupon = new ShopgateExternalCoupon();
				$coupon->setCode( $external_coupon->getCode() );
				$coupon->setIsValid( $is_valid );
				$coupon->setCurrency( get_woocommerce_currency() );

				if ( $is_valid ) {
					if ( get_option( 'woocommerce_tax_display_shop' ) == 'incl' ) {
						$coupon->setAmountGross(
							$this->woocommerce_cart->get_coupon_discount_amount( $external_coupon->getCode(), false )
						);
					} else {
						$coupon->setAmountNet(
							$this->woocommerce_cart->get_coupon_discount_amount( $external_coupon->getCode() )
						);
					}
				} else {
					$this->woocommerce_cart->remove_coupon( $external_coupon->getCode() );
				}

				$coupons[] = $coupon;
			}

			return $coupons;
		}

		/**
		 * @return array
		 */
		protected function set_items() {
			$result = array();
			foreach ( $this->shopgate_cart->getItems() as $item ) {

				$shopgate_cart_item = new ShopgateCartItem();
				$shopgate_cart_item->setItemNumber( $item->getItemNumber() );
				$shopgate_cart_item->setOptions( $item->getOptions() );
				$shopgate_cart_item->setInputs( $item->getInputs() );
				$shopgate_cart_item->setAttributes( $item->getAttributes() );

				// also add it to real cart, e. g. for shipping
				$cart_item_id        = $this->woocommerce_cart->add_to_cart(
					$item->getItemNumber(),
					$item->getQuantity()
				);
				$cart_item           = $this->woocommerce_cart->get_cart_item( $cart_item_id );
				$woocommerce_product = $this->item_helper->get_product_by_id( $item->getItemNumber() );

				if ( $woocommerce_product === false
				     || empty( $cart_item )
				) {
					$shopgate_cart_item->setError( ShopgateLibraryException::CART_ITEM_PRODUCT_NOT_FOUND );
				} else {
					$is_buyable     = $woocommerce_product->is_in_stock();
					$stock_quantity = $woocommerce_product->get_stock_quantity();
					$qty_buyable    = is_null( $stock_quantity )
						? $item->getQuantity()
						: min( $item->getQuantity(), $stock_quantity );

					if ( ! $woocommerce_product || ! $woocommerce_product->exists()
					     || 'trash' === $woocommerce_product->get_status()
					) {
						$is_buyable = 0;
						$shopgate_cart_item->setError( ShopgateLibraryException::CART_ITEM_PRODUCT_NOT_FOUND );
					}

					if ( $woocommerce_product->managing_stock()
					     && ! $woocommerce_product->has_enough_stock( $item->getQuantity() )
					) {
						$is_buyable = 0;
						$shopgate_cart_item->setError( ShopgateLibraryException::CART_ITEM_OUT_OF_STOCK );
					}

					$amount_net = $cart_item['line_subtotal'] / $cart_item['quantity'];
					$shopgate_cart_item->setUnitAmount(
						round( $amount_net, 2 )
					);
					$tax_amount = $cart_item['line_tax'] / $cart_item['quantity'];
					$shopgate_cart_item->setUnitAmountWithTax(
						round( $amount_net + $tax_amount, 2 )
					);
					$shopgate_cart_item->setIsBuyable( $is_buyable );
					$shopgate_cart_item->setQtyBuyable( $qty_buyable );
					$shopgate_cart_item->setStockQuantity( $stock_quantity );
				}

				$result[] = $shopgate_cart_item;
			}

			return $result;
		}

		protected function set_shipping_methods() {
			$result   = array();
			$packages = WC()->shipping()->get_packages();

			foreach ( $packages as $package ) {
				/** @var WC_Shipping_Rate $rate */
				foreach ( $package['rates'] as $rate_id => $rate ) {

					$export_method = new ShopgateShippingMethod();
					$export_method->setId( $rate_id );
					$export_method->setTitle( $rate->get_label() );
					$export_method->setAmountWithTax( $rate->get_cost() + $this->get_tax_amount( $rate->get_taxes() ) );
					$export_method->setAmount( round( $rate->get_cost(), 2 ) );

					$result[] = $export_method;
				}
			}


			return $result;
		}

		/**
		 * Returns accumulated tax amount
		 *
		 * @param array $taxes
		 *
		 * @return int
		 */
		protected function get_tax_amount( $taxes ) {
			$amount = 0;
			foreach ( $taxes as $tax ) {
				$amount += $tax;
			}

			return round( $amount, 2 );
		}
	}
endif;
