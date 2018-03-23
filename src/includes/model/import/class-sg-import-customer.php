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
if ( ! class_exists( 'SG_Import_Customer' ) ) :

	class SG_Import_Customer extends SG_Abstract_Loader {

		CONST DEFAULT_PAYMENT_METHOD = 'shopgate';

		protected $loader_methods = array(
			'user',
			'basic_data',
			'address_data',
		);
		/** @var ShopgateCustomer */
		protected $shopgate_customer;
		/** @var string */
		protected $email;
		/** @var string */
		protected $password;
		/** @var int */
		protected $woocommerce_customer_id;

		public function __construct( $shopgate_customer, $email, $password ) {
			$this->shopgate_customer = $shopgate_customer;
			$this->email             = $email;
			$this->password          = $password;
		}

		/**
		 * @return int
		 *
		 * @throws ShopgateLibraryException
		 */
		public function register_customer() {
			$this->load_methods( $this->loader_methods );

			return $this->woocommerce_customer_id;
		}

		/**
		 * @throws ShopgateLibraryException
		 */
		protected function set_user() {
			if ( email_exists( $this->email ) ) {
				throw new ShopgateLibraryException( ShopgateLibraryException::REGISTER_USER_ALREADY_EXISTS );
			}

			$this->woocommerce_customer_id = wc_create_new_customer( $this->email, '', $this->password );
		}

		protected function set_basic_data() {
			update_user_meta( $this->woocommerce_customer_id, 'first_name', $this->shopgate_customer->getFirstName() );
			update_user_meta( $this->woocommerce_customer_id, 'last_name', $this->shopgate_customer->getLastName() );
		}

		protected function set_address_data() {
			/** @var ShopgateAddress $shopgateCustomerAddress */
			foreach ( $this->shopgate_customer->getAddresses() as $shopgate_customer_address ) {

				$prefix = $shopgate_customer_address->getIsInvoiceAddress()
					? 'billing'
					: 'shipping';

				update_user_meta( $this->woocommerce_customer_id,
					$prefix . '_first_name',
					$shopgate_customer_address->getFirstName() );
				update_user_meta( $this->woocommerce_customer_id,
					$prefix . '_last_name',
					$shopgate_customer_address->getLastName() );
				update_user_meta( $this->woocommerce_customer_id,
					$prefix . '_company',
					$shopgate_customer_address->getCompany() ? $shopgate_customer_address->getCompany() : '' );
				update_user_meta( $this->woocommerce_customer_id,
					$prefix . '_country',
					$shopgate_customer_address->getCountry() );
				update_user_meta( $this->woocommerce_customer_id,
					$prefix . '_address_1',
					$shopgate_customer_address->getStreet1() );
				update_user_meta( $this->woocommerce_customer_id,
					$prefix . '_address_2',
					$shopgate_customer_address->getStreet2() );
				update_user_meta( $this->woocommerce_customer_id,
					$prefix . '_city',
					$shopgate_customer_address->getCity() );
				update_user_meta( $this->woocommerce_customer_id,
					$prefix . '_state',
					$shopgate_customer_address->getState() );
				update_user_meta( $this->woocommerce_customer_id,
					$prefix . '_postcode',
					$shopgate_customer_address->getZipcode() );

				if ( $prefix == 'billing' ) {
					update_user_meta( $this->woocommerce_customer_id,
						$prefix . '_phone',
						$this->get_phone_number( $shopgate_customer_address ) );
					update_user_meta( $this->woocommerce_customer_id, $prefix . '_email', $this->email );
				}
			}
		}

		/**
		 * @param ShopgateAddress $customer_address
		 *
		 * @return string
		 */
		protected function get_phone_number( $customer_address ) {
			$phoneNumber = '';

			if ( $customer_address->getPhone() ) {
				$phoneNumber = $customer_address->getPhone();
			} elseif ( $customer_address->getMobile() ) {
				$phoneNumber = $customer_address->getMobile();
			} elseif ( $this->shopgate_customer->getPhone() ) {
				$phoneNumber = $this->shopgate_customer->getPhone();
			} elseif ( $this->shopgate_customer->getMobile() ) {
				$phoneNumber = $this->shopgate_customer->getMobile();
			}

			return $phoneNumber;
		}
	}
endif;
