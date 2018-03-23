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
if ( ! class_exists( 'SG_Shopgate_Customer' ) ) :

	class SG_Shopgate_Customer {

		public $id;
		public $customer_id;
		public $token;

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
		 * @param string $customer_id
		 *
		 * @return SG_Shopgate_Customer
		 */
		public function load_by_customer_id( $customer_id ) {
			global $wpdb;

			$shopgate_customer = $wpdb->get_row(
				$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}shopgate_customer WHERE customer_id = %s LIMIT 1",
					$customer_id )
			);

			return new SG_Shopgate_Customer( $shopgate_customer );
		}

		/**
		 * @param int $woocommerce_customer_id
		 * @param string $token
		 */
		public function create_and_save( $woocommerce_customer_id, $token ) {
			global $wpdb;

			$wpdb->insert( "{$wpdb->prefix}shopgate_customer",
				array(
					'customer_id' => $woocommerce_customer_id,
					'token'       => $token,
				) );
		}
	}
endif;
