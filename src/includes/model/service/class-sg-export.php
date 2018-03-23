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
if ( ! class_exists( 'SG_Export' ) ) :

	class SG_Export {

		/**
		 * @inheritdoc
		 */
		public function get_customer( $user, $pass ) {
			/** @var WP_User|WP_Error $wordpress_user */
			$wordpress_user = wp_authenticate( $user, $pass );
			if ( is_wp_error( $wordpress_user ) ) {
				throw new ShopgateLibraryException( ShopgateLibraryException::PLUGIN_WRONG_USERNAME_OR_PASSWORD );
			}

			$shopgate_customer = new ShopgateCustomer();
			$shopgate_customer->setCustomerId( $wordpress_user->ID );
			$shopgate_customer->setFirstName( $wordpress_user->user_firstname );
			$shopgate_customer->setLastName( $wordpress_user->user_lastname );

			$local_shopgate_customer = new SG_Shopgate_Customer();
			$local_shopgate_customer = $local_shopgate_customer->load_by_customer_id( $wordpress_user->ID );

			if ( empty( $local_shopgate_customer->token ) ) {
				$token = md5( $wordpress_user->ID . $wordpress_user->user_email );
				$local_shopgate_customer->create_and_save( $wordpress_user->ID, $token );
			}

			$shopgate_customer->setCustomerToken( $local_shopgate_customer->token );
			$addresses = array();

			// Invoice Address
			$invoice_address = new ShopgateAddress();
			$invoice_address->setAddressType( ShopgateAddress::INVOICE );
			$invoice_address->setFirstName( get_user_meta( $wordpress_user->ID, 'billing_first_name', true ) );
			$invoice_address->setLastName( get_user_meta( $wordpress_user->ID, 'billing_last_name', true ) );
			$invoice_address->setCompany( get_user_meta( $wordpress_user->ID, 'billing_company', true ) );
			$invoice_address->setStreet1( get_user_meta( $wordpress_user->ID, 'billing_address_1', true ) );
			$invoice_address->setStreet2( get_user_meta( $wordpress_user->ID, 'billing_address_2', true ) );
			$invoice_address->setZipcode( get_user_meta( $wordpress_user->ID, 'billing_postcode', true ) );
			$invoice_address->setCity( get_user_meta( $wordpress_user->ID, 'billing_city', true ) );
			$invoice_address->setCountry( get_user_meta( $wordpress_user->ID, 'billing_country', true ) );
			$invoice_address->setPhone( get_user_meta( $wordpress_user->ID, 'billing_phone', true ) );
			$invoice_address->setState( get_user_meta( $wordpress_user->ID, 'billing_state', true ) );
			$addresses[] = $invoice_address;

			// Shipping Address
			$shipping_address = new ShopgateAddress();
			$shipping_address->setAddressType( ShopgateAddress::DELIVERY );
			$shipping_address->setFirstName( get_user_meta( $wordpress_user->ID, 'shipping_first_name', true ) );
			$shipping_address->setLastName( get_user_meta( $wordpress_user->ID, 'shipping_last_name', true ) );
			$shipping_address->setCompany( get_user_meta( $wordpress_user->ID, 'shipping_company', true ) );
			$shipping_address->setStreet1( get_user_meta( $wordpress_user->ID, 'shipping_address_1', true ) );
			$shipping_address->setStreet2( get_user_meta( $wordpress_user->ID, 'shipping_address_2', true ) );
			$shipping_address->setZipcode( get_user_meta( $wordpress_user->ID, 'shipping_postcode', true ) );
			$shipping_address->setCity( get_user_meta( $wordpress_user->ID, 'shipping_city', true ) );
			$shipping_address->setCountry( get_user_meta( $wordpress_user->ID, 'shipping_country', true ) );
			$shipping_address->setState( get_user_meta( $wordpress_user->ID, 'shipping_state', true ) );
			$addresses[] = $shipping_address;

			$shopgate_customer->setAddresses( $addresses );

			return $shopgate_customer;
		}

		/**
		 * @inheritdoc
		 */
		public function check_cart( ShopgateCart $cart ) {
			$cart_model = new SG_Export_Cart( $cart );

			return $cart_model->check_cart();
		}

		/**
		 * @inheritdoc
		 */
		public function get_items( $limit, $offset, $uids ) {
			ShopgateLogger::getInstance()->log( 'Start Product Export...', ShopgateLogger::LOGTYPE_DEBUG );
			$product_retriever = new SG_Retriever_Products();
			$products          = $product_retriever->get_items( $limit, $offset, $uids );
			$result            = array();
			$export_cache      = new SG_Helper_Export_Cache();

			foreach ( $products as $product ) {
				$shopgate_product = new SG_Export_Products_Xml();
				$shopgate_product->set_cache( $export_cache );
				$shopgate_product->setItem( $product );
				$shopgate_product->set_variation_data( $product_retriever->get_children( $product->get_id() ) );
				$result[] = $shopgate_product->generateData();
			}

			ShopgateLogger::getInstance()->log( 'Finished Product Export...', ShopgateLogger::LOGTYPE_DEBUG );

			return $result;
		}

		/**
		 * @inheritdoc
		 */
		public function get_categories( $limit, $offset, $uids ) {
			ShopgateLogger::getInstance()->log( 'Start Category Export...', ShopgateLogger::LOGTYPE_DEBUG );
			$category_retriever = new SG_Retriever_Terms();
			$categories         = $category_retriever->get_categories( $limit, $offset, $uids );
			$result             = array();

			foreach ( $categories as $category ) {
				$shopgate_category = new SG_Export_Categories_Xml();
				$shopgate_category->setItem( $category );

				$result[] = $shopgate_category->generateData();
			}
			ShopgateLogger::getInstance()->log( 'Finished Category Export...', ShopgateLogger::LOGTYPE_DEBUG );

			return $result;
		}
	}
endif;
