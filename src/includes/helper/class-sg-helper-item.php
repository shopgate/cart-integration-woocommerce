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
if ( ! class_exists( 'SG_Helper_Item' ) ) :
	class SG_Helper_Item {

		public function get_product_by_id( $product_id, $variation_id = 0 ) {
			$product_id   = absint( $product_id );
			$variation_id = absint( $variation_id );

			// Ensure we don't add a variation to the cart directly by variation ID
			if ( 'product_variation' === get_post_type( $product_id ) ) {
				$variation_id = $product_id;
				$product_id   = wp_get_post_parent_id( $variation_id );
			}

			// Get the product
			return wc_get_product( $variation_id ? $variation_id : $product_id );
		}
	}
endif;
