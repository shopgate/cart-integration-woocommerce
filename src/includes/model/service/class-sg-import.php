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
if ( ! class_exists( 'SG_Import' ) ) :

	class SG_Import {

		/**
		 * @inheritdoc
		 */
		public function register_customer( $user, $pass, ShopgateCustomer $customer ) {
			$import_model = new SG_Import_Customer( $customer, $user, $pass );

			return $import_model->register_customer();
		}

		/**
		 * @inheritdoc
		 */
		public function add_order( ShopgateOrder $order ) {

			try {
				$import_model      = new SG_Import_Order( $order );
				$woocommerce_order = $import_model->add_order();
			} catch ( ShopgateLibraryException $e ) {
				throw $e;
			} catch ( Exception $e ) {
				throw new ShopgateLibraryException(
					ShopgateLibraryException::UNKNOWN_ERROR_CODE,
					"{$e->getMessage()}\n{$e->getTraceAsString()}",
					true
				);
			}

			return [
				'external_order_id'     => $woocommerce_order->id,
				'external_order_number' => $woocommerce_order->id
			];
		}

		/**
		 * @inheritdoc
		 */
		public function update_order( ShopgateOrder $order ) {
			// TODO: implement
			return $order;
		}
	}
endif;
